<?php

namespace TinyMediaCenter\API\Model\Movie;

/**
 * Class CollectionModel
 */
class CollectionModel implements CollectionModelInterface
{
    /**
     * @var int
     */
    private $id;

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
        $this->id = $id;
        $this->name = $name;
        $this->overview = $overview;
        $this->parts = $parts;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
