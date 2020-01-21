<?php

namespace TinyMediaCenter\API\Model\Resource;

use TinyMediaCenter\API\Model\AbstractResourceModel;

/**
 * Class AreaModel
 */
class AreaModel extends AbstractResourceModel
{
    /**
     * @var string
     */
    protected $type = 'area';

    /**
     * @var string
     */
    private $description;

    /**
     * AreaModel constructor.
     *
     * @param string $id
     * @param string $description
     */
    public function __construct($id, $description)
    {
        parent::__construct($id);
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'meta' => [
                    'description' => $this->getDescription(),
                ],
            ]
        );
    }
}
