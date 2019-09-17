<?php

set_time_limit(900);
require "vendor/autoload.php";
require "vendor/james-heinrich/getid3/getid3/getid3.php";

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
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

//Routes configuration
$app->get('/', function (Request $request, Response $response) {
    return $response->withJson([
        'data' => [
            'message' => 'nothing to see here',
        ],
    ]);
});

$app
    ->group(
        '/config',
        function () {
            $this->map(['GET', 'POST'], '/', SetupController::class.':indexAction');

            $this->get('/check/{type}/', SetupController::class.':checkAction');

            $this->post('/db/', SetupController::class.':setupDBAction');
        }
    );

$app
    ->group(
        '/categories',
        function () {
            $this->get('/', CategoryController::class.':indexAction');
        }
    );

$app
    ->group(
        '/shows',
        function () {
            $this->post('/maintenance/', ShowController::class.':maintenanceAction');

            $this->get('/{category}/', ShowController::class.':indexAction');
            $this->get('/{category}/episodes/{episode}/', ShowController::class.':episodesAction');
            $this->get('/{category}/{show}/', ShowController::class.':detailsAction');
            $this->post('/{category}/edit/{show}/', ShowController::class.':editAction');
        }
    );

$app
    ->group(
        '/movies',
        function () {
            $this->post('/maintenance/', MovieController::class.':maintenanceAction');

            $this->get('/lookup/{externalId}/', MovieController::class.':lookupAction');

            $this->get('/{category}/', MovieController::class.':indexAction');
            $this->get('/{category}/genres/', MovieController::class.':genresAction');
            $this->get('/{category}/compilations/', MovieController::class.':compilationsAction');

            $this->get('/{category}/{id}/', MovieController::class.':detailsAction');
            $this->post('/{category}/{id}/', MovieController::class.':editAction');
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

    $app->run();
} catch (API\Exception\InvalidDataException $e) {
    $app->respond(new Response(500, 'Invalid Config'));
}
