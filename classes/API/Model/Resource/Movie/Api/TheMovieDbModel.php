<?php

namespace TinyMediaCenter\API\Model\Resource\Movie\Api;

use TinyMediaCenter\API\Model\Resource\Movie\AbstractMovieModel;

/**
 * Class TheMovieDbModel
 */
class TheMovieDbModel extends AbstractMovieModel
{
    const KEY_ID = 'id';

    const KEY_TITLE = 'title';

    const KEY_ORIGINAL_TITLE = 'original_title';

    const KEY_OVERVIEW = 'overview';

    const KEY_RELEASE_DATE = 'release_date';

    const KEY_GENRES = 'genres';

    const KEY_COUNTRIES = 'production_countries';

    const KEY_CREDITS = 'credits';

    const KEY_CAST = 'cast';

    const KEY_CREW = 'crew';

    const KEY_NAME = 'name';

    const KEY_JOB = 'job';

    const KEY_COUNTRY_ISO = 'iso_3166_1';

    const KEY_COLLECTION_INFO = 'belongs_to_collection';

    const JOB_DIRECTOR = 'Director';

    /**
     * TheMovieDbModel constructor.
     *
     * @param array $data
     *
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if (false === $this->isValid($data)) {
            throw new \Exception('Invalid data');
        }

        $this->id = $data[self::KEY_ID];
        $this->title = $data[self::KEY_TITLE];
        $this->originalTitle = $data[self::KEY_ORIGINAL_TITLE];
        $this->overview = $data[self::KEY_OVERVIEW];
        $this->releaseDate = $this->parseReleaseDate($data[self::KEY_RELEASE_DATE]);
        $this->genres = $this->parseGenres($data[self::KEY_GENRES]);
        $this->countries = $this->parseCountries($data[self::KEY_COUNTRIES]);
        $this->directors = $this->parseDirectors($data[self::KEY_CREDITS]);
        $this->actors = $this->parseActors($data[self::KEY_CREDITS]);
        $this->collectionId = $this->parseCollectionInfo($data[self::KEY_COLLECTION_INFO]);
    }

    /**
     * @param string $raw
     *
     * @return bool|\DateTime
     */
    private function parseReleaseDate($raw)
    {
        return \DateTime::createFromFormat('Y-m-d', $raw);
    }

    /**
     * @param array $raw
     *
     * @return array
     */
    private function parseGenres(array $raw)
    {
        $genres = [];

        foreach ($raw as $item) {
            $genres[] = $item[self::KEY_NAME];
        }

        return $genres;
    }

    /**
     * @param array $raw
     *
     * @return array
     */
    private function parseCountries(array $raw)
    {
        $countries = [];

        foreach ($raw as $item) {
            $countries[] = $item[self::KEY_COUNTRY_ISO];
        }

        return $countries;
    }

    /**
     * @param array $credits
     *
     * @return array
     */
    private function parseDirectors(array $credits)
    {
        $directors = [];

        foreach ($credits[self::KEY_CREW] as $item) {
            if (self::JOB_DIRECTOR === $item[self::KEY_JOB]) {
                $directors[] = $item[self::KEY_NAME];
            }
        }

        return $directors;
    }

    /**
     * @param array $credits
     *
     * @return array
     */
    private function parseActors(array $credits)
    {
        $actors = [];

        foreach ($credits[self::KEY_CAST] as $item) {
            $actors[] = $item[self::KEY_NAME];
        }

        return $actors;
    }

    /**
     * @param array|null $raw
     *
     * @return string|null
     */
    private function parseCollectionInfo(array $raw = null)
    {
        return isset($raw[self::KEY_ID]) ? $raw[self::KEY_ID] : null;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function isValid(array $data)
    {
        return true;
    }
}
