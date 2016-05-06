<?php 

require_once('lib/bfocore/alerter/class.AlertsModel.php');

class AlerterV2
{
	public function addAlert($email, $oddstype, $criterias)
	{
		//Before adding alert, check if criterias are already met. If so we return an exception
		if ($this->evaluateCriterias($criterias) == false)
		{
			throw new Exception("Criteria already met", 10);
		}


		$am = new AlertsModel();
		try 
		{
			return $am->addAlert($email, $oddstype, $criterias);
		}
		catch (Exception $e)
		{
			//TODO: Add Klogger entry
			echo 'Error: ' . $e->getCode();
			return false;
		}
	}

	public function deleteAlert($alert_id)
	{
		$am = new AlertsModel();
		try
		{
			return $am->deleteAlert($alert_id);
		}
		catch (Exception $e)
		{
			//TODO: Add Klogger entry
			echo 'Error: ' . $e->getCode() . ' for ID:' . $alert_id;
			return false;
		}
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
			//TODO: Klogger
		}
	}

	private function evaluateCriterias($criterias)
	{
		if (isset($criterias['matchup_id']))
		{
			//Matchup based alert

			if (isset($criterias['proptype_id']))
			{
				//Proptype for matchup
			}
			else if (isset($criterias['proptype_category']))
			{
				//Proptype category for matchup
			}

		}
		else if (isset($criterias['event_id']))
		{
			//Event based alert
			if (isset($criterias['proptype_id']))
			{
				//Prop type
			}
			else if (isset($criterias['proptype_category']))
			{
				//Prop type category
			}
		}

		return true;
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
				//Put these alerts together in this loop
				echo 'Adding an alert
				';	
				$ids_dispatched[] = $alert->getID();
			}
			//...and send them off
			echo '..and lift off for ' . $rcpt . ' 
			';
			
		}
		return $ids_dispatched;
	}

	private function sendAlertsByMail()
	{
		//TODO: Format each alert determined by their criteras.. Do we do this earlier in the chain while we evaluate the criteras?
	}

	private function sendAlertsByTwitter()
	{
		//TODO: Currently halted due to the direct message limit (250 a day) on Twitter. Not sure if we can work through it
	}


}

?>