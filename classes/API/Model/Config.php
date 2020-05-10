<?php

namespace TinyMediaCenter\API\Model;

use TinyMediaCenter\API\Exception\InvalidDataException;

/**
 * Class ConfigModel
 */
class Config
{
    /**
     * Name of the config file.
     */
    const FILENAME = 'config.json';

    /**
     * Name of the example config file.
     */
    const EXAMPLE_FILENAME = 'example_config.json';

    /**
     * Path to movies folder.
     */
    const KEY_PATH_MOVIES = 'pathMovies';

    /**
     * Path to shows folder.
     */
    const KEY_PATH_SHOWS = 'pathShows';

    /**
     * API key for themoviedb.org
     */
    const KEY_TMDB_API_KEY = 'TMDBApiKey';

    /**
     * API key for thetvdb.com
     */
    const KEY_TTVDB_API_KEY = 'TTVDBApiKey';

    /**
     * Database host.
     */
    const KEY_DB_HOST = 'dbHost';

    /**
     * Database name.
     */
    const KEY_DB_NAME = 'dbName';

    /**
     * Database user.
     */
    const KEY_DB_USER = 'dbUser';

    /**
     * Database password.
     */
    const KEY_DB_PASSWORD = 'dbPassword';

    /**
     * @var array
     */
    private $data;

    /**
     * @var Database
     */
    private $dbModel;

    /**
     * ConfigModel constructor.
     *
     * @throws InvalidDataException
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (false === $this->isValid($config)) {
            throw new InvalidDataException();
        }

        $this->data = $config;
        $this->dbModel = new Database($config[self::KEY_DB_HOST], $config[self::KEY_DB_NAME], $config[self::KEY_DB_USER], $config[self::KEY_DB_PASSWORD]);
    }

    /**
     * Creates a new ConfigModel from the given file.
     *
     * File content is expected in JSON format.
     *
     * @throws InvalidDataException
     *
     * @return Config
     */
    public static function init()
    {
        $filename = file_exists(self::FILENAME) ? self::FILENAME : self::EXAMPLE_FILENAME;

        if (false === file_exists($filename)) {
            throw new InvalidDataException();
        }

        $fileData = file_get_contents($filename);

        if (false === $fileData) {
            throw new InvalidDataException();
        }

        if (false === mb_check_encoding($fileData, 'UTF-8')) {
            $fileData = utf8_encode($fileData);
        }

        $config = json_decode($fileData, true);

        return new Config($config);
    }

    /**
     * Saves the config data.
     *
     * @return bool
     */
    public function save()
    {
        $json = json_encode($this->toArray());
        $pp = $this->prettyPrint($json);
        $res = file_put_contents(self::FILENAME, $pp);

        return ($res !== false);
    }

    /**
     * Returns the config data as an associative array.
     *
     * @param bool $grouped
     *
     * @return array
     */
    public function toArray($grouped = false)
    {
        if (false === $grouped) {
            $result = $this->data;
        } else {
            $result = [
                'movies' => [
                    self::KEY_PATH_MOVIES => $this->get(self::KEY_PATH_MOVIES),
                ],
                'show' => [
                    self::KEY_PATH_SHOWS => $this->get(self::KEY_PATH_SHOWS),
                ],
                'db' => [
                    self::KEY_DB_HOST => $this->get(self::KEY_DB_HOST),
                    self::KEY_DB_NAME => $this->get(self::KEY_DB_NAME),
                    self::KEY_DB_USER => $this->get(self::KEY_DB_USER),
                    self::KEY_DB_PASSWORD => $this->get(self::KEY_DB_PASSWORD),
                ],
                'api' => [
                    self::KEY_TMDB_API_KEY => $this->get(self::KEY_TMDB_API_KEY),
                    self::KEY_TTVDB_API_KEY => $this->get(self::KEY_TTVDB_API_KEY),
                ],
            ];
        }

        return $result;
    }

    /**
     * Returns the path to the movies folder.
     *
     * @return string
     */
    public function getPathMovies()
    {
        return $this->get(self::KEY_PATH_MOVIES);
    }

    /**
     * Returns the path to the shows folder.
     *
     * @return string
     */
    public function getPathShows()
    {
        return $this->get(self::KEY_PATH_SHOWS);
    }

    /**
     * Returns the API key for themoviedb.org.
     *
     * @return string
     */
    public function getTmdbApiKey()
    {
        return $this->get(self::KEY_TMDB_API_KEY);
    }

    /**
     * Returns the API key for thetvdb.com.
     *
     * @return string
     */
    public function getTtvdbApiKey()
    {
        return $this->get(self::KEY_TTVDB_API_KEY);
    }

    /**
     * Returns the database model.
     *
     * @return Database
     */
    public function getDbModel()
    {
        return $this->dbModel;
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    private function isValid(array $config)
    {
        $required = [
            self::KEY_PATH_MOVIES,
            self::KEY_PATH_SHOWS,
            self::KEY_TMDB_API_KEY,
            self::KEY_TTVDB_API_KEY,
            self::KEY_DB_HOST,
            self::KEY_DB_NAME,
            self::KEY_DB_USER,
            self::KEY_DB_PASSWORD,
        ];

        $valid = true;

        foreach ($required as $item) {
            $valid &= array_key_exists($item, $config);
        }

        return $valid;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function get($key)
    {
        return $this->data[$key];
    }

    /**
     * @param string $json
     *
     * @return string
     */
    private function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $prevChar = '';
        $inQuotes = false;
        $endsLineLevel = null;
        $jsonLength = strlen($json);

        for ($i = 0; $i < $jsonLength; $i++) {
            $char = $json[$i];
            $newLineLevel = null;
            $post = "";
            if ($endsLineLevel !== null) {
                $newLineLevel = $endsLineLevel;
                $endsLineLevel = null;
            }
            if ($char === '"' && $prevChar != '\\') {
                $inQuotes = !$inQuotes;
            } else {
                if (!$inQuotes) {
                    switch ($char) {
                        case '}':
                        case ']':
                            $level--;
                            $endsLineLevel = null;
                            $newLineLevel = $level;
                            break;
                        //fall-through
                        case '{':
                        //fall-through
                        case '[':
                            $level++;
                        //fall-through
                        case ',':
                            $endsLineLevel = $level;
                            break;
                        case ':':
                            $post = " ";
                            break;
                        case " ":
                        case "\t":
                        case "\n":
                        case "\r":
                            $char = "";
                            $endsLineLevel = $newLineLevel;
                            $newLineLevel = null;
                            break;
                    }
                }
            }
            if ($newLineLevel !== null) {
                $result .= "\r\n".str_repeat("\t", $newLineLevel);
            }
            $result .= $char.$post;
            $prevChar = $char;
        }

        return $result;
    }
}

