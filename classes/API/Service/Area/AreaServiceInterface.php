<?php

namespace TinyMediaCenter\API\Service\Area;

use TinyMediaCenter\API\Model\Resource\Area\Category;

/**
 * Service for an area.
 */
interface AreaServiceInterface
{
    /**
     * @return string
     */
    public function getArea();

    /**
     * Get all categories of the area.
     *
     * @return Category[]
     */
    public function getCategories();
}
