<?php

namespace TinyMediaCenter\API\Model\Store;

/**
 * Class MovieModel
 */
class Movie
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $originalTitle;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var \DateTime
     */
    private $releaseDate;

    /**
     * @var array
     */
    private $genres;

    /**
     * @var array
     */
    private $directors;

    /**
     * @var array
     */
    private $actors;

    /**
     * @var array
     */
    private $countries;

    /**
     * @var string
     */
    private $apiId;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $info;

    /**
     * @var string
     */
    private $collectionId;

    /**
     * @var string
     */
    private $collectionName;

    /**
     * MovieModel constructor.
     *
     * @param int       $id
     * @param string    $title
     * @param string    $originalTitle
     * @param string    $overview
     * @param \DateTime $releaseDate
     * @param array     $genres
     * @param array     $directors
     * @param array     $actors
     * @param array     $countries
     * @param string    $apiId
     * @param string    $filename
     * @param string    $info
     * @param string    $collectionId
     * @param string    $collectionName
     */
    public function __construct($id, $title, $originalTitle, $overview, \DateTime $releaseDate, array $genres, array $directors, array $actors, array $countries, $apiId, $filename, $info, $collectionId, $collectionName)
    {
        $this->id = $id;
        $this->title = $title;
        $this->originalTitle = $originalTitle;
        $this->overview = $overview;
        $this->releaseDate = $releaseDate;
        $this->genres = $genres;
        $this->directors = $directors;
        $this->actors = $actors;
        $this->countries = $countries;
        $this->apiId = $apiId;
        $this->filename = $filename;
        $this->info = $info;
        $this->collectionId = $collectionId;
        $this->collectionName = $collectionName;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getOriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * @return string
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * @return \DateTime
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * @return array
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * @return array
     */
    public function getDirectors()
    {
        return $this->directors;
    }

    /**
     * @return array
     */
    public function getActors()
    {
        return $this->actors;
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @return string
     */
    public function getApiId()
    {
        return $this->apiId;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }
}
