<?php

namespace TinyMediaCenter\API\Service\Area;

use Slim\Interfaces\RouterInterface;
use TinyMediaCenter\API\Model\MovieInterface;
use TinyMediaCenter\API\Model\MediaFileInfo;
use TinyMediaCenter\API\Model\Resource\Area\Category\SimpleMovie;
use TinyMediaCenter\API\Model\Resource\Area\Category;
use TinyMediaCenter\API\Model\Resource\Area;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Collection;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Genres;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Maintenance;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movie;
use TinyMediaCenter\API\Model\Store\Movie as StoreMovieModel;
use TinyMediaCenter\API\Service\Api\MoviesApiClientInterface;
use TinyMediaCenter\API\Service\Store\MovieStoreInterface;

/**
 * Class MovieService
 *
 * Throughout this class $localId refers to an entry in the local database, whereas $remoteId refers to an
 * entry in the external movie database.
 */
class MovieService extends AbstractAreaService implements MovieServiceInterface
{
    const DEFAULT_CATEGORY = "Filme";
    const PICTURES_FOLDER = 'pictures';

    const THUMBNAIL_WIDTH = 333;
    const THUMBNAIL_HEIGHT = 500;
    const THUMBNAIL_SUFFIX = '_333x500';
    const FULL_IMAGE_SUFFIX = '_big';

    const AREA = 'MOVIES_AREA';

    /**
     * @var MovieStoreInterface
     */
    private $movieStore;

    /**
     * @var MoviesApiClientInterface
     */
    private $moviesApiClient;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $path;

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
     * @param MovieStoreInterface      $movieStore
     * @param MoviesApiClientInterface $moviesApiClient
     * @param RouterInterface          $router
     * @param string                   $path
     */
    public function __construct(MovieStoreInterface $movieStore, MoviesApiClientInterface $moviesApiClient, RouterInterface $router, $path)
    {
        $this->movieStore = $movieStore;
        $this->moviesApiClient = $moviesApiClient;
        $this->router = $router;
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetaInfo()
    {
        return new Area(
            'movie',
            'Movies area overview'
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
     * {@inheritdoc}
     */
    public function getCategories()
    {
        $categories = [];

        foreach ($this->getCategoryNames() as $category) {
            $categories[] = new Category($category);
        }

        return $categories;
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
     * {@inheritDoc}
     */
    public function getByCategory($category, $sort, $order, $filter = '', array $genres = [], $count = 0, $offset = 0)
    {
        $models = $this->movieStore->getMovies($category, $sort, $order, $filter, $genres, $count, $offset);

        return $this->convertAllModels($models, $category);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCategoryAndCollection($category, $collectionID, $count = 0, $offset = 0)
    {
        $models = $this->movieStore->getMoviesForCollection($category, $collectionID, $count, $offset);

        return $this->convertAllModels($models, $category);
    }

    /**
     * {@inheritDoc}
     */
    public function get($category, $localId)
    {
        $model = $this->movieStore->getMovieById($category, $localId);

        return $this->convertModels($model, $category);
    }

    /**
     * {@inheritDoc}
     */
    public function lookupMovie($remoteId)
    {
        $apiModel = $this->moviesApiClient->getMovieInfo($remoteId);

        return new SimpleMovie(
            $apiModel->getId(),
            $apiModel->getTitle(),
            $apiModel->getOriginalTitle(),
            $apiModel->getOverview(),
            $apiModel->getReleaseDate(),
            $apiModel->getGenres(),
            $apiModel->getDirectors(),
            $apiModel->getActors(),
            $apiModel->getCountries(),
            $apiModel->getCollectionId()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function update($category, $localId, $remoteId)
    {
        $movie = $this->moviesApiClient->getMovieInfo($remoteId);
        $model = $this->movieStore->getMovieById($category, $localId);
        $fileInfo = new MediaFileInfo($this->getFilePath($category, $model->getFilename()));

        return $this->updateMovie($category, $movie, $fileInfo, $model->getFilename(), $localId);
    }

    /**
     * {@inheritDoc}
     */
    public function getGenres($category, $filter = null)
    {
        $models = [];

        foreach ($this->movieStore->getGenres($category, $filter) as $genre) {
            $models[] = new Genres($genre);
        }

        return $models;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollections($category)
    {
        $models = [];

        foreach ($this->movieStore->getCollections($category) as $collection) {
            $models[] = new Collection($collection['id'], $collection['name'], $collection['overview'], []);
        }

        return $models;
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
    public function getImage($category, $id, $type)
    {
        $movie = $this->movieStore->getMovieById($category, $id);
        $path = $this->getCategoryPath($category);

        if ($type === 'big') {
            $poster = sprintf('%s%s/%s_big.jpg', $path, self::PICTURES_FOLDER, $movie->getApiId());
        } else {
            $poster = sprintf('%s%s/%s_333x500.jpg', $path, self::PICTURES_FOLDER, $movie->getApiId());
        }

        return $poster;
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @return string
     */
    public function getMovieFile($category, $id)
    {
        $movie = $this->movieStore->getMovieById($category, $id);
        $path = $this->getCategoryPath($category);

        return $path.$movie->getFilename();
    }

    /**
     * @param string $category
     *
     * @return Maintenance
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
        $protocol = $this->movieStore->checkDuplicates($category);
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
        $protocol = $this->movieStore->checkRemovedFiles($category, $path);
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

        return new Maintenance($category, $steps);
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
    private function getPicturePath($category)
    {
        $path = sprintf('%s%s/', $this->getCategoryPath($category), self::PICTURES_FOLDER);

        if (!file_exists($path)) {
            mkdir($path);
        }

        return $path;
    }

    /**
     * @param string         $category
     * @param MovieInterface $movie
     * @param MediaFileInfo  $fileInfoModel
     * @param string         $filename
     * @param string         $localId
     *
     * @throws \Exception
     *
     * @return Movie
     */
    private function updateMovie($category, MovieInterface $movie, MediaFileInfo $fileInfoModel, $filename, $localId = ""): Movie
    {
        $picturePath = $this->getPicturePath($category);
        $this->downloadMoviePicture($picturePath, $movie->getId());
        $this->createMovieThumbnails($picturePath);

        $localId = $this
            ->movieStore
            ->updateMovie($category, $movie, $fileInfoModel, $this->getCategoryPath($category), $filename, $localId);

        return $this->get($category, $localId);
    }

    /**
     * @param string $category
     * @param string $title
     * @param string $filename
     *
     * @return string
     */
    private function searchMovie($category, $title, $filename): string
    {
        try {
            $movie = $this->moviesApiClient->searchMovie($title);

            if ($movie === null) {
                throw new \Exception(sprintf('"%s" not found', $title)); //TODO not really an exception
            }

            $fileInfo = new MediaFileInfo($this->getFilePath($category, $filename));
            $this->updateMovie($category, $movie, $fileInfo, $filename);

            return "Ok";
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
            $poster = $this->moviesApiClient->getMoviePoster($id);
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
        $res = $this->movieStore->getMissingPictures($category, $pictureDir);
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
        $res = $this->movieStore->getMissingPictures($category, $pictureDir);

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
        $missing = $this->movieStore->checkExisting($category, $path);
        $protocol = [];

        foreach ($missing as $filename) {
            try {
                $title = $this->getMovieTitle($filename);
                $result = $this->searchMovie($category, $title, $filename);
                $protocol[] = [
                    'object' => $filename,
                    'title' => $title,
                    'success' => substr($result, 0, 2) === 'OK', //TODO Do not return "Ok:Movie Title" from searchMovie
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $protocol[] = [
                    'object' => $filename,
                    'success' => false,
                    'result' => $e->getMessage(),
                ];
            }
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
        $res = $this->movieStore->checkCollections($category);
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
        $res = $this->movieStore->checkCollections($category);
        $protocol = [];

        foreach ($res["obsolete"] as $obs) {
            $protocol[] = $obs;
            $this->movieStore->removeCollection($obs);
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
            $collectionModel = $this->moviesApiClient->getCollectionInfo($collectionId);
            $this->movieStore->updateCollection($category, $collectionModel, $collectionId);
            $collectionStr = "[Id: ".$collectionModel->getId();
            $collectionStr .= ", Name: ".$collectionModel->getName();
            $collectionStr .= ", Overview: ".$collectionModel->getOverview()."]";
        } catch (\Exception $e) {
            $collectionStr = $e->getMessage();
        }

        return $collectionStr;
    }

    /**
     * @param StoreMovieModel $model
     * @param string          $category
     *
     * @return Movie
     */
    private function convertModels(StoreMovieModel $model, $category)
    {
        $poster = $this->router->pathFor('app.movies.movie_images', ['category' => $category, 'id' => $model->getId(), 'type' => 'thumbnail']);
        $posterBig = $this->router->pathFor('app.movies.movie_images', ['category' => $category, 'id' => $model->getId(), 'type' => 'big']);
        $filename = $this->router->pathFor('app.movies.movie_file', ['category' => $category, 'id' => $model->getId()]);

        return new Movie(
            $model->getId(),
            $model->getTitle(),
            $model->getOriginalTitle(),
            $model->getOverview(),
            $model->getReleaseDate(),
            $model->getGenres(),
            $model->getDirectors(),
            $model->getActors(),
            $model->getCountries(),
            $model->getApiId(),
            $filename,
            $poster,
            $posterBig,
            $model->getInfo(),
            $model->getCollectionId(),
            $model->getCollectionName()
        );
    }

    /**
     * @param StoreMovieModel[] $models
     * @param string            $category
     *
     * @return array
     */
    private function convertAllModels(array $models, $category)
    {
        $result = [];

        foreach ($models as $model) {
            $result[] = $this->convertModels($model, $category);
        }

        return $result;
    }
}