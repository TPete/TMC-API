<?php

namespace TinyMediaCenter\API\Model\Resource;

use TinyMediaCenter\API\Model\AbstractResource;

/**
 * Class AreaModel
 */
class Area extends AbstractResource
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
    public function __construct(string $id, string $description)
    {
        parent::__construct($id);
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
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
