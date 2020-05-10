<?php

namespace TinyMediaCenter\API\Controller\Area;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;
use TinyMediaCenter\API\Controller\AbstractController;
use TinyMediaCenter\API\Service\Area\SeriesServiceInterface;

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
    public function maintenanceAction(Request $request, Response $response): Response
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
    public function indexAction(Request $request, Response $response): Response
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
    public function categoriesAction(Request $request, Response $response): Response
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
    public function categoryAction(Request $request, Response $response, $category): Response
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
    public function detailsAction(Request $request, Response $response, string $category, string $series): Response
    {
        try {
            if ($request->isGet()) {
                $seriesDetails = $this->seriesService->get($category, $series);
                //TODO move setting of includes to service/models
                $seasons = $seriesDetails->getSeasons();

                foreach ($seasons as $season) {
                    $episodes = $season->getEpisodes();
                    $season->setIncludes($episodes);
                }

                $seriesDetails->setIncludes($seasons);

                return $this->returnResources($response, $seriesDetails);
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
    public function seasonsIndexAction(Request $request, Response $response, string $category, string $series): Response
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
    public function seasonDetailsAction(Request $request, Response $response, string $category, string $series, string $season): Response
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
    public function episodesIndexAction(Request $request, Response $response, string $category, string $series, string $season): Response
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
    public function episodeDetailsAction(Request $request, Response $response, string $category, string $series, string $season, string $episode): Response
    {
        try {
            return $this->returnResources($response, $this->seriesService->getEpisode($category, $series, $season, $episode));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $slug
     * @param string   $type
     *
     * @return Response
     */
    public function imageAction(Request $request, Response $response, string $category, string $slug, string $type): Response
    {
        try {
            $category = $this->validateCategory($category);
            $type = $this->validateImageType($type);
            $image = $this->seriesService->getImage($category, $slug, $type);
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
     * @param string   $series
     * @param string   $season
     * @param string   $episode
     *
     * @return Response
     */
    public function episodeFileAction(Request $request, Response $response, string $category, string $series, string $season, string $episode)
    {
        try {
            $file = $this->seriesService->getEpisodeFile($category, $series, $season, $episode);

            return $response
                ->withAddedHeader('Content-Type', 'video/x-msvideo')
                ->withAddedHeader('Content-Length', filesize($file))
                ->withAddedHeader('Content-Disposition', sprintf('attachment; filename= "%s"', basename($file)))
                ->withBody(new Stream(fopen($file, 'rb')));
        } catch (\Exception $e) {
            return $this->handleException($e, $response);
        }
    }

    /**
     * @param string $category
     *
     * @return string
     */
    private function validateCategory(string $category): string
    {
        //TODO add some validation
        return $category;
    }

    /**
     * @param string $type
     *
     * @throws \Exception
     *
     * @return string
     */
    private function validateImageType(string $type): string
    {
        $types = [
            'bg',
            'thumb',
            'thumbnail',
        ];

        if (!in_array($type, $types)) {
            throw new \Exception(sprintf('Invalid image type: %s', $type));
        }

        if ($type === 'thumbnail') {
            $type = 'thumb';
        }

        return $type;
    }
}
