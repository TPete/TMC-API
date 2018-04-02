<?php

namespace TinyMediaCenter\API\Controller;

use TinyMediaCenter\API;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class SetupController
 */
class SetupController extends AbstractController
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function indexAction(Request $request, Response $response)
    {
        if ($request->isPost()) {
            $body = $request->getParsedBody();

            $config = [
                "pathMovies"  => $body["pathMovies"],
                "aliasMovies" => $body["aliasMovies"],
                "pathShows"   => $body["pathShows"],
                "aliasShows"  => $body["aliasShows"],
                "dbHost"      => $body["dbHost"],
                "dbName"      => $body["dbName"],
                "dbUser"      => $body["dbUser"],
                "dbPassword"  => $body["dbPassword"],
                "TMDBApiKey"  => $body["TMDBApiKey"],
                "TTVDBApiKey" => $body["TTVDBApiKey"],
            ];

            API\Util::writeJSONFile("config.json", $config);

            return $response->withStatus(202);
        } else {
            $file = "config.json";

            if (false === file_exists($file)) {
                $file = "example_config.json";
            }

            $config = API\Util::readJSONFile($file);

            return $response->withJson($config);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $type
     *
     * @return Response
     */
    public function checkAction(Request $request, Response $response, $type)
    {
        $res = [];

        if ($type === "db") {
            $res = $this->checkDB($request);
        }

        if (in_array($type, ['movies', 'shows'])) {
            $res = $this->checkCategory($request, $type);
        }

        return $response->withJson($res);
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function setupDBAction(Request $request, Response $response)
    {
        try {
            $showStore   = new API\Service\Store\ShowStoreDB($this->db);
            $checkShows  = $showStore->checkSetup();
            $movieStore  = new API\Service\Store\MovieStoreDB($this->db);
            $checkMovies = $movieStore->checkSetup();

            if (!$checkShows && !$checkMovies) {
                $showStore->setupDB();
                $movieStore->setupDB();

                $status = 202;
            } else {
                $status = 500;
            }

            return $response->withStatus($status);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function checkDB(Request $request)
    {
        $res = [];

        try {
            $res["dbAccess"] = "Ok";
            $query = $request->getQueryParams();
            $db = new API\Model\DBModel($query['host'], $query['name'], $query['user'], $query['password']);
            $showStore   = new API\Service\Store\ShowStoreDB($db);
            $checkShows  = $showStore->checkSetup();
            $movieStore  = new API\Service\Store\MovieStoreDB($db);
            $checkMovies = $movieStore->checkSetup();

            if ($checkShows && $checkMovies) {
                $res["dbSetup"] = "Ok";
            } else {
                $res["dbSetup"] = "Error";
            }
        } catch (\PDOException $e) {
            $res["dbAccess"] = "Error: ".$e->getMessage();
            $res["dbSetup"]  = "Error";
        }

        return $res;
    }

    /**
     * @param Request $request
     * @param string  $category
     *
     * @return array
     */
    private function checkCategory(Request $request, $category)
    {
        $pathKey = sprintf('path%s', ucfirst($category));
        $aliasKey = sprintf('alias%s', ucfirst($category));
        $path = $request->getQueryParam($pathKey);
        $alias = $request->getQueryParam($aliasKey);

        $res = [];

        if (is_dir($path) && is_writable($path) && API\Util::checkUrl($alias)) {
            $res["result"]  = "Ok";
            $res["folders"] = API\Util::getFolders($path);
        } else {
            $res["result"] = "Error";
        }

        return $res;
    }
}
