<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
//use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use League\Plates\Engine;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

require 'vendor/autoload.php';
require 'controllers/controller.php';
require 'controllers/api_controller.php';

$container = (new \DI\ContainerBuilder())
  ->useAutowiring(true)
  ->addDefinitions([
    \League\Plates\Engine::class => function(){
        return new League\Plates\Engine(__DIR__ . '/templates/');
    }
])
  ->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

//Add minify middleware
$app->add(function (Request $request, RequestHandler $handler) {
  $response = $handler->handle($request);
  $data = $response->getBody();
  $minified = preg_replace('/\>\s+\</m', '><', $data);
  return $response->withBody((new StreamFactory())->createStream($minified));
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
    $response->getBody()->write('Error ' . $exception->getCode());
    return $response;
};

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true, null);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$app->get('[/]', \MainController::class . ':home');
$app->get('/alerts', \MainController::class . ':alerts');
$app->get('/terms', \MainController::class . ':terms');
$app->get('/archive', \MainController::class . ':archive');
$app->get('/search', \MainController::class . ':search');
$app->get('/links', \MainController::class . ':widget');
$app->get('/fighters/{id}', \MainController::class . ':viewTeam');
$app->get('/events/{id}', \MainController::class . ':viewEvent');

$app->run();