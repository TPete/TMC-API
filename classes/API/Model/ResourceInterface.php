<?php

namespace TinyMediaCenter\API\Model;

/**
 * A JSONAPI resource.
 */
interface ResourceInterface
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
    public function getType(): string;

    /**
     * @param array $includes
     */
    public function setIncludes(array $includes);

    /**
     * @return ResourceInterface[]
     */
    public function getIncludes(): array;

    /**
     * Returns the resources's array representation.
     *
     * @return array
     */
    public function toArray(): array;
}
