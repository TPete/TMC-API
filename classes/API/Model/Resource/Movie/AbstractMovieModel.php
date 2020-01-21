<?php

namespace TinyMediaCenter\API\Model\Resource\Movie;

/**
 * Class AbstractMovieModel
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

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->getType(),
            'id' => $this->getId(),
            'attributes' => [
                self::MOVIE_TITLE => $this->getTitle(),
                self::MOVIE_ORIGINAL_TITLE => $this->getOriginalTitle(),
                self::MOVIE_OVERVIEW => $this->getOverview(),
                self::MOVIE_RELEASE_DATE => $this->getReleaseDate()->format('Y-m-d'),
                self::MOVIE_DIRECTORS => $this->getDirectors(),
                self::MOVIE_ACTORS => $this->getActors(),
                self::MOVIE_COUNTRIES => $this->getCountries(),
                self::MOVIE_GENRES => $this->getGenres(),
                self::MOVIE_COLLECTION_ID => $this->getCollectionId(), //TODO move to relationship?
            ],
        ];
    }
}
