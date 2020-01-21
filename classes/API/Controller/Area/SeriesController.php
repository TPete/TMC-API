<?php

namespace TinyMediaCenter\API\Controller\Area;

use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API\Controller\AbstractController;
use TinyMediaCenter\API\Service\SeriesServiceInterface;

/**
 * Controller of the series area.
 */
class SeriesController extends AbstractController
{
    /**
     * @var SeriesServiceInterface
     */
    private $seriesService;

    /**
     * SeriesController constructor.
     *
     * @param SeriesServiceInterface $service
     */
    public function __construct(SeriesServiceInterface $service)
    {
        $this->seriesService = $service;
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
            return $this->returnResources($response, $this->seriesService->updateData());
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
            return $this->returnResources($response, $this->seriesService->getMetaInfo());
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
            return $this->returnResources($response, $this->seriesService->getCategories());
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
            return $this->returnResources($response, $this->seriesService->getByCategory($category));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $series
     *
     * @return Response
     */
    public function detailsAction(Request $request, Response $response, $category, $series)
    {
        try {
            if ($request->isGet()) {
                //TODO add includes for seasons, episodes

                return $this->returnResources($response, $this->seriesService->get($category, $series));
            } else {
                $title = $request->getParsedBodyParam('title');
                $tvDbId = (int) $request->getParsedBodyParam('tvdbId');
                $lang = $request->getParsedBodyParam('lang');

                $seriesModel = $this
                    ->seriesService
                    ->update($category, $series, $title, $tvDbId, $lang);

                return $this->returnResources($response, $seriesModel);
            }
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $series
     *
     * @return Response
     */
    public function seasonsIndexAction(Request $request, Response $response, $category, $series)
    {
        try {
            return $this->returnResources($response, $this->seriesService->getSeasons($category, $series));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $series
     * @param string   $season
     *
     * @return Response
     */
    public function seasonDetailsAction(Request $request, Response $response, $category, $series, $season)
    {
        try {
            return $this->returnResources($response, $this->seriesService->getSeason($category, $series, $season));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $series
     * @param string   $season
     *
     * @return Response
     */
    public function episodesIndexAction(Request $request, Response $response, $category, $series, $season)
    {
        try {
            return $this->returnResources($response, $this->seriesService->getEpisodes($category, $series, $season));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $series
     * @param string   $season
     * @param string   $episode
     *
     * @return Response
     */
    public function episodeDetailsAction(Request $request, Response $response, $category, $series, $season, $episode)
    {
        try {
            return $this->returnResources($response, $this->seriesService->getEpisode($category, $series, $season, $episode));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }
}
