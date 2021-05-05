<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Middleware\SessionCookie;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine;

require 'vendor/autoload.php';
require 'app/admin/controllers/admin_controller.php';
require 'app/admin/controllers/admin_api_controller.php';
require 'app/admin/middleware/auth_middleware.php';
require 'app/admin/middleware/session_middleware.php';

$container = (new \DI\ContainerBuilder())
  ->useAutowiring(true)
  ->addDefinitions([
    \League\Plates\Engine::class => function () {
      return new League\Plates\Engine(__DIR__ . '/../../../../shared/app/admin/templates/');
    }
  ])
  ->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->add(SessionMiddleware::class);

$app->setBasePath('/cnadm');

//Login/logout
$app->get('/lin', \AdminController::class . ':loginPage');
$app->post('/lin', \AdminController::class . ':login');
$app->get('/logout', \AdminController::class . ':logout');

//Page routes
$app->group('', function (RouteCollectorProxy $group) {
  $group->get('[/]', \AdminController::class . ':home');
  $group->get('/manualactions', \AdminController::class . ':viewManualActions');
  $group->get('/newmatchup', \AdminController::class . ':createMatchup');
  $group->get('/events[/{show}]', \AdminController::class . ':eventsOverview');
  $group->get('/fighters/{id}', \AdminController::class . ':viewFighter');
  $group->get('/addOddsManually', \AdminController::class . ':addOddsManually');
  $group->get('/clearOddsForMatchupAndBookie', \AdminController::class . ':clearOddsForMatchupAndBookie');
  $group->get('/proptemplate', \AdminController::class . ':addNewPropTemplate');
  $group->get('/proptemplates', \AdminController::class . ':viewPropTemplates');
  $group->get('/resetchangenums', \AdminController::class . ':resetChangeNums');
  $group->get('/testMail', \AdminController::class . ':testMail');
  $group->get('/logs[/{logfile}]', \AdminController::class . ':viewLatestLog');
  $group->get('/parserlogs[/{bookie_name}]', \AdminController::class . ':viewParserLogs');
  $group->get('/alerts', \AdminController::class . ':viewAlerts');
  $group->get('/matchups/{id}', \AdminController::class . ':viewMatchup');
  $group->get('/propcorrelation', \AdminController::class . ':createPropCorrelation');
  $group->get('/flagged', \AdminController::class . ':viewFlaggedOdds');
  $group->get('/parser_status', \AdminController::class . ':viewParserStatus');
  $group->get('/changeauditlog', \AdminController::class . ':viewChangeAuditLog');
  $group->get('/legacylog', \AdminController::class . ':viewLegacyLog');
  
})->add(new AuthMiddleware());



//API Routes
$app->group('/api', function (RouteCollectorProxy $group) {

  $group->post('/matchups', \AdminAPIController::class . ':createMatchup');
  $group->put('/matchups/{id}', \AdminAPIController::class . ':updateMatchup');
  $group->delete('/matchups/{id}', \AdminAPIController::class . ':deleteMatchup');

  $group->post('/events', \AdminAPIController::class . ':createEvent');
  $group->put('/events/{id}', \AdminAPIController::class . ':updateEvent');
  $group->delete('/events/{id}', \AdminAPIController::class . ':deleteEvent');

  $group->put('/fighters/{id}', \AdminAPIController::class . ':updateFighter');

  $group->post('/resetchangenums', \AdminAPIController::class . ':resetChangeNum');
  $group->post('/clearunmatched', \AdminAPIController::class . ':clearUnmatched');
  $group->post('/proptemplates', \AdminAPIController::class . ':createPropTemplate');
  $group->post('/propcorrelation', \AdminAPIController::class . ':createPropCorrelation');
  $group->delete('/manualactions/{id}', \AdminAPIController::class . ':deleteManualAction');

  $group->delete('/odds', \AdminAPIController::class . ':deleteOdds');
});
$app->run();
