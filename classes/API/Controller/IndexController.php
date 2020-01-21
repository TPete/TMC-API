<?php

namespace TinyMediaCenter\API\Controller;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;

/**
 * Class IndexController
 */
class IndexController extends AbstractController
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * IndexController constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function indexAction(Request $request, Response $response)
    {
        return $response->withJson([
            'meta' => [
                'title' => 'TMC API',
                'version' => '2.0',
            ],
            'links' => [
                'self' => $this->router->pathFor('app.main'),
                'config' => [
                    'href' => $this->router->pathFor('app.config'),
                    'meta' => [
                        'description' => 'Application setup',
                    ],
                ],
                'area' => [
                    'href' => $this->router->pathFor('app.areas'),
                    'meta' => [
                        'description' => 'Content areas',
                    ],
                ],
            ],
        ]);
    }
}