<?php

namespace TinyMediaCenter\API\Model\Store\Series;

/**
 * Class Episode
 */
class Episode
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var int
     */
    private $seasonNumber;

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
     * @param int    $seasonNumber
     * @param string $title
     * @param string $description
     */
    public function __construct(int $number, int $seasonNumber, string $title, string $description)
    {
        $this->number = $number;
        $this->seasonNumber = $seasonNumber;
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
     * @return int
     */
    public function getSeasonNumber(): int
    {
        return $this->seasonNumber;
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
