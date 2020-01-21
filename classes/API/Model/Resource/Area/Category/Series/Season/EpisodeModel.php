<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category\Series\Season;

use TinyMediaCenter\API\Model\AbstractResourceModel;

/**
 * An episode of a tv series.
 */
class EpisodeModel extends AbstractResourceModel
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
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'attributes' => [
                    'title' => $this->title,
                    'url' => $this->url,
                    'description' => $this->description,
                ],
            ]
        );
    }
}
