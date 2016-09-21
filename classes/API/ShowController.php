<?php

namespace TinyMediaCenter\API;

/**
 * Class ShowController
 */
class ShowController extends AbstractController
{

    const DEFAULT_CATEGORY = "Serien";

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
     * @param string $path
     * @param string $alias
     * @param string $dbConfig
     * @param string $apiKey
     */
    public function __construct($path, $alias, $dbConfig, $apiKey)
    {
        $store = new ShowStoreDB($dbConfig);
        $scraper = new TTVDBWrapper($apiKey);
        parent::__construct($path, $alias, $store, $scraper);
        $this->categoryNames = $this->getCategoryNames();
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        $categories = array();
        $names = $this->categoryNames;
        foreach ($names as $name) {
            $categories["shows/".$name."/"] = $name;
        }

        return $categories;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getList($category)
    {
        $overview = $this->store->getShows($category);
        $result = array();
        $path = $this->getCategoryAlias($category);
        foreach ($overview as $show) {
            $result[] = [
                "folder" => $show["folder"],
                "title" => $show["title"],
                "tvdb_id" => $show["tvdb_id"],
                "thumbUrl" => $path.$show["folder"]."/thumb_260.jpg",
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
        $episodesData = $this->store->getEpisodes($category, $id);
        $showDetails = $this->store->getShowDetails($category, $id);
        $base = $this->getCategoryPath($category);
        $base .= $id."/";
        $files = glob($base."*.avi");

        $episodesArray = array();
        $season = array();
        $current = 0;
        foreach ($episodesData as $ep) {
            if ($current !== $ep["season_no"]) {
                if ($current > 0) {
                    $episodesArray["Staffel ".$current] = $season;
                }
                $current = $ep["season_no"];
                $season = array();
            }
            $season[$ep["episode_no"]] = array("title" => $ep["title"], "id" => $ep["id"]);
        }
        $episodesArray["Staffel ".$current] = $season;

        $showData = array();
        $seasonData = array();
        $seasonNo = 0;
        $lastSeason = "";
        foreach ($episodesArray as $season => $episodes) {
            if (count($seasonData) > 0) {
                $showData[] = array("title" => $lastSeason, "episodes" => $seasonData);
            }
            $lastSeason = $season;
            $seasonData = array();
            $seasonNo++;
            $episodeNo = 0;
            foreach ($episodes as $episode) {
                $episodeNo++;
                $link = $this->getFileLink($seasonNo, $episodeNo, $files, $this->path);
                $label = sprintf("%02d", $episodeNo)." - ".$episode["title"];
                if ($link !== false) {
                    $seasonData[] = array("link" => $this->alias.$link, "label" => $label, "id" => $episode["id"]);
                } else {
                    $seasonData[] = array("link" => "", "label" => $label, "id" => $episode["id"]);
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
        return $this->store->getEpisodeDescription($category, $id);
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
        $oldId = $this->store->updateDetails($category, $folder, $title, $tvdbId, $lang);
        if ($oldId !== $tvdbId) {
            $path = $this->getCategoryPath($category);
            $path .= $folder."/bg.jpg";
            if (file_exists($path)) {
                unlink($path);
            }
            $path = $this->getCategoryPath($category);
            $path .= $folder."/thumb_260.jpg";
            if (file_exists($path)) {
                unlink($path);
            }
            $shows = array();
            $shows[] = $this->store->getShowDetails($category, $folder);
            $this->updateEpisodes($category, $shows);
            $this->updateThumbs($category, array($folder));
        }
    }

    /**
     * @return array
     */
    public function updateData()
    {
        $protocol = "";
        $categories = $this->categoryNames;

        foreach ($categories as $category) {
            $protocol .= $this->maintenance($category);
        }

        return array("result" => "Ok", "protocol" => $protocol);
    }

    /**
     * @return array
     */
    private function getCategoryNames()
    {
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
        $folders = Util::getFolders($this->path);
        $sub = Util::getFolders($this->path.$folders[0]."/");
        $this->useDefault = true;
        if (count($sub) > 0) {
            $files = glob($this->path.$folders[0]."/".$sub[0]."/*.avi");
            if (count($files) > 0) {
                $this->useDefault = false;
            }
        }
        if ($this->useDefault) {
            $categories = array(ShowController::DEFAULT_CATEGORY);
        } else {
            $categories = array();
            foreach ($folders as $folder) {
                $categories[] = $folder;
            }
        }

        return $categories;
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
    private function getFolders($category)
    {
        $path = $this->getCategoryPath($category);
        $folders = Util::getFolders($path);

        return $folders;
    }

    /**
     * @param string $category
     *
     * @return string
     */
    private function maintenance($category)
    {
        $protocol = "";
        $protocol .= "<h2>Maintenance ".$category."</h2>";
        $protocol .= "<h3>Check missing show entries (new shows)</h3>";
        $protocol .= $this->addMissingShows($category);

        $protocol .= "<h3>Check obsolete show entries (removed shows)</h3>";
        $protocol .= $this->removeObsoleteShows($category);

        $protocol .= "<h3>Update episodes</h3>";
        $shows = $this->store->getShows($category);
        $protocol .= $this->updateEpisodes($category, $shows);

        $protocol .= "<h3>Update thumbnails</h3>";
        $folders = $this->getFolders($category);
        $protocol .= $this->updateThumbs($category, $folders);

        return $protocol;
    }

    /**
     * @param string $category
     *
     * @return string
     */
    private function addMissingShows($category)
    {
        $protocol = "";
        $folders = $this->getFolders($category);
        foreach ($folders as $folder) {
            $protocol .= $this->store->createIfMissing($category, $folder);
        }

        return $protocol;
    }

    /**
     * @param string $category
     *
     * @return mixed
     */
    private function removeObsoleteShows($category)
    {
        $folders = $this->getFolders($category);

        return $this->store->removeIfObsolete($category, $folders);
    }

    /**
     * @param string $category
     * @param array  $shows
     *
     * @return string
     */
    private function updateEpisodes($category, $shows)
    {
        $protocol = "";
        foreach ($shows as $show) {
            try {
                $protocol .= "Updating ".$show["title"]." ... ";
                if ($show["tvdb_id"] === null) {
                    $search = urlencode($show["title"]);
                    $id = $this->scraper->getSeriesId($search);
                    $this->store->updateDetails($category, $show["folder"], $show["title"], $id, $show["lang"]);
                    $show = $this->store->getShowDetails($category, $show["folder"]);
                }
                $path = $this->getCategoryPath($category);
                $path .= $show["folder"]."/bg.jpg";
                if (!file_exists($path)) {
                    $protocol .= "Getting bakground image ... ";
                    $this->scraper->downloadBG($show["tvdb_id"], $path);
                }
                $protocol .= "Scraping ... ";
                $seasons = $this->scraper->getSeriesInfoById($show["tvdb_id"], $show["ordering_scheme"], $show["lang"]);
                if (count($seasons) > 0) {
                    $this->store->updateEpisodes($show["id"], $seasons);
                    $protocol .= "Done";
                } else {
                    $protocol .= "Scraping failed (check ID): No data";
                }
            } catch (ScrapeException $e) {
                $protocol .= "Scraping failed (check ID): ".$e->getMessage();
            }

            $protocol .= "<br>";
        }

        return $protocol;
    }

    /**
     * @param string $category
     * @param array  $folders
     *
     * @return string
     */
    private function updateThumbs($category, $folders)
    {
        $protocol = "";
        $basePath = $this->getCategoryPath($category);
        foreach ($folders as $folder) {
            $path = $basePath.$folder."/";
            $protocol .= $path;
            $dim = 260;
            if (!file_exists($path."thumb_".$dim.".jpg")) {
                if (file_exists($path."bg.jpg")) {
                    Util::resizeImage($path."bg.jpg", $path."thumb_".$dim.".jpg", $dim, $dim);
                    $protocol .= "done";
                } else {
                    $protocol .= "Failed to create thumbnail: no background image.";
                }
            }
            $protocol .= "<br>";
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
