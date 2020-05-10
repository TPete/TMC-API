<?php

namespace TinyMediaCenter\API\Service\Api;

use TinyMediaCenter\API\Model\CollectionInterface;
use TinyMediaCenter\API\Model\MovieInterface;

/**
 * Interface MovieApiInterface
 */
interface MoviesApiClientInterface
{
    /**
     * Returns the info for the given movie id.
     *
     * @param string $id
     *
     * @throws \Exception
     *
     * @return MovieInterface
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
     * @return MovieInterface|null
     */
    public function searchMovie($title);

    /**
     * Returns the info for the given collection id.
     *
     * @param string $id
     *
     * @throws \Exception
     *
     * @return CollectionInterface
     */
    public function getCollectionInfo($id);
}
