<?php

namespace TinyMediaCenter\API\Controller;

use Psr\Http\Message\ResponseInterface;
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

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws \Exception
     *
     * @return ResponseInterface
     */
    public function areasAction(Request $request, Response $response)
    {
        //expects tv shows to be in sub folders of $config["pathShows"]
        //where each sub folder will be listed as a different category

        //expects movies to be directly in $config["pathMovies"]
        //which will be listed as a single category
        //TODO: make this consistent and/or more flexible
        return $response->withJson([
            'meta' => [
                'description' => 'The API has several main areas. Find further information in the "links" entry.',
                'links' => [
                    'series' => [
                        'href' => $this->router->pathFor('app.series.index'),
                        'meta' => [
                            'description' => 'TV series',
                        ],
                    ],
                    'movies' => [
                        'href' => $this->router->pathFor('app.movies.index'),
                        'meta' => [
                            'description' => 'Movies',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
