<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');




$aUnmatchedCol = EventHandler::getUnmatched(1500);

$sMatchups = '';
$sPropsMatchup = '';
$sPropsTemplate = '';
foreach($aUnmatchedCol as $aUnmatched)
{
	$sBookie = BookieHandler::getBookieByID($aUnmatched['bookie_id'])->getName();
  	$sModifiedDate = date("Y-m-d H:i:s", strtotime("+6 hours", strtotime($aUnmatched['log_date']))); //Add 6 hours to date for admin timezone
  	$aSplit = explode(' vs ', $aUnmatched['matchup']);
  	if ($aUnmatched['type'] == 0)
  	{
  		$sMatchups .= '<tr><td>' . $sModifiedDate . '</td><td><b>' . $sBookie . '</b></td><td>' . $aUnmatched['matchup'] . '</td><td>[<a href="?p=addNewFightForm&inFighter1=' . $aSplit[0] .  '&inFighter2=' . $aSplit[1] . '">add</a>] [<a href="http://www.google.se/search?q=' . $aUnmatched['matchup'] . '">google</a>]</td></tr>';	
  	}
  	else if ($aUnmatched['type'] == 1)
  	{
  		$sPropsMatchup .= '<tr><td>' . $sModifiedDate . '</td><td><b>' . $sBookie . '</b></td><td>' . $aUnmatched['matchup'] . '</td><td>[<a href="?p=addManualPropCorrelation&inBookieID=' . $aUnmatched['bookie_id'] . '&inCorrelation=' . $aUnmatched['matchup'] . '">link manually</a>]</td></tr>';		
  	}
  	else if ($aUnmatched['type'] == 2)
  	{
  		$sPropsTemplate .= '<tr><td>' . $sModifiedDate . '</td><td><b>' . $sBookie . '</b></td><td>' . $aUnmatched['matchup'] . '</td><td>[<a href="?p=addNewPropTemplate&inBookieID=' . $aUnmatched['bookie_id'] . '&inTemplate=' . $aSplit[0] . '&inNegTemplate=' . $aSplit[1] . '">add</a>]</td></tr>';		
  	}
}


if ($sMatchups != '')
{
	echo '<b>Matchups:</b> <br />';
	echo '<table class="genericTable">';
	echo $sMatchups;
	echo '</table><br />';	
}
if ($sPropsTemplate != '')
{
	echo '<b>Props without templates</b>: <br />';
	echo '<table class="genericTable">';
	echo $sPropsTemplate;
	echo '</table><br />';	
}
if ($sPropsMatchup != '')
{
	echo '<b>Props without matchups:</b> <br />';
	echo '<table class="genericTable">';
	echo $sPropsMatchup;
	echo '</table><br />';	
}

if (count($aUnmatchedCol) > 0)
{
	echo '<a href="logic/logic.php?action=clearUnmatched">Clear unmatched table</a>';
}
echo '<br /><br />';




?>