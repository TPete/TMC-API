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
