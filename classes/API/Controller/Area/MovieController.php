<?php

namespace TinyMediaCenter\API\Controller\Area;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use TinyMediaCenter\API\Controller\AbstractController;
use TinyMediaCenter\API\Model\Resource\Area\CategoryModel;
use TinyMediaCenter\API\Model\Resource\AreaModel;
use TinyMediaCenter\API\Service\MovieService;

/**
 * Class MovieController
 */
class MovieController extends AbstractController
{
    /**
     * @var MovieService
     */
    private $movieService;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * MovieController constructor.
     *
     * @param MovieService    $service
     * @param RouterInterface $router
     */
    public function __construct(MovieService $service, RouterInterface $router)
    {
        $this->movieService = $service;
        $this->router = $router;
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
            return $this->returnResources($response, $this->movieService->updateData());
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
    public function indexAction(Request $request, Response $response)
    {
        $model = new AreaModel(
            'movies',
            $links = [
                'href' => $this->router->pathFor('app.movies.categories.index'),
                'meta' => [
                    'title' => 'Category overview',
                ],
            ],
            'Movies area overview'
        );

        return $this->returnResources($response, $model);
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function categoriesAction(Request $request, Response $response)
    {
        $categories = [];

        foreach ($this->movieService->getCategories() as $category) {
            $categories[] = new CategoryModel($category, [
                'self' => $this->router->pathFor('app.movies.categories.category.index', ['category' => $category]),
            ]);
        }

        return $this->returnResources($response, $categories);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return Response
     */
    public function categoryAction(Request $request, Response $response, $category)
    {
        try {
            //Pagination controls
            $cnt = (int) $request->getQueryParam('cnt', -1);
            $offset = (int) $request->getQueryParam('offset', 0);

            //if collection is provided, other parameters are ignored
            $collection = (int) $request->getQueryParam('collection', 0);

            if ($collection > 0) {
                $movieList = $this->movieService->getMoviesForCollection($category, $collection, $cnt, $offset);
            } else {
                $orgSort = $request->getQueryParam('sort', 'name_asc');
                $split = explode('_', $orgSort);
                $sort = $split[0];
                $order = $split[1];
                $filter = $request->getQueryParam('filter');
                $genre = $request->getQueryParam('genre');
                $movieList = $this->movieService->getMovies($category, $sort, $order, $filter, $genre, $cnt, $offset);
            }

            return $this->returnResources($response, $movieList);
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
            $filter = $request->getQueryParam('term');
            $genres = $this->movieService->getGenres($category, $filter);

            return $this->returnResources($response, $genres);
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
    public function collectionsAction(Request $request, Response $response, $category)
    {
        try {
            return $this->returnResources($response, $this->movieService->getCollections($category));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $remoteId
     *
     * @return Response
     */
    public function lookupAction(Request $request, Response $response, $remoteId)
    {
        try {
            return $this->returnResources($response, $this->movieService->lookupMovie($remoteId));
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
            $id = intval($id, 10);

            if ($request->isGet()) {
                $movie = $this->movieService->getMovieDetails($category, $id);
            } else {
                $remoteId = $request->getParsedBodyParam('remoteId');
                $filename = $request->getParsedBodyParam('filename');
                $movie = $this
                    ->movieService
                    ->updateMovieFromApi($category, $id, $remoteId, $filename);
            }

            return $this->returnResources($response, $movie);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }
}
