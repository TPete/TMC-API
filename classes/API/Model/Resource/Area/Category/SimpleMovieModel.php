<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category;

use TinyMediaCenter\API\Model\AbstractMovieModel;
use TinyMediaCenter\API\Model\ResourceModelInterface;

/**
 * Class SimpleMovieModel
 */
class SimpleMovieModel extends AbstractMovieModel implements ResourceModelInterface
{
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->getType(),
            'id' => $this->getId(),
            'attributes' => [
                self::MOVIE_TITLE => $this->getTitle(),
                self::MOVIE_ORIGINAL_TITLE => $this->getOriginalTitle(),
                self::MOVIE_OVERVIEW => $this->getOverview(),
                self::MOVIE_RELEASE_DATE => $this->getReleaseDate()->format('Y-m-d'),
                self::MOVIE_DIRECTORS => $this->getDirectors(),
                self::MOVIE_ACTORS => $this->getActors(),
                self::MOVIE_COUNTRIES => $this->getCountries(),
                self::MOVIE_GENRES => $this->getGenres(),
                self::MOVIE_COLLECTION_ID => $this->getCollectionId(), //TODO move to relationship?
            ],
        ];
    }
}
