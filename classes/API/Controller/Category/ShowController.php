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
    private $service;

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function maintenanceAction(Request $request, Response $response)
    {
        try {
            $result = $this->getService()->updateData();

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
            $result = $this->getService()->getList($category);

            return $response->withJson($result);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $episode
     *
     * @return Response
     */
    public function episodesAction(Request $request, Response $response, $category, $episode)
    {
        try {
            $result = $this->getService()->getEpisodeDescription($category, $episode);

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
            $result = $this->getService()->getDetails($category, $show);

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
                ->getService()
                ->updateDetails($category, $show, $title, $tvdbId, $lang);

            return $response->withStatus(204);
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @throws \Exception
     *
     * @return ShowService
     */
    private function getService()
    {
        if (null === $this->service) {
            $this->service = $this->get('show_service');
        }

        return $this->service;
    }
}
