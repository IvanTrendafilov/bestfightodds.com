<?php
//This class handles the model for Alerts. To be called from AlerterV2 only
require_once('lib/bfocore/alerter/class.AlertV2.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php');

class AlertsModel 
{
	public function addAlert($email, $oddstype, $criterias)
	{
		//Validate input
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			throw new Exception("Invalid e-mail format", 30);	
		}
		if (!$this->isJson($criterias) || strlen($criterias) > 1000)
		{
			throw new Exception("Invalid criterias", 20);
		}
		if (!is_int($oddstype) || ($oddstype > 4 || $oddstype < 1))
		{
			throw new Exception("Invalid odds type", 40);	
		}

		//Validate proper combinations
		$json = json_decode($criterias, true);
		if (!(isset($criterias['matchup_id']) && isset($criterias['team_num'])
			|| !(isset($criterias['proptype_id']) && (isset($criterias['matchup_id']) || isset($criterias['event_id'])))))
		{
			throw new Exception("Invalid field combinations", 50);
		}

		$query = "INSERT INTO alerts_entries(email, oddstype, criterias) VALUES (?,?,?)";
		$params = [$email, $oddstype, $criterias];
		try 
		{
			$id = PDOTools::insert($query, $params);
		}
		catch(PDOException $e)
		{
		    if($e->getCode() == 23000){
				throw new Exception("Duplicate entry", 50);	
		    }
		}
		return $id;
	}

	public function deleteAlert($id)
	{
		if (!is_int($id))
		{
			throw new Exception("Invalid ID", 10);	
		}
		$query = "DELETE FROM alerts_entries WHERE id = ?";
		$params = [$id];
		return PDOTools::delete($query, $params);
	}

	public function getAllAlerts()
	{
		$query = "SELECT * FROM alerts_entries ORDER BY id ASC";
		$result = PDOTools::findMany($query);
        $alerts = [];
        foreach ($result as $alert)
        {
        	$alerts[] = new AlertV2($alert['id'], $alert['email'], $alert['oddstype'], $alert['criterias']);
        }

        return $alerts;
	}

	public function isAlertReached($criterias)
	{
		if (isset($criterias['proptype_id']))
		{
			return $this->isPropAlertReached($criterias);
		}
		else if (isset($criterias['matchup_id']))
		{
			return $this->isMatchupAlertReached($criterias);
		}
		return false;
	}

	private function isMatchupAlertReached($criterias)
	{
		$query_checks = '';
		$query_params = [];
		foreach ($criterias as $criteria => $val)
		{
			switch ($criteria)
			{
				case 'matchup_id':
					$query_checks .= ' AND f.id = :matchup_id';
					$query_params[':matchup_id'] = $val;
					break;
				case 'line_limit':
					$query_checks .= ' AND fo.' . ($criterias['team_num'] == 1 ? 'fighter1_odds' : 'fighter2_odds') . ' = :line_limit';
					$query_params['line_limit'] = $val;
					break;
				case 'bookie_id':
					$query_checks .= ' AND fo.bookie_id = :bookied_id';
					$query_params['bookie_id'] = $val;
					break;
			}
		}

		$query = "SELECT * FROM fightodds fo 
						INNER JOIN fights f ON fo.fight_id = f.id 
						INNER JOIN events e ON f.event_id = e.id 
						WHERE LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL 2 HOUR), 10)
						 " . $query_checks;

		return (PDOTools::findMany($query, $query_params)->fetchColumn() > 0);
	}

	private function isPropAlertReached($criterias)
	{
		$query_checks = '';
		$query_params = [];
		foreach ($criterias as $criteria => $val)
		{
			switch ($criteria)
			{
				case 'event_id':
					$query_checks .= ' AND e.id = :event_id';
					$query_params[':event_id'] = $val;
					break;
				case 'matchup_id':
					$query_checks .= ' AND f.id = :matchup_id';
					$query_params[':matchup_id'] = $val;
					break;
				case 'proptype_id':
					$query_checks .= ' AND pt.id = :proptype_id';
					$query_params['proptype_id'] = $val;
					break;
				case 'team_num':
					$query_checks .= ' AND lp.team_num = :team_num';
					$query_params['team_num'] = $val;
					break;
				case 'line_limit':
					$query_checks .= ' AND lp.' . ($criterias['line_side'] == 1 ? 'prop_odds' : 'negprop_odds') . ' = :line_limit';
					$query_params['line_limit'] = $val;
				case 'bookie_id':
					$query_checks .= ' AND lp.bookie_id = :bookied_id';
					$query_params['bookie_id'] = $val;
					break;
			}
		}

		$query = "SELECT * FROM lines_props lp 
						INNER JOIN fights f ON lp.matchup_id = f.id 
						INNER JOIN events e ON f.event_id = e.id 
						WHERE LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL 2 HOUR), 10)
						 " . $query_checks;

		return (PDOTools::findMany($query, $query_params)->fetchColumn() > 0);
	}


	private function isJson($string) 
	{
		 json_decode($string);
		 return (json_last_error() == JSON_ERROR_NONE);
	}

		


}

?>