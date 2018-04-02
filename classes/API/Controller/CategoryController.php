<?php

namespace TinyMediaCenter\API\Controller;

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
     * @param Request  $request
     * @param Response $response
     *
     * @throws \Interop\Container\Exception\ContainerException
     *
     * @return Response
     */
    public function indexAction(Request $request, Response $response)
    {
        //expects tv shows to be in sub folders of $config["pathShows"]
        //where each sub folder will be listed as a different category

        //expects movies to be directly in $config["pathMovies"]
        //which will be listed as a single category
        //TODO: make this consistent and/or more flexible
        /** @var ShowService $showService */
        $showService = $this->container->get('show_service');
        $shows = $showService->getCategories();

        /** @var MovieService $movieService */
        $movieService = $this->container->get('movie_service');
        $movies     = $movieService->getCategories();

        $categories = [
            'shows' => $shows,
            'movies' => $movies,
        ];

        return $response->withJson($categories);
    }
}
