<?php

namespace TinyMediaCenter\API\Controller\Category;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API\Controller\AbstractController;
use TinyMediaCenter\API\Service\MovieService;

/**
 * Class MovieController
 */
class MovieController extends AbstractController
{
    /**
     * @var MovieService
     */
    private $service;

    /**
     * MovieController constructor.
     *
     * @param Container $container
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->service = $container->get('movie_service');
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return Response
     */
    public function indexAction(Request $request, Response $response, $category)
    {
        try {
            $orgSort = $request->getQueryParam('sort', 'name_asc');
            $split   = explode('_', $orgSort);
            $sort    = $split[0];
            $order   = $split[1];

            $filter     = $request->getQueryParam('filter');
            $genre      = $request->getQueryParam('genre');
            $cnt        = (int) $request->getQueryParam('cnt', -1);
            $offset     = (int) $request->getQueryParam('offset', 0);
            $collection = (int) $request->getQueryParam('collection', 0);
            $list       = (int) $request->getQueryParam('list', 0);

            if ($collection > 0) {
                $movieList = $this->service->getMoviesForCollection($category, $collection, $cnt, $offset);
            } elseif ($list > 0) {
                $movieList = $this->service->getMoviesForList($category, $list, $cnt, $offset);
            } else {
                $movieList = $this->service->getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset);
            }

            return $response->withJson($movieList);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return Response
     */
    public function genresAction(Request $request, Response $response, $category)
    {
        try {
            $genres = $this->service->getGenres($category);

            $resp = [];
            $comp = $request->getQueryParam('term');
            $comp = mb_strtolower($comp);
            $l    = strlen($comp);

            foreach ($genres as $gen) {
                if (substr(mb_strtolower($gen), 0, $l) === $comp) {
                    $resp[] = $gen;
                }
            }

            return $response->withJson($resp);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return Response
     */
    public function compilationsAction(Request $request, Response $response, $category)
    {
        try {
            $lists       = $this->service->getLists($category);
            $collections = $this->service->getCollections($category);
            $comp = [
                "lists"       => $lists,
                "collections" => $collections,
            ];

            return $response->withJson($comp);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function maintenanceAction(Request $request, Response $response)
    {
        try {
            $result = $this->service->updateData();

            return $response->withJson($result);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $id
     *
     * @return Response
     */
    public function lookupAction(Request $request, Response $response, $id)
    {
        try {
            $id = intval($id, 10);
            $details = $this->service->lookupMovie($id);

            return $response->withJson($details);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $id
     *
     * @return Response
     */
    public function detailsAction(Request $request, Response $response, $category, $id)
    {
        try {
            $id      = intval($id, 10);
            $details = $this->service->getMovieDetails($category, $id);

            return $response->withJson($details);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $id
     *
     * @return Response
     */
    public function editAction(Request $request, Response $response, $category, $id)
    {
        try {
            $id  = intval($id, 10);
            $movieDbId = $request->getParsedBodyParam('movieDbId');
            $filename = $request->getParsedBodyParam('filename');
            $result = $this
                ->service
                ->updateFromScraper($category, $id, $movieDbId, $filename);

            return $response->withJson($result);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }
}
