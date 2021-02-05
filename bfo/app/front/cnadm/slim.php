<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine;

require 'vendor/autoload.php';
require 'controller.php';
require 'api_controller.php';

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

$app->setBasePath('/cnadm');

//Page routes
$app->get('/', \AdminController::class . ':home');
$app->get('/manualactions', \AdminController::class . ':viewManualActions');
$app->get('/addNewFightForm', \AdminController::class . ':addNewFightForm');
$app->get('/events[/{show}]', \AdminController::class . ':eventsOverview'); //Almost done!
$app->get('/addNewEventForm', \AdminController::class . ':addNewEventForm');
$app->get('/fighters/{id}', \AdminController::class . ':viewFighter');
$app->get('/addOddsManually', \AdminController::class . ':addOddsManually');
$app->get('/clearOddsForMatchupAndBookie', \AdminController::class . ':clearOddsForMatchupAndBookie');
$app->get('/addNewPropTemplate', \AdminController::class . ':addNewPropTemplate');
$app->get('/proptemplates', \AdminController::class . ':viewPropTemplates'); //DONE!
$app->get('/resetChangeNum', \AdminController::class . ':resetChangeNum');
$app->get('/testMail', \AdminController::class . ':testMail');
$app->get('/logs[/{logfile}]', \AdminController::class . ':viewLatestLog'); //DONE!
$app->get('/alerts', \AdminController::class . ':viewAlerts'); //DONE!
$app->get('/matchups/{id}', \AdminController::class . ':viewMatchup'); //DONE!
$app->get('/propcorrelation', \AdminController::class . ':addNewPropCorrelation');

//API Routes
$app->post('/api/matchups', \AdminAPIController::class . ':addNewMatchup'); //DONE!
$app->put('/api/matchups/{id}', \AdminAPIController::class . ':updateMatchup'); //DONE!
$app->post('/api/events', \AdminAPIController::class . ':addNewEvent');

$app->run();