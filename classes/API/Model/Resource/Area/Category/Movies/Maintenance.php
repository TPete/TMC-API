<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category\Movies;

use TinyMediaCenter\API\Model\AbstractResource;

/**
 * Class MaintenanceModel
 */
class Maintenance extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'movie_maintenance';

    /**
     * @var array
     */
    private $steps;

    /**
     * MaintenanceModel constructor.
     *
     * @param string $id
     * @param array  $steps
     */
    public function __construct($id, array $steps)
    {
        parent::__construct($id);
        $this->steps = $steps;
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
                    'steps' => $this->steps,
                ],
            ]
        );
    }
}
