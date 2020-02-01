<?php

namespace TinyMediaCenter\API\Model;

/**
 * Interface CollectionModelInterface
 */
interface CollectionModelInterface
{
    /**
     * @return int
     */
    public function getId();

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
