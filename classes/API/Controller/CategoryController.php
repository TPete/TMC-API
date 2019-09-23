<?php

namespace TinyMediaCenter\API\Controller;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API\Service\MovieService;
use TinyMediaCenter\API\Service\ShowService;

/**
 * Class CategoryController
 */
class CategoryController extends AbstractController
{
    /**
     * @var ShowService
     */
    private $showService;

    /**
     * @var MovieService
     */
    private $movieService;

    /**
     * CategoryController constructor.
     *
     * @param ShowService  $showService
     * @param MovieService $movieService
     */
    public function __construct(ShowService $showService, MovieService $movieService)
    {
        $this->showService = $showService;
        $this->movieService = $movieService;
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

        return $response->withJson([
            'data' => [
                [
                    'type' => 'category',
                    'id' => 'shows',
                    'attributes' => [
                        'subCategories' => $this->showService->getCategories(),
                    ]
                ],
                [
                    'type' => 'category',
                    'id' => 'movies',
                    'attributes' => [
                        'subCategories' => $this->movieService->getCategories(),
                    ]

                ]
            ]
        ]);
    }
}
