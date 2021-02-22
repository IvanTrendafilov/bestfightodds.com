<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once 'config/inc.config.php'; //TODO: Required?
require_once 'lib/bfocore/general/class.EventHandler.php';
require_once 'lib/bfocore/general/class.BookieHandler.php';
require_once 'lib/bfocore/general/class.FighterHandler.php';

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

    public function search(Request $request, Response $response)
    {
        $view_data = [];

        $search_query = $request->getQueryParams()['query'] ?? '';
        $view_data['search_query'] = $search_query;

        if (strlen($search_query) >= 3)
        {
            $teams = FighterHandler::searchFighter($search_query);
            $events = EventHandler::searchEvent($search_query);
            if ($teams != null || $events != null)
            {
                //If we only get one result we will redirect to that page right away
                if ((count($teams) + count($events)) == 1)
                {
                    if (count($teams) == 1)
                    {
                        return $response
                            ->withHeader('Location', '/fighters/' . $teams[0]->getFighterAsLinkString())
                            ->withStatus(302);
                    }
                    else
                    {
                        return $response
                            ->withHeader('Location', '/events/' . $events[0]->getEventAsLinkString())
                            ->withStatus(302);
                    }
                }
                else if (count($teams) + count($events) > 1)
                {
                    //Reduce teams lists if exceeding 25
                    $view_data['teams_totalsize'] = count($teams);
                    if (count($teams) > 25)
                    {
                        $teams = array_slice($teams, 0, 25);
                    }
                    //Reduce events lists if exceeding 25
                    $view_data['events_totalsize'] = count($events);
                    if (count($events) > 25)
                    {
                        $events = array_slice($events, 0, 25);
                    }
                }
            }
            $view_data['teams_results'] = $teams ?? [];
            $view_data['events_results'] = $events ?? [];

        }

        $response->getBody()->write($this->plates->render('searchresults', $view_data));
        return $response;
    }

}