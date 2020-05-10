<?php

namespace TinyMediaCenter\API\Model;

/**
 * Abstract base class for the movies.
 */
abstract class AbstractMovie implements MovieInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $originalTitle;

    /**
     * @var string
     */
    protected $overview;

    /**
     * @var \DateTime
     */
    protected $releaseDate;

    /**
     * @var array
     */
    protected $genres;

    /**
     * @var array
     */
    protected $countries;

    /**
     * @var array
     */
    protected $directors;

    /**
     * @var array
     */
    protected $actors;

    /**
     * @var string
     */
    protected $collectionId;

    /**
     * BasicMovieModel constructor.
     *
     * @param string    $id
     * @param string    $title
     * @param string    $originalTitle
     * @param string    $overview
     * @param \DateTime $releaseDate
     * @param array     $genres
     * @param array     $directors
     * @param array     $actors
     * @param array     $countries
     * @param string    $collectionId
     */
    public function __construct($id, $title, $originalTitle, $overview, \DateTime $releaseDate, array $genres, array $directors, array $actors, array $countries, $collectionId)
    {
        $this->id = $id;
        $this->title = $title;
        $this->originalTitle = $originalTitle;
        $this->overview = $overview;
        $this->releaseDate = $releaseDate;
        $this->genres = $genres;
        $this->countries = $countries;
        $this->directors = $directors;
        $this->actors = $actors;
        $this->collectionId = $collectionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'movie';
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * {@inheritdoc}
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectors()
    {
        return $this->directors;
    }

    /**
     * {@inheritdoc}
     */
    public function getActors()
    {
        return $this->actors;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }
}
