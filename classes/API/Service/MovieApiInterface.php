<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Model\Resource\Movie\CollectionModelInterface;
use TinyMediaCenter\API\Model\Resource\Movie\MovieModelInterface;

/**
 * Interface MovieApiInterface
 */
interface MovieApiInterface
{
    /**
     * Returns the info for the given movie id.
     *
     * @param string $id
     *
     * @throws \Exception
     *
     * @return MovieModelInterface
     */
    public function getMovieInfo($id);

    /**
     * Returns the movie poster for the given movie id.
     *
     * @param string $id
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getMoviePoster($id);

    /**
     * Searches for the given movie title and returns the best hit, if available. Null otherwise.
     *
     * @param string $title
     *
     * @throws \Exception
     *
     * @return MovieModelInterface|null
     */
    public function searchMovie($title);

    /**
     * Returns the info for the given collection id.
     *
     * @param string $id
     *
     * @throws \Exception
     *
     * @return CollectionModelInterface
     */
    public function getCollectionInfo($id);
}
