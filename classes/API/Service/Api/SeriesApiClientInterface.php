<?php

namespace TinyMediaCenter\API\Service\Api;

use TinyMediaCenter\API\Exception\MediaApiClientException;
use TinyMediaCenter\API\Model\SeriesInterface;

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
     * @throws MediaApiClientException
     *
     * @return string
     */
    public function getSeriesId(string $name): string;

    /**
     * Returns the info for the series, if available.
     *
     * @param string $id
     * @param string $orderingScheme
     * @param string $lang
     *
     * @throws MediaApiClientException
     *
     * @return SeriesInterface
     */
    public function getSeriesInfoById(string $id, string $orderingScheme, ?string $lang = 'de'): SeriesInterface;

    /**
     * Downloads the background image for the series.
     *
     * @param string $seriesId
     *
     * @return string
     */
    public function downloadBackgroundImage(string $seriesId): string;
}
