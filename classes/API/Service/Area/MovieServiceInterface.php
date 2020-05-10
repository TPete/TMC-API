<?php

namespace TinyMediaCenter\API\Service\Area;

use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Collection;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Genres;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Maintenance;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movie;
use TinyMediaCenter\API\Model\Resource\Area;

/**
 * Movies service.
 */
interface MovieServiceInterface extends AreaServiceInterface
{
    /**
     * Get meta info about the series area.
     *
     * @return Area
     */
    public function getMetaInfo();

    /**
     * Get movies matching the given criteria.
     *
     * @param string $category
     * @param string $sort
     * @param string $order
     * @param string $filter
     * @param array  $genres
     * @param int    $count
     * @param int    $offset
     *
     * @return Movie[]
     */
    public function getByCategory($category, $sort, $order, $filter = '', array $genres = [], $count = 0, $offset = 0);

    /**
     * Get all movies of a category and collection.
     * Results are ordered by release date.
     *
     * @param string $category
     * @param int    $collection
     * @param int    $count
     * @param int    $offset
     *
     * @return Movie[]
     */
    public function getByCategoryAndCollection($category, $collection, $count = 0, $offset = 0);

    /**
     * Get details of a movies from a category.
     *
     * @param string $category
     * @param int    $id
     *
     * @throws \Exception
     *
     * @return Movie
     */
    public function get($category, $id);

    /**
     * @param string $category
     * @param int    $id
     * @param int    $remoteId
     *
     * @throws \Exception
     *
     * @return Movie
     */
    public function update($category, $id, $remoteId);

    /**
     * Get genres for the given category, optionally filtered.
     *
     * @param string      $category
     * @param string|null $filter
     *
     * @return Genres
     */
    public function getGenres($category, $filter = null);

    /**
     * Get collections for the given category.
     *
     * @param string $category
     *
     * @return Collection[]
     */
    public function getCollections($category);

    /**
     * Get movie details from the external movie database api.
     *
     * @param string $remoteId
     *
     * @throws \Exception
     *
     * @return Movie
     */
    public function lookupMovie($remoteId);

    /**
     * Update data.
     *
     * @throws \Exception
     *
     * @return Maintenance[]
     */
    public function updateData();

    /**
     * @param string $category
     * @param int    $id
     * @param string $type
     *
     * @return string
     */
    public function getImage($category, $id, $type);

    /**
     * @param string $category
     * @param int    $id
     *
     * @return string
     */
    public function getMovieFile($category, $id);
}
