<?php 

require_once('config/inc.generalConfig.php');
require_once('lib/bfocore/alerter/class.AlertsModel.php');


class AlerterV2
{
	private $logger;

	public function __construct()
	{
		$this->logger = new Katzgrau\KLogger\Logger(GENERAL_KLOGDIR, Psr\Log\LogLevel::DEBUG, ['prefix' => 'alerter_']);
	}

	//Possible error codes:
	//-100 Criteria already met
	//-2xx (passed from Model)
	//  -210 Duplicate entry
	//	-220 Invalid criterias
	//	-230 Invalid e-mail
	//  -240 Invalid odds type
	//  -250 Invalid criterias combination
	//
	// 1 = Alert added OK!

	public function addAlert($email, $oddstype, $criterias)
	{
		//Before adding alert, check if criterias are already met. If so we return an exception
		if ($this->evaluateCriterias($criterias) == true)
		{
			//Criteria already met
			return -101;
		}

		$am = new AlertsModel();
		try 
		{
			$id = $am->addAlert($email, $oddstype, $criterias);
			$this->logger->info('Added alert ' . $id . ' for ' . $email . ', oddstype ' . $oddstype . ': ' . $criterias);
			return 1;
		}
		catch (Exception $e)
		{
			//TODO: Pass back code to front end somehow so it knows how to respond
			return (int) ('-2' . $e->getCode());
		}
	}

	public function deleteAlert($alert_id)
	{
		$am = new AlertsModel();
		try
		{
			$am->deleteAlert($alert_id);
			$this->logger->info('Cleared alert ' . $alert_id);
		}
		catch (Exception $e)
		{
			$this->logger->error('Unable to clear alert ' . $alert_id);
			return false;
		}
		return true;
	}

	public function checkAlerts()
	{
		$am = new AlertsModel();
		$alerts = $am->getAllAlerts();
		$alerts_to_send = [];
		foreach ($alerts as $alert)
		{
			//Evaluate if stored alert fills the criterias to be triggered
			if ($this->evaluateCriterias($alert->getCriterias()))
			{
				//Fills criteria, add it to list of alerts to dispatch
				$alerts_to_send[] = $alert;
			}
		}
		$ids_dispatched = $this->dispatchAlerts($alerts_to_send);
		foreach ($ids_dispatched as $alert_id)
		{
			$this->deleteAlert($alert_id);
		}
	}



	private function evaluateCriterias($criterias)
	{
		$is_reached = (new AlertsModel())->isAlertReached($criterias);	
		return $is_reached; 

		// switch ($this->getCriteriasCategory($criterias))
		// {
		// 	case 1: //Matchup

		// 	break;
		// 	case 2: //Matchup prop
		// 	break;
		// 	case 3: //Matchup proptype
		// 	break;
		// 	case 4: //Event 
		// 	break;
		// 	case 5: //Event prop
		// 	break;
		// 	case 6: //Event proptype
		// 	break;
		// 	default:	
		// }

	}

	private function getCriteriasCategory($criterias)
	{
		if (isset($criterias['matchup_id']))
		{
			if (isset($criterias['proptype_id']))
			{
				//Proptype for matchup
				return 2;
			}
			else if (isset($criterias['proptype_category']))
			{
				//Proptype category for matchup
				return 3;
			}
			else
			{
				//Matchup based alert
				return 1;
			}
		}
		else if (isset($criterias['event_id']))
		{
			if (isset($criterias['proptype_id']))
			{
				return 5;
				//Prop type
			}
			else if (isset($criterias['proptype_category']))
			{
				return 6;
				//Prop type category
			}
			else
			{
				return 4;
				//Event based alert
			}
		}
		return null;
	}


	// Take a list of alerts and creates a new array grouping them by recipient e-mail
	private function groupAlerts($alerts)
	{
		$retgroup = [];
		foreach ($alerts as $alert)
		{
			$retgroup[(string) $alert->getEmail()][] = $alert;
		}
		return $retgroup;
	}

	// Dispatches alerts to a user, returns an array with the alert IDs successfully dispatched 
	private function dispatchAlerts($alerts)
	{
		//Group alerts before dispatching them
		$grouped_alerts = $this->groupAlerts($alerts);
		$ids_dispatched = [];

		foreach ($grouped_alerts as $rcpt => $alerts)
		{
			foreach ($alerts as $alert)
			{
				$ids_dispatched[] = $alert->getID();
			}
			//TODO: Perform actual dispatch of alerts
			$this->logger->info('Dispatched ' . implode($ids_dispatched, ' ,') . ' for ' . $rcpt);
		}

		

		return $ids_dispatched;
	}

	private function sendAlertsByMail()
	{
		//TODO: Format each alert determined by their criteras.. Do we do this earlier in the chain while we evaluate the criteras?
	}

}

?>