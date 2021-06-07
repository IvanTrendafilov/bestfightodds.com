<?php

require_once __DIR__ . "/../bootstrap.php";

use BFO\General\FacebookHandler;
use BFO\General\OddsHandler;
use BFO\General\EventHandler;

if (!FACEBOOK_ENABLED) {
	echo 'Facebook posting disabled';
	exit;
}

$fh = new FacebookHandler();

//Post any new opening odds for matchups that have not yet been posted
$matchups = $fh->getUnpostedMatchups();
foreach ($matchups as $matchup) {
	if ($matchup->isMainEvent() == true) {
		$event = EventHandler::getEvent($matchup->getEventID());
		$odds = OddsHandler::getOpeningOddsForMatchup($matchup->getID());
		$message = '';
		$link = 'https://www.bestfightodds.com/events/' . $event->getEventAsLinkString();

		//Determine who (fighter position, 1 or 2) is the favourite
		$favourite_num = 1;
		if ($odds->getFighterOddsAsDecimal(2) < $odds->getFighterOddsAsDecimal(1)) {
			$favourite_num = 2;
		} else if ($odds->getFighterOddsAsDecimal(1) == $odds->getFighterOddsAsDecimal(2)) {
			$favourite_num = 0; //Even odds
		}


		//If matchup date is not decided (future event) adjust wording accordingly
		$event_message = '';
		if ($event->getID() == PARSE_FUTURESEVENT_ID) {
			//Matchup is assigned to future events (unknown date)
			$event_message = 'in their upcoming/rumoured matchup';
		} else {
			//Matchup is assigned to future events (unknown date)
			$event_message = 'at ' . $event->getName() . ', set to take place on ' . date('F jS', strtotime($event->getDate()));
		}

		if ($favourite_num == 0) {
			//Odds are even
			$message = $matchup->getTeamAsString(1) . ' (' . $odds->getFighterOddsAsString(1) . ') vs. ' . $matchup->getTeamAsString(2) . ' (' . $odds->getFighterOddsAsString(2) . ') opens at even betting odds ' . $event_message;
		} else {
			$message = $matchup->getTeamAsString($favourite_num) . ' opens as a ' . $odds->getFighterOddsAsString($favourite_num) . ' betting favorite over ' . $matchup->getTeamAsString(($favourite_num % 2) + 1) . ' (' . $odds->getFighterOddsAsString(($favourite_num % 2) + 1) . ') ' . $event_message;
		}

		if ($fh->postToFeed($message, $link)) {
			$fh->saveMatchupAsPosted($matchup->getID());
		} else {
			echo 'Failed to post matchup ' . $matchup->getID();
		}
	} else {
		//Not a main event so lets not post it. Mark as posted however
		$fh->saveMatchupAsPosted($matchup->getID(), true);
	}
}

//Post a full event headsup 24 hours prior to the event (only for UFC and Bellator right now)
$events = $fh->getUnpostedEvents();
foreach ($events as $event) {

	if (
		strtolower(substr($event->getName(), 0, 3)) == 'ufc' ||
		strtolower(substr($event->getName(), 0, 8)) == 'bellator'
	) {
		//Compile list of matchups with best odds available
		$fightodds = '';
		$matchups = EventHandler::getMatchups(event_id: $event->getID(), only_with_odds: true);
		if (sizeof($matchups) > 0) {
			foreach ($matchups as $matchup) {
				$odds = OddsHandler::getBestOddsForFight($matchup->getID());
				if ($odds != null) {
					$fightodds .= $matchup->getTeamAsString(1) . " (" . $odds->getFighterOddsAsString(1) . ") vs. " . $matchup->getTeamAsString(2) . " (" . $odds->getFighterOddsAsString(2) . ")\r\n";
				}
			}

			$message = $event->getName() . " is only one day away! Here are the best odds currently available for each fighter and matchup:\r\n\r\n" . $fightodds;
			$link = "https://www.bestfightodds.com/events/" . $event->getEventAsLinkString();

			if ($fh->postToFeed($message, $link)) {
				$fh->saveEventAsPosted($event->getID());
			} else {
				echo 'Failed to post matchup ' . $matchup->getID();
			}
		} else {
			//No odds available, mark as posted
			$fh->saveEventAsPosted($event->getID(), true);
		}
	} else {
		//Not UFC or Bellator, mark as posted
		$fh->saveEventAsPosted($event->getID(), true);
	}
}
