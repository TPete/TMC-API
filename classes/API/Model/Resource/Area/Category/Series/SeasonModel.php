<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category\Series;

use TinyMediaCenter\API\Model\AbstractResourceModel;

/**
 * A season of a tv series.
 */
class SeasonModel extends AbstractResourceModel
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'season';

    /**
     * @var int
     */
    private $episodeCount;

    /**
     * SeasonModel constructor.
     *
     * @param string $id
     * @param int    $episodeCount
     */
    public function __construct($id, $episodeCount)
    {
        parent::__construct($id);
        $this->episodeCount = $episodeCount;
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
                    'episodeCount' => $this->episodeCount,
                ],
            ]
        );
    }
}
