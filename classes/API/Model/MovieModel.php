<?php

namespace TinyMediaCenter\API\Model;

/**
 * Class Movie.
 *
 * @deprecated
 */
class MovieModel
{
    private $filename;
    private $title;
    private $originalTitle;
    private $id;
    private $overview;
    private $poster;
    private $posterPath;
    private $releaseDate;
    private $countries;
    private $genres;
    private $actors;
    private $director;
    private $info;
    private $collectionId;
    private $empty;
    private $movieDir;

    /**
     * @param array  $movieData
     * @param string $movieDir
     */
    public function __construct($movieData, $movieDir)
    {
        $this->movieDir = $movieDir;

        if (count($movieData) > 0) {
            $this->filename = $movieData["filename"];
            $this->title = $movieData["title"];
            $this->originalTitle = $movieData["original_title"];
            $this->id = $movieData["id"];
            $this->overview = $movieData["overview"];
            $this->poster = $movieData["poster"];
            $this->posterPath = $movieData["poster_path"];
            $this->releaseDate = $movieData["release_date"];
            $this->countries = $movieData["countries"];
            $this->genres = $movieData["genres"];
            $this->director = $movieData["director"];
            $this->collectionId = $movieData["collection_id"];
            $this->actors = [];

            foreach ($movieData["actors"] as $act) {
                $this->actors[] = str_replace(" ", "&nbsp;", $act);
            }

            if (isset($movieData["info"])) {
                $this->info = $movieData["info"];
            } else {
                $this->info = "";
            }

            $this->empty = false;
        } else {
            $this->empty = true;
        }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOverview()
    {
        return $this->overview;
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
    public function getPosterPath()
    {
        return $this->posterPath;
    }

    /**
     * @return string
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * @return string
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * @return string
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * @return string
     */
    public function getDirector()
    {
        return $this->director;
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function getActors($limit = 0)
    {
        $res = [];

        if ($limit > 0) {
            foreach ($this->actors as $actor) {
                $limit--;
                if ($limit >= 0) {
                    $res[] = $actor;
                }
            }
        } else {
            $res = $this->actors;
        }

        return $res;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = "Movie: [";
        $str .= "Title: ".$this->title.", ";
        $str .= "Id: ".$this->id.", ";
        $str .= "File: ".$this->filename."]";

        return $str;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $res = [
            "id" => $this->id,
            "title" => $this->title,
            "filename" => $this->filename,
            "overview" => $this->overview,
            "poster" => $this->poster,
            "release_date" => $this->releaseDate,
            "genres" => $this->genres,
            "countries" => $this->countries,
            "actors" => $this->actors,
            "director" => $this->director,
            "info" => $this->getInfo(),
            "original_title" => $this->originalTitle,
            "collection_id" => $this->collectionId,
            "empty" => $this->empty,
        ];

        return $res;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        $res = [
            "id" => $this->id,
            "title" => $this->title,
            "filename" => $this->filename,
            "overview" => $this->overview,
            "poster" => $this->poster,
            "year" => substr($this->releaseDate, 0, 4),
            "genres" => $this->genres,
            "countries" => $this->countries,
            "actors" => $this->getActors(4),
            "director" => $this->director, "info" => $this->getInfo(),
        ];

        return $res;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        $res = $this->toArray();

        return json_encode($res);
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->empty;
    }

    /**
     * @return string|array
     */
    private function getInfo()
    {
        if ($this->info === "" and $this->filename !== "") {
            $getID3 = new \getID3();

            $fileInfo = $getID3->analyze($this->movieDir.$this->filename);
            $duration = $fileInfo["playtime_string"];
            $tmp = substr($fileInfo["playtime_string"], 0, strrpos($duration, ":"));

            if (strpos($tmp, ":") !== false) {
                $duration = $tmp;
            } else {
                $duration = "0:".$tmp;
            }

            $duration .= "&nbsp;h";
            $resolution = $fileInfo["video"]["resolution_x"]."&nbsp;x&nbsp;".$fileInfo["video"]["resolution_y"];
            $sound = $fileInfo["audio"]["channels"];

            if ($sound === 2) {
                $sound = "Stereo";
            }

            if ($sound === "5.1") {
                $sound = "DD&nbsp;5.1";
            }

            $this->info = $duration.", ".$resolution.", ".$sound;
        }

        return $this->info;
    }
}
