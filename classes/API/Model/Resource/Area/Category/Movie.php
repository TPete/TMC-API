<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category;

use TinyMediaCenter\API\Model\ResourceInterface;

/**
 * Class MovieModel
 */
class Movie extends SimpleMovie implements ResourceInterface
{
    /**
     * @var string
     */
    private $movieDbId;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $poster;

    /**
     * @var string
     */
    private $posterBig;

    /**
     * @var string
     */
    private $info;

    /**
     * @var string
     */
    private $collectionName;

    /**
     * MovieModel constructor.
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
     * @param string    $movieDbId
     * @param string    $filename
     * @param string    $poster
     * @param string    $posterBig
     * @param string    $info
     * @param null      $collectionId
     * @param string    $collectionName
     */
    public function __construct($id, $title, $originalTitle, $overview, \DateTime $releaseDate, array $genres, array $directors, array $actors, array $countries, $movieDbId, $filename, $poster, $posterBig, $info, $collectionId = null, $collectionName = null)
    {
        parent::__construct($id, $title, $originalTitle, $overview, $releaseDate, $genres, $directors, $actors, $countries, $collectionId);
        $this->movieDbId = $movieDbId;
        $this->filename = $filename;
        $this->poster = $poster;
        $this->posterBig = $posterBig;
        $this->info = $info;
        $this->collectionName = $collectionName;
    }

    /**
     * @return string
     */
    public function getMovieDbId(): string
    {
        return $this->movieDbId;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getPoster(): string
    {
        return $this->poster;
    }

    /**
     * @return string
     */
    public function getPosterBig(): string
    {
        return $this->posterBig;
    }

    /**
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['attributes']['movie_db_id'] = $this->getMovieDbId();
        $array['attributes']['filename'] = $this->getFilename();
        $array['attributes']['poster'] = $this->getPoster();
        $array['attributes']['poster_big'] = $this->getPosterBig();
        $array['attributes']['info'] = $this->getInfo();
        $array['attributes']['collection_name'] = $this->getCollectionName();

        return $array;
    }
}
