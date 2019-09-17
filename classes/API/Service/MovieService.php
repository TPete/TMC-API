<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Model\Movie\MovieModelInterface;
use TinyMediaCenter\API\Model\MediaFileInfoModel;
use TinyMediaCenter\API\Service\Store\MovieStoreDB;

/**
 * Class MovieService
 */
class MovieService extends AbstractCategoryService
{
    const DEFAULT_CATEGORY = "Filme";

    const PICTURES_FOLDER = 'pictures';

    /**
     * @var MovieStoreDB
     */
    private $movieStoreDB;

    /**
     * @var MovieApiInterface
     */
    private $movieApi;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $alias;

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
     * @param MovieStoreDB      $movieStoreDB
     * @param MovieApiInterface $movieApi
     * @param string            $path
     * @param string            $alias
     */
    public function __construct(MovieStoreDB $movieStoreDB, MovieApiInterface $movieApi, $path, $alias)
    {
        $this->movieStoreDB = $movieStoreDB;
        $this->movieApi = $movieApi;
        $this->path = $path;
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories()
    {
        $categories = [];

        foreach ($this->getCategoryNames() as $name) {
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
        $movieData = $this->movieStoreDB->getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset);
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
        $movieData = $this->movieStoreDB->getMoviesForCollection($category, $collectionID, $cnt, $offset);
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
        $movieData = $this->movieStoreDB->getMoviesForList($category, $listId, $cnt, $offset);
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
        $movie = $this->movieStoreDB->getMovieById($category, $id);

        if (isset($movie["error"])) {
            return $movie;
        }

        $movie["filename"] = $this->getCategoryAlias($category).$movie["filename"];
        $alias = $this->getCategoryAlias($category);
        $movie["poster"] = $alias.self::PICTURES_FOLDER."/".$movie["movie_db_id"]."_333x500.jpg";
        $movie["poster_big"] = $alias.self::PICTURES_FOLDER."/".$movie["movie_db_id"]."_big.jpg";
        $actors = explode(",", $movie["actors"]);
        $movie["actors"] = $actors;
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
     * @throws \Exception
     *
     * @return array
     */
    public function updateFromScraper($category, $localId, $movieDBID, $filename)
    {
        $movie = $this->movieApi->getMovieInfo($movieDBID);
        $fileInfo = new MediaFileInfoModel($this->getFilePath($category, $filename));

        $result = $this->updateMovie($category, $movie, $fileInfo, $filename, $localId);

        if ($result === 'Error') {
            return [
                'status' => 'Error',
                'error' => '',
            ];
        } else {
            return [
                'status' => 'Ok',
                'result' => $result,
            ];
        }
    }

    /**
     * @param string $id
     *
     * @throws \Exception
     *
     * @return array
     */
    public function lookupMovie($id)
    {
        $movie = $this->movieApi->getMovieInfo($id);

        return $movie->toArray();
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getGenres($category)
    {
        return $this->movieStoreDB->getGenres($category);
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getLists($category)
    {
        return $this->movieStoreDB->getLists($category);
    }

    /**
     * @param string $category
     *
     * @return mixed
     */
    public function getCollections($category)
    {
        return $this->movieStoreDB->getCollections($category);
    }

    /**
     * @return array
     */
    public function updateData()
    {
        $protocol = "";

        foreach ($this->getCategoryNames() as $category) {
            $protocol .= $this->maintenance($category);
        }

        return ["result" => "Ok", "protocol" => $protocol];
    }

    /**
     * @param string $category
     *
     * @return string
     */
    public function maintenance($category)
    {
        $path = $this->getCategoryPath($category);

        $protocol = "<h1>Maintenance ".$category."<small>".$path."</small></h1>";
        $protocol .= "<h2>Duplicate movie files</h2>";

        $pp = $this->getPicturePath($category);
        $res = $this->checkDuplicateFiles($path);

        if (0 === count($res)) {
            $protocol .= "none";
        }

        foreach ($res as $movie) {
            $protocol .= $movie."<br>";
        }

        $protocol .= "<h2>Duplicate movie entries</h2>";
        $res = $this->movieStoreDB->checkDuplicates($category);

        if (0 === count($res)) {
            $protocol .= "none";
        }

        foreach ($res as $movie) {
            $protocol .= $movie."<br>";
        }

        $missing = $this->movieStoreDB->checkExisting($category, $path);
        $protocol .= "<h2>Missing movie entries (new movies)</h2>";

        if (0 === count($missing)) {
            $protocol .= "none";
        }

        foreach ($missing as $filename) {
            $title = $this->getMovieTitle($filename);
            $protocol .= $title." (File: ".$filename.")<br>";
            $protocol .= $this->searchMovie($category, $title, $filename);
            $protocol .= "<br>";
        }

        $protocol .= "<h2>Obsolete movie entries</h2>";
        $protocol .= $this->movieStoreDB->checkRemovedFiles($category, $path);

        $protocol .= "<h2>Missing collection entries</h2>";
        $res = $this->movieStoreDB->checkCollections($category);

        if (0 === count($res['missing'])) {
            $protocol .= "none";
        }

        foreach ($res["missing"] as $miss) {
            $col = $this->updateCollectionFromScraper($category, $miss);
            $protocol .= $col;
            $protocol .= "<br>";
        }

        $protocol .= "<h2>Obsolete collection entries</h2>";

        if (0 === count($res['obsolete'])) {
            $protocol .= "none";
        }

        foreach ($res["obsolete"] as $obs) {
            $protocol .= $obs;
            $protocol .= $this->removeObsoleteCollection($obs);
            $protocol .= "<br>";
        }

        $protocol .= "<h2>Fetching missing Movie Pics</h2>";
        $res = $this->movieStoreDB->getMissingPics($category, $pp);

        if (0 === count($res['missing'])) {
            $protocol .= "none";
        }

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
    public function getCategoryNames()
    {
        if (empty($this->categoryNames)) {
            $folders = $this->getFolders($this->path, [self::PICTURES_FOLDER]);
            $categories = [MovieService::DEFAULT_CATEGORY];
            $this->useDefault = true;

            if (count($folders) > 0) {
                $this->useDefault = false;
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
     * @param string $category
     * @param array  $movies
     *
     * @return array
     */
    private function addPosterEntry($category, $movies)
    {
        $alias = $this->getCategoryAlias($category);

        foreach ($movies as &$movie) {//call by reference
            $movie["poster"] = $alias.self::PICTURES_FOLDER."/".$movie["movie_db_id"]."_333x500.jpg";
            $movie["poster_big"] = $alias.self::PICTURES_FOLDER."/".$movie["movie_db_id"]."_big.jpg";
            $movie["filename"] = $alias.$movie["filename"];
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
        $this->getCategoryNames(); //TODO wird nur aufgerufen, um useDefault zu setzen
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
     * @param string $filename
     *
     * @return string
     */
    private function getFilePath($category, $filename)
    {
        return $this->getCategoryPath($category).$filename;
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
        return sprintf('%s%s/', $this->getCategoryPath($category), self::PICTURES_FOLDER);
    }

    /**
     * @param string              $category
     * @param MovieModelInterface $movie
     * @param MediaFileInfoModel  $fileInfoModel
     * @param string              $filename
     * @param string              $localId
     *
     * @throws \Exception
     *
     * @return string
     */
    private function updateMovie($category, MovieModelInterface $movie, MediaFileInfoModel $fileInfoModel, $filename, $localId = "")
    {
        if ($movie !== null) {
            $picturePath = $this->getPicturePath($category);
            $this->downloadMoviePic($picturePath, $movie->getId());
            $this->resizeMoviePics($picturePath);

            $this
                ->movieStoreDB
                ->updateMovie($category, $movie, $fileInfoModel, $this->getCategoryPath($category), $filename, $localId);

            return "OK:".$movie->getTitle();
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
            $movie = $this->movieApi->searchMovie($title);
            $fileInfo = new MediaFileInfoModel($this->getFilePath($category, $filename));

            return $this->updateMovie($category, $movie, $fileInfo, $filename);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $picturePath
     * @param int    $id
     *
     * @return string
     */
    private function downloadMoviePic($picturePath, $id)
    {
        try {
            $poster = $this->movieApi->getMoviePoster($id);

            $file = $picturePath.$id.'_big.jpg';

            if (file_exists($file)) {
                unlink($file);
            }

            $fp = fopen($file, 'x');
            fwrite($fp, $poster);
            fclose($fp);

            return "OK";
        } catch (\Exception $e) {
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
        $images = $this->globRecursive($picsDir."*big.jpg");
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
            $this->resizeImage($dest, $target, 333, 500);
        }

        return $protocol;
    }

    /**
     * Remove obsolete pictures.
     *
     * @param array  $movieDBIDS
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
     * Returns the titles of all duplicate movies.
     *
     * @param string $path
     *
     * @return array
     */
    private function checkDuplicateFiles($path)
    {
        $files = glob($path."*.avi");
        $titles = [];

        foreach ($files as $file) {
            $titles[] = $this->getMovieTitle($file);
        }

        $counts = array_count_values($titles);
        arsort($counts);
        $result = [];

        foreach ($counts as $title => $count) {
            if ($count > 1) {
                $result[] = $title;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Returns the movie title from a file name by striping all unnecessary info.
     *
     * @param string $file
     *
     * @return string
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
            $collectionModel = $this->movieApi->getCollectionInfo($collectionId);
            $this->movieStoreDB->updateCollectionById($category, $collectionModel, $collectionId);
            $collectionStr = "[Id: ".$collectionModel->getId();
            $collectionStr .= ", Name: ".$collectionModel->getName();
            $collectionStr .= ", Overview: ".$collectionModel->getOverview()."]";
        } catch (\Exception $e) {
            $collectionStr = $e->getMessage();
        }

        return $collectionStr;
    }

    /**
     * @param int $collectionId
     */
    private function removeObsoleteCollection($collectionId)
    {
        $this->movieStoreDB->removeObsoleteCollection($collectionId);
    }
}
