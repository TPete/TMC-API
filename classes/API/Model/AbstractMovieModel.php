<?php

namespace TinyMediaCenter\API\Model;

/**
 * Class BasicMovieModel
 */
abstract class AbstractMovieModel implements MovieModelInterface
{
    const MOVIE_TITLE = 'title';
    const MOVIE_ORIGINAL_TITLE = 'original_title';
    const MOVIE_OVERVIEW = 'overview';
    const MOVIE_RELEASE_DATE = 'release_date';
    const MOVIE_DIRECTORS = 'directors';
    const MOVIE_ACTORS = 'actors';
    const MOVIE_COUNTRIES = 'countries';
    const MOVIE_GENRES = 'genres';
    const MOVIE_COLLECTION_ID = 'collection_id';

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
    public function getType()
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
