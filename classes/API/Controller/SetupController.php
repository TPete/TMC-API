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

            try {
                $config = new API\Model\ConfigModel($body);
                $config->saveTo('config.json');

                return $response->withStatus(202);
            } catch (API\Exception\InvalidDataException $e) {
                return $response->withStatus(400);
            }
        } else {
            try {
                $config = API\Model\ConfigModel::init();

                return $response->withJson($config->toArray());
            } catch (API\Exception\InvalidDataException $e) {
                return $response->withStatus(500);
            }
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
     * @throws \Exception
     *
     * @return Response
     */
    public function setupDBAction(Request $request, Response $response)
    {
        try {
            /* @var API\Service\Store\ShowStoreDB $showStore */
            $showStore   = $this->get('show_store');
            $checkShows  = $showStore->checkSetup();
            /* @var API\Service\Store\MovieStoreDB $movieStore */
            $movieStore  = $this->get('movie_store');
            $checkMovies = $movieStore->checkSetup();

            if (false === $checkShows && false === $checkMovies) {
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
