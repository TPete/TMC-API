<?php

namespace TinyMediaCenter\API\Model\Resource\Movie;

use TinyMediaCenter\API\Model\ResourceModelInterface;

/**
 * Interface CollectionModelInterface
 */
interface CollectionModelInterface extends ResourceModelInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getOverview();

    /**
     * @return array
     */
    public function getParts();
}
