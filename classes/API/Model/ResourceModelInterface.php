<?php

namespace TinyMediaCenter\API\Model;

/**
 * A JSONAPI resource.
 */
interface ResourceModelInterface
{
    /**
     * Returns the resource id.
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the resource type.
     *
     * @return string
     */
    public function getType();

    /**
     * Returns the resources's array representation.
     *
     * @return array
     */
    public function toArray();
}
