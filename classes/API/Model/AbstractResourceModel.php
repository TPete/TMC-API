<?php

namespace TinyMediaCenter\API\Model;

/**
 * Class AbstractResourceModel
 */
abstract class AbstractResourceModel implements ResourceModelInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * AbstractJsonApiModel constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        //a minimal resource
        //TODO add handling of optional attributes?
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
        ];
    }
}
