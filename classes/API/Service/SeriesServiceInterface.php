<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Model\Resource\Area\Category\SeriesModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\SeasonModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season\EpisodeModel;
use TinyMediaCenter\API\Model\Resource\Area\CategoryModel;
use TinyMediaCenter\API\Model\Resource\AreaModel;

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
interface SeriesServiceInterface
{
    /**
     * Get meta info about the series area.
     *
     * @return AreaModel
     */
    public function getMetaInfo();

    /**
     * Get all categories of the series area.
     *
     * @return CategoryModel[]
     */
    public function getCategories();

    /**
     * Get all series of a category.
     *
     * @param string $category
     *
     * @throws \Exception
     *
     * @return SeriesModel[]
     */
    public function getByCategory($category);

    /**
     * Get details of a series from a category.
     *
     * @param string $category
     * @param string $series
     *
     * @throws \Exception
     *
     * @return SeriesModel
     */
    public function get($category, $series);

    /**
     * Update a series.
     *
     * @param string $category
     * @param string $series
     * @param string $title
     * @param string $tvDbId
     * @param string $language
     *
     * @throws \Exception
     *
     * @return SeriesModel
     */
    public function update($category, $series, $title, $tvDbId, $language);

    /**
     * Get the seasons of a series.
     *
     * @param string $category
     * @param string $series
     *
     * @throws \Exception
     *
     * @return SeasonModel[]
     */
    public function getSeasons($category, $series);

    /**
     * Get the season details of a series.
     *
     * @param string $category
     * @param string $series
     * @param string $season
     *
     * @throws \Exception
     *
     * @return SeasonModel
     */
    public function getSeason($category, $series, $season);

    /**
     * Get the episodes of a season.
     *
     * @param string $category
     * @param string $series
     * @param string $season
     *
     * @throws \Exception
     *
     * @return EpisodeModel[]
     */
    public function getEpisodes($category, $series, $season);

    /**
     * Get the details of an episode.
     *
     * @param string $category
     * @param string $series
     * @param string $season
     * @param string $episode
     *
     * @throws \Exception
     *
     * @return EpisodeModel
     */
    public function getEpisode($category, $series, $season, $episode);

    /**
     * Update data.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function updateData();
}
