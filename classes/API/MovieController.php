<?php
namespace TinyMediaCenter\API;

/**
 * Class MovieController
 */
class MovieController extends AbstractController
{

    const DEFAULT_CATEGORY = "Filme";

    /**
     * @var bool
     */
    private $useDefault;

    /**
     * @var array
     */
    private $categoryNames;

    /**
     * MovieController constructor.
     *
     * @param string $path
     * @param string $alias
     * @param string $dbConfig
     * @param string $apiKey
     */
    public function __construct($path, $alias, $dbConfig, $apiKey)
    {
        $scraper = new TMDBWrapper($apiKey);
        $store = new MovieStoreDB($dbConfig);
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
            $categories["movies/".$name."/"] = $name;
        }

        return $categories;
    }

    /**
     * Get movies matching the given criteria.
     *
     * @param string $category the category
     * @param string $sort     the sort criteria (name|date|year)
     * @param string $order    the sort order (asc|desc)
     * @param string $filter   search terms
     * @param string $genre    genres
     * @param int    $cnt      pagination control; maximum number of results
     * @param int    $offset   pagination control; offset
     *
     * @return array the movies (array of arrays)
     *
     */
    public function getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset)
    {
        $movieData = $this->store->getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset);
        $movieData["list"] = $this->addPosterEntry($category, $movieData["list"]);

        return $movieData;
    }

    /**
     * Get movies for the given collection id.
     * Results are ordered by release date.
     *
     * @param string $category
     * @param int    $collectionID the collection id
     * @param int    $cnt          pagination control; maximum number of results
     * @param int    $offset       pagination control; offset
     *
     * @return array the movies (array of arrays)
     *
     */
    public function getMoviesForCollection($category, $collectionID, $cnt, $offset)
    {
        $movieData = $this->store->getMoviesForCollection($category, $collectionID, $cnt, $offset);
        $movieData["list"] = $this->addPosterEntry($category, $movieData["list"]);

        return $movieData;
    }

    /**
     * Get movies for the given list id.
     * Results are ordered by release date.
     *
     * @param string $category
     * @param string $listId
     * @param int    $cnt
     * @param int    $offset
     *
     * @return array
     *
     */
    public function getMoviesForList($category, $listId, $cnt, $offset)
    {
        $movieData = $this->store->getMoviesForList($category, $listId, $cnt, $offset);
        $movieData["list"] = $this->addPosterEntry($category, $movieData["list"]);

        return $movieData;
    }

    /**
     * Get details for the given id (database id).
     *
     * @param string $category
     * @param int    $id
     *
     * @return array the movie details, an error message if the movie was not found
     */
    public function getMovieDetails($category, $id)
    {
        $movie = $this->store->getMovieById($category, $id);
        if (isset($movie["error"])) {
            return $movie;
        }
        $movie["filename"] = $this->getCategoryAlias($category).$movie["filename"];
        $alias = $this->getCategoryAlias($category);
        $movie["poster"] = $alias."pictures/".$movie["movie_db_id"]."_333x500.jpg";
        $actors = explode(",", $movie["actors"]);
        $movie["actors"] = array_slice($actors, 0, 4);
        $movie["countries"] = explode(",", $movie["countries"]);
        $movie["genres"] = explode(",", $movie["genres"]);
        $movie["year"] = substr($movie["release_date"], 0, 4);

        return $movie;
    }

    /**
     * @param string $category
     * @param string $localId
     * @param string $movieDBID
     * @param string $filename
     *
     * @return string
     */
    public function updateFromScraper($category, $localId, $movieDBID, $filename)
    {
        $movie = $this->scraper->getMovieInfo($movieDBID, $this->getCategoryPath($category), $filename);

        return $this->updateMovie($category, $movie, $localId);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function lookupMovie($id)
    {
        $movie = $this->scraper->getMovieInfo($id);

        return $movie->toArray();
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getGenres($category)
    {
        return $this->store->getGenres($category);
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getLists($category)
    {
        return $this->store->getLists($category);
    }

    /**
     * @param string $category
     *
     * @return mixed
     */
    public function getCollections($category)
    {
        return $this->store->getCollections($category);
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
     * @param string $category
     *
     * @return string
     */
    public function maintenance($category)
    {
        $protocol = "<h1>Maintenance ".$category."</h1>";
        $protocol .= "<h2>Duplicate movie files</h2>";
        $path = $this->getCategoryPath($category);
        $pp = $this->getPicturePath($category);
        $res = $this->checkDuplicateFiles($path);
        foreach ($res as $movie) {
            $protocol .= $movie."<br>";
        }
        $protocol .= "<h2>Duplicate movie entries</h2>";
        $res = $this->store->checkDuplicates($category);
        foreach ($res as $movie) {
            $protocol .= $movie."<br>";
        }

        $missing = $this->store->checkExisting($category, $path);

        $protocol .= "<h2>Missing movie entries (new movies)</h2>";
        foreach ($missing as $filename) {
            $title = $this->getMovieTitle($filename);
            $protocol .= $title." (File: ".$filename.")<br>";
            $protocol .= $this->searchMovie($category, $title, $filename);
            $protocol .= "<br>";
        }

        $protocol .= "<h2>Obsolete movie entries</h2>";
        $protocol .= $this->store->checkRemovedFiles($category, $path);

        $protocol .= "<h2>Missing collection entries</h2>";
        $res = $this->store->checkCollections($category);
        foreach ($res["missing"] as $miss) {
            $col = $this->updateCollectionFromScraper($category, $miss);
            $protocol .= $col;
            $protocol .= "<br>";
        }

        $protocol .= "<h2>Obsolete collection entries</h2>";
        foreach ($res["obsolete"] as $obs) {
            $protocol .= $obs;
            $protocol .= $this->removeObsoleteCollection($obs);
            $protocol .= "<br>";
        }

        $protocol .= "<h2>Fetching missing Movie Pics</h2>";
        $res = $this->store->getMissingPics($category, $pp);
        foreach ($res["missing"] as $miss) {
            $protocol .= "fetching ".$miss["MOVIE_DB_ID"]."<br>";
            $protocol .= $this->downloadMoviePic($pp, $miss["MOVIE_DB_ID"]);
        }
        $protocol .= "<h2>Remove obsolete Movie Pics</h2>";
        $protocol .= $this->removeObsoletePics($res["all"], $pp);

        $protocol .= "<h2>Resizing images</h2>";
        $this->resizeMoviePics($pp);

        return $protocol;
    }

    /**
     * @return array
     */
    private function getCategoryNames()
    {
        $folders = Util::getFolders($this->path, array("pictures"));
        $categories = array(MovieController::DEFAULT_CATEGORY);
        $this->useDefault = true;
        if (count($folders) > 0) {
            $this->useDefault = false;
            $categories = array();
            foreach ($folders as $folder) {
                $categories[] = $folder;
            }
        }

        return $categories;
    }

    /**
     * @param string $category
     * @param array  $movies
     *
     * @return array
     */
    private function addPosterEntry($category, $movies)
    {
        $alias = $this->getCategoryAlias($category);
        foreach ($movies as &$movie) {//call by reference
            $movie["poster"] = $alias."pictures/".$movie["movie_db_id"]."_333x500.jpg";
        }

        return $movies;
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
     * @return string
     */
    private function getPicturePath($category)
    {
        $pp = $this->getCategoryPath($category);
        $pp .= "pictures/";

        return $pp;
    }

    /**
     * @param string $category
     * @param Movie  $movie
     * @param string $localId
     *
     * @return string
     */
    private function updateMovie($category, $movie, $localId = "")
    {
        if ($movie !== null) {
            $picturePath = $this->getPicturePath($category);
            $this->downloadMoviePic($picturePath, $movie->getId(), $movie);
            $this->store->updateMovie($category, $movie->toArray(), $this->getCategoryPath($category), $localId);
            $this->resizeMoviePics($picturePath);

            return "OK:".$movie->__toString();
        } else {
            return "Error";
        }
    }

    /**
     * @param string $category
     * @param string $title
     * @param string $filename
     *
     * @return string
     */
    private function searchMovie($category, $title, $filename)
    {
        try {
            $movie = $this->scraper->searchMovie($title, $filename, $this->getCategoryPath($category));

            return $this->updateMovie($category, $movie);
        } catch (ScrapeException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $picturePath
     * @param int    $id
     * @param string $movie
     *
     * @return string
     */
    private function downloadMoviePic($picturePath, $id, $movie = "")
    {
        try {
            if ($movie === "") {
                $movie = $this->scraper->getMovieInfo($id);
            }
            if ($movie !== null) {
                $this->scraper->downloadPoster($id, $movie->getPosterPath(), $picturePath);

                return "OK";
            }

            return "No Match";
        } catch (ScrapeException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $picsDir
     *
     * @return string
     */
    private function resizeMoviePics($picsDir)
    {
        $images = Util::globRecursive($picsDir."*big.jpg");
        $protocol = "";
        foreach ($images as $image) {
            $id = substr($image, strrpos($image, "/") + 1);
            $id = substr($id, 0, strpos($id, "_"));

            $dest = $picsDir.$id."_big.jpg";
            $target = $picsDir.$id."_333x500.jpg";

            if (file_exists($target)) {
                continue;
            }
            $protocol .= $dest." - ".$target."<br>";
            Util::resizeImage($dest, $target, 333, 500);
        }

        return $protocol;
    }

    /**
     * @param string $movieDBIDS
     * @param string $picsDir
     *
     * @return string
     */
    private function removeObsoletePics($movieDBIDS, $picsDir)
    {
        $files = glob($picsDir."*_big.jpg");

        $protocol = "";
        foreach ($files as $file) {
            $id = substr($file, strlen($picsDir));
            $id = substr($id, 0, strpos($id, "_"));
            if (in_array($id, $movieDBIDS)) {
                continue;
            } else {
                $protocol .= "removing ".$id."<br>";
                unlink($file);
                $small = $picsDir.$id."_333x500.jpg";
                if (file_exists($small)) {
                    unlink($small);
                }
            }
        }

        return $protocol;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function checkDuplicateFiles($path)
    {
        $files = glob($path."*.avi");
        $titles = array();
        foreach ($files as $file) {
            $titles[] = $this->getMovieTitle($file);
        }
        $cnts = array_count_values($titles);
        arsort($cnts);
        $result = array();
        foreach ($cnts as $title => $cnt) {
            if ($cnt > 1) {
                $result[] = $title;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $file
     *
     * @return mixed|string
     */
    private function getMovieTitle($file)
    {
        $file = preg_replace("/\d{2}\.\d{2}\.\d{2}.*/i", "", $file); //match for yy.mm.dd
        $file = preg_replace("/\.mpg.*/i", "", $file); //match for .mpg
        $file = preg_replace("/\.hq.*/i", "", $file); //match for .hq
        $file = preg_replace("/\.avi.*/i", "", $file); //match for .avi
        $file = str_replace("_", " ", $file);
        $file = trim($file);

        return $file;
    }

    /**
     * @param string $category
     * @param int    $collectionId
     *
     * @return string
     */
    private function updateCollectionFromScraper($category, $collectionId)
    {
        try {
            $collectionData = $this->scraper->getCollectionInfo($collectionId);
            $this->store->updateCollectionById($category, $collectionData, $collectionId);
            $collectionStr = "[Id: ".$collectionData["id"];
            $collectionStr .= ", Name: ".$collectionData["name"];
            $collectionStr .= ", Overview: ".$collectionData["overview"]."]";
        } catch (ScrapeException $e) {
            $collectionStr = $e->getMessage();
        }

        return $collectionStr;
    }

    /**
     * @param int $collectionId
     */
    private function removeObsoleteCollection($collectionId)
    {
        $this->store->removeObsoleteCollection($collectionId);
    }
}
