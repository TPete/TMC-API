<?php

namespace TinyMediaCenter\API\Model\Resource\Movie;

use TinyMediaCenter\API\Model\AbstractResourceModel;

/**
 * Class CollectionModel
 */
class CollectionModel extends AbstractResourceModel implements CollectionModelInterface
{
    /**
     * @var string
     */
    protected $type = 'movie_collection';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var array
     */
    private $parts;

    /**
     * CollectionModel constructor.
     *
     * @param int    $id
     * @param string $name
     * @param string $overview
     * @param array  $parts
     */
    public function __construct($id, $name, $overview, array $parts)
    {
        parent::__construct($id);
        $this->name = $name;
        $this->overview = $overview;
        $this->parts = $parts;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * {@inheritDoc}
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return [
            'type' => $this->getType(),
            'id' => $this->getId(),
            'attributes' => [
                'name' => $this->getName(),
                'overview' => $this->getOverview(),
                'parts' => $this->getParts(), //TODO move to relationships?
            ],
        ];
    }
}
