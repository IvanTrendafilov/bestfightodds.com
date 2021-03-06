<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

use BFO\General\ScheduleHandler;
use BFO\General\TeamHandler;
use BFO\General\EventHandler;
use BFO\General\OddsHandler;
use BFO\General\BookieHandler;
use BFO\General\TwitterHandler;
use BFO\Utils\OddsTools;

use BFO\DataTypes\Fight;
use BFO\DataTypes\Event;
use BFO\DataTypes\PropTemplate;
use BFO\DataTypes\FightOdds;
use BFO\DataTypes\PropType;
use BFO\General\PropTypeHandler;

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

        if (
            !isset($data->event_id, $data->team1_name, $data->team2_name)
            || (int) $data->event_id <= 0 || (string) $data->team1_name == '' || (string) $data->team2_name == ''
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            $matchup = new Fight(0, $data->team1_name, $data->team2_name, (int) $data->event_id);
            $matchup->setCreateSource(2);
            if (isset($data->create_source) && is_numeric($data->create_source) && $data->create_source >= 0 && $data->create_source <= 2) {
                $matchup->setCreateSource(intval($data->create_source));
            }
            if ($matchup_id = EventHandler::createMatchup($matchup)) {
                $return_data['msg'] = 'Successfully added';
                $return_data['matchup_id'] = $matchup_id;
            } else {
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

        if (!v::intType()->validate($data->matchup_id)) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            if (isset($data->is_main_event) && is_bool(boolval($data->is_main_event))) {
                //Flip main even status
                if (EventHandler::setFightAsMainEvent((int) $data->matchup_id, (bool) $data->is_main_event)) {
                    $return_data['msg'] = 'Successfully switched matchup to main event';
                    $return_data['matchup_id'] = $data->matchup_id;
                    $return_data['is_main_event'] = $data->is_main_event;
                } else {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error updating matchup';
                    $return_data['error'] = true;
                }
            } elseif (isset($data->event_id)) {
                //Update matchup event
                if (EventHandler::changeFight($data->matchup_id, $data->event_id)) {
                    $return_data['msg'] = 'Successfully updated';
                    $return_data['matchup_id'] = $data->matchup_id;
                } else {
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

        if (
            !isset($data->matchup_id)
            || (int) $data->matchup_id <= 0
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            $result = EventHandler::removeMatchup((int) $data->matchup_id);
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

        if (
            !isset($data->event_id)
            || (int) $data->event_id <= 0
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
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

        if (
            !v::stringVal()->length(3, null)->validate($data->event_name)
            || !v::date('Y-m-d')->validate($data->event_date)
            || !v::boolVal()->validate($data->event_hidden)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        } else {
            $event = new Event(0, $data->event_date, $data->event_name, !$data->event_hidden);
            if (($event_obj = EventHandler::addNewEvent($event))) {
                $return_data['msg'] = 'Successfully added';
                $return_data['event_id'] = $event_obj->getID();
            } else {
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

        if (!v::intType()->validate($data->event_id)) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        } else {
            $display = null;
            if (isset($data->event_display)) {
                $display = boolval($data->event_display);
            }

            //Update matchup event
            if (EventHandler::changeEvent($data->event_id, $data->event_name ?? '', $data->event_date ?? '', $display)) {
                $return_data['msg'] = 'Successfully updated';
                $return_data['event_id'] = $data->event_id;
            } else {
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

        if (!v::intType()->validate($data->fighter_id)) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        } else {
            //Check for twitter handle update
            if (v::noWhitespace()->length(4, null)->validate(@$data->twitter_handle)) {
                if (TwitterHandler::addTwitterHandle($data->fighter_id, $data->twitter_handle)) {
                    $return_data['msg'] = 'Successfully updated twitter Handle. ';
                    $return_data['fighter_id'] = $data->fighter_id;
                } else {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error updating twitter handle';
                    $return_data['error'] = true;
                }
            }

            //Check for alt name update (if so just add it to the bunch)
            if (v::length(5, null)->validate(@$data->alt_name)) {
                if (TeamHandler::addTeamAltName((int) $data->fighter_id, (string) $data->alt_name)) {
                    $return_data['msg'] .= 'Successfully updated altname.';
                    $return_data['fighter_id'] = $data->fighter_id;
                } else {
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

        if (
            !v::stringVal()->length(10, null)->validate($data->proptemplate)
            || !v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->proptype_id)
            || !v::intType()->validate($data->fieldstype_id)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        } else {
            $template = new PropTemplate(0, (int) $data->bookie_id, $data->proptemplate, $data->negproptemplate ?? '', (int) $data->proptype_id, (int) $data->fieldstype_id, '');
            $exception_msg = '';
            $new_template_id = null;
            try {
                $new_template_id = BookieHandler::addNewPropTemplate($template);
            } catch (Exception $e) {
                $exception_msg = $e->getMessage();
            }
            if ($new_template_id && $exception_msg == '') {
                $return_data['msg'] = 'Successfully added';
                $return_data['proptemplate_id'] = $new_template_id;
            } else {
                $response->withStatus(500);
                $return_data['msg'] = 'Error creating prop template: ' . $exception_msg;
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function createPropType(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (
            !v::stringVal()->length(5, null)->validate($data->prop_desc)
            || !v::stringVal()->length(5, null)->validate($data->negprop_desc)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        } else {
            $prop_type = new PropType(-1, $data->prop_desc, $data->negprop_desc, 0);
            if (isset($data->is_event_prop) && boolval($data->is_event_prop) == true) {
                $prop_type->setEventProp(true);
            }
            $new_proptype_id = PropTypeHandler::createNewPropType($prop_type);
            if ($new_proptype_id) {
                $return_data['msg'] = 'Successfully added';
                $return_data['proptype_id'] = $new_proptype_id;
            } else {
                $response->withStatus(500);
                $return_data['msg'] = 'Error creating prop type';
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

        if (
            !v::stringVal()->length(10, null)->validate($data->correlation)
            || !v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->matchup_id)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing/invalid parameters';
            $return_data['error'] = true;
        } else {
            if (OddsHandler::storeCorrelations((int) $data->bookie_id, [['correlation' => $data->correlation, 'matchup_id' => $data->matchup_id]])) {
                $return_data['msg'] = 'Successfully added correlation';
            } else {
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

        if (v::intType()->validate(@$data->bookie_id)) {
            //Reset specific
            if (BookieHandler::resetChangeNums((int) $data->bookie_id) > 0) {
                $return_data['msg'] = 'Successfully reset changenum for ' . $data->bookie_id;
            } else {
                $response->withStatus(500);
                $return_data['msg'] = 'Failed to reset changenum for parser ' . $data->bookie_id;
                $return_data['error'] = true;
            }
        } else {
            //Reset all
            if (BookieHandler::resetChangeNums() > 0) {
                $return_data['msg'] = 'Successfully reset all changenums';
            } else {
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

        if (EventHandler::clearUnmatched()) {
            $return_data['msg'] = 'Successfully cleared all unmatched';
        } else {
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

        if (
            !isset($data->action_id)
            || (int) $data->action_id <= 0
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            $result = ScheduleHandler::clearManualAction($data->action_id);
            if ($result && $result > 0) {
                $return_data['msg'] = 'Successfully deleted action ' . $data->action_id;
            } else {
                $response->withStatus(500);
                $return_data['msg'] = 'Failed to delete action with ID ' . $data->action_id;
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function deletePropTemplate(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (
            !isset($data->template_id)
            || (int) $data->template_id <= 0
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            $result = BookieHandler::deletePropTemplate((int) $data->template_id);
            if ($result && $result > 0) {
                $return_data['msg'] = 'Successfully deleted prop template ' . $data->template_id;
            } else {
                $response->withStatus(500);
                $return_data['msg'] = 'Failed to delete prop template with ID ' . $data->template_id;
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

        if (
            !v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->matchup_id)
            || !v::stringVal()->length(2, null)->validate($data->team1_odds)
            || !v::stringVal()->length(2, null)->validate($data->team2_odds)
            || !OddsTools::checkCorrectOdds($data->team1_odds)
            || !OddsTools::checkCorrectOdds($data->team2_odds)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            $new_odds = new FightOdds((int) $data->matchup_id, (int) $data->bookie_id, (string) $data->team1_odds, (string) $data->team2_odds, (string) OddsTools::standardizeDate(date('Y-m-d')));
            if (OddsHandler::checkMatchingOdds($new_odds)) {
                $return_data['msg'] = 'Odds have not changed (' . $data->team1_odds . '/' . $data->team2_odds . ')';
            } else {
                if (OddsHandler::addNewFightOdds($new_odds)) {
                    $return_data['msg'] = 'Successfully added odds: (' . $data->team1_odds . '/' . $data->team2_odds . ')';
                } else {
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

    public function deleteOdds(Request $request, Response $response)
    {
        //In progress
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (
            !v::intType()->validate($data->bookie_id)
            || !v::intType()->validate($data->matchup_id)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            //Delete odds
            if (isset($data->proptype_id)) {
                //TODO: Deleting prop odds for a specific prop type ID, also needs team_num
            } else {
                if (OddsHandler::removeOddsForMatchupAndBookie($data->matchup_id, $data->bookie_id)) {
                    $return_data['msg'] = 'Odds removed for matchup ' . $data->matchup_id . ' and bookie_id ' . $data->bookie_id;
                } else {
                    $response->withStatus(500);
                    $return_data['msg'] = 'Error removing odds for matchup ' . $data->matchup_id . ' and bookie_id ' . $data->bookie_id;
                    $return_data['error'] = true;
                }

                $return_data['matchup_id'] = $data->matchup_id;
                $return_data['bookie_id'] = $data->bookie_id;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }

    public function updateBookie(Request $request, Response $response)
    {
        $json = $request->getBody();
        $data = json_decode($json, false);
        $return_data = [];
        $return_data['error'] = false;

        if (
            !v::intType()->validate($data->bookie_id)
            || !v::stringVal()->validate($data->url)
        ) {
            $response->withStatus(422);
            $return_data['msg'] = 'Missing parameters';
            $return_data['error'] = true;
        } else {
            if (BookieHandler::updateBookieURL((int) $data->bookie_id, (string) $data->url)) {
                $return_data['msg'] = 'Successfully updated bookie URL';
            } else {
                $response->withStatus(500);
                $return_data['msg'] = 'Error updating bookie';
                $return_data['error'] = true;
            }
        }

        $response->getBody()->write(json_encode($return_data));
        return $this->returnJson($response);
    }
}
