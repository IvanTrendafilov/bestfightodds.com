<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once 'config/inc.config.php'; //TODO: Required?
require_once 'lib/bfocore/general/class.EventHandler.php';
require_once 'lib/bfocore/general/class.BookieHandler.php';

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

    public function terms(Request $request, Response $response)
    {
        $response->getBody()->write($this->plates->render('terms', []));
        return $response;
    }

    public function archive(Request $request, Response $response)
    {
        $view_data = ['recent_events' => EventHandler::getRecentEvents(20, 0)];
        $response->getBody()->write($this->plates->render('archive', $view_data));
        return $response;
    }

    public function alerts(Request $request, Response $response)
    {
        $view_data = [];
        $view_events = [];

        $events = EventHandler::getAllUpcomingEvents();
        foreach ($events as $event)
        {
            $matchups = EventHandler::getAllFightsForEventWithoutOdds($event->getID());
            if (count($matchups) > 0) //Only add the event if matchups were found
            {
                //If non bellator, ufc or future events we limit to just the main event (first fight)
                if (substr(strtoupper($event->getName()), 0, 3) != 'UFC' 
                    && substr(strtoupper($event->getName()), 0, 8) != 'BELLATOR' 
                    && substr(strtoupper($event->getName()), 0, 13) != 'FUTURE EVENTS')
                {
                    $view_events[] = ['event_obj' => $event, 'matchups' => [$matchups[0]]];
                }
                else
                {
                    $view_events[] = ['event_obj' => $event, 'matchups' => $matchups];
                }
            }
        }
        $view_data['events'] = $view_events;
        $view_data['bookies'] = BookieHandler::getAllBookies();

        //Add view data that contains the users email (populated when previously creating an alert and stored in cookie)
        $cookies = $request->getCookieParams();
        $view_data['in_alertmail'] = $cookies['bfo_alertmail'] ?? '';

        $response->getBody()->write($this->plates->render('alerts', $view_data));
        return $response;
    }

}