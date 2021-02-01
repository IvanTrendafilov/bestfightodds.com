<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine;

require 'vendor/autoload.php';
require 'controller.php';


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


// Define named route
/*$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'profile.html', [
        'name' => $args['name']
    ]);
})->setName('profile');

// Render from string
$app->get('/hi/{name}', function ($request, $response, $args) {
    $str = $this->get('view')->fetchFromString(
        '<p>Hi, my name is {{ name }}.</p>',
        [
            'name' => $args['name']
        ]
    );
    $response->getBody()->write($str);
    return $response;
});*/


//Routes
$app->setBasePath('/cnadm');
// Render from string
$app->get('/abc', function ($request, $response, $args) 
{
    echo $this->get('view')->render('home', ['name' => 'Jonathan']);
    return $response;
});
$app->get('/', \AdminController::class . ':home');

$app->get('/manualactions', \AdminController::class . ':viewManualActions');
$app->get('/addNewEventForm', \AdminController::class . ':addNewEventForm');
$app->get('/addNewFightForm', \AdminController::class . ':addNewFightForm');
$app->get('/events[/{show}]', \AdminController::class . ':eventsOverview'); //Almost done!
$app->get('/fighters/{id}', \AdminController::class . ':viewFighter');
$app->get('/addOddsManually', \AdminController::class . ':addOddsManually');
$app->get('/clearOddsForMatchupAndBookie', \AdminController::class . ':clearOddsForMatchupAndBookie');
$app->get('/addNewPropTemplate', \AdminController::class . ':addNewPropTemplate');
$app->get('/proptemplates', \AdminController::class . ':viewPropTemplates'); //DONE!
$app->get('/resetChangeNum', \AdminController::class . ':resetChangeNum');
$app->get('/testMail', \AdminController::class . ':testMail');
$app->get('/logs[/{logfile}]', \AdminController::class . ':viewLatestLog'); //DONE!
$app->get('/alerts', \AdminController::class . ':viewAlerts'); //DONE!


// Run app
$app->run();