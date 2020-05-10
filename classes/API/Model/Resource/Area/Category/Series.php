<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category;

use TinyMediaCenter\API\Model\AbstractResource;
use TinyMediaCenter\API\Model\Series\SeasonInterface;
use TinyMediaCenter\API\Model\SeriesInterface;

/**
 * A tv series.
 */
class Series extends AbstractResource implements SeriesInterface
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'series';

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $thumbnail;

    /**
     * @var string
     */
    private $background;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $tvdbId;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var SeasonInterface[]
     */
    private $seasons;

    /**
     * Series constructor.
     *
     * @param string      $id
     * @param string      $title
     * @param string      $thumbnail
     * @param string      $background
     * @param string      $language
     * @param string      $folder
     * @param string|null $tvdbId
     */
    public function __construct(string $id, string $title, string $thumbnail, string $background, string $language, string $folder, ?string $tvdbId = null)
    {
        parent::__construct($id);
        $this->title = $title;
        $this->thumbnail = $thumbnail;
        $this->background = $background;
        $this->language = $language;
        $this->folder = $folder;
        $this->tvdbId = $tvdbId;
        $this->seasons = [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * {@inheritDoc}
     */
    public function getBackground(): string
    {
        return $this->background;
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * {@inheritDoc}
     */
    public function getMediaApiId(): ?string
    {
        return $this->tvdbId;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeasons(): array
    {
        return $this->seasons;
    }

    /**
     * {@inheritDoc}
     */
    public function addSeason(SeasonInterface $season)
    {
        $this->seasons[$season->getNumber()] = $season;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeason(int $number): ?SeasonInterface
    {
        return $this->seasons[$number];
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'attributes' => [
                    'title' => $this->getTitle(),
                    'thumbnail' => $this->getThumbnail(),
                    'background' => $this->getBackground(),
                    'language' => $this->getLanguage(),
                    'tvdbId' => $this->getMediaApiId(), //TODO rename key to media_api_id
                    'folder' => $this->getFolder(),
                ],
            ]
        );
    }
}
