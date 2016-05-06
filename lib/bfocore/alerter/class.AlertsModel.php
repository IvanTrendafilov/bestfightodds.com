<?php
//This class handles the model for Alerts. To be called from AlerterV2 only
require_once('lib/bfocore/alerter/class.AlertV2.php');
require_once('lib/bfocore/utils/db/class.DBTools.php');

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

		$query = "INSERT INTO alerts_entries(email, oddstype, criterias) VALUES (?,?,?)";
		$params = [$email, $oddstype, $criterias];
		try 
		{
			DBTools::doParamQuery($query, $params);
		}
		catch(Exception $e)
		{
			echo 'dupe!';
		    if($e.getErrorCode() == 1062){
		        //duplicate primary key 
		        echo 'dupe';
		        return false;
		    }
		}
		return true;
	}

	public function deleteAlert($id)
	{
		if (!is_int($id))
		{
			throw new Exception("Invalid ID", 10);	
		}

		$query = "DELETE FROM alerts_entries WHERE id = ?";
		$params = [$id];
		DBTools::doParamQuery($query, $params);
        if (DBTools::getAffectedRows() > 0)
        {
            return true;
        }
        return false;
	}

	public function getAllAlerts()
	{
		$query = "SELECT * FROM alerts_entries ORDER BY id ASC";
		$result = DBTools::doQuery($query);
        $alerts = [];
        while ($alert = mysql_fetch_array($result))
        {
            $alerts[] = new AlertV2($alert['id'], $alert['email'], $alert['oddstype'], $alert['criterias']);
        }
        return $alerts;
	}

	private function isJson($string) 
	{
		 json_decode($string);
		 return (json_last_error() == JSON_ERROR_NONE);
	}
		


}

?>