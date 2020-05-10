<?php

namespace TinyMediaCenter\API\Model\Series\Season;

/**
 * An episode of a season of a series.
 */
interface EpisodeInterface
{
    /**
     * @return int
     */
    public function getNumber(): int;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getDescription(): string;
}
