<?php

//Admin API

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.ScheduleHandler.php');

$oInstance = AdminAPI::getInstance();
$oInstance->processCall();

class AdminAPI
{
	private static $instance = null;

    private function __construct()
    {

    }

	public function processCall()
	{
		$sFunction = $this->getParam('apiFunction');
		if ($sFunction == false)
		{
			$this->returnError('0001', 'Missing function');
		}

		switch ($sFunction)
		{
			case 'addEvent':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('eventName') || !$this->getParam('eventDate'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
		        $oEvent = new Event(0, $this->getParam('eventDate'), $this->getParam('eventName'), 1);
		        if ($iEventID = EventHandler::addNewEvent($oEvent))
		        {
		            $this->returnSuccess('Event created', array('eventID' => $iEventID));
		        }
				$this->returnError('0004', 'Error when creating event');
				break;

			case 'addMatchup':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('eventID') || !$this->getParam('team1Name') || !$this->getParam('team2Name'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
		        $oFight = new Fight(0, $_POST['team1Name'], $_POST['team2Name'], $_POST['eventID']);
    			if ($iMatchupID = EventHandler::addNewFight($oFight))
    			{
    				$this->returnSuccess('Matchup created', array('matchupID' => $iMatchupID));
    			}
				$this->returnError('0004', 'Error when creating matchup');
				break;

			case 'renameEvent':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('eventID') || !$this->getParam('eventTitle'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
				if (EventHandler::changeEvent($this->getParam('eventID'), $this->getParam('eventTitle'), '', true))
				{
    				$this->returnSuccess('Event renamed', array('eventID' => $this->getParam('eventID')));
    			}
				$this->returnError('0004', 'Error when renaming event');
				break;

			case 'clearManualAction':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('actionID'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
    			if ($bResult = ScheduleHandler::clearManualAction($this->getParam('actionID')))
    			{
    				$this->returnSuccess('Manual action cleared');
    			}
				$this->returnError('0004', 'Error when clearing manual action');
				break;

			case 'deleteMatchup':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('matchupID'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
				if (EventHandler::removeFight($this->getParam('matchupID')))
				{
    				$this->returnSuccess('Matchup removed', array('matchupID' => $this->getParam('matchupID')));
    			}
				$this->returnError('0004', 'Error when removing matchup');
				break;

			case 'moveMatchup':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('matchupID') || !$this->getParam('matchupID'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
				if (EventHandler::changeFight($this->getParam('matchupID'), $this->getParam('eventID')))
				{
    				$this->returnSuccess('Matchup moved', array('matchupID' => $this->getParam('matchupID'), 'eventID' => $this->getParam('eventID')));
    			}
				$this->returnError('0004', 'Error when moving matchup');
				break;

			case 'deleteEvent':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('eventID'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
				if (EventHandler::removeEvent($this->getParam('eventID')))
				{
    				$this->returnSuccess('Event removed', array('eventID' => $this->getParam('matchupID')));
    			}
				$this->returnError('0004', 'Error when removing event');
				break;

			case 'redateEvent':
				if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('eventID') || !$this->getParam('eventDate'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
				if (EventHandler::changeEvent($this->getParam('eventID'), '', $this->getParam('eventDate'), true))
				{
    				$this->returnSuccess('Event redated', array('eventID' => $this->getParam('eventID')));
    			}
				$this->returnError('0004', 'Error when redating event');
				break;


		    case 'removeOddsForMatchupAndBookie':
		    	if (!$this->isPOST())
				{
					$this->returnError('0005', 'Must be called as POST');
					break;	
				}
				if (!$this->getParam('matchupID') || !$this->getParam('bookieID'))
				{
					$this->returnError('0003', 'Missing parameters');
					break;
				}
				if (OddsHandler::removeOddsForMatchupAndBookie($this->getParam('matchupID'), $this->getParam('bookieID')))
				{
					$this->returnSuccess('Odds removed', array('matchupID' => $this->getParam('matchupID'), 'bookieID' => $this->getParam('bookieID')));
				}
		        $this->returnError('0004', 'Error when removing odds for matchup/bookie');
		        break;

			default:
				$this->returnError('0002', 'Invalid function');
		}
		return;
	}


    /**
     * Returns the singleton instance
     * 
     * @return Logger instance
     */
    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new AdminAPI();
        }
        return self::$instance;
    }

	private function isPOST()
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}

	private function isGET()
	{
		return $_SERVER['REQUEST_METHOD'] === 'GET';
	}

	private function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	private function getParam($a_sParamName)
	{
		if ($this->getMethod() == 'POST')
		{
			return isset($_POST[$a_sParamName]) ? $_POST[$a_sParamName] : false;
		}
		return isset($_GET[$a_sParamName]) ? $_GET[$a_sParamName] : false;
	}

	private function returnError($a_iErrorCode, $a_sMessage)
	{
		$this->returnJSON(array('status_code' => $a_iErrorCode, 'status_message' => $a_sMessage, 'meta_data' => array('error' => true)));
	}

	private function returnSuccess($a_sMessage, $a_aMetaData = null)
	{
		$this->returnJSON(array('status_code' => 1000, 'status_message' => $a_sMessage, 'meta_data' => $a_aMetaData));
	}

	private function returnJSON($a_aStructure)
	{
		echo json_encode(array('result' => $a_aStructure));
		exit;
	}

}


?>