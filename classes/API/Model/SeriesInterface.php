<?php

namespace TinyMediaCenter\API\Model;

use TinyMediaCenter\API\Model\Series\SeasonInterface;

/**
 * A TV series.
 */
interface SeriesInterface
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getThumbnail(): string;

    /**
     * @return string
     */
    public function getBackground(): string;

    /**
     * @return string
     */
    public function getLanguage(): string;

    /**
     * @return string|null
     */
    public function getMediaApiId(): ?string;

    /**
     * @return SeasonInterface[]
     */
    public function getSeasons(): array;

    /**
     * @param SeasonInterface $season
     */
    public function addSeason(SeasonInterface $season);

    /**
     * @param int $number
     *
     * @return SeasonInterface|null
     */
    public function getSeason(int $number): ?SeasonInterface;
}
