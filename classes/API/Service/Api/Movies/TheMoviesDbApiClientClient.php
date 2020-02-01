<?php

namespace TinyMediaCenter\API\Service\Api\Movies;

use TinyMediaCenter\API\Model\Api\Movies\TheMovieDbModel;
use TinyMediaCenter\API\Model\Resource\Area\Category\Movies\CollectionModel;
use TinyMediaCenter\API\Service\Api\MoviesApiClientInterface;

/**
 * Client for themoviedb.org API.
 *
 * @see https://developers.themoviedb.org/3/
 */
class TheMoviesDbApiClientClient implements MoviesApiClientInterface
{
    const BASE_URL = 'http://api.themoviedb.org/3/';

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
    private $defaultArgs;

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
        $this->defaultArgs = [
            self::PARAM_API_KEY => $apiKey,
            self::PARAM_LANGUAGE => $language,
        ];
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
        $data = $this->get($url, $args);

        if (isset($data[self::KEY_RESULTS])) {
            $results = $data[self::KEY_RESULTS];

            $id = $results[0][self::KEY_ID];

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
        $data = $this->get($url);

        if (null === $data || false === is_array($data)) {
            throw new \Exception('Invalid data');
        }

        if (isset($data[self::KEY_ERROR_MESSAGE])) {
            throw new \Exception('Error from API: '.$data[self::KEY_ERROR_MESSAGE]);
        }

        //TODO should be a different model
        return new CollectionModel($data['id'], $data['name'], $data['overview'], $data['parts']);
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

        $data = $this->get($url, $args);

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
            $this->config = $this->get(self::CONFIGURATION_URL);
        }

        return $this->config;
    }

    /**
     * @param string $url
     * @param array  $args
     *
     * @return mixed
     */
    private function get($url, $args = [])
    {
        $args = array_merge($this->defaultArgs, $args);
        $url = sprintf('%s%s?%s', self::BASE_URL, $url, http_build_query($args));

        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        return json_decode($output, true);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function downloadImage($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close($ch);

        return $raw;
    }
}
