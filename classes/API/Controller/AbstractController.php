<?php

namespace TinyMediaCenter\API\Controller;

use Slim\Container;
use Slim\Http\Response;
use TinyMediaCenter\API\Model\DBModel;

/**
 * Class AbstractController
 */
abstract class AbstractController
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var DBModel
     */
    protected $db;

    /**
     * AbstractController constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->db = $container['db'];
    }

    /**
     * @param \Exception $exception
     * @param Response   $response
     *
     * @return Response
     */
    protected function handleException(\Exception $exception, Response $response)
    {
        $error = [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
        ];

        return $response->withJson($error, 500);
    }
}
