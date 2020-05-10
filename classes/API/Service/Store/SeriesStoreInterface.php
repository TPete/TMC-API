<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Model\SeriesInterface;
use TinyMediaCenter\API\Model\Store\Series;

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
     * @return Series[]
     */
    public function getSeries(string $category): array;

    /**
     * Get the series details.
     *
     * @param string $category
     * @param string $folder
     *
     * @throws NotFoundException
     *
     * @return Series
     */
    public function getSeriesDetails(string $category, string $folder): Series;

    /**
     * Get the number of seasons of a series.
     *
     * @param string $category
     * @param string $folder
     *
     * @return int
     */
    public function getSeasonCount(string $category, string $folder): int;

    /**
     * Get episodes for a series.
     *
     * @param string   $category
     * @param string   $folder
     * @param int|null $season
     *
     * @throws NotFoundException
     *
     * @return Series\Episode[]
     */
    public function getEpisodes(string $category, string $folder, ?int $season = null): array;

    /**
     * Update the details of a series.
     *
     * Returns the old web database id.
     *
     * @param string $category
     * @param string $folder
     * @param string $title
     * @param int    $mediaApiId
     * @param string $lang
     *
     * @return int
     */
    public function updateDetails(string $category, string $folder, string $title, int $mediaApiId, string $lang): int;

    /**
     * Create movie record, if not already existing.
     *
     * @param string $category
     * @param string $folder
     * @param string $title
     *
     * @return string|null
     */
    public function createIfMissing(string $category, string $folder, string $title): ?string;

    /**
     * Remove all series, which are not listed in the $folders parameter.
     *
     * Returns all removed series folders.
     *
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    public function removeIfObsolete(string $category, array $folders): array;

    /**
     * Update the episodes of a show.
     *
     * @param int             $showId
     * @param SeriesInterface $series
     */
    public function updateEpisodes(int $showId, SeriesInterface $series);
}
