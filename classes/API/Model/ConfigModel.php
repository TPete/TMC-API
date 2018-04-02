<?php

namespace TinyMediaCenter\API\Model;

/**
 * Class ConfigModel
 */
class ConfigModel
{
    /**
     * @var string
     */
    private $pathMovies;

    /**
     * @var string
     */
    private $aliasMovies;

    /**
     * @var string
     */
    private $pathShows;

    /**
     * @var string
     */
    private $aliasShows;

    /**
     * @var string
     */
    private $tmdbApiKey;

    /**
     * @var string
     */
    private $ttvdbApiKey;

    /**
     * @var DBModel
     */
    private $dbModel;

    /**
     * ConfigModel constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->pathMovies = $config['pathMovies'];
        $this->aliasMovies = $config['aliasMovies'];
        $this->pathShows = $config['pathShows'];
        $this->aliasShows = $config['aliasShows'];
        $this->tmdbApiKey = $config['TMDBApiKey'];
        $this->ttvdbApiKey = $config['TTVDBApiKey'];

        $this->dbModel = new DBModel($config['dbHost'], $config['dbName'], $config['dbUser'], $config['dbPassword']);
    }

    /**
     * @return string
     */
    public function getPathMovies()
    {
        return $this->pathMovies;
    }

    /**
     * @return string
     */
    public function getAliasMovies()
    {
        return $this->aliasMovies;
    }

    /**
     * @return string
     */
    public function getPathShows()
    {
        return $this->pathShows;
    }

    /**
     * @return string
     */
    public function getAliasShows()
    {
        return $this->aliasShows;
    }

    /**
     * @return string
     */
    public function getTmdbApiKey()
    {
        return $this->tmdbApiKey;
    }

    /**
     * @return string
     */
    public function getTtvdbApiKey()
    {
        return $this->ttvdbApiKey;
    }

    /**
     * @return DBModel
     */
    public function getDbModel()
    {
        return $this->dbModel;
    }
}
