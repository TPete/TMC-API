<?php

namespace TinyMediaCenter\API\Controller;

use Interop\Container\Exception\ContainerException;
use Slim\Container;
use Slim\Http\Response;

/**
 * Class AbstractController
 */
abstract class AbstractController
{
    /**
     * @var Container
     */
    private $container;

    /**
     * AbstractController constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $service
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function get($service)
    {
        try {
            return $this->container->get($service);
        } catch (ContainerException $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
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
