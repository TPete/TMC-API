<?php

set_time_limit(900);
require "vendor/autoload.php";
require "vendor/james-heinrich/getid3/getid3/getid3.php";

use Slim\Http\Request;
use Slim\Http\Response;
use TinyMediaCenter\API;
use TinyMediaCenter\API\Controller\Category\MovieController;
use TinyMediaCenter\API\Controller\Category\ShowController;
use TinyMediaCenter\API\Controller\CategoryController;
use TinyMediaCenter\API\Controller\SetupController;
use TinyMediaCenter\API\Service\MovieService;
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
$app->get('/', function () {
    echo "nothing to see here";
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

            $this->get('/lookup/{id}/', MovieController::class.':lookupAction');

            $this->get('/{category}/', MovieController::class.':indexAction');
            $this->get('/{category}/genres/', MovieController::class.':genresAction');
            $this->get('/{category}/compilations/', MovieController::class.':compilationsAction');

            $this->get('/{category}/{id}/', MovieController::class.':detailsAction');
            $this->post('/{category}/{id}/', MovieController::class.':editAction');
        }
    );

try {
    /* @var \Psr\Container\ContainerInterface $container */
    $container = $app->getContainer();

    //use RequestResponseArgs strategy
    $container['foundHandler'] = function () {
        return new \Slim\Handlers\Strategies\RequestResponseArgs();
    };

    $configModel = API\Model\ConfigModel::init();
    $dbModel = $configModel->getDbModel();

    $container['db'] = $dbModel;

    $showService = new ShowService(
        $configModel->getPathShows(),
        $configModel->getAliasShows(),
        $dbModel,
        $configModel->getTtvdbApiKey()
    );
    $container['show_service'] = $showService;

    $movieService = new MovieService(
        $configModel->getPathMovies(),
        $configModel->getAliasMovies(),
        $dbModel,
        $configModel->getTmdbApiKey()
    );
    $container['movie_service'] = $movieService;

    $app->run();
} catch (API\Exception\InvalidDataException $e) {
    $app->respond(new Response(500, 'Invalid Config'));
}
