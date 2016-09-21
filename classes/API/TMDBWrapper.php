<?php

namespace TinyMediaCenter\API;

/**
 * Class TMDBWrapper
 *
 * Wrapper for themoviedb.org API
 */
class TMDBWrapper extends AbstractDBAPIWrapper
{
    /**
     * @var array
     */
    private $config;

    /**
     * TMDBWrapper constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $defaults = array("api_key" => $apiKey, "language" => "de");
        parent::__construct("http://api.themoviedb.org/3/", $defaults);
    }

    /**
     * @param int    $id
     * @param string $path
     * @param string $storeDir
     */
    public function downloadPoster($id, $path, $storeDir)
    {
        $this->fetchConfiguration();
        $url = $this->config["images"]["base_url"]."original".$path;
        $this->downloadImage($url, $storeDir.$id."_big.jpg");
    }

    /**
     * @param int $id
     *
     * @throws ScrapeException
     *
     * @return mixed
     */
    public function getCollectionInfo($id)
    {
        $url = "collection/".$id;
        $data = $this->curlDownload($url);
        $data = json_decode($data, true);
        if ($data !== null && is_array($data) && isset($data["id"])) {
            return $data;
        }
        $msg = "Unknown error";
        if ($data !== null && is_array($data) && isset($data["status_message"])) {
            $msg = $data["status_message"];
        }
        throw new ScrapeException("Failed to retrieve collection info for id ".$id." (".$msg.")");
    }

    /**
     * @param int    $id
     * @param string $movieDir
     * @param string $filename
     *
     * @throws ScrapeException
     *
     * @return Movie
     */
    public function getMovieInfo($id, $movieDir = "", $filename = "")
    {
        $url = "movie/".$id;
        $args = array("append_to_response" => "credits");
        $data = $this->curlDownload($url, $args);
        $data = json_decode($data, true);
        if ($data !== null && is_array($data) && isset($data["id"])) {
            $tmp = $data["genres"];
            $genres = array();
            foreach ($tmp as $ele) {
                $genres[] = $ele["name"];
            }
            $tmp = $data["production_countries"];
            $countries = array();
            foreach ($tmp as $ele) {
                $countries[] = $ele["iso_3166_1"];
            }
            $credits = $data["credits"];
            $tmp = $credits["cast"];
            $actors = array();
            foreach ($tmp as $ele) {
                $actors[] = $ele["name"];
            }
            $tmp = $credits["crew"];
            $director = "";
            foreach ($tmp as $ele) {
                if ($ele["job"] === "Director") {
                    $director = $ele["name"];
                    break;
                }
            }
            $collectionId = $data["belongs_to_collection"]["id"];

            $movieData = [
                "id" => $id,
                "title" => $data["title"],
                "filename" => $filename,
                "overview" => $data["overview"],
                "poster" => $id."_big.jpg",
                "poster_path" => $data["poster_path"],
                "release_date" => $data["release_date"],
                "genres" => $genres,
                "countries" => $countries,
                "actors" => $actors,
                "director" => $director,
                "collection_id" => $collectionId,
                "original_title" => $data["original_title"],
            ];

            $mov = new Movie($movieData, $movieDir);

            return $mov;
        }
        $msg = "Unknown error";
        if ($data !== null && is_array($data) && isset($data["status_message"])) {
            $msg = $data["status_message"];
        }
        throw new ScrapeException("Failed to retrieve movie info for id ".$id." (".$msg.")");
    }

    /**
     * Search for a movie using the provided title. If sth. was found fetch the info for the
     * first id in the result (using getMovieInfo).
     *
     * @param string $title    the title to search for
     * @param string $filename the name of the movie file
     * @param string $path     path to the file
     *
     * @throws ScrapeException
     *
     * @return Movie|null
     */
    public function searchMovie($title, $filename, $path)
    {
        $url = "search/movie";
        $args = array("query" => $title);

        $data = $this->curlDownload($url, $args);
        $data = json_decode($data, true);
        if (isset($data["results"][0])) {
            $id = $data["results"][0]["id"];
            $result = $this->getMovieInfo($id, $path, $filename);

            return $result;
        }

        throw new ScrapeException("Failed to retrieve movie info for title ".$title." (No data available)");
    }

    /**
     * Fetch configuration.
     */
    private function fetchConfiguration()
    {
        $url = "configuration";
        $tmp = $this->curlDownload($url);
        $this->config = json_decode($tmp, true);
    }
}
