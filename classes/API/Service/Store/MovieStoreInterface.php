<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Model\CollectionInterface;
use TinyMediaCenter\API\Model\MediaFileInfo;
use TinyMediaCenter\API\Model\MovieInterface;
use TinyMediaCenter\API\Model\Store\Movie;

/**
 * Interface MovieStoreInterface
 */
interface MovieStoreInterface extends StoreInterface
{
    /**
     * Get movies matching the given criteria.
     *
     * @param string $category
     * @param string $sort
     * @param string $order
     * @param string $filter
     * @param array  $genres
     * @param int    $cnt
     * @param int    $offset
     *
     * @return Movie[]
     */
    public function getMovies($category, $sort, $order, $filter, array $genres, $cnt, $offset);

    /**
     * Get movie from a collection.
     *
     * @param string $category
     * @param int    $collectionId
     * @param int    $cnt
     * @param int    $offset
     *
     * @return Movie[]
     */
    public function getMoviesForCollection($category, $collectionId, $cnt, $offset);

    /**
     * Get a movie.
     *
     * @param string $category
     * @param int    $id
     *
     * @return Movie
     */
    public function getMovieById($category, $id);

    /**
     * Update a movie.
     *
     * @param string         $category
     * @param MovieInterface $movie
     * @param MediaFileInfo  $mediaFileInfoModel
     * @param string         $dir
     * @param string         $filename
     * @param string         $id
     *
     * @throws \Exception
     *
     * @return string
     */
    public function updateMovie($category, MovieInterface $movie, MediaFileInfo $mediaFileInfoModel, $dir, $filename, $id = "");

    /**
     * @param string              $category
     * @param CollectionInterface $collectionModel
     * @param int                 $id
     */
    public function updateCollection($category, $collectionModel, $id);

    /**
     * @param int $collectionId
     */
    public function removeCollection($collectionId);

    /**
     * @param string $category
     *
     * @return array
     */
    public function checkDuplicates($category);

    /**
     * @param string      $category
     * @param string|null $filter
     *
     * @return array
     */
    public function getGenres($category, $filter = null);

    /**
     * @param string $category
     *
     * @return array
     */
    public function getCollections($category);
}
