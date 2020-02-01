<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Model\Store\SeriesModel;

/**
 * Store for series.
 *
 * TODO should be more consistent: use folder to identify new and id for existing entries.
 */
interface SeriesStoreInterface extends StoreInterface
{
    /**
     * Gets all series of the given category.
     *
     * @param string $category
     *
     * @throws NotFoundException
     *
     * @return SeriesModel[]
     */
    public function getSeries($category);

    /**
     * Get the series details.
     *
     * @param string $category
     * @param string $folder
     *
     * @throws NotFoundException
     *
     * @return SeriesModel
     */
    public function getSeriesDetails($category, $folder);

    /**
     * Get episodes for a series.
     *
     * @param string $category
     * @param string $folder
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public function getEpisodes($category, $folder);

    /**
     * Update the details of a series.
     *
     * Returns the old web database id.
     *
     * @param string $category
     * @param string $folder
     * @param string $title
     * @param int    $tvdbId
     * @param string $lang
     *
     * @return int
     */
    public function updateDetails($category, $folder, $title, $tvdbId, $lang);

    /**
     * Create movie record, if not already existing.
     *
     * @param string $category
     * @param string $folder
     * @param string $title
     *
     * @return string|null
     */
    public function createIfMissing($category, $folder, $title);

    /**
     * Remove all series, which are not listed in the $folders parameter.
     *
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    public function removeIfObsolete($category, $folders);

    /**
     * Update the episodes of a show.
     *
     * @param int   $showId
     * @param array $seasons
     */
    public function updateEpisodes($showId, $seasons);
}
