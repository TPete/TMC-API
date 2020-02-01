<?php

namespace TinyMediaCenter\API\Service\Area;

use TinyMediaCenter\API\Model\Resource\Area\CategoryModel;

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
     * @return CategoryModel[]
     */
    public function getCategories();
}
