<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category;

use TinyMediaCenter\API\Model\ResourceModelInterface;

/**
 * Class MovieModel
 */
class MovieModel extends SimpleMovieModel implements ResourceModelInterface
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
    public function getMovieDbId()
    {
        return $this->movieDbId;
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
    public function getPoster()
    {
        return $this->poster;
    }

    /**
     * @return string
     */
    public function getPosterBig()
    {
        return $this->posterBig;
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
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
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
