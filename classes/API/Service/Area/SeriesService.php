<?php

namespace TinyMediaCenter\API\Service\Area;

use Slim\Interfaces\RouterInterface;
use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Exception\MediaApiClientException;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Maintenance;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season\Episode;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series;
use TinyMediaCenter\API\Model\Resource\Area\Category;
use TinyMediaCenter\API\Model\Resource\Area;
use TinyMediaCenter\API\Model\Store\Series as StoreSeriesModel;
use TinyMediaCenter\API\Service\Api\SeriesApiClientInterface;
use TinyMediaCenter\API\Service\Store\SeriesStoreInterface;

/**
 * Series service.
 *
 * TODO folder should become slug
 * TODO sometimes id is used instead of folder, this should also become slug
 */
class SeriesService extends AbstractAreaService implements SeriesServiceInterface
{
    const DEFAULT_CATEGORY = "Serien";

    const THUMBNAIL_SIZE = 512;

    const AREA = 'SERIES_AREA';

    /**
     * @var SeriesStoreInterface
     */
    private $seriesStore;

    /**
     * @var SeriesApiClientInterface
     */
    private $seriesApiClient;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $path;

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
     * @param SeriesStoreInterface     $seriesStore
     * @param SeriesApiClientInterface $seriesApiClient
     * @param RouterInterface          $router
     * @param string                   $path
     */
    public function __construct(SeriesStoreInterface $seriesStore, SeriesApiClientInterface $seriesApiClient, RouterInterface $router, $path)
    {
        $this->seriesStore = $seriesStore;
        $this->seriesApiClient = $seriesApiClient;
        $this->router = $router;
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetaInfo(): Area
    {
        return new Area(
            'series',
            'Series area overview'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getArea()
    {
        return self::AREA;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories()
    {
        return array_map(function (string $category): Category {
            return new Category($category);
        }, $this->getCategoryNames());
    }

    /**
     * {@inheritDoc}
     */
    public function getByCategory(string $category): array
    {
        return array_map(function (StoreSeriesModel $series) use ($category): Series {
            return $this->convertModel($series, $category);
        }, $this->seriesStore->getSeries($category));
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $category, string $slug): Series
    {
        return $this->getSeriesDetails($category, $slug);
    }

    /**
     * {@inheritDoc}
     */
    public function update(string $category, string $slug, string $title, string $mediaApiId, string $lang): Series
    {
        $this->seriesStore->updateDetails($category, $slug, $title, $mediaApiId, $lang);
        $model = $this->seriesStore->getSeriesDetails($category, $slug);
        $this->updateEpisodes($model);
        $this->fetchBackgroundImage($category, $slug, $model->getApiId(), true);
        $this->updateThumbnail($category, $slug, true);

        return $this->getSeriesDetails($category, $slug);
    }

    /**
     * {@inheritDoc}
     */
    public function getSeasons(string $category, string $slug): array
    {
        $seasons = [];

        for ($i = 1; $i <= $this->seriesStore->getSeasonCount($category, $slug); $i++) {
            $seasons[] = new Season($i);
        }

        foreach ($seasons as $season) {
            foreach ($this->getEpisodes($category, $slug, $season->getNumber()) as $episode) {
                $season->addEpisode($episode);
            }
        }

        return $seasons;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeason(string $category, string $slug, int $season): Season
    {
        $seasons = $this->getSeasons($category, $slug);

        return $seasons[$season - 1];
    }

    /**
     * {@inheritDoc}
     */
    public function getEpisodes(string $category, string $slug, int $season): array
    {
        $files = $this->getEpisodeFiles($category, $slug);
        $episodes = [];

        foreach ($this->seriesStore->getEpisodes($category, $slug, $season) as $episode) {
            $link = null;

            if ($this->getFileLink($season, $episode->getNumber(), $files) !== null) {
                $link = $this->router->pathFor('app.series.categories.category.entries.series.seasons.season.episodes.episode.file', [
                    'category' => $category,
                    'series' => $slug,
                    'season' => $season,
                    'episode' => $episode->getNumber(),
                ]);
            }

            $episodes[] = new Episode($episode->getNumber(), $episode->getTitle(), $link, $episode->getDescription());
        }

        return $episodes;
    }

    /**
     * {@inheritDoc}
     */
    public function getEpisode(string $category, string $slug, int $season, int $episode): Episode
    {
        $episodes = $this->getEpisodes($category, $slug, $season);
        $episodes = array_values(array_filter($episodes, function (Episode $episodeModel) use ($episode) {
            return $episodeModel->getId() === $episode;
        }));

        if (count($episodes) !== 1) {
            throw new NotFoundException(sprintf('Episode %s of season %s of series %s not found', $episode, $season, $slug));
        }

        return $episodes[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getEpisodeFile(string $category, string $slug, int $season, int $episode): ?string
    {
        return $this->getFileLink($season, $episode, $this->getEpisodeFiles($category, $season));
    }

    /**
     * {@inheritDoc}
     */
    public function updateData(): array
    {
        return array_map(function (string $category) {
            return $this->maintenance($category);
        }, $this->getCategoryNames());
    }

    /**
     * {@inheritDoc}
     */
    public function getImage(string $category, string $slug, string $type): string
    {
        $path = $this->getCategoryPath($category);

        return sprintf('%s%s/%s.jpg', $path, $slug, $type);
    }

    /**
     * @return string[]
     */
    private function getCategoryNames(): array
    {
        if (empty($this->categoryNames)) {
            /*
             * series_root
             * 	|_ folders (A)
             *      |_ sub  (B)
             * Each series has to be placed in its own folder. These folders can be
             * placed at level (A) or (B) below the series_root. If they are on level (A)
             * all series will be available via a single menu entry (a category -
             * which will be named default in the database).
             * Series can be placed on level (B) to put them into several categories
             * (the level (A) folders). The setup is auto detected below.
             */
            $this->useDefault = true;
            $folders = $this->getFolders($this->path);
            $sub = $this->getFolders($this->path.$folders[0]."/");

            if (count($sub) > 0) {
                $files = glob($this->path.$folders[0]."/".$sub[0]."/*.avi");
                $this->useDefault = count($files) === 0;
            }

            $this->categoryNames = $this->useDefault ? [SeriesService::DEFAULT_CATEGORY] : $folders;
        }

        return $this->categoryNames;
    }

    /**
     * @param string $category
     *
     * @return string
     */
    private function getCategoryPath(string $category): string
    {
        return $this->useDefault
            ? $this->path
            : sprintf('%s%s/', $this->path, $category);
    }

    /**
     * Get the path for the series.
     *
     * @param string $category
     * @param string $series
     *
     * @return string
     */
    private function getSeriesPath(string $category, string $series): string
    {
        return sprintf('%s%s', $this->getCategoryPath($category), $series);
    }

    /**
     * @param string $category
     *
     * @return array
     */
    private function getFoldersByCategory(string $category): array
    {
        return $this->getFolders($this->getCategoryPath($category));
    }

    /**
     * @param string $category
     * @param string $series
     *
     * @return array
     */
    private function getEpisodeFiles(string $category, string $series): array
    {
        return glob(sprintf("%s%s/*.avi", $this->getCategoryPath($category), $series));
    }

    /**
     * @param string $category
     *
     * @throws \Exception
     *
     * @return Maintenance
     */
    private function maintenance(string $category): Maintenance
    {
        $steps = [];
        $folders = $this->getFoldersByCategory($category);
        $protocol = $this->addMissingShows($category, $folders);
        $steps[] = [
            'description' => 'Check missing show entries (new shows)',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->removeObsoleteShows($category, $folders);
        $steps[] = [
            'description' => 'Check obsolete show entries (removed shows)',
            'protocol' => $protocol,
            'success' => true,
        ];
        //fetching shows from db now, as they might have been updated
        $series = $this->seriesStore->getSeries($category);
        $protocol = $this->updateEpisodesForSeries($series, $category);
        $steps[] = [
            'description' => 'Update episodes',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->fetchBackgroundImagesForSeries($series, $category);
        $steps[] = [
            'description' => 'Fetch background images',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->updateThumbnailsForSeries($category, $folders);
        $steps[] = [
            'description' => 'Update thumbnails',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];

        return new Maintenance($category, $steps);
    }

    /**
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    private function addMissingShows(string $category, array $folders): array
    {
        $protocol = [];

        foreach ($folders as $folder) {
            try {
                $added = null;
                $title = str_replace("-", " ", $folder);
                $showProtocol = [
                    'object' => $title,
                    'success' => true,
                ];
                $added = $this->seriesStore->createIfMissing($category, $folder, $title);

                if (!empty($added)) {
                    $this->updateMediaApiId($category, $folder, $title);
                    $protocol[] = $showProtocol;
                }
            } catch (MediaApiClientException $e) {
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
     * @throws MediaApiClientException
     *
     * @return string
     */
    private function updateMediaApiId(string $category, string $folder, string $title, ?string $language = 'de'): string
    {
        $tvDbId = $this->seriesApiClient->getSeriesId($title);
        $this->seriesStore->updateDetails($category, $folder, $title, $tvDbId, $language);

        return $tvDbId;
    }

    /**
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    private function removeObsoleteShows(string $category, array $folders): array
    {
        return $this->seriesStore->removeIfObsolete($category, $folders);
    }

    /**
     * @param StoreSeriesModel[] $seriesModels
     * @param string             $category
     *
     * @throws \Exception
     *
     * @return array
     */
    private function updateEpisodesForSeries(array $seriesModels, string $category): array
    {
        $protocol = [];

        foreach ($seriesModels as $seriesModel) {
            try {
                $showProtocol = [
                    'object' => $seriesModel->getTitle(),
                    'success' => true,
                ];

                if (empty($seriesModel->getApiId())) {
                    $seriesModel->setApiId($this->updateMediaApiId($category, $seriesModel->getFolder(), $seriesModel->getTitle()));
                }

                if (!empty([$seriesModel->getApiId()])) {
                    $this->updateEpisodes($seriesModel);
                }
            } catch (MediaApiClientException $e) {
                $showProtocol['success'] = false;
                $showProtocol['error'] = $e->getMessage();
            }

            $protocol[] = $showProtocol;
        }

        return $protocol;
    }

    /**
     * @param StoreSeriesModel $seriesModel
     *
     * @throws MediaApiClientException
     */
    private function updateEpisodes(StoreSeriesModel $seriesModel)
    {
        $series = $this->seriesApiClient->getSeriesInfoById($seriesModel->getApiId(), $seriesModel->getOrderingScheme(), $seriesModel->getLanguage());
        $this->seriesStore->updateEpisodes($seriesModel->getId(), $series);
    }

    /**
     * @param StoreSeriesModel[] $seriesModels
     * @param string             $category
     *
     * @return array
     */
    private function fetchBackgroundImagesForSeries(array $seriesModels, string $category): array
    {
        $protocol = [];

        foreach ($seriesModels as $seriesModel) {
            try {
                $folderProtocol = [
                    'object' => $seriesModel->getTitle(),
                    'success' => true,
                ];
                $this->fetchBackgroundImage($category, $seriesModel->getFolder(), $seriesModel->getApiId());
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
    private function fetchBackgroundImage(string $category, string $series, string $tvDbId, bool $force = false)
    {
        $seriesPath = $this->getSeriesPath($category, $series);
        $backgroundImage = sprintf('%s/bg.jpg', $seriesPath);

        if ($force && file_exists($backgroundImage)) {
            unlink($backgroundImage);
        }

        if (!file_exists($backgroundImage)) {
            $image = $this->seriesApiClient->downloadBackgroundImage($tvDbId);

            $fp = fopen($backgroundImage, 'x');
            fwrite($fp, $image);
            fclose($fp);
        }
    }

    /**
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    private function updateThumbnailsForSeries(string $category, array $folders): array
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
    private function updateThumbnail(string $category, string $series, bool $force = false)
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
     * @param StoreSeriesModel $model
     * @param string           $category
     *
     * @return Series
     */
    private function convertModel(StoreSeriesModel $model, string $category): Series
    {
        $thumbnail = $this->router->pathFor('app.series.categories.category.entries.series.image', [
            'category' => $category,
            'series' => $model->getFolder(),
            'type' => 'thumbnail',
        ]);
        $background = $this->router->pathFor('app.series.categories.category.entries.series.image', [
            'category' => $category,
            'series' => $model->getFolder(),
            'type' => 'bg',
        ]);

        return new Series(
            $model->getId(),
            $model->getTitle(),
            $thumbnail,
            $background,
            $model->getLanguage(),
            $model->getFolder(),
            $model->getApiId()
        );
    }

    /**
     * @param string $category
     * @param string $slug
     *
     * @throws NotFoundException if the series was not found in the store
     * @throws \Exception
     *
     * @return Series
     */
    private function getSeriesDetails(string $category, string $slug): Series
    {
        $storeModel = $this->seriesStore->getSeriesDetails($category, $slug);
        $series = $this->convertModel($storeModel, $category);

        foreach ($this->getSeasons($category, $slug) as $season) {
            $series->addSeason($season);
        }

        return $series;
    }

    /**
     * @param string $seasonNo
     * @param string $episodeNo
     * @param array  $files
     *
     * @return string|null
     */
    private function getFileLink(string $seasonNo, string $episodeNo, array $files): ?string
    {
        $link = null;

        if (strlen($episodeNo) === 1) {
            $episodeNo = "0".$episodeNo;
        }

        foreach ($files as $file) {
            if (strpos($file, "_".$seasonNo."x".$episodeNo) !== false) {
                $link = str_replace("\\", "/", $file);
            }
        }

        return $link;
    }
}
