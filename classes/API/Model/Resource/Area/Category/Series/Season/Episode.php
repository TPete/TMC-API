<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season;

use TinyMediaCenter\API\Model\AbstractResource;
use TinyMediaCenter\API\Model\Series\Season\EpisodeInterface;

/**
 * An episode of a tv series.
 */
class Episode extends AbstractResource implements EpisodeInterface
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'series_episode';

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $description;

    /**
     * EpisodeModel constructor.
     *
     * @param string $id
     * @param string $title
     * @param string $url
     * @param string $description
     */
    public function __construct($id, $title, $url, $description)
    {
        parent::__construct($id);
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumber(): int
    {
        return $this->getId();
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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
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
                    'url' => $this->getUrl(),
                    'description' => $this->getDescription(),
                ],
            ]
        );
    }
}
