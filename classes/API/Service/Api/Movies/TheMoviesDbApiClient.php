<?php

namespace TinyMediaCenter\API\Service\Api\Movies;

use TinyMediaCenter\API\Model\Api\Movies\TheMovieDbModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\Collection;
use TinyMediaCenter\API\Service\Api\AbstractMediaApiClient;
use TinyMediaCenter\API\Service\Api\MoviesApiClientInterface;

/**
 * Client for themoviedb.org API.
 *
 * @see https://developers.themoviedb.org/3/
 */
class TheMoviesDbApiClient extends AbstractMediaApiClient implements MoviesApiClientInterface
{
    const MOVIE_INFO_URL = 'movie';

    const COLLECTION_INFO_URL = 'collection';

    const CONFIGURATION_URL = 'configuration';

    const SEARCH_MOVIE_URL = 'search/movie';

    const PARAM_API_KEY = 'api_key';

    const PARAM_LANGUAGE = 'language';

    const KEY_ERROR_MESSAGE = 'status_message';

    const KEY_POSTER_PATH = 'poster_path';

    const KEY_CONFIG_IMAGES = 'images';

    const KEY_BASE_URL = 'base_url';

    const KEY_RESULTS = 'results';

    const KEY_ID = 'id';

    const IMAGE_PATH_ORIGINAL = 'original';

    const ARGUMENT_QUERY = 'query';

    /**
     * @var array
     */
    private $config;

    /**
     * TheMovieDbApi constructor.
     *
     * @param string $apiKey
     * @param string $language
     */
    public function __construct($apiKey, $language)
    {
        parent::__construct('http://api.themoviedb.org/3/', [
            self::PARAM_API_KEY => $apiKey,
            self::PARAM_LANGUAGE => $language,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMovieInfo($id)
    {
        $data = $this->getMovieFromApi($id, true);

        return new TheMovieDbModel($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getMoviePoster($id)
    {
        $data = $this->getMovieFromApi($id);
        $posterPath = $data[self::KEY_POSTER_PATH];
        $url = $this->getPosterUrl($posterPath);

        return $this->downloadImage($url);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developers.themoviedb.org/3/search/search-movies
     */
    public function searchMovie($title)
    {
        $url = self::SEARCH_MOVIE_URL;
        $args = [self::ARGUMENT_QUERY => $title];
        $data = $this->curlDownload($url, $args, true);

        if (isset($data[self::KEY_RESULTS]) && isset($data[self::KEY_RESULTS][0])) {
            $id = $data[self::KEY_RESULTS][0][self::KEY_ID];

            return $this->getMovieInfo($id);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developers.themoviedb.org/3/collections/get-collection-details
     */
    public function getCollectionInfo($id)
    {
        $url = sprintf('%s/%s', self::COLLECTION_INFO_URL, $id);
        $data = $this->curlDownload($url, [], true);

        if (null === $data || false === is_array($data)) {
            throw new \Exception('Invalid data');
        }

        if (isset($data[self::KEY_ERROR_MESSAGE])) {
            throw new \Exception('Error from API: '.$data[self::KEY_ERROR_MESSAGE]);
        }

        //TODO should be a different model
        return new Collection($data['id'], $data['name'], $data['overview'], $data['parts']);
    }

    /**
     * @param string $id
     * @param bool   $includeCredits
     *
     * @throws \Exception
     *
     * @return array
     *
     * @see https://developers.themoviedb.org/3/movies/get-movie-details
     */
    private function getMovieFromApi($id, $includeCredits = false)
    {
        $url = sprintf('%s/%s', self::MOVIE_INFO_URL, $id);
        $args = [];

        if ($includeCredits) {
            $args = ['append_to_response' => 'credits'];
        }

        $data = $this->curlDownload($url, $args, true);

        if (null === $data || false === is_array($data)) {
            throw new \Exception('Invalid data');
        }

        if (isset($data[self::KEY_ERROR_MESSAGE])) {
            throw new \Exception($data[self::KEY_ERROR_MESSAGE]);
        }

        return $data;
    }

    /**
     * @param string $posterPath
     *
     * @return string
     *
     * @see https://developers.themoviedb.org/3/configuration/get-api-configuration
     */
    private function getPosterUrl($posterPath)
    {
        $config = $this->getConfiguration();
        $baseUrl = $config[self::KEY_CONFIG_IMAGES][self::KEY_BASE_URL];

        return sprintf('%s%s%s', $baseUrl, self::IMAGE_PATH_ORIGINAL, $posterPath);
    }

    /**
     * @return array
     *
     * @see https://developers.themoviedb.org/3/configuration/get-api-configuration
     */
    private function getConfiguration()
    {
        if (null === $this->config) {
            $this->config = $this->curlDownload(self::CONFIGURATION_URL, [], true);
        }

        return $this->config;
    }
}
