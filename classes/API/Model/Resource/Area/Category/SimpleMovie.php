<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category;

use TinyMediaCenter\API\Model\AbstractMovie;
use TinyMediaCenter\API\Model\ResourceInterface;

/**
 * Class SimpleMovieModel
 */
class SimpleMovie extends AbstractMovie implements ResourceInterface
{
    /**
     * @var array
     */
    protected $includes = [];

    /**
     * @param array $includes
     */
    public function setIncludes(array $includes)
    {
        $this->includes = $includes;
    }

    /**
     * @return ResourceInterface[]
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'id' => $this->getId(),
            'attributes' => [
                'title' => $this->getTitle(),
                'original_title' => $this->getOriginalTitle(),
                'overview' => $this->getOverview(),
                'release_date' => $this->getReleaseDate()->format('Y-m-d'),
                'directors' => $this->getDirectors(),
                'actors' => $this->getActors(),
                'countries' => $this->getCountries(),
                'genres' => $this->getGenres(),
                'collection_id' => $this->getCollectionId(),
            ],
        ];
    }
}
