<?php

namespace TinyMediaCenter\API\Model;

/**
 * Interface MovieModelInterface
 */
interface MovieModelInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getOriginalTitle();

    /**
     * @return string
     */
    public function getOverview();

    /**
     * @return \DateTime
     */
    public function getReleaseDate();

    /**
     * @return array
     */
    public function getGenres();

    /**
     * @return array
     */
    public function getDirectors();

    /**
     * @return array
     */
    public function getActors();

    /**
     * @return array
     */
    public function getCountries();

    /**
     * @return string|null
     */
    public function getCollectionId();
}
