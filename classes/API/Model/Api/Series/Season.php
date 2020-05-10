<?php

namespace TinyMediaCenter\API\Model\Api\Series;

use TinyMediaCenter\API\Model\Series\Season\EpisodeInterface;
use TinyMediaCenter\API\Model\Series\SeasonInterface;

/**
 * Class Season
 */
class Season implements SeasonInterface
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var EpisodeInterface[]
     */
    private $episodes;

    /**
     * Season constructor.
     *
     * @param int                $number
     * @param EpisodeInterface[] $episodes
     */
    public function __construct(int $number, ?array $episodes = [])
    {
        $this->number = $number;
        $this->episodes = $episodes;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return EpisodeInterface[]
     */
    public function getEpisodes(): array
    {
        return $this->episodes;
    }

    /**
     * @param EpisodeInterface $episode
     */
    public function addEpisode(EpisodeInterface $episode)
    {
        $this->episodes[$episode->getNumber()] = $episode;
    }
}
