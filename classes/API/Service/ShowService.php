<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Exception\ScrapeException;
use TinyMediaCenter\API\Service\MediaLibrary\TTVDBWrapper;
use TinyMediaCenter\API\Service\Store\ShowStoreDB;

/**
 * Class ShowService
 */
class ShowService extends AbstractCategoryService
{

    const DEFAULT_CATEGORY = "Serien";

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
     * ShowController constructor.
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
     * @return array
     */
    public function getCategories()
    {
        return $this->getCategoryNames();
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getList($category)
    {
        $overview = $this->showStoreDB->getShows($category);
        $result = [];
        $path = $this->getCategoryAlias($category);

        foreach ($overview as $show) {
            $result[] = [
                "folder" => $show["folder"],
                "title" => $show["title"],
                "tvdb_id" => $show["tvdb_id"],
                "thumbUrl" => $path.$show["folder"]."/thumb.jpg",
            ];
        }

        return $result;
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @return array
     */
    public function getDetails($category, $id)
    {
        $episodesData = $this->showStoreDB->getEpisodes($category, $id);
        $showDetails = $this->showStoreDB->getShowDetails($category, $id);
        $base = $this->getCategoryPath($category);
        $base .= $id."/";
        $files = glob($base."*.avi");

        $episodesArray = [];
        $season = [];
        $current = 0;

        foreach ($episodesData as $ep) {
            if ($current !== $ep["season_no"]) {
                if ($current > 0) {
                    $episodesArray["Staffel ".$current] = $season;
                }
                $current = $ep["season_no"];
                $season = [];
            }
            $season[$ep["episode_no"]] = ["title" => $ep["title"], "id" => $ep["id"]];
        }

        $episodesArray["Staffel ".$current] = $season;

        $showData = [];
        $seasonData = [];
        $seasonNo = 0;
        $lastSeason = "";

        foreach ($episodesArray as $season => $episodes) {
            if (count($seasonData) > 0) {
                $showData[] = ["title" => $lastSeason, "episodes" => $seasonData];
            }
            $lastSeason = $season;
            $seasonData = [];
            $seasonNo++;
            $episodeNo = 0;

            foreach ($episodes as $episode) {
                $episodeNo++;
                $link = $this->getFileLink($seasonNo, $episodeNo, $files, $this->path);
                $label = sprintf("%02d", $episodeNo)." - ".$episode["title"];

                if ($link !== false) {
                    $seasonData[] = ["link" => $this->alias.$link, "label" => $label, "id" => $episode["id"]];
                } else {
                    $seasonData[] = ["link" => "", "label" => $label, "id" => $episode["id"]];
                }
            }
        }

        if (count($seasonData) > 0) {
            $showData[] = ["title" => $lastSeason, "episodes" => $seasonData];
        }

        $path = $this->getCategoryAlias($category);
        $result = [
            "title" => $showDetails["title"],
            "seasons" => $showData,
            "tvdbId" => $showDetails["tvdb_id"],
            "imageUrl" => $path.$id."/bg.jpg",
            "lang" => $showDetails["lang"],
        ];

        return $result;
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @return string
     */
    public function getEpisodeDescription($category, $id)
    {
        return $this->showStoreDB->getEpisodeDescription($category, $id);
    }

    /**
     * @param string $category
     * @param string $folder
     * @param string $title
     * @param string $tvdbId
     * @param string $lang
     */
    public function updateDetails($category, $folder, $title, $tvdbId, $lang)
    {
        $oldId = $this->showStoreDB->updateDetails($category, $folder, $title, $tvdbId, $lang);

        if ($oldId !== $tvdbId) {
            $path = $this->getCategoryPath($category);
            $path .= $folder."/bg.jpg";

            if (file_exists($path)) {
                unlink($path);
            }

            $path = $this->getCategoryPath($category);
            $path .= $folder."/thumb.jpg";

            if (file_exists($path)) {
                unlink($path);
            }

            $shows = [];
            $shows[] = $this->showStoreDB->getShowDetails($category, $folder);
            $this->updateEpisodes($category, $shows);
            $this->updateThumbs($category, [$folder]);
        }
    }

    /**
     * @return array
     */
    public function updateData()
    {
        $protocol = [];

        foreach ($this->getCategoryNames() as $category) {
            $protocol[] = [
                'type' => 'show_maintenance',
                'attributes' => $this->maintenance($category),
            ];
        }

        return $protocol;
    }

    /**
     * @return array
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
                $categories = [ShowService::DEFAULT_CATEGORY];
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
        $path = $this->getCategoryPath($category);
        $folders = $this->getFolders($path);

        return $folders;
    }

    /**
     * @param string $category
     *
     * @return array
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
        $protocol = $this->updateEpisodes($category, $this->showStoreDB->getShows($category));
        $steps[] = [
            'description' => 'Update episodes',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->updateThumbs($category, $this->getFoldersByCategory($category));
        $steps[] = [
            'description' => 'Update thumbnails',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];

        return $maintenance = [
            'category' => $category,
            'steps' => $steps,
        ];
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
            $added = $this->showStoreDB->createIfMissing($category, $folder);

            if (!empty($added)) {
                $protocol[] = $added;
            }
        }

        return $protocol;
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
     * @param string $category
     * @param array  $shows
     *
     * @return array
     */
    private function updateEpisodes($category, $shows)
    {
        $protocol = [];

        foreach ($shows as $show) {
            try {
                $showProtocol = [
                    'object' => $show['title'],
                    'success' => false,
                ];

                if ($show["tvdb_id"] === null) {
                    $search = urlencode($show["title"]);
                    $id = $this->ttvdbWrapper->getSeriesId($search);
                    $this->showStoreDB->updateDetails($category, $show["folder"], $show["title"], $id, $show["lang"]);
                    $show = $this->showStoreDB->getShowDetails($category, $show["folder"]);
                }

                $path = $this->getCategoryPath($category);
                $path .= $show["folder"]."/bg.jpg";

                if (!file_exists($path)) {
                    $this->ttvdbWrapper->downloadBG($show["tvdb_id"], $path);
                }

                $seasons = $this->ttvdbWrapper->getSeriesInfoById($show["tvdb_id"], $show["ordering_scheme"], $show["lang"]);

                if (count($seasons) > 0) {
                    $this->showStoreDB->updateEpisodes($show["id"], $seasons);
                    $showProtocol['success'] = true;
                } else {
                    $showProtocol['error'] = "Scraping failed (check ID): No data";
                }
            } catch (ScrapeException $e) {
                $showProtocol['error'] = sprintf("Scraping failed (check ID): %s", $e->getMessage());
            }

            $protocol[] = $showProtocol;
        }

        return $protocol;
    }

    /**
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    private function updateThumbs($category, $folders)
    {
        $protocol = [];
        $basePath = $this->getCategoryPath($category);

        foreach ($folders as $folder) {
            $path = $basePath.$folder."/";
            $folderProtocol = [
                'object' => $folder,
                'success' => true,
            ];
            $dim = 512;

            if (!file_exists($path."thumb.jpg")) {
                if (file_exists($path."bg.jpg")) {
                    $this->resizeImage($path."bg.jpg", $path."thumb.jpg", $dim, $dim);
                } else {
                    $folderProtocol['success'] = false;
                    $folderProtocol['error'] = "Failed to create thumbnail: no background image.";
                }
            }

            $protocol[] = $folderProtocol;
        }

        return $protocol;
    }

    /**
     * @param string $seasonNo
     * @param string $episodeNo
     * @param array  $files
     * @param string $baseDir
     *
     * @return bool|mixed
     */
    private function getFileLink($seasonNo, $episodeNo, $files, $baseDir)
    {
        if (strlen($episodeNo) === 1) {
            $episodeNo = "0".$episodeNo;
        }

        foreach ($files as $file) {
            if (strpos($file, "_".$seasonNo."x".$episodeNo) !== false) {
                $link = str_replace($baseDir, "", $file);
                $link = str_replace("\\", "/", $link);

                return $link;
            }
        }

        return false;
    }
}
