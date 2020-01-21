<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Model\Resource\Movie\CollectionModel;
use TinyMediaCenter\API\Model\Resource\Movie\CollectionModelInterface;
use TinyMediaCenter\API\Model\Resource\Movie\GenresModel;
use TinyMediaCenter\API\Model\Resource\Movie\MaintenanceModel;
use TinyMediaCenter\API\Model\Resource\Movie\MovieModel;
use TinyMediaCenter\API\Model\Resource\Movie\MovieModelInterface;
use TinyMediaCenter\API\Model\MediaFileInfoModel;
use TinyMediaCenter\API\Service\Store\MovieStoreDB;

/**
 * Class MovieService
 *
 * Throughout this class $localId refers to an entry in the local database, whereas $remoteId refers to an
 * entry in the external movie database.
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
     * @return MovieModel[]
     */
    public function getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset)
    {
        //TODO alias is passed to construct paths for pictures and movie files, this does not belong in the store
        $alias = $this->getCategoryAlias($category);

        return $this->movieStoreDB->getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset, $alias);
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
     * @return MovieModel[]
     */
    public function getMoviesForCollection($category, $collectionID, $cnt, $offset)
    {
        //TODO alias is passed to construct paths for pictures and movie files, this does not belong in the store
        $alias = $this->getCategoryAlias($category);

        return $this->movieStoreDB->getMoviesForCollection($category, $collectionID, $cnt, $offset, $alias);
    }

    /**
     * Get details for the given id (database id).
     *
     * @param string $category
     * @param int    $localId
     *
     * @return MovieModel
     */
    public function getMovieDetails($category, $localId)
    {
        //TODO alias is passed to construct paths for pictures and movie files, this does not belong in the store
        $alias = $this->getCategoryAlias($category);

        return $this->movieStoreDB->getMovieById($category, $localId, $alias);
    }

    /**
     * Get movie details from the external movie database api.
     *
     * @param string $remoteId
     *
     * @throws \Exception
     *
     * @return MovieModelInterface
     */
    public function lookupMovie($remoteId)
    {
        return $this->movieApi->getMovieInfo($remoteId);
    }

    /**
     * @param string $category
     * @param string $localId
     * @param string $remoteId
     * @param string $filename
     *
     * @throws \Exception
     *
     * @return MovieModel
     */
    public function updateMovieFromApi($category, $localId, $remoteId, $filename)
    {
        $movie = $this->movieApi->getMovieInfo($remoteId);
        $fileInfo = new MediaFileInfoModel($this->getFilePath($category, $filename));

        return $this->updateMovie($category, $movie, $fileInfo, $filename, $localId);
//
//        if ($result === 'Error') {
//            return [
//                'status' => 'Error',
//                'error' => '',
//            ];
//        } else {
//            return [
//                'status' => 'Ok',
//                'result' => $result,
//            ];
//        }
    }

    /**
     * Get genres for the given category, optionally filtered.
     *
     * @param string      $category
     * @param string|null $filter
     *
     * @return GenresModel[]
     */
    public function getGenres($category, $filter = null)
    {
        $models = [];

        foreach ($this->movieStoreDB->getGenres($category, $filter) as $genre) {
            $models[] = new GenresModel($genre);
        }

        return $models;
    }

    /**
     * Get collections for the given category.
     *
     * @param string $category
     *
     * @return CollectionModelInterface[]
     */
    public function getCollections($category)
    {
        $models = [];

        foreach ($this->movieStoreDB->getCollections($category) as $collection) {
            $models[] = new CollectionModel($collection['id'], $collection['name'], $collection['overview'], []);
        }

        return $models;
    }

    /**
     * @return array
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
     * @param string $category
     *
     * @return MaintenanceModel
     */
    private function maintenance($category)
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

        return new MaintenanceModel($category, $steps);
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
     * TODO sort all this categories, aliases and paths
     *
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
     * @return MovieModel
     */
    private function updateMovie($category, MovieModelInterface $movie, MediaFileInfoModel $fileInfoModel, $filename, $localId = "")
    {
        $picturePath = $this->getPicturePath($category);
        $this->downloadMoviePicture($picturePath, $movie->getId());
        $this->createMovieThumbnails($picturePath);

        $localId = $this
            ->movieStoreDB
            ->updateMovie($category, $movie, $fileInfoModel, $this->getCategoryPath($category), $filename, $localId);

        return $this->getMovieDetails($category, $localId);
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

            //TODO change to more sensible return
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
                'success' => substr($result, 0, 2) === 'OK', //TODO Do not return "Ok:Movie Title" from searchMovie
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
