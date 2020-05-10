<?php

namespace TinyMediaCenter\API\Controller;

use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API;
use TinyMediaCenter\API\Model\Config;
use TinyMediaCenter\API\Model\Database;
use TinyMediaCenter\API\Service\SetupService;

/**
 * Class SetupController
 *
 * TODO migrate to JSON API
 */
class SetupController extends AbstractController
{
    /**
     * Setup type: database.
     */
    const TYPE_DATABASE = 'db';

    /**
     * @var SetupService
     */
    private $setupService;

    /**
     * SetupController constructor.
     *
     * @param SetupService $service
     */
    public function __construct(SetupService $service)
    {
        $this->setupService = $service;
    }

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
                $config = new Config($body);
                $config->save();

                return $response->withStatus(202);
            } catch (API\Exception\InvalidDataException $e) {
                return $response->withStatus(400);
            }
        } else {
            try {
                $config = Config::init();

                return $response->withJson($config->toArray(true));
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

        if (self::TYPE_DATABASE === $type) {
            $res = $this->checkDatabase($request);
        }

        if ($this->setupService->isValidArea($type)) {
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
            if ($this->setupService->setupDatabase()) {
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
     * @throws \Exception
     *
     * @return array
     */
    private function checkDatabase(Request $request)
    {
        $query = $request->getQueryParams();
        $dbModel = new Database($query['host'], $query['name'], $query['user'], $query['password']);

        return $this->setupService->checkDatabase($dbModel);
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
        $path = $request->getQueryParam($pathKey);

        return $this->setupService->checkArea($category, $path);
    }
}
