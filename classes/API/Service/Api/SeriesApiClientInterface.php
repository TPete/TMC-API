<?php

namespace TinyMediaCenter\API\Service\Api;

use TinyMediaCenter\API\Exception\ScrapeException;

/**
 * Interface SeriesApiInterface
 */
interface SeriesApiClientInterface
{
    /**
     * Returns the id for the series, if available.
     *
     * @param string $name
     *
     * @throws ScrapeException
     *
     * @return string
     */
    public function getSeriesId($name);

    /**
     * Returns the info for the series, if available.
     *
     * @param int    $id
     * @param string $orderingScheme
     * @param string $lang
     *
     * @throws ScrapeException
     *
     * @return array
     */
    public function getSeriesInfoById($id, $orderingScheme, $lang = 'de');

    /**
     * Downloads the background image for the series.
     *
     * @param string $seriesId
     * @param string $path
     */
    public function downloadBackgroundImage($seriesId, $path);
}
