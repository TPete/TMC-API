<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Exception\ScrapeException;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\MaintenanceModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season\EpisodeModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\SeasonModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\SeriesModel;
use TinyMediaCenter\API\Model\Resource\Area\CategoryModel;
use TinyMediaCenter\API\Model\Resource\AreaModel;
use TinyMediaCenter\API\Service\MediaLibrary\TTVDBWrapper;
use TinyMediaCenter\API\Service\Store\ShowStoreDB;

/**
 * Series service.
 */
class SeriesService extends AbstractCategoryService implements SeriesServiceInterface
{

    const DEFAULT_CATEGORY = "Serien";

    const THUMBNAIL_SIZE = 512;

    /**
     * @var ShowStoreDB
     */
    private $showStoreDB;

    /**
     * @var TTVDBWrapper
     */
    private $ttvdbWrapper;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var boolean
     */
    private $useDefault;

    /**
     * @var array
     */
    private $categoryNames;

    /**
     * Series service constructor.
     *
     * @param ShowStoreDB  $showStoreDB
     * @param TTVDBWrapper $ttvdbWrapper
     * @param string       $path
     * @param string       $alias
     */
    public function __construct(ShowStoreDB $showStoreDB, TTVDBWrapper $ttvdbWrapper, $path, $alias)
    {
        $this->showStoreDB = $showStoreDB;
        $this->ttvdbWrapper = $ttvdbWrapper;
        $this->path = $path;
        $this->alias = $alias;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetaInfo()
    {
        return new AreaModel(
            'series',
            'Series area overview'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories()
    {
        $categories = [];

        foreach ($this->getCategoryNames() as $category) {
            $categories[] = new CategoryModel($category);
        }

        return $categories;
    }

    /**
     * {@inheritDoc}
     */
    public function getByCategory($category)
    {
        $path = $this->getCategoryAlias($category);
        $result = [];

        foreach ($this->showStoreDB->getShows($category) as $show) {
            $result[] = new SeriesModel(
                $show['id'],
                $show['title'],
                $path.$show['folder'].'/thumb.jpg',
                $show['lang'],
                $show['tvdb_id'],
                $show['folder']
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function get($category, $id)
    {
        return $this->getSeriesDetails($category, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function update($category, $series, $title, $tvDbId, $lang)
    {
        $this->showStoreDB->updateDetails($category, $series, $title, $tvDbId, $lang);
        $seriesDetails = $this->showStoreDB->getShowDetails($category, $series);
        $this->updateEpisodes($seriesDetails);
        $this->fetchBackgroundImage($category, $series, $seriesDetails['tvdb_id'], true);
        $this->updateThumbnail($category, $series, true);

        return $this->getSeriesDetails($category, $series);
    }

    /**
     * {@inheritDoc}
     */
    public function getSeasons($category, $series)
    {
        $seasons = [];
        $episodeCount = 0;
        $current = 0;

        foreach ($this->showStoreDB->getEpisodes($category, $series) as $episode) {
            if ($current !== $episode["season_no"] && $current > 0) {
                $seasons[] = new SeasonModel($current, $episodeCount);
                $episodeCount = 0;
            }

            $current = $episode["season_no"];
            $episodeCount++;
        }

        if ($episodeCount > 0) {
            $seasons[] = new SeasonModel($current, $episodeCount);
        }

        return $seasons;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeason($category, $series, $season)
    {
        $seasons = $this->getSeasons($category, $series);

        return $seasons[$season - 1];
    }

    /**
     * {@inheritDoc}
     */
    public function getEpisodes($category, $series, $season)
    {
        $data = $this->showStoreDB->getEpisodes($category, $series);
        $files = glob(sprintf("%s%s/*.avi", $this->getCategoryPath($category), $series));
        $episodes = [];

        foreach ($data as $episode) {
            if ($episode["season_no"] < $season) {
                continue;
            }

            if ($episode["season_no"] > $season) {
                break;
            }

            $link = $this->getFileLink($season, $episode['episode_no'], $files, $this->path);
            $episodes[] = new EpisodeModel($episode['episode_no'], $episode['title'], $link, $episode['description']);
        }

        return $episodes;
    }

    /**
     * {@inheritDoc}
     */
    public function getEpisode($category, $series, $season, $episode)
    {
        $episodes = $this->getEpisodes($category, $series, $season);
        $episodes = array_values(array_filter($episodes, function (EpisodeModel $episodeModel) use ($episode) {
            return $episodeModel->getId() === $episode;
        }));

        if (count($episodes) !== 1) {
            throw new NotFoundException(sprintf('Episode %s of season %s of series %s not found', $episode, $season, $series));
        }

        return $episodes[0];
    }

    /**
     * {@inheritDoc}
     */
    public function updateData()
    {
        $protocol = [];

        foreach ($this->getCategoryNames() as $category) {
            $protocol[] = $this->maintenance($category);
        }

        return $protocol;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryNames()
    {
        if (empty($this->categoryNames)) {
            /*
             * shows_root
            * 	|_ folders (A)
            *      |_ sub  (B)
            * Each show has to be placed in its own folder. These folders can be
            * placed at level (A) or (B) below the shows_root. If they are on level (A)
            * all shows will be available via a single menu entry (a category -
            * which will be named default in the database).
            * Shows can be placed on level (B) to put them into several categories
            * (the level (A) folders). The setup is auto detected below.
            */
            $folders = $this->getFolders($this->path);
            $sub = $this->getFolders($this->path.$folders[0]."/");
            $this->useDefault = true;

            if (count($sub) > 0) {
                $files = glob($this->path.$folders[0]."/".$sub[0]."/*.avi");

                if (count($files) > 0) {
                    $this->useDefault = false;
                }
            }

            if ($this->useDefault) {
                $categories = [SeriesService::DEFAULT_CATEGORY];
            } else {
                $categories = [];

                foreach ($folders as $folder) {
                    $categories[] = $folder;
                }
            }

            $this->categoryNames = $categories;
        }

        return $this->categoryNames;
    }

    /**
     * @param string $base
     * @param string $category
     *
     * @return string
     */
    private function getCategory($base, $category)
    {
        $path = $base;

        if (!$this->useDefault) {
            $path .= $category."/";
        }

        return $path;
    }

    /**
     * @param string $category
     *
     * @return string
     */
    private function getCategoryPath($category)
    {
        return $this->getCategory($this->path, $category);
    }

    /**
     * Get the path for the series.
     *
     * @param string $category
     * @param string $series
     *
     * @return string
     */
    private function getSeriesPath($category, $series)
    {
        return sprintf('%s%s', $this->getCategoryPath($category), $series);
    }

    /**
     * @param string $category
     *
     * @return string
     */
    private function getCategoryAlias($category)
    {
        return $this->getCategory($this->alias, $category);
    }

    /**
     * @param string $category
     *
     * @return array
     */
    private function getFoldersByCategory($category)
    {
        return $this->getFolders($this->getCategoryPath($category));
    }

    /**
     * @param string $category
     *
     * @throws \Exception
     *
     * @return MaintenanceModel
     */
    private function maintenance($category)
    {
        $steps = [];
        $protocol = $this->addMissingShows($category);
        $steps[] = [
            'description' => 'Check missing show entries (new shows)',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->removeObsoleteShows($category);
        $steps[] = [
            'description' => 'Check obsolete show entries (removed shows)',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->updateEpisodesForSeries($this->showStoreDB->getShows($category), $category);
        $steps[] = [
            'description' => 'Update episodes',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->fetchBackgroundImagesForSeries($this->showStoreDB->getShows($category), $category); //fetching shows from db, as they might have been updated
        $steps[] = [
            'description' => 'Fetch background images',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->updateThumbnailsForSeries($category, $this->getFoldersByCategory($category));
        $steps[] = [
            'description' => 'Update thumbnails',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];

        return new MaintenanceModel($category, $steps);
    }

    /**
     * @param string $category
     *
     * @return array
     */
    private function addMissingShows($category)
    {
        $protocol = [];

        foreach ($this->getFoldersByCategory($category) as $folder) {
            try {
                $added = null;
                $title = str_replace("-", " ", $folder);
                $showProtocol = [
                    'object' => $title,
                    'success' => true,
                ];
                $added = $this->showStoreDB->createIfMissing($category, $folder, $title);

                if (!empty($added)) {
                    $this->updateTvDbId($category, $folder, $title);
                    $protocol[] = $showProtocol;
                }
            } catch (ScrapeException $e) {
                $showProtocol['success'] = false;
                $showProtocol['error'] = $e->getMessage();
                $protocol[] = $showProtocol;
            }
        }

        return $protocol;
    }

    /**
     * @param string $category
     * @param string $folder
     * @param string $title
     * @param string $language
     *
     * @throws ScrapeException
     *
     * @return string
     */
    private function updateTvDbId($category, $folder, $title, $language = 'de')
    {
        $tvDbId = $this->ttvdbWrapper->getSeriesId($title);
        $this->showStoreDB->updateDetails($category, $folder, $title, $tvDbId, $language);

        return $tvDbId;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    private function removeObsoleteShows($category)
    {
        $folders = $this->getFoldersByCategory($category);

        return $this->showStoreDB->removeIfObsolete($category, $folders);
    }

    /**
     * @param array  $series
     * @param string $category
     *
     * @throws \Exception
     *
     * @return array
     */
    private function updateEpisodesForSeries(array $series, $category)
    {
        $protocol = [];

        //id, title, folder, tvdb_id, ordering_scheme, lang
        foreach ($series as $show) {
            try {
                $showProtocol = [
                    'object' => $show['title'],
                    'success' => true,
                ];

                if (empty($show['tvdb_id'])) {
                    $show['tvdb_id'] = $this->updateTvDbId($category, $show['folder'], $show['title']);
                }

                if (!empty([$show['tvdb_id']])) {
                    $this->updateEpisodes($show);
                }
            } catch (ScrapeException $e) {
                $showProtocol['success'] = false;
                $showProtocol['error'] = $e->getMessage();
            }

            $protocol[] = $showProtocol;
        }

        return $protocol;
    }

    /**
     * @param array $seriesData
     *
     * @throws ScrapeException
     */
    private function updateEpisodes(array $seriesData)
    {
        $seasons = $this->ttvdbWrapper->getSeriesInfoById($seriesData["tvdb_id"], $seriesData["ordering_scheme"], $seriesData["lang"]);
        $this->showStoreDB->updateEpisodes($seriesData["id"], $seasons);
    }

    /**
     * @param array  $series
     * @param string $category
     *
     * @return array
     */
    private function fetchBackgroundImagesForSeries(array $series, $category)
    {
        $protocol = [];

        //id, title, folder, tvdb_id, ordering_scheme, lang
        foreach ($series as $show) {
            try {
                $folderProtocol = [
                    'object' => $show['title'],
                    'success' => true,
                ];
                $this->fetchBackgroundImage($category, $show['folder'], $show['tvdb_id']);
            } catch (\Exception $e) {
                $folderProtocol['success'] = false;
                $folderProtocol['error'] = $e->getMessage();
            }

            $protocol[] = $folderProtocol;
        }

        return $protocol;
    }

    /**
     * Fetches the background image for the series.
     *
     * @param string $category
     * @param string $series
     * @param string $tvDbId
     * @param bool   $force
     */
    private function fetchBackgroundImage($category, $series, $tvDbId, $force = false)
    {
        $seriesPath = $this->getSeriesPath($category, $series);
        $backgroundImage = sprintf('%s/bg.jpg', $seriesPath);

        if ($force && file_exists($backgroundImage)) {
            unlink($backgroundImage);
        }

        if (!file_exists($backgroundImage)) {
            $this->ttvdbWrapper->downloadBG($tvDbId, $backgroundImage);
        }
    }

    /**
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    private function updateThumbnailsForSeries($category, array $folders)
    {
        $protocol = [];

        foreach ($folders as $folder) {
            try {
                $folderProtocol = [
                    'object' => $folder,
                    'success' => true,
                ];
                $this->updateThumbnail($category, $folder);
            } catch (\Exception $e) {
                $folderProtocol['success'] = false;
                $folderProtocol['error'] = $e->getMessage();
            }

            $protocol[] = $folderProtocol;
        }

        return $protocol;
    }

    /**
     * Generates thumbnail from the existing background image, if necessary (or forced).
     *
     * @param string $category
     * @param string $series
     * @param bool   $force
     *
     * @throws \Exception
     */
    private function updateThumbnail($category, $series, $force = false)
    {
        $seriesPath = $this->getSeriesPath($category, $series);
        $backgroundImage = sprintf('%s/bg.jpg', $seriesPath);
        $thumbnail = sprintf('%s/thumb.jpg', $seriesPath);

        if ($force && file_exists($thumbnail)) {
            unlink($thumbnail);
        }

        if (!file_exists($thumbnail)) {
            if (file_exists($backgroundImage)) {
                $this->resizeImage($backgroundImage, $thumbnail, self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
            } else {
                throw new \Exception(sprintf('Unable to create thumbnail for series "%s": background image is missing', $series));
            }
        }
    }

    /**
     * @param string $category
     * @param string $id
     *
     * @throws NotFoundException if the series was not found in the store
     *
     * @return SeriesModel
     */
    private function getSeriesDetails($category, $id)
    {
        $show = $this->showStoreDB->getShowDetails($category, $id);
        $path = $this->getCategoryAlias($category);

        return new SeriesModel(
            $show['id'],
            $show['title'],
            $path.$show['folder'].'/thumb.jpg',
            $show['lang'],
            $show['tvdb_id'],
            $show['folder']
        );
    }

    /**
     * @param string $seasonNo
     * @param string $episodeNo
     * @param array  $files
     * @param string $baseDir
     *
     * @return string|null
     */
    private function getFileLink($seasonNo, $episodeNo, $files, $baseDir)
    {
        $link = null;

        if (strlen($episodeNo) === 1) {
            $episodeNo = "0".$episodeNo;
        }

        foreach ($files as $file) {
            if (strpos($file, "_".$seasonNo."x".$episodeNo) !== false) {
                $link = str_replace($baseDir, "", $file);
                $link = str_replace("\\", "/", $link);
            }
        }

        return $link;
    }
}
