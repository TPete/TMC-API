<?php

namespace TinyMediaCenter\API\Controller\Area;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;
use TinyMediaCenter\API\Controller\AbstractController;
use TinyMediaCenter\API\Service\Area\MovieServiceInterface;

/**
 * Class MovieController
 */
class MovieController extends AbstractController
{
    /**
     * @var MovieServiceInterface
     */
    private $movieService;

    /**
     * MovieController constructor.
     *
     * @param MovieServiceInterface $service
     */
    public function __construct(MovieServiceInterface $service)
    {
        $this->movieService = $service;
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
        try {
            return $this->returnResources($response, $this->movieService->getMetaInfo());
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
    public function categoriesAction(Request $request, Response $response)
    {
        try {
            return $this->returnResources($response, $this->movieService->getCategories());
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
    public function categoryAction(Request $request, Response $response, $category)
    {
        try {
            //Pagination controls
            $count = (int) $request->getQueryParam('count', -1);
            $offset = (int) $request->getQueryParam('offset', 0);

            //if collection is provided, other parameters are ignored
            $collection = (int) $request->getQueryParam('collection', 0);

            if ($collection > 0) {
                $movieList = $this->movieService->getByCategoryAndCollection($category, $collection, $count, $offset);
            } else {
                $orgSort = $request->getQueryParam('sort', 'name_asc');
                $split = explode('_', $orgSort);
                $sort = $split[0];
                $order = $split[1];
                $filter = $request->getQueryParam('filter');
                $genres = $request->getQueryParam('genre');
                $genres = explode(',', $genres);
                $movieList = $this->movieService->getByCategory($category, $sort, $order, $filter, $genres, $count, $offset);
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
                $movie = $this->movieService->get($category, $id);
            } else {
                $remoteId = $request->getParsedBodyParam('remoteId');
                $movie = $this
                    ->movieService
                    ->update($category, $id, $remoteId);
            }

            return $this->returnResources($response, $movie);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param int      $id
     * @param string   $type
     *
     * @return Response
     */
    public function imageAction(Request $request, Response $response, $category, $id, $type)
    {
        try {
            $image = $this->movieService->getImage($category, $id, $type);
            $response->write(file_get_contents($image));

            return $response
                ->withHeader('Content-Type', 'image/jpeg')
                ->withHeader('Cache-Control', 'public, max-age=31536000');
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param int      $id
     *
     * @return Response
     */
    public function fileAction(Request $request, Response $response, $category, $id)
    {
        try {
            $file = $this->movieService->getMovieFile($category, $id);

            return $response
                ->withAddedHeader('Content-Type', 'video/x-msvideo')
                ->withAddedHeader('Content-Length', filesize($file))
                ->withAddedHeader('Content-Disposition', sprintf('attachment; filename= "%s"', basename($file)))
                ->withBody(new Stream(fopen($file, 'rb')));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }
}
