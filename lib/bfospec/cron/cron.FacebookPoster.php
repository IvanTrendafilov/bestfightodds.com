<?php

require_once('lib/bfocore/general/class.FacebookHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('config/inc.facebookConfig.php');

if (FACEBOOK_ENABLED == false)
{
	echo 'Facebook disabled';
	exit;
}


$fh = new FacebookHandler();



$matchups = $fh->getUnpostedMatchups();
foreach ($matchups as $matchup)
{
	if ($matchup->isMainEvent() == true)
	{
		$event = EventHandler::getEvent($matchup->getEventID());
		$odds = OddsHandler::getOpeningOddsForMatchup($matchup->getID());

		//Determine who is the favourite

		//TODO: handle future events wording

		$message = $matchup->getTeamAsString(1) . ' opens as a ' . $odds->getFighterOddsAsString(1) . ' betting favorite over ' . $matchup->getTeamAsString(2) . ' (' . $odds->getFighterOddsAsString(2) . ') at ' . $event->getName() . ', set to take place on ' . date('F jS', strtotime($event->getDate()));
		$link = 'https://www.bestfightodds.com/events/' . $event->getEventAsLinkString(); 

		if ($fh->postToFeed($message, $link))
		{
			//Success, TODO: mark as posted
		}
	}
	else
	{
		//Not a main event so lets not post it. Mark as posted however
		//TODO: Mark as posted
	}
}









//$fh->getUnpostedEvents();







//Loop through upcoming events and find unfacebooked matchups



//Filter out only the main events for now



//Check if we have done a full event 24 hour prior to event posting of the odds (UFC and Bellator only)




//$message = 'Peter Werdum opens as a -400 betting favourite over Ben Rothwell (+200) at UFC 197: Werdum vs. Rothwell, set for October 5th';
//$link = 'https://www.bestfightodds.com/events/ufc-on-fox-20-holm-vs-shevchenko-1114';




?>