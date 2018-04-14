<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Service\Store\ShowStoreDB;
use TinyMediaCenter\API\Service\Store\MovieStoreDB;
use TinyMediaCenter\API\Model\DBModel;

/**
 * Class SetupService
 */
class SetupService
{
    /**
     * Setup type: database.
     */
    const TYPE_DATABASE = 'db';

    /**
     * Setup type: movies category.
     */
    const TYPE_CATEGORY_MOVIES = 'movies';

    /**
     * Setup type: shows category.
     */
    const TYPE_CATEGORY_SHOWS = 'shows';

    /**
     * Category collection.
     */
    const CATEGORIES = [
        self::TYPE_CATEGORY_MOVIES,
        self::TYPE_CATEGORY_SHOWS,
    ];

    /**
     * @var ShowService
     */
    private $showService;

    /**
     * @var MovieService
     */
    private $movieService;

    /**
     * @var ShowStoreDB
     */
    private $showStoreDB;

    /**
     * @var MovieStoreDB
     */
    private $movieStoreDB;

    /**
     * SetupService constructor.
     *
     * @param ShowService  $showService
     * @param MovieService $movieService
     * @param ShowStoreDB  $showStoreDB
     * @param MovieStoreDB $movieStoreDB
     */
    public function __construct(ShowService $showService, MovieService $movieService, ShowStoreDB $showStoreDB, MovieStoreDB $movieStoreDB)
    {
        $this->showService = $showService;
        $this->movieService = $movieService;
        $this->showStoreDB = $showStoreDB;
        $this->movieStoreDB = $movieStoreDB;
    }

    /**
     * Verifies the database is set up correctly.
     *
     * @param DBModel $dbModel
     *
     * @return array
     */
    public function checkDatabase(DBModel $dbModel)
    {
        $res = [];

        try {
            $res['dbAccess'] = 'Ok';
            $checkShows  = $this->showStoreDB->checkSetup($dbModel);
            $checkMovies = $this->movieStoreDB->checkSetup($dbModel);

            if ($checkShows && $checkMovies) {
                $res['dbSetup'] = 'Ok';
            } else {
                $res['dbSetup'] = 'Error';
            }
        } catch (\PDOException $e) {
            $res['dbAccess'] = 'Error: '.$e->getMessage();
            $res['dbSetup']  = 'Error';
        }

        return $res;
    }

    /**
     * Verifies that the category is set up correctly.
     *
     * @param string $category
     * @param string $path
     * @param string $alias
     *
     * @throws \Exception
     *
     * @return array
     */
    public function checkCategory($category, $path, $alias)
    {
        $res = [];

        if (in_array($category, self::CATEGORIES) && is_dir($path) && is_writable($path) && $this->isValid($alias)) {
            $service = $this->getServiceByCategory($category);

            $res['result']  = 'Ok';
            $res['folders'] = $service->getFolders($path);
        } else {
            $res['result'] = 'Error';
        }

        return $res;
    }

    /**
     * Set up the database.
     *
     * @return bool
     */
    public function setupDatabase()
    {
        $checkShows = $this->showStoreDB->checkSetup();
        $checkMovies = $this->movieStoreDB->checkSetup();

        if (false === $checkShows && false === $checkMovies) {
            $this->showStoreDB->setupDB();
            $this->movieStoreDB->setupDB();

            return true;
        }

        return false;
    }

    /**
     * @param string $category
     *
     * @throws \Exception
     *
     * @return AbstractCategoryService
     */
    private function getServiceByCategory($category)
    {
        if (false === in_array($category, self::CATEGORIES)) {
            throw new \Exception(sprintf('Invalid category %s', $category));
        }

        $services = [
            self::TYPE_CATEGORY_MOVIES => $this->movieService,
            self::TYPE_CATEGORY_SHOWS => $this->showService,
        ];

        return $services[$category];
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function isValid($url)
    {
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode < 400);
    }
}
