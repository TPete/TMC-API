<?php

namespace TinyMediaCenter\API\Service\Area;

use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Maintenance;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season\Episode;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series;
use TinyMediaCenter\API\Model\Resource\Area;

/**
 * Series service.
 *
 * Hierarchy of the series area:
 *
 * - categories
 * - series
 * - seasons
 * - episodes
 */
interface SeriesServiceInterface extends AreaServiceInterface
{
    /**
     * Get meta info about the series area.
     *
     * @return Area
     */
    public function getMetaInfo(): Area;

    /**
     * Get all series of a category.
     *
     * @param string $category
     *
     * @throws \Exception
     *
     * @return Series[]
     */
    public function getByCategory(string $category): array;

    /**
     * Get details of a series from a category.
     *
     * @param string $category
     * @param string $slug
     *
     * @throws \Exception
     *
     * @return Series
     */
    public function get(string $category, string $slug): Series;

    /**
     * Update a series.
     *
     * @param string $category
     * @param string $slug
     * @param string $title
     * @param string $mediaApiId
     * @param string $language
     *
     * @throws \Exception
     *
     * @return Series
     */
    public function update(string $category, string $slug, string $title, string $mediaApiId, string $language): Series;

    /**
     * Get the seasons of a series.
     *
     * @param string $category
     * @param string $slug
     *
     * @throws \Exception
     *
     * @return Season[]
     */
    public function getSeasons(string $category, string $slug): array;

    /**
     * Get the season details of a series.
     *
     * @param string $category
     * @param string $slug
     * @param int    $season
     *
     * @throws \Exception
     *
     * @return Season
     */
    public function getSeason(string $category, string $slug, int $season): Season;

    /**
     * Get the episodes of a season.
     *
     * @param string $category
     * @param string $slug
     * @param int    $season
     *
     * @throws \Exception
     *
     * @return Episode[]
     */
    public function getEpisodes(string $category, string $slug, int $season): array;

    /**
     * Get the details of an episode.
     *
     * @param string $category
     * @param string $slug
     * @param int    $season
     * @param int    $episode
     *
     * @throws \Exception
     *
     * @return Episode
     */
    public function getEpisode(string $category, string $slug, int $season, int $episode): Episode;

    /**
     * Get the details of an episode.
     *
     * @param string $category
     * @param string $slug
     * @param int    $season
     * @param int    $episode
     *
     * @throws \Exception
     *
     * @return string|null
     */
    public function getEpisodeFile(string $category, string $slug, int $season, int $episode): ?string;

    /**
     * Update data.
     *
     * @throws \Exception
     *
     * @return Maintenance[]
     */
    public function updateData(): array;

    /**
     * Get series image.
     *
     * @param string $category
     * @param string $slug
     * @param string $type
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getImage(string $category, string $slug, string $type): string;
}
