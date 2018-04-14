<?php

namespace TinyMediaCenter\API\Controller;

use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API;
use TinyMediaCenter\API\Model\ConfigModel;
use TinyMediaCenter\API\Model\DBModel;
use TinyMediaCenter\API\Service\SetupService;

/**
 * Class SetupController
 */
class SetupController extends AbstractController
{
    /**
     * @var SetupService
     */
    private $service;

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
                $config = new ConfigModel($body);
                $config->save();

                return $response->withStatus(202);
            } catch (API\Exception\InvalidDataException $e) {
                return $response->withStatus(400);
            }
        } else {
            try {
                $config = ConfigModel::init();

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
     * @throws \Exception
     *
     * @return Response
     */
    public function checkAction(Request $request, Response $response, $type)
    {
        $res = [];

        if (SetupService::TYPE_DATABASE === $type) {
            $res = $this->checkDatabase($request);
        }

        if (in_array($type, SetupService::CATEGORIES)) {
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
            if ($this->getService()->setupDatabase()) {
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
     * @throws \Exception
     *
     * @return SetupService
     */
    private function getService()
    {
        if (null === $this->service) {
            $this->service = $this->get('setup_service');
        }

        return $this->service;
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkDatabase(Request $request)
    {
        $query = $request->getQueryParams();
        $dbModel = new DBModel($query['host'], $query['name'], $query['user'], $query['password']);

        return $this->getService()->checkDatabase($dbModel);
    }

    /**
     * @param Request $request
     * @param string  $category
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkCategory(Request $request, $category)
    {
        $pathKey = sprintf('path%s', ucfirst($category));
        $aliasKey = sprintf('alias%s', ucfirst($category));
        $path = $request->getQueryParam($pathKey);
        $alias = $request->getQueryParam($aliasKey);

        return $this->getService()->checkCategory($category, $path, $alias);
    }
}
