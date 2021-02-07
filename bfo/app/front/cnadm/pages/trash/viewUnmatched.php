<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

echo '<a href="logic/logic.php?action=clearUnmatched">Clear unmatched table</a><br /><br />';

$aUnmatchedCol = EventHandler::getUnmatched(1500);

$sMatchups = '';
$sPropsMatchup = '';
$sPropsTemplate = '';
foreach($aUnmatchedCol as $aUnmatched)
{
	$aSplit = explode(' vs ', $aUnmatched['matchup']);
	$event_text = '';
	//If metadata is set, parse it and check for interesting information like date, event that we can later match on
	if (isset($aUnmatched['metadata']))
	{
		if (isset($aUnmatched['metadata']['event_name']))
		{
			$cut_pos = strpos($aUnmatched['metadata']['event_name'], " -");
			if (!isset($cut_pos) || $cut_pos == null || $cut_pos == 0)
			{
				$cut_pos = strlen($aUnmatched['metadata']['event_name']);
			}
			$event_name = substr($aUnmatched['metadata']['event_name'], 0, $cut_pos);

			$link_add = '';
			$event_date = '';
			if (isset($aUnmatched['metadata']['gametime']))
			{
				$event_date = (new DateTime('@' . $aUnmatched['metadata']['gametime']))->format('Y-m-d');
				$link_add = '&eventDate=' . $event_date; 
			}

			$event_search = EventHandler::searchEvent($event_name, true);
			$event_maybe = '';
			if ($event_search != null)
			{
				$event_maybe = '  Match: ' . $event_search[0]->getName() . ' (' . $event_search[0]->getDate() . ') [<a href="/cnadm/?p=addNewFightForm&inEventID=' . $event_search[0]->getID() . '&inFighter1=' . $aSplit[0] . '&inFighter2=' . $aSplit[1] . '">add</a>]';
			}
			else
			{

				$event_maybe = '  No match.. [<a href="/cnadm/?p=addNewEventForm&eventName=' . $event_name . '&eventDate=' . $link_add . '">create</a>] [<a href="http://www.google.se/search?q=tapology ' . $event_name . '">google</a>]';
			}


			
			$event_text = $event_name . ' (' . $event_date . ') ' . $event_maybe;

		}
	}

	$sBookie = BookieHandler::getBookieByID($aUnmatched['bookie_id'])->getName();
  	$sModifiedDate = date("Y-m-d H:i:s", strtotime($aUnmatched['log_date']));
	
  	if ($aUnmatched['type'] == 0)
  	{
  		$sMatchups .= '<tr><td>' . $sModifiedDate . '</td><td><b>' . $sBookie . '</b></td><td>' . $aUnmatched['matchup'] . '</td><td>[<a href="?p=addNewFightForm&inFighter1=' . $aSplit[0] .  '&inFighter2=' . $aSplit[1] . '">add</a>] [<a href="http://www.google.se/search?q=tapology ' . $aUnmatched['matchup'] . '">google</a>] ' . $event_text . '</td></tr>';	
  	}
  	else if ($aUnmatched['type'] == 1)
  	{
  		$sPropsMatchup .= '<tr><td>' . $sModifiedDate . '</td><td><b>' . $sBookie . '</b></td><td>' . $aUnmatched['matchup'] . '</td><td>[<a href="/cnadm/propcorrelation/&bookie_id=' . $aUnmatched['bookie_id'] . '&input_prop=' . $aUnmatched['matchup'] . '">link manually</a>]</td></tr>';		
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

echo '<br /><br />';




?>