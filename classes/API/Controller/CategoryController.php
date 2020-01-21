<?php

namespace TinyMediaCenter\API\Controller;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use TinyMediaCenter\API\Service\MovieService;
use TinyMediaCenter\API\Service\SeriesService;

/**
 * Class CategoryController
 */
class CategoryController extends AbstractController
{
    /**
     * @var SeriesService
     */
    private $showService;

    /**
     * @var MovieService
     */
    private $movieService;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * CategoryController constructor.
     *
     * @param SeriesService   $showService
     * @param MovieService    $movieService
     * @param RouterInterface $router
     */
    public function __construct(SeriesService $showService, MovieService $movieService, RouterInterface $router)
    {
        $this->showService = $showService;
        $this->movieService = $movieService;
        $this->router = $router;
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws \Exception
     *
     * @return ResponseInterface
     */
    public function indexAction(Request $request, Response $response)
    {
        //expects tv shows to be in sub folders of $config["pathShows"]
        //where each sub folder will be listed as a different category

        //expects movies to be directly in $config["pathMovies"]
        //which will be listed as a single category
        //TODO: make this consistent and/or more flexible

        //TODO Use models for service returns
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
