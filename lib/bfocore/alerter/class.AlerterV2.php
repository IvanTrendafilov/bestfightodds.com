<?php 

require_once('config/inc.generalConfig.php');
require_once('config/inc.alertConfig.php');
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
		$am = new AlertsModel();
		//Before adding alert, check if criterias are already met. If so we return an exception
		if ($am->isAlertReached($criterias) == true)
		{
			//Criteria already met
			return -101;
		}
		try 
		{
			$id = $am->addAlert($email, $oddstype, $criterias);
			$this->logger->info('Added alert ' . $id . ' for ' . $email . ', oddstype ' . $oddstype . ': ' . $criterias);
			return 1;
		}
		catch (Exception $e)
		{
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
			if ($am->isAlertReached($alert->getCriterias()))
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

	private function getAlertCategory($criterias)
	{
		if (isset($criterias['matchup_id']))
		{
			return $this->isMatchupAlertReached($criterias);
		}
		else if (isset($criterias['proptype_id']))
		{
			return $this->isPropAlertReached($criterias);
		}
		else if (isset($criterias['is_eventprop']))
		{
			return $this->isEventPropAlertReached($criterias);
		}
		return false;
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
		$all_ids_dispatched = [];

		foreach ($grouped_alerts as $rcpt => $alerts)
		{
			$ids_dispatched = [];
			foreach ($alerts as $alert)
			{
				$ids_dispatched[] = $alert->getID();
			}
			//TODO: Perform actual dispatch of alerts

			$text = $this->formatAlertMail($alerts);
			$this->sendAlertsByMail($text);

			$this->logger->info('Dispatched ' . implode($ids_dispatched, ' ,') . ' for ' . $rcpt);
			$all_ids_dispatched = array_merge($all_ids_dispatched, $ids_dispatched);
		}
		return $all_ids_dispatched;
	}

	private function formatAlertMail($alerts)
	{
		$text = "Alert mail:\n\n";
		foreach ($alerts as $alert)
		{
			$criterias = $alert->getCriterias(); 
			//Add alert row
			if (isset($criterias['proptype_id']))
			{
				//Prop row
				$text .= "Prop available";
			}
			else
			{
				//Matchup
				$text .= "Regular matchup";
				if (!isset($alert->getCriterias()['line_limit']))
				{
					//No limit set, this is for show only
					
				}
				else
				{
					//Limit set, include it
				}
			}

			$text .= "\n";
		}
		$text .= "End of alert mail\n\n";
		return $text;
	}

	private function sendAlertsByMail($mail_text)
	{
		if (ALERTER_DEV_MODE == true)
		{
			//Do not actually send the alert, just echo it
			echo $mail_text;

		}
		//TODO: Format each alert determined by their criteras.. Do we do this earlier in the chain while we evaluate the criteras?
	}

	public function getAllAlerts()
	{
		return (new AlertsModel())->getAllAlerts();
	}

}

?>