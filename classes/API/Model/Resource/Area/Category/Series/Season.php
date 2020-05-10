<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category\Series;

use TinyMediaCenter\API\Model\AbstractResource;
use TinyMediaCenter\API\Model\Series\Season\EpisodeInterface;
use TinyMediaCenter\API\Model\Series\SeasonInterface;

/**
 * A season of a tv series.
 */
class Season extends AbstractResource implements SeasonInterface
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'season';

    /**
     * @var EpisodeInterface[]
     */
    private $episodes;

    /**
     * SeasonModel constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->episodes = [];
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
    public function getEpisodes(): array
    {
        return $this->episodes;
    }

    /**
     * {@inheritDoc}
     */
    public function addEpisode(EpisodeInterface $episode)
    {
        $this->episodes[$episode->getNumber()] = $episode;
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
                    'episodeCount' => count($this->episodes), //TODO Why?
                ],
            ]
        );
    }
}
