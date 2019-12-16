<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('config/inc.config.php');

$oXML = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><bfo_feed></bfo_feed>');
$aEvent = EventHandler::getAllUpcomingEvents();
$iLatestTime = 0;

//List all events
foreach ($aEvent as $oEvent)
{
    $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
    if (sizeof($aFights) > 0 && $oEvent->isDisplayed())
    {
        //Check if event is named FUTURE EVENTS, if so, do not display the date
        $sAddDate = $oEvent->getDate();
        if (strtoupper($oEvent->getName()) == 'FUTURE EVENTS')
        {
            $sAddDate = '9999-12-31';
        }

		$oEventXML = $oXML->addChild('event');
        $oEventXML->addAttribute('name', $oEvent->getName());
        $oEventXML->addAttribute('date', $sAddDate);
        $oEventXML->addAttribute('id', $oEvent->getID());

        foreach ($aFights as $oFight)
        {
            $oBestOdds = EventHandler::getBestOddsForFight($oFight->getID());

            $oMatchupXML = $oEventXML->addChild('matchup');
            $oMatchupXML->addAttribute('name', $oFight->getFighterAsString(1) . ' vs. ' . $oFight->getFighterAsString(2));
            $oMatchupXML->addAttribute('id', $oFight->getID());

            $oFighter1XML = $oMatchupXML->addChild('fighter');
            $oFighter1XML->addAttribute('name', $oFight->getFighterAsString(1));
            $oFighter1XML->addAttribute('id', $oFight->getFighterID(1));
            $oFighter1XML->addAttribute('moneyline', $oBestOdds->getFighterOdds(1));
            $oFighter2XML = $oMatchupXML->addChild('fighter');
            $oFighter2XML->addAttribute('name', $oFight->getFighterAsString(2));
            $oFighter2XML->addAttribute('id', $oFight->getFighterID(2));
            $oFighter2XML->addAttribute('moneyline', $oBestOdds->getFighterOdds(2));
        }
    }
}

echo $oXML->asXML();

?>


