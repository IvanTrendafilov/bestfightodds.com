<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once 'config/inc.config.php'; //TODO: Required?

class MainController
{
    private $plates;

    // constructor receives container instance
    public function __construct(\League\Plates\Engine $plates)
    {
        $this->plates = $plates;
    }

    public function __invoke(Request $request, Response $response)
    {
        return $response;
    }

    public function home(Request $request, Response $response)
    {
        $view_data = [];

        $response->getBody()->write($this->plates->render('home', $view_data));
        return $response;
    }

}