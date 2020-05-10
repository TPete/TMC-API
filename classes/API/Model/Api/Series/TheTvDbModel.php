<?php

namespace TinyMediaCenter\API\Model\Api\Series;

use TinyMediaCenter\API\Model\Series\SeasonInterface;
use TinyMediaCenter\API\Model\SeriesInterface;

/**
 * Class TheTvDbModel
 */
class TheTvDbModel implements SeriesInterface
{
    /**
     * @var SeasonInterface[]
     */
    private $seasons;

    /**
     * TheTvDbModel constructor.
     *
     * @param SeasonInterface[] $seasons
     */
    public function __construct(?array $seasons = [])
    {
        $this->seasons = $seasons;
    }

    /**
     * @return SeasonInterface[]
     */
    public function getSeasons(): array
    {
        return $this->seasons;
    }

    /**
     * @param SeasonInterface $season
     */
    public function addSeason(SeasonInterface $season)
    {
        $this->seasons[$season->getNumber()] = $season;
    }

    /**
     * @param int $number
     *
     * @return SeasonInterface|null
     */
    public function getSeason(int $number): ?SeasonInterface
    {
        return $this->seasons[$number] ?? null;
    }

    public function getTitle(): string
    {
        // TODO: Implement getTitle() method.
    }

    public function getThumbnail(): string
    {
        // TODO: Implement getThumbnail() method.
    }

    public function getBackground(): string
    {
        // TODO: Implement getBackground() method.
    }

    public function getLanguage(): string
    {
        // TODO: Implement getLanguage() method.
    }

    public function getMediaApiId(): ?string
    {
        // TODO: Implement getMediaApiId() method.
    }
}
