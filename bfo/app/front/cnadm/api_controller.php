<?php 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

require_once 'config/inc.config.php';
require_once 'lib/bfocore/general/class.ScheduleHandler.php';
require_once 'lib/bfocore/general/class.EventHandler.php';
require_once 'lib/bfocore/general/class.OddsHandler.php';
require_once 'lib/bfocore/general/class.BookieHandler.php';
require_once 'lib/bfocore/general/class.FighterHandler.php';
require_once 'lib/bfocore/general/class.TwitterHandler.php';
require_once 'lib/bfocore/general/class.Alerter.php';

class AdminAPIController
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

    /**
     * Adds a new matchup
     * 
     * Input (JSON payload):
     * (int) event_id
     * (int) team1
     * (int) team2
     */
    public function addNewMatchup(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!isset($data->event_id, $data->team1_name, $data->team2_name) 
            || (int) $data->event_id <= 0 || (string) $data->team1_name == '' || (string) $data->team2_name == '')
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        }
        else
        {
            $fight = new Fight(0, $data->team1_name, $data->team2_name, $data->event_id);
            if ($matchup_id = EventHandler::addNewFight($fight))
            {
                $return_data['msg'] = 'Successfully added';
                $return_data['matchup_id'] = $matchup_id;
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Error creating matchup';
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function updateMatchup(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!isset($data->matchup_id) 
            || (int) $data->matchup_id <= 0)
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        }
        else
        {
            if (isset($data->is_main_event) && is_bool(boolval($data->is_main_event)))
            {
                //Flip main even status
                if (EventHandler::setFightAsMainEvent($data->matchup_id, $data->is_main_event))
                {
                    $return_data['msg'] = 'Successfully switched matchup to main event';
                    $return_data['matchup_id'] = $data->matchup_id;
                    $return_data['is_main_event'] = $data->is_main_event;
                }
                else
                {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error updating matchup';
                    $return_data['error'] = true;
                }
            }
            else if (isset($data->event_id))
            {
                //Update matchup event
                if (EventHandler::changeFight($data->matchup_id, $data->event_id))
                {
                    $return_data['msg'] = 'Successfully updated';
                    $return_data['matchup_id'] = $data->matchup_id;
                }
                else
                {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error updating matchup';
                    $return_data['error'] = true;
                }
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function addNewEvent(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!v::stringVal()->length(3, null)->validate($data->event_name)
            || !v::date('Y-m-d')->validate($data->event_date)
            || !v::boolVal()->validate($data->event_hidden))
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        }
        else
        {
            $event = new Event(0, $data->event_date, $data->event_name, !$data->event_hidden);
            if (($event_id = EventHandler::addNewEvent($event)))
            {
                $return_data['msg'] = 'Successfully added';
                $return_data['event_id'] = $event_id;
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Error creating event';
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function resetChangeNum(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (v::intType()->validate($data->bookie_id))
        {
            //Reset specific
            if (BookieHandler::resetChangeNum($data->bookie_id))
            {
                $return_data['msg'] = 'Successfully reset changenum for ' . $data->bookie_id;
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Failed to reset changenum for parser ' . $data->bookie_id;
                $return_data['error'] = true;
            }
        }
        else
        {
            //Reset all
            if (BookieHandler::resetAllChangeNums())
            {
                $return_data['msg'] = 'Successfully reset all changenums';
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Failed to reset all changenums';
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    private function returnJson($response)
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

}