<?php

require_once('lib/bfocore/general/class.EventHandler.php');

if (!isset($_POST['fighter_id']) || !isset($_POST['alt_name']) || $_POST['fighter_id'] == "" || $_POST['alt_name'] == "")
{
	echo 'Missing parameters';
	exit();
}

$bSuccess = EventHandler::addFighterAltName($_POST['fighter_id'], $_POST['alt_name']);

if ($bSuccess == true)
{
	echo 'Alt name added.';
}
else
{
	echo 'Error adding alt name';
}



?>