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
require_once 'lib/bfocore/utils/class.OddsTools.php';

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
    public function createMatchup(Request $request, Response $response)
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

        if (!v::intType()->validate($data->matchup_id))
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

    public function deleteMatchup(Request $request, Response $response)
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
            $result = EventHandler::removeFight($data->matchup_id);
            $return_data['msg'] = 'Successfully deleted matchup ' . $data->matchup_id;
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function deleteEvent(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!isset($data->event_id) 
            || (int) $data->event_id <= 0)
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        }
        else
        {
            $result = EventHandler::removeEvent($data->event_id);
            $return_data['msg'] = 'Successfully deleted event ' . $data->event_id;
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function createEvent(Request $request, Response $response)
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

    public function updateEvent(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!v::intType()->validate($data->event_id)) 
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        }
        else
        {
            //Update matchup event
            if (EventHandler::changeEvent($data->event_id, $data->event_name ?? '', $data->event_date ?? '', $data->event_display ?? null))
            {
                $return_data['msg'] = 'Successfully updated';
                $return_data['event_id'] = $data->event_id;
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Error updating matchup';
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function updateFighter(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;
        $return_data['msg'] = '';

        if (!v::intType()->validate($data->fighter_id))
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        }
        else
        {
            //Check for twitter handle update
            if (v::alnum()->noWhitespace()->length(4, null)->validate(@$data->twitter_handle))
            {
                $result = TwitterHandler::addTwitterHandle($data->fighter_id, $data->twitter_handle);
                echo $result;
                exit;
                if (TwitterHandler::addTwitterHandle($data->fighter_id, $data->twitter_handle))
                {
                    $return_data['msg'] = 'Successfully updated twitter Handle. ';
                    $return_data['fighter_id'] = $data->fighter_id;
                }
                else
                {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error updating twitter handle';
                    $return_data['error'] = true;
                }
            }

            //Check for alt name update (if so just add it to the bunch)
            if (v::alnum(' ')->length(5, null)->validate(@$data->alt_name))
            {
                if (EventHandler::addFighterAltName($data->fighter_id, $data->alt_name))
                {
                    $return_data['msg'] .= 'Successfully updated altname.';
                    $return_data['fighter_id'] = $data->fighter_id;
                }
                else
                {
                    $response->withStatus(500);
                    $return_data['msg'] .= 'Error updating altname';
                    $return_data['error'] = true;
                }
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function createPropTemplate(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!v::stringVal()->length(10, null)->validate($data->proptemplate)
            || !v::stringVal()->length(10, null)->validate($data->negproptemplate)
            || !v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->proptype_id)
            || !v::intType()->validate($data->fieldstype_id))
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        }
        else
        {
            $template = new PropTemplate(0, $data->bookie_id, $data->proptemplate, $data->negproptemplate, $data->proptype_id, $data->fieldstype_id, '');
            $new_template_id = BookieHandler::addNewPropTemplate($template);
            if ($new_template_id)
            {
                $return_data['msg'] = 'Successfully added';
                $return_data['proptemplate_id'] = $new_template_id;
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Error creating prop template';
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);

    }

    public function createPropCorrelation(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!v::stringVal()->length(10, null)->validate($data->correlation)
            || !v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->matchup_id))
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        }
        else
        {
            if (OddsHandler::storeCorrelations($data->bookie_id, [['correlation' => $data->correlation, 'matchup_id' => $data->matchup_id]]))
            {
                $return_data['msg'] = 'Successfully added correlation';
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Error creating prop correlation';
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

        if (v::intType()->validate(@$data->bookie_id))
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

    public function clearUnmatched(Request $request, Response $response)
    {
        $return_data = [];
        $return_data['error'] = false;

        if (EventHandler::clearUnmatched())
        {
            $return_data['msg'] = 'Successfully cleared all unmatched';
        }
        else
        {
            $response->withStatus(500);
            $return_data['msg'] = 'Failed to clear unmatched';
            $return_data['error'] = true;
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    private function returnJson($response)
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteManualAction(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!isset($data->action_id) 
            || (int) $data->action_id <= 0)
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        }
        else
        {
            $result = ScheduleHandler::clearManualAction($data->action_id);
            if ($result && $result > 0)
            {
                $return_data['msg'] = 'Successfully deleted action ' . $data->action_id;
            }
            else
            {
                $response->withStatus(500);
                $return_data['msg'] = 'Failed to delete action with ID ' . $data->action_id;
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function createOdds(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (!v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->matchup_id)
            || !v::stringVal()->length(2, null)->validate($data->team1_odds)
            || !v::stringVal()->length(2, null)->validate($data->team2_odds)
            || !OddsTools::checkCorrectOdds($data->team1_odds) 
            || !OddsTools::checkCorrectOdds($data->team2_odds))
        {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        }
        else
        {
            $new_odds = new FightOdds($data->matchup_id, $data->bookie_id, $data->team1_odds, $data->team2_odds, OddsTools::standardizeDate(date('Y-m-d')));
            if (EventHandler::checkMatchingOdds($new_odds))
            {
                $return_data['msg'] = 'Odds have not changed (' . $data->team1_odds . '/' . $data->team2_odds . ')';
            }
            else
            {
                if (EventHandler::addNewFightOdds($new_odds))
                {
                    $return_data['msg'] = 'Successfully added odds: (' . $data->team1_odds . '/' . $data->team2_odds . ')';
                }
                else
                {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error adding odds: (' . $data->team1_odds . '/' . $data->team2_odds . ')';
                    $return_data['error'] = true;
                }
            }

            $return_data['matchup_id'] = $data->matchup_id;
            $return_data['bookie_id'] = $data->bookie_id;
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }
}