<?php

/**
 * XML Parser
 *
 * Bookie: Pinnacle
 * Sport: Boxing
 *
 * Comment: Prod version
 *
 */
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Utils\Logger;
use BFO\Parser\Utils\ParseTools;
use BFO\General\BookieHandler;
 
class XMLParserPinnacle
{
    private $bAuthorativeRun = false;
    private $oParsedSport;

    public function parseXML($sInput)
    {
        $this->oParsedSport = new ParsedSport('Boxing');
        //Actually JSON
        //We already have the fixtures ($sInput) so let's grab the actual odds here:
        $sCN = BookieHandler::getChangeNum(9); //TODO: bookie_id is hardcoded here..
        $sOddsJSON = ParseTools::retrievePageFromURL('https://api.pinnacle.com/v1/odds?sportId=6&since=' . $sCN . '&isLive=0&oddsFormat=AMERICAN');

        $aFetchedFixtures = json_decode($sInput, true);
        $aFetchedOdds = json_decode($sOddsJSON, true);
        
        //Loop through fixtures
        foreach ($aFetchedFixtures['league'] as $aLeague)
        {
            foreach ($aLeague['events'] as $aEvent)
            {
                if ($aEvent['status'] != 'H') //Status H = temporary unavailable
                {
                    $aOdds = $this->findMatchingOdds((int) $aEvent['id'], $aFetchedOdds);
                    if ($aOdds != null)
                    {
                        $this->parseEntry($aEvent, $aOdds);
                    }
                    else
                    {
                        Logger::getInstance()->log("Notice: Did not find matching odds for fixture: " . $aEvent['id'], 0);
                    }
                }
            }
        } 

        //Declare authorative run if we fill the criteria
        if (count($this->oParsedSport->getParsedMatchups()) >= 5 && $sCN == '-1')
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run (changenum was omitted)", 0);
        }


        //Before finishing up, save the changenum to be able to fetch future feeds
        $sCN = trim((string) $aFetchedOdds['last']);
        if ($sCN != '-1' && $sCN != null && $sCN != '')
        {
            //Store the changenum - WARNING, bookie_id is hardcoded here, should be fixed..
            $sCN = ((float) $sCN);
            if (BookieHandler::saveChangeNum(9, $sCN))
            {
                Logger::getInstance()->log("ChangeNum (for odds call) stored OK: " . $sCN, 0);
            }
            else
            {
                Logger::getInstance()->log("Error: ChangeNum (for odds call) was not stored", -2);
            }
        }
        else
        {
            Logger::getInstance()->log("Error: Bad ChangeNum in feed", -2);
        }

        return [$this->oParsedSport];
    }

    private function findMatchingOdds($iEventID, $aFetchedOdds)
    {
        foreach ($aFetchedOdds['leagues'] as $aLeague)
        {
            foreach ($aLeague['events'] as $aEvent)
            {
                if ($aEvent['id'] == $iEventID)
                {
                    return $aEvent;
                }
            }
        }
        return null;
    }

    private function parseEntry($aEvent, $aOdds)
    {
        if (!isset($aOdds['periods'][0]['moneyline']))
        {
            Logger::getInstance()->log("Notice: Odds not set for: " . $aEvent['home'] . ' vs ' . $aEvent['away'], 0);
            return false;
        }

        $oParsedMatchup = new ParsedMatchup(
                        $aEvent['home'],
                        $aEvent['away'],
                        $aOdds['periods'][0]['moneyline']['home'],
                        $aOdds['periods'][0]['moneyline']['away']
        );
        $oGameDate = new DateTime($aEvent['starts']);
        $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
        //Add ID as correlation ID
        $oParsedMatchup->setCorrelationID($aEvent['id']);
        $this->oParsedSport->addParsedMatchup($oParsedMatchup);

        if (isset($aOdds['periods'][0]['totals']))
        {
            //Totals exist, add it
            $oParsedProp = new ParsedProp(
                              $aEvent['home'] . ' vs ' . $aEvent['away'] . ' - OVER ' . $aOdds['periods'][0]['totals'][0]['points'],
                              $aEvent['home'] . ' vs ' . $aEvent['away'] . ' - UNDER ' . $aOdds['periods'][0]['totals'][0]['points'],
                              $aOdds['periods'][0]['totals'][0]['over'],
                              $aOdds['periods'][0]['totals'][0]['under']);
            $oParsedProp->setCorrelationID($aEvent['id']);
            $this->oParsedSport->addFetchedProp($oParsedProp);
        }
        return true;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }
}
?>

