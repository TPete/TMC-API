<?php

namespace TinyMediaCenter\API\Model\Series;

use TinyMediaCenter\API\Model\Series\Season\EpisodeInterface;

/**
 * A season of a series;
 */
interface SeasonInterface
{
    /**
     * @return int
     */
    public function getNumber(): int;

    /**
     * @return EpisodeInterface[]
     */
    public function getEpisodes(): array;

    /**
     * @param EpisodeInterface $episode
     */
    public function addEpisode(EpisodeInterface $episode);
}
