<?php

namespace TinyMediaCenter\API\Controller\Category;

use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API\Controller\AbstractController;
use TinyMediaCenter\API\Service\ShowService;

/**
 * Class ShowController
 */
class ShowController extends AbstractController
{
    /**
     * @var ShowService
     */
    private $showService;

    /**
     * ShowController constructor.
     *
     * @param ShowService $service
     */
    public function __construct(ShowService $service)
    {
        $this->showService = $service;
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
            $result = $this->showService->updateData();

            return $response->withJson($result);
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
    public function indexAction(Request $request, Response $response, $category)
    {
        try {
            $result = $this->showService->getList($category);

            return $response->withJson($result);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $show
     * @param string   $episode
     *
     * @return Response
     */
    public function episodesAction(Request $request, Response $response, $category, $show, $episode)
    {
        try {
            $result = $this->showService->getEpisodeDescription($category, $episode);

            return $response->withJson($result);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $show
     *
     * @return Response
     */
    public function detailsAction(Request $request, Response $response, $category, $show)
    {
        try {
            $result = $this->showService->getDetails($category, $show);

            return $response->withJson($result);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $show
     *
     * @return Response
     */
    public function editAction(Request $request, Response $response, $category, $show)
    {
        try {
            $title = $request->getParsedBodyParam('title');
            $tvdbId = (int) $request->getQueryParam('tvdbId');
            $lang = $request->getQueryParam('lang');

            $this
                ->showService
                ->updateDetails($category, $show, $title, $tvdbId, $lang);

            return $response->withStatus(204);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }
}
