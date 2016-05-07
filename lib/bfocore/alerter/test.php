<?php

require 'vendor/autoload.php';

require_once('lib/bfocore/alerter/class.AlerterV2.php');


$alerter = new AlerterV2();

try
{
	 $alerter->addAlert('cnordvaller@gmail.com', 2, '{"matchup_id": 123}');	
}
catch (Exception $e)
{
	echo 'Exception: ' . $e;
}

try
{
	 $alerter->addAlert('cnordvaller@gmail.com', 2, '{"matchup_id": 123}');	
}
catch (Exception $e)
{
	echo 'Exception: ' . $e;
}



try
{
	 $alerter->addAlert('cnordvalleil.com', 2, '{"matchup_id": 123}');	
}
catch (Exception $e)
{
	echo 'Exception: ' . $e;
}

try
{
	 $alerter->addAlert('cndsvaller@gmail.com', 2, '{"matchup_id": 123}');	
}
catch (Exception $e)
{
	echo 'Exception: ' . $e;
}

try
{
	 $alerter->addAlert('cnordvaller@gmail.com', 15, '{"matchup_id": 123}');	
}
catch (Exception $e)
{
	echo 'Exception: ' . $e;
}

try
{
	 $alerter->addAlert('cnordvfffler@gmail.com', 0, '{"matchup_id": 123}');	
}
catch (Exception $e)
{
	echo 'Exception: ' . $e;
}


$alerter->checkAlerts();

?>