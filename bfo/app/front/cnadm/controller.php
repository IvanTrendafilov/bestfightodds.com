<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


require_once 'config/inc.config.php';
require_once 'lib/bfocore/general/class.ScheduleHandler.php';
require_once 'lib/bfocore/general/class.EventHandler.php';
require_once 'lib/bfocore/general/class.OddsHandler.php';
require_once 'lib/bfocore/general/class.BookieHandler.php';
require_once 'lib/bfocore/general/class.FighterHandler.php';
require_once 'lib/bfocore/general/class.TwitterHandler.php';
require_once 'lib/bfocore/general/class.Alerter.php';

class AdminController
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
        $response->getBody()->write($this->plates->render('home'));
        return $response;
    }

    public function test(Request $request, Response $response)
    {
        echo $this->plates->render('home', ['name' => 'Jonathan']);
        return $response;
    }

    public function viewManualActions(Request $request, Response $response)
    {
        $aManualActions = ScheduleHandler::getAllManualActions();

        return $response;
    }

    public function addNewEventForm(Request $request, Response $response)
    {
        return $response;
    }

    public function addNewFightForm(Request $request, Response $response)
    {
        return $response;
    }

    public function eventsOverview(Request $request, Response $response, array $args)
    {
        $view_data = ['events' => []];
        $events = null;
        if (isset($args['show']) && $args['show'] == 'all')
        {
            $events = EventHandler::getAllEvents();
        }
        else
        {
            $events = EventHandler::getAllUpcomingEvents();
        }

        foreach ($events as $event)
        {
            $fights = EventHandler::getAllFightsForEvent($event->getID(), false);
            $event_view = [];
            foreach ($fights as $fight)
            {
                $arbitrage_info = Alerter::getArbitrageInfo($fight->getID(), 100);
                $fight_view = ['arbitrage_info' => $arbitrage_info];

                $event_view[] = ['fight_obj' => $fight, 'arbitrage_info' => $arbitrage_info];
            }
            $view_data['events'][] = ['event_obj' => $event, 'fights' => $event_view];
        }

        $response->getBody()->write($this->plates->render('events', $view_data));
        return $response;
    }

    public function viewFighter(Request $request, Response $response, array $args)
    {
        if (isset($args['id']))
        {
            $fighter = FighterHandler::getFighterByID($args['id']);
            $twitter_handle = TwitterHandler::getTwitterHandle($args['id']);
            
            $view_data = ['fighter_obj' => $fighter, 'twitter_handle' => $twitter_handle];
            $response->getBody()->write($this->plates->render('fighters', $view_data));
            return $response;
        }

        return $response;
    }

    public function addOddsManually(Request $request, Response $response)
    {
        return $response;
    }

    public function clearOddsForMatchupAndBookie(Request $request, Response $response)
    {
        return $response;
    }

    public function addNewPropTemplate(Request $request, Response $response)
    {
        return $response;
    }

    public function viewPropTemplates(Request $request, Response $response)
    {
        $view_data = ['bookies' => []];
        $bookies = BookieHandler::getAllBookies();
        foreach ($bookies as $bookie)
        {
            $templates = BookieHandler::getPropTemplatesForBookie($bookie->getID());
            $view_data['bookies'][] = ['bookie' => $bookie, 'templates' => $templates];
        }
        $response->getBody()->write($this->plates->render('proptemplates', $view_data));
        return $response;
    }

    public function resetChangeNum(Request $request, Response $response)
    {
        return $response;
    }

    public function testMail(Request $request, Response $response)
    {
        return $response;
    }

    public function viewLatestLog(Request $request, Response $response, array $args)
    {
        $view_data = [];
        if (isset($args['logfile']))
        {
            $logfile = $args['logfile'] == 'latest' ? scandir(PARSE_LOGDIR, SCANDIR_SORT_DESCENDING)[0] : $args['logfile'];
            $log_contents =  file_get_contents(PARSE_LOGDIR . '/' . $logfile);
            $view_data = ['log_contents' => $log_contents];
        }
        else
        {
            //List all available log files
            $logdir = opendir(PARSE_LOGDIR);
            $files = [];
            while ($file = readdir($logdir))
            {
                if (substr($file, 0, 1) != ".")
                {
                    $files[] = $file;
                }
            }
            sort($files);
            $view_data['logs'] = $files;
        }

        $response->getBody()->write($this->plates->render('logs', $view_data));
        return $response;
    }

    public function viewAlerts(Request $request, Response $response, array $args)
    {
        $view_data['alerts'] = [];
        $alerts = Alerter::getAllAlerts();
        foreach ($alerts as $alert)
        {
            $fight = EventHandler::getFightByID($alert->getFightID());
            $view_data['alerts'][] = ['alert_obj' => $alert, 'fight_obj' => $fight];
        }
        $response->getBody()->write($this->plates->render('alerts', $view_data));
        return $response;
    }
}