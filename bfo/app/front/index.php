<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Factory\StreamFactory;
//use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use League\Plates\Engine;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

require_once __DIR__ . "/../../bootstrap.php";
require __DIR__ . "/../../../shared/app/front/controllers/controller.php";
require __DIR__ . "/../../../shared/app/front/controllers/api_controller.php";

$container = (new \DI\ContainerBuilder())
  ->useAutowiring(true)
  ->addDefinitions([
    \League\Plates\Engine::class => function () {
      return new League\Plates\Engine(__DIR__ . '/templates/');
    }
  ])
  ->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

//Add minify middleware
$app->add(function (Request $request, RequestHandler $handler) {
  $response = $handler->handle($request);
  $response = $response->withHeader('Cache-Control', 'no-cache, public, must-revalidate, proxy-revalidate');
  $response = $response->withHeader('Expires', 'Mon, 12 Jul 1996 04:11:00 GMT');
  $response = $response->withHeader('Pragma', 'no-cache');
  return $response;
});

// Add Routing Middleware
$app->addRoutingMiddleware();

// Define Custom Error Handler
$customErrorHandler = function (
  ServerRequestInterface $request,
  Throwable $exception,
  bool $displayErrorDetails,
  bool $logErrors,
  bool $logErrorDetails,
  ?LoggerInterface $logger = null
) use ($app) {
  $response = $app->getResponseFactory()->createResponse();
  $response->getBody()->write('Error ' . $exception->getMessage() . $exception->getCode());
  return $response;
};

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true, null);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

//Page routes
$app->group('', function (RouteCollectorProxy $group) {
  $group->get('[/]', \MainController::class . ':home');
  $group->get('/alerts', \MainController::class . ':alerts');
  $group->get('/terms', \MainController::class . ':terms');
  $group->get('/archive', \MainController::class . ':archive');
  $group->get('/search', \MainController::class . ':search');
  $group->get('/links', \MainController::class . ':widget');
  $group->get('/fighters/{id}', \MainController::class . ':viewTeam');
  $group->get('/events/{id}', \MainController::class . ':viewEvent');
});

//API Routes
$app->group('/api', function (RouteCollectorProxy $group) {
  $group->get('/ggd', \APIController::class . ':getGraphData');
  $group->post('/aa', \APIController::class . ':addAlert');
});

$app->run();
