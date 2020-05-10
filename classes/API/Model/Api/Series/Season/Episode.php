<?php

namespace TinyMediaCenter\API\Model\Api\Series\Season;

use TinyMediaCenter\API\Model\Series\Season\EpisodeInterface;

/**
 * Class Episode
 */
class Episode implements EpisodeInterface
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * Episode constructor.
     *
     * @param int    $number
     * @param string $title
     * @param string $description
     */
    public function __construct(int $number, string $title, string $description)
    {
        $this->number = $number;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
