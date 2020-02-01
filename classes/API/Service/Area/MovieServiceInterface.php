<?php

namespace TinyMediaCenter\API\Service\Area;

use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\CollectionModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\GenresModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\MaintenanceModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\MovieModel;
use TinyMediaCenter\API\Model\Resource\AreaModel;

/**
 * Movies service.
 */
interface MovieServiceInterface extends AreaServiceInterface
{
    /**
     * Get meta info about the series area.
     *
     * @return AreaModel
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
     * @return MovieModel[]
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
     * @return MovieModel[]
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
     * @return MovieModel
     */
    public function get($category, $id);

    /**
     * @param string $category
     * @param int    $id
     * @param int    $remoteId
     * @param string $filename
     *
     * @throws \Exception
     *
     * @return MovieModel
     */
    public function update($category, $id, $remoteId, $filename);

    /**
     * Get genres for the given category, optionally filtered.
     *
     * @param string      $category
     * @param string|null $filter
     *
     * @return GenresModel
     */
    public function getGenres($category, $filter = null);

    /**
     * Get collections for the given category.
     *
     * @param string $category
     *
     * @return CollectionModel[]
     */
    public function getCollections($category);

    /**
     * Get movie details from the external movie database api.
     *
     * @param string $remoteId
     *
     * @throws \Exception
     *
     * @return MovieModel
     */
    public function lookupMovie($remoteId);

    /**
     * Update data.
     *
     * @throws \Exception
     *
     * @return MaintenanceModel[]
     */
    public function updateData();
}
