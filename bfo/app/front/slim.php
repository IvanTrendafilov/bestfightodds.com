<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use League\Plates\Engine;

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

//$app->setBasePath('/cnadm');

$app->get('[/]', \MainController::class . ':home');
$app->get('/alerts', \MainController::class . ':alerts');
$app->get('/terms', \MainController::class . ':terms');
$app->get('/archive', \MainController::class . ':archive');
$app->get('/search', \MainController::class . ':search');
$app->get('/links', \MainController::class . ':widget');
$app->get('/fighters/{id}', \MainController::class . ':viewTeam');
$app->get('/events2/{id}', \MainController::class . ':viewEvent');


//Page routes
/*$app->get('[/]', \AdminController::class . ':home');
$app->get('/manualactions', \AdminController::class . ':viewManualActions');
$app->get('/newmatchup', \AdminController::class . ':createMatchup');
$app->get('/events[/{show}]', \AdminController::class . ':eventsOverview');
$app->get('/fighters/{id}', \AdminController::class . ':viewFighter');
$app->get('/addOddsManually', \AdminController::class . ':addOddsManually');
$app->get('/clearOddsForMatchupAndBookie', \AdminController::class . ':clearOddsForMatchupAndBookie');
$app->get('/proptemplate', \AdminController::class . ':addNewPropTemplate');
$app->get('/proptemplates', \AdminController::class . ':viewPropTemplates');
$app->get('/resetchangenums', \AdminController::class . ':resetChangeNums');
$app->get('/testMail', \AdminController::class . ':testMail');
$app->get('/logs[/{logfile}]', \AdminController::class . ':viewLatestLog');
$app->get('/alerts', \AdminController::class . ':viewAlerts');
$app->get('/matchups/{id}', \AdminController::class . ':viewMatchup');
$app->get('/propcorrelation', \AdminController::class . ':createPropCorrelation');
$app->get('/odds', \AdminController::class . ':oddsOverview');

//API Routes
$app->post('/api/matchups', \AdminAPIController::class . ':createMatchup');
$app->put('/api/matchups/{id}', \AdminAPIController::class . ':updateMatchup');
$app->delete('/api/matchups/{id}', \AdminAPIController::class . ':deleteMatchup');
$app->post('/api/events', \AdminAPIController::class . ':createEvent');
$app->put('/api/events/{id}', \AdminAPIController::class . ':updateEvent');
$app->delete('/api/events/{id}', \AdminAPIController::class . ':deleteEvent');
$app->put('/api/fighters/{id}', \AdminAPIController::class . ':updateFighter');


$app->post('/api/resetchangenums', \AdminAPIController::class . ':resetChangeNum');
$app->post('/api/clearunmatched', \AdminAPIController::class . ':clearUnmatched');
$app->post('/api/proptemplates', \AdminAPIController::class . ':createPropTemplate');
$app->post('/api/propcorrelation', \AdminAPIController::class . ':createPropCorrelation');
$app->delete('/api/manualactions/{id}', \AdminAPIController::class . ':deleteManualAction');*/

$app->run();