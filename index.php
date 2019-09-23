<?php

set_time_limit(900);
require "vendor/autoload.php";
require "vendor/james-heinrich/getid3/getid3/getid3.php";

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;
use Slim\Router;
use TinyMediaCenter\API;
use TinyMediaCenter\API\Controller\Category\MovieController;
use TinyMediaCenter\API\Controller\Category\ShowController;
use TinyMediaCenter\API\Controller\CategoryController;
use TinyMediaCenter\API\Controller\SetupController;
use TinyMediaCenter\API\Service\MovieService;
use TinyMediaCenter\API\Service\SetupService;
use TinyMediaCenter\API\Service\ShowService;

$app = new Slim\App();

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
$app->get('/', function (Request $request, Response $response) use ($router) {
    return $response->withJson([
        'meta' => [
            'title' => 'TMC API',
            'version' => '2.0',
        ],
        'links' => [
            'self' => $router->pathFor('app.main'),
            'config' => [
                'href' => $router->pathFor('app.config'),
                'meta' => [
                    'description' => 'Application setup',
                ],
            ],
            'categories' => [
                'href' => $router->pathFor('app.categories'),
                'meta' => [
                    'description' => 'Content categories',
                ],
            ]
        ],
    ]);
})->setName('app.main');

$app
    ->group(
        '/config',
        function () {
            $this->map(['GET', 'POST'], '/', SetupController::class.':indexAction')->setName('app.config');

            $this->get('/check/{type}/', SetupController::class.':checkAction');

            $this->post('/db/', SetupController::class.':setupDBAction');
        }
    );

$app
    ->group(
        '/categories',
        function () {
            $this->get('/', CategoryController::class.':indexAction')->setName('app.categories');
        }
    );

$app
    ->group(
        '/shows',
        function () {

            $this->post('/maintenance/', ShowController::class.':maintenanceAction')->setName('app.shows.maintenance');

            $this->get('/categories/{category}/', ShowController::class.':indexAction');
            $this->get('/categories/{category}/shows/{show}/', ShowController::class.':detailsAction');
            $this->post('/categories/{category}/shows/{show}/', ShowController::class.':editAction');
            $this->get('/categories/{category}/shows/{show}/episodes/{episode}/', ShowController::class.':episodesAction');
        }
    );

$app
    ->group(
        '/movies',
        function () {
            $this->post('/maintenance/', MovieController::class.':maintenanceAction')->setName('app.movies.maintenance');
            $this->get('/lookup/{externalId}/', MovieController::class.':lookupAction');

            $this->get('/categories/{category}/', MovieController::class.':indexAction');
            $this->get('/categories/{category}/movies/{id}/', MovieController::class.':detailsAction');
            $this->post('/categories/{category}/movies/{id}/', MovieController::class.':editAction');
            $this->get('/categories/{category}/genres/', MovieController::class.':genresAction');
            $this->get('/categories/{category}/compilations/', MovieController::class.':compilationsAction');
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

    //shows
    $showStore = new API\Service\Store\ShowStoreDB($dbModel);
    $tTvDbWrapper = new API\Service\MediaLibrary\TTVDBWrapper($configModel->getTtvdbApiKey());
    $showService = new ShowService(
        $showStore,
        $tTvDbWrapper,
        $configModel->getPathShows(),
        $configModel->getAliasShows()
    );
    $container['show_service'] = $showService;
    $container[ShowController::class] = function (Container $container) {
        /** @var ShowService $showService */
        $showService = $container->get('show_service');

        return new ShowController($showService);
    };

    //movies
    $movieStore = new API\Service\Store\MovieStoreDB($dbModel);
    $theMovieDbApi = new API\Service\Api\Movie\TheMovieDbApi($configModel->getTmdbApiKey(), $language);
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

    //categories
    $container[CategoryController::class] = function (Container $container) {
        /** @var ShowService $showService */
        $showService = $container->get('show_service');
        /** @var MovieService $movieService */
        $movieService = $container->get('movie_service');

        return new CategoryController($showService, $movieService);
    };

    //setup
    $setupService = new SetupService($showService, $movieService, $showStore, $movieStore);
    $container['setup_service'] = $setupService;
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
} catch (API\Exception\InvalidDataException $e) {
    $app->respond(new Response(500, 'Invalid Config'));
}
