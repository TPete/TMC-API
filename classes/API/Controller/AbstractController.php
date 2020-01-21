<?php

namespace TinyMediaCenter\API\Controller;

use Slim\Http\Response;
use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Model\ResourceModelInterface;

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
        if ($exception instanceof NotFoundException) {
            return $response->withJson($exception->getMessage(), 404);
        }

        $error = [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
        ];

        return $response->withJson($error, 500);
    }

    /**
     * @param Response                                        $response
     * @param ResourceModelInterface|ResourceModelInterface[] $resourceModels
     *
     * @return Response
     */
    protected function returnResources(Response $response, $resourceModels)
    {
        $returnList = true;

        if (!is_array($resourceModels)) {
            $resourceModels = [$resourceModels];
            $returnList = false;
        }

        $resources = array_map(function (ResourceModelInterface $resourceModel) {
            return $resourceModel->toArray();
        }, $resourceModels);

        if (!$returnList) {
            $resources = $resources[0];
        }

        return $response->withJson($resources);
    }

    /**
     * @param Response $response
     * @param callable $callable
     *
     * @return Response
     */
    protected function returnResourcesCallable(Response $response, callable $callable)
    {
        try {
            $resourceModels = $callable();
            $returnList = true;

            if (!is_array($resourceModels)) {
                $resourceModels = [$resourceModels];
                $returnList = false;
            }

            $resources = array_map(function (ResourceModelInterface $resourceModel) {
                return $resourceModel->toArray();
            }, $resourceModels);

            if (!$returnList) {
                $resources = $resources[0];
            }

            return $response->withJson($resources);
        } catch (\Exception $exception) {
            return $this->handleException($exception, $response);
        }
    }
}
