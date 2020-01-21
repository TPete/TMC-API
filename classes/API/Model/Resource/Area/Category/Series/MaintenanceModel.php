<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category\Series;

use TinyMediaCenter\API\Model\AbstractResourceModel;

/**
 * Class MaintenanceModel
 */
class MaintenanceModel extends AbstractResourceModel
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'series_maintenance';

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
    public function toArray()
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
