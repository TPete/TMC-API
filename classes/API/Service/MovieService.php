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

    const THUMBNAIL_WIDTH = 333;
    const THUMBNAIL_HEIGHT = 500;
    const THUMBNAIL_SUFFIX = '_333x500';
    const FULL_IMAGE_SUFFIX = '_big';

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
        return $this->getCategoryNames();
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
     * @return array
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
        $protocol = [];

        foreach ($this->getCategoryNames() as $category) {
            $protocol[] = [
                'type' => 'movie_maintenance',
                'attributes' => $this->maintenance($category),
            ];
        }

        return $protocol;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function maintenance($category)
    {
        $path = $this->getCategoryPath($category);
        $picturePath = $this->getPicturePath($category);

        $steps = [];
        $protocol = $this->checkDuplicateFiles($path);
        $steps[] = [
            'description' => 'Possibly duplicate movie files',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->movieStoreDB->checkDuplicates($category);
        $steps[] = [
            'description' => 'Duplicate movie entries',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->addMissingMovieEntries($category, $path);
        $steps[] = [
            'description' => 'Missing movie entries (new movies)',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->movieStoreDB->checkRemovedFiles($category, $path);
        $steps[] = [
            'description' => 'Obsolete movie entries',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->addMissingCollectionEntries($category);
        $steps[] = [
            'description' => 'Missing collection entries',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->removeObsoleteCollectionEntries($category);
        $steps[] = [
            'description' => 'Obsolete collection entries',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->addMissingMoviePictures($category, $picturePath);
        $steps[] = [
            'description' => 'Missing movie pictures',
            'protocol' => $protocol,
            'success' => $this->isMaintenanceSuccessful($protocol),
            'errors' => $this->getMaintenanceErrors($protocol),
        ];
        $protocol = $this->removeObsoleteMoviePictures($category, $picturePath);
        $steps[] = [
            'description' => 'Obsolete movie pictures',
            'protocol' => $protocol,
            'success' => true,
        ];
        $protocol = $this->createMovieThumbnails($picturePath);
        $steps[] = [
            'description' => 'Creating movie thumbnails',
            'protocol' => $protocol,
            'success' => true,
        ];

        return $maintenance = [
            'category' => $category,
            'steps' => $steps,
        ];
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
            $this->downloadMoviePicture($picturePath, $movie->getId());
            $this->createMovieThumbnails($picturePath);

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
     * @param string $pictureDir
     * @param int    $id
     *
     * @return string
     */
    private function downloadMoviePicture($pictureDir, $id)
    {
        try {
            $poster = $this->movieApi->getMoviePoster($id);
            $file = $this->getFullImageFilename($pictureDir, $id);

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
     * @param string $pictureDir
     *
     * @return array
     */
    private function getAllPictures($pictureDir)
    {
        return glob(sprintf('%s*%s.jpg', $pictureDir, self::FULL_IMAGE_SUFFIX));
    }

    /**
     * @param string $pictureDir
     * @param string $id
     *
     * @return string
     */
    private function getFullImageFilename($pictureDir, $id)
    {
        return sprintf('%s%s%s.jpg', $pictureDir, $id, self::FULL_IMAGE_SUFFIX);
    }

    /**
     * @param string $pictureDir
     * @param string $id
     *
     * @return string
     */
    private function getThumbnailFilename($pictureDir, $id)
    {
        return sprintf('%s%s%s.jpg', $pictureDir, $id, self::THUMBNAIL_SUFFIX);
    }

    /**
     * @param string $file
     * @param string $pictureDir
     *
     * @return string
     */
    private function getIdFromPictureFilename($file, $pictureDir)
    {
        $id = substr($file, strlen($pictureDir));

        return substr($id, 0, strpos($id, "_"));
    }

    /**
     * @param string $pictureDir
     *
     * @return array
     */
    private function createMovieThumbnails($pictureDir)
    {
        $pictures = $this->getAllPictures($pictureDir);
        $protocol = [];

        foreach ($pictures as $picture) {
            $id = $this->getIdFromPictureFilename($picture, $pictureDir);
            $dest = $this->getFullImageFilename($pictureDir, $id);
            $target = $this->getThumbnailFilename($pictureDir, $id);

            if (!file_exists($target)) {
                $protocol[] = sprintf('%s -> %s', $dest, $target);
                $this->resizeImage($dest, $target, self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT);
            }
        }

        return $protocol;
    }

    /**
     * Remove obsolete pictures.
     *
     * @param array  $movieDBIDS
     * @param string $pictureDir
     *
     * @return array
     */
    private function removeObsoletePictures($movieDBIDS, $pictureDir)
    {
        $files = $this->getAllPictures($pictureDir);
        $protocol = [];

        foreach ($files as $file) {
            $id = $this->getIdFromPictureFilename($file, $pictureDir);

            if (!in_array($id, $movieDBIDS)) {
                $protocol[] = $id;
                unlink($file);
                $thumbnail = $this->getThumbnailFilename($pictureDir, $id);

                if (file_exists($thumbnail)) {
                    unlink($thumbnail);
                }
            }
        }

        return $protocol;
    }

    /**
     * @param string $category
     * @param string $pictureDir
     *
     * @return array
     */
    private function addMissingMoviePictures($category, $pictureDir)
    {
        $res = $this->movieStoreDB->getMissingPictures($category, $pictureDir);
        $protocol = [];

        foreach ($res["missing"] as $miss) {
            $result = $this->downloadMoviePicture($pictureDir, $miss["MOVIE_DB_ID"]);
            $protocol[] = [
                'object' => $miss["MOVIE_DB_ID"],
                'success' => $result === 'OK',
                'errors' => $result === 'OK' ? null : $result,
            ];
        }

        return $protocol;
    }

    /**
     * @param string $category
     * @param string $pictureDir
     *
     * @return array
     */
    private function removeObsoleteMoviePictures($category, $pictureDir)
    {
        $res = $this->movieStoreDB->getMissingPictures($category, $pictureDir);

        return $this->removeObsoletePictures($res["all"], $pictureDir);
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
     * @param string $category
     * @param string $path
     *
     * @return array
     */
    private function addMissingMovieEntries($category, $path)
    {
        $missing = $this->movieStoreDB->checkExisting($category, $path);
        $protocol = [];

        foreach ($missing as $filename) {
            $title = $this->getMovieTitle($filename);
            $result = $this->searchMovie($category, $title, $filename);
            $protocol = [
                'object' => $filename,
                'title' => $title,
                'success' => substr($result, 0, 2) === 'OK', //TODO
                'result' => $result,
            ];
        }

        return $protocol;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    private function addMissingCollectionEntries($category)
    {
        $res = $this->movieStoreDB->checkCollections($category);
        $protocol = [];

        foreach ($res["missing"] as $miss) {
            $protocol[] = $this->updateCollectionFromScraper($category, $miss);
        }

        return $protocol;
    }

    /**
     * @param string $category
     *
     * @return array|string
     */
    private function removeObsoleteCollectionEntries($category)
    {
        $res = $this->movieStoreDB->checkCollections($category);
        $protocol = [];

        foreach ($res["obsolete"] as $obs) {
            $protocol[] = $obs;
            $this->movieStoreDB->removeObsoleteCollection($obs);
        }

        return $protocol;
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
}
