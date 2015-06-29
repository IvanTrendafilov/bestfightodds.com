<?php

require_once('lib/bfocore/general/class.EventHandler.php');

if (!isset($_GET['eventID']))
{
  echo 'Missing event ID';
  exit();
}

$aFights = EventHandler::getAllFights($_GET['eventID']);













?>