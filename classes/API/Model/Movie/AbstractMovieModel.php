<?php

namespace TinyMediaCenter\API\Model\Movie;

/**
 * Class AbstractMovieModel
 */
abstract class AbstractMovieModel implements MovieModelInterface
{
    const MOVIE_ID = 'id';

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
     * @return array
     */
    public function toArray()
    {
        return [
            self::MOVIE_ID => $this->getId(),
            self::MOVIE_TITLE => $this->getTitle(),
            self::MOVIE_ORIGINAL_TITLE => $this->getOriginalTitle(),
            self::MOVIE_OVERVIEW => $this->getOverview(),
            self::MOVIE_RELEASE_DATE => $this->getReleaseDate(),
            self::MOVIE_DIRECTORS => $this->getDirectors(),
            self::MOVIE_ACTORS => $this->getActors(),
            self::MOVIE_COUNTRIES => $this->getCountries(),
            self::MOVIE_GENRES => $this->getGenres(),
            self::MOVIE_COLLECTION_ID => $this->getCollectionId(),
        ];
    }
}
