<?php

set_time_limit(900);
require "vendor/autoload.php";
require "vendor/james-heinrich/getid3/getid3/getid3.php";

use Slim\App;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;
use TinyMediaCenter\API;
use TinyMediaCenter\API\Controller\Area\MovieController;
use TinyMediaCenter\API\Controller\Area\SeriesController;
use TinyMediaCenter\API\Controller\IndexController;
use TinyMediaCenter\API\Controller\SetupController;
use TinyMediaCenter\API\Service\Area\MovieService;
use TinyMediaCenter\API\Service\Area\SeriesService;
use TinyMediaCenter\API\Service\SetupService;

$app = new App();

//Redirect url ending in non-trailing slash to trailing equivalent
$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && substr($path, -1) !== '/') {
        $uri = $uri->withPath($path.'/');

        return $response->withRedirect((string) $uri, 301);
    }

    return $next($request, $response);
});

/** @var Router $router */
$router = $app->getContainer()->get('router');

//Routes configuration
//API information
$app->get('/', IndexController::class.':indexAction')->setName('app.main');

//config API
$app
    ->group(
        '/config',
        function () {
            /** @var App $this */
            $this->map(['GET', 'POST'], '/', SetupController::class.':indexAction')->setName('app.config');
            $this->get('/check/{type}/', SetupController::class.':checkAction');
            $this->post('/db/', SetupController::class.':setupDBAction');
        }
    );

$app
    ->group(
        '/areas',
        function () {
            /** @var App $this */
            $this->get('/', IndexController::class.':areasAction')->setName('app.areas');

            $this
                ->group(
                    '/series',
                    function () {
                        /** @var App $this */
                        $this->get('/', SeriesController::class.':indexAction')->setName('app.series.index');
                        $this->get('/categories/', SeriesController::class.':categoriesAction')->setName('app.series.categories.index');
                        $this->get('/categories/{category}/', SeriesController::class.':categoryAction')->setName('app.series.categories.category.index');
                        $this->map(['GET', 'POST'], '/categories/{category}/entries/{series}/', SeriesController::class.':detailsAction')->setName('app.series.categories.category.entries.series');
                        $this->get('/categories/{category}/entries/{series}/seasons/', SeriesController::class.':seasonsIndexAction')->setName('app.series.categories.category.entries.series.seasons');
                        $this->get('/categories/{category}/entries/{series}/seasons/{season}/', SeriesController::class.':seasonDetailsAction')->setName('app.series.categories.category.entries.series.seasons.season');
                        $this->get('/categories/{category}/entries/{series}/seasons/{season}/episodes/', SeriesController::class.':episodesIndexAction')->setName('app.series.categories.category.entries.series.seasons.season.episodes');
                        $this->get('/categories/{category}/entries/{series}/seasons/{season}/episodes/{episode}/', SeriesController::class.':episodeDetailsAction')->setName('app.series.categories.category.entries.series.seasons.season.episodes.episode');

                        $this->post('/maintenance/', SeriesController::class.':maintenanceAction')->setName('app.series.maintenance');
                    }
                );

            $this
                ->group(
                    '/movies',
                    function () {
                        /** @var App $this */
                        $this->get('/', MovieController::class.':indexAction')->setName('app.movies.index');
                        $this->get('/categories/', MovieController::class.':categoriesAction')->setName('app.movies.categories.index');
                        $this->get('/categories/{category}/', MovieController::class.':categoryAction')->setName('app.movies.categories.category.index');
                        $this->map(['GET', 'POST'], '/categories/{category}/movies/{id}/', MovieController::class.':detailsAction')->setName('app.movies.movie_details');
                        $this->get('/categories/{category}/genres/', MovieController::class.':genresAction')->setName('app.movies.genres');
                        //TODO add /categories/{category}/genres/{genre}/
                        $this->get('/categories/{category}/collections/', MovieController::class.':collectionsAction')->setName('app.movies.collections');
                        //TODO /categories/{category}/collections/{collection}/

                        $this->post('/maintenance/', MovieController::class.':maintenanceAction')->setName('app.movies.maintenance');
                        $this->get('/lookup/{externalId}/', MovieController::class.':lookupAction')->setName('app.movies.lookup');
                    }
                );
        }
    );



try {
    $language = 'de';

    /* @var Container $container */
    $container = $app->getContainer();

    //use RequestResponseArgs strategy
    $container['foundHandler'] = function () {
        return new \Slim\Handlers\Strategies\RequestResponseArgs();
    };

    //config data
    $configModel = API\Model\ConfigModel::init();
    $dbModel = $configModel->getDbModel();

    //index
    $container[IndexController::class] = function (Container $container) {
        /** @var \Slim\Interfaces\RouterInterface $router */
        $router = $container->get('router');

        return new IndexController($router);
    };

    //TV series
    $seriesStore = new API\Service\Store\SeriesStoreDB($dbModel);
    $tTvDbWrapper = new API\Service\Api\Series\TheTvDbApiClientClient($configModel->getTtvdbApiKey());
    $seriesService = new SeriesService(
        $seriesStore,
        $tTvDbWrapper,
        $configModel->getPathShows(),
        $configModel->getAliasShows()
    );
    $container['series_service'] = $seriesService;
    $container[SeriesController::class] = function (Container $container) {
        /** @var SeriesService $seriesService */
        $seriesService = $container->get('series_service');

        return new SeriesController($seriesService);
    };

    //movies
    $movieStore = new API\Service\Store\MovieStoreDB($dbModel);
    $theMovieDbApi = new API\Service\Api\Movies\TheMoviesDbApiClientClient($configModel->getTmdbApiKey(), $language);
    $movieService = new MovieService(
        $movieStore,
        $theMovieDbApi,
        $configModel->getPathMovies(),
        $configModel->getAliasMovies()
    );
    $container['movie_service'] = $movieService;
    $container[MovieController::class] = function (Container $container) {
        /** @var MovieService $movieService */
        $movieService = $container->get('movie_service');

        return new MovieController($movieService);
    };

    //setup
    $container['setup_service'] = new SetupService([$seriesService, $movieService], [$seriesStore, $movieStore]);
    $container[SetupController::class] = function (Container $container) {
        /** @var SetupService $setupService */
        $setupService = $container->get('setup_service');

        return new SetupController($setupService);
    };

//    $routes = array_reduce($app->getContainer()->get('router')->getRoutes(), function ($target, Route $route) {
//        $target[] = sprintf(
//            '%s => (%s) %s',
//            $route->getPattern(),
//            implode('|', $route->getMethods()),
//            is_string($route->getCallable()) ? $route->getCallable() : 'closure'
//        );
//        return $target;
//    }, []);
//    print_r(implode(PHP_EOL, $routes));
//    die();

    $app->run();
} catch (\Exception $e) {
    $app->respond(new Response(500, 'Invalid Config'));
}
