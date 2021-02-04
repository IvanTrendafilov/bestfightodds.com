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

    private function returnJson($response)
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

}