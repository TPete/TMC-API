<?php

namespace TinyMediaCenter\API\Service;

use TinyMediaCenter\API\Model\Resource\Area\CategoryModel;
use TinyMediaCenter\API\Model\DBModel;

/**
 * Class SetupService
 */
class SetupService
{
    /**
     * @var AreaServiceInterface[]
     */
    private $services;

    /**
     * @var StoreInterface[]
     */
    private $stores;

    /**
     * SetupService constructor.
     *
     * @param AreaServiceInterface[] $services
     * @param StoreInterface[]       $stores
     */
    public function __construct(array $services, array $stores)
    {
        $this->stores = $stores;
        $this->services = [];
        /** @var AreaServiceInterface $service */
        foreach ($services as $service) {
            $this->services[$service->getArea()] = $service;
        }
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
        try {
            $res = ['dbAccess' => 'Ok'];
            $allSetup = true;

            foreach ($this->stores as $store) {
                $allSetup = $store->checkSetup($dbModel) && $allSetup;
            }

            $res['dbSetup'] = $allSetup ? 'Ok' : 'Error';
        } catch (\PDOException $e) {
            $res['dbAccess'] = 'Error: '.$e->getMessage();
            $res['dbSetup']  = 'Error';
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
        foreach ($this->stores as $store) {
            if (!$store->checkSetup()) {
                $store->setup();
            }
        }

        return true;
    }

    /**
     * @param string $area
     *
     * @return bool
     */
    public function isValidArea($area)
    {
        return in_array($area, array_keys($this->services));
    }

    /**
     * Verifies that the area is set up correctly.
     *
     * @param string $area
     * @param string $path
     * @param string $alias
     *
     * @throws \Exception
     *
     * @return array
     */
    public function checkArea($area, $path, $alias)
    {
        $res = [];

        if ($this->isValidArea($area) && $this->isValidPath($path) && $this->isValidAlias($alias)) {
            $service = $this->getServiceByArea($area);
            $categories = array_map(function (CategoryModel $model) {
                return $model->getId();
            }, $service->getCategories());

            $res['result']  = 'Ok';
            $res['folders'] = $categories;
        } else {
            $res['result'] = 'Error';
        }

        return $res;
    }

    /**
     * @param string $area
     *
     * @return AreaServiceInterface
     */
    private function getServiceByArea($area)
    {
        return $this->services[$area];
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function isValidPath($path)
    {
        return is_dir($path) && is_writable($path);
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function isValidAlias($url)
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
