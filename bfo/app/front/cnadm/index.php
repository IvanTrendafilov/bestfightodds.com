<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use League\Plates\Engine;


require_once 'vendor/autoload.php';

// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Set view in Container
$container->set('view', function() {
    //return Engine::create(__DIR__ . '/templates/');
    //League\Plates\Engine::create('/path/to/templates', 'phtml');
    return new League\Plates\Engine(__DIR__ . '/templates/');
});

// Create new Plates engine
//$templates = new League\Plates\Engine(__DIR__ . '/templates');
// Add any additional folders
//$templates->addFolder('emails', '/path/to/emails');
// Load any additional extensions
//$templates->loadExtension(new League\Plates\Extension\Asset('/path/to/public'));
// Create a new template
//$template = $templates->make('emails::welcome');




// Create App
$app = AppFactory::create();
$app->setBasePath('/cnadm');

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


// Render from string
$app->get('/', function ($request, $response, $args) 
{
    echo $this->get('view')->render('profile', ['name' => 'Jonathan']);
    return $response;
});

// Run app
$app->run();