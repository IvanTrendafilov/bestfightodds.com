<?php

require 'vendor/autoload.php';

require_once('lib/bfocore/alerter/class.AlerterV2.php');


$alerter = new AlerterV2();

//Initialization: Clear all alerts
$alerts = $alerter->getAllAlerts();
foreach ($alerts as $alert)
{
	$alerter->deleteAlert($alert->getID());
}

translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"matchup_id": 12156}'));	 //Ok (Matchup 11816 Show)
translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"matchup_id": 12156, "bookie_id": 1}'));	 //Ok (Matchup 11816 Show, Bookie 1)
translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"matchup_id": 12156}'));	//Fail: Should be treated as dupe
translateResult($alerter->addAlert('cnordvalleil.com', 2, '{"matchup_id": 12156}'));	//Fail: Invalid e-mail
translateResult($alerter->addAlert('cndsvaller@gmail.com', 2, '{"matchup_id": 12156, "line_limit":150, "team_num": 1}')); //Ok (Limit not met yet)
translateResult($alerter->addAlert('cndsvaller@gmail.com', 2, '{"matchup_id": 12156, "line_limit":-200, "team_num": 1}')); //Ok (Limit met)

translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"event_id": 1139, "proptype_id": 35, "team_num": 0}')); //Ok (Prop matchup in event 1139, proptype Over/Under 4½) - Should trigger
translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"event_id": 1139, "proptype_id": 35, "team_num": 0}')); //Fail: Should be dupe
translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"event_id": 1139, "proptype_id": 34, "team_num": 0}')); //Ok (Prop matchup in event 1139, proptype Over/Under 3½) - Should not trigger
translateResult($alerter->addAlert('cnordvaller@gmail.com', 2, '{"event_id": 1139, "proptype_id": 2, "team_num": 0}')); //Ok (Prop matchup in event 1139, proptype Over/Under 3½) - Should trigger


function translateResult($result)
{
	switch ($result)
	{
		case 1:
			echo "OK!
			";
		break;
		case -100:
			echo "Criteria already met
			";
		break;
		case -210: 
			echo "Duplicate entry
			";
		break;
		case -220: 
			echo "Invalid criterias
			";
		break;
		case -230: 
			echo "Invalid e-mail
			";
		break;
		case -240: 
			echo "Invalid odds type
			";
		break;
		case -250: 
			echo "Invalid criterias combination
			";
		break;
	}
}


$alerter->checkAlerts();

?>