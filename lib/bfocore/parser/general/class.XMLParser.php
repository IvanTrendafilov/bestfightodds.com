<?php

require_once('lib/bfocore/parser/general/inc.ParserMain.php');
require_once('config/inc.parseConfig.php');

/**
 * Main XML parser - This is where the magic happens..
 *
 * @author Christian Nordvaller
 */
class XMLParser
{
    /**
     * Retrieves the feed from the bookie and launches the bookie specific XML parser. The
     * result is then passed to the parseEvents function to store the new odds.
     *
     * @param string $a_sBookie Bookie name
     * @return boolean If dispatch was successful or not
     *
     */
    public static function dispatch($a_oParser)
    {
        $oLogger = Logger::getInstance();
        $oLogger->log("============== " . $a_oParser->getName() . " ===============");

        $fStartTime = microtime(true);

        //Fetch XML file. But first check if mock feeds mode is on, if so fetch feeds from static files instead of real URL feeds
        $sXML = null;
        if (PARSE_MOCKFEEDS_ON == true)
        {
            $oLogger->log("Note: Using mock file at " . PARSE_MOCKFEEDS_DIR . $a_oParser->getMockFile() . "", 0);
            $sXML = ParseTools::retrievePageFromFile(PARSE_MOCKFEEDS_DIR . $a_oParser->getMockFile());
        }
        else
        {
            $sURL = $a_oParser->getParseURL();
            if ($a_oParser->hasChangenumInUse())
            {
                $iChangeNum = BookieHandler::getChangeNum($a_oParser->getBookieID());
                if ($iChangeNum != -1)
                {
                    $sURL .= $a_oParser->getChangenumSuffix() . $iChangeNum;    
                }
            }

            //Check if page already has been fetched using multifetch
            $sXML = ParseTools::getStoredContentForURL($sURL);
            if ($sXML == null)
            {
                $oLogger->log("Note: URL was NOT prefetched. Fetching..", -1);
                //No stored page, retrieve the URL
                $sXML = ParseTools::retrievePageFromURL($sURL);
            }
            else
            {
                $oLogger->log("Note: URL was prefetched", 0);
            }
        }

        if ($sXML == 'FAILED')
        {
            $oLogger->log("Error: Failed to open URL (" . $a_oParser->getParseURL() . ")", -2);
            $oLogger->log("===== " . $a_oParser->getName() . " finished in " . round(microtime(true) - $fStartTime, 3) . " =====", 0);
            $oLogger->seperate();
            return false;
        }
        else if ($sXML != null && $sXML != '')
        {
            $oLogger->log("URL (" . $a_oParser->getParseURL() . ") fetched OK in " . round(microtime(true) - $fStartTime, 3), 0);

            require_once('app/parsers/xmlparsers/parser.' . $a_oParser->getName() . '.php');
            $sClassName = 'XMLParser' . $a_oParser->getName();
            $oBookieParser = new $sClassName;

            //Launch bookie-specific parsing
            $aParseResults = $oBookieParser->parseXML($sXML);

            //Merge matchup-objects for the same matchups
            foreach ($aParseResults as $oSport)
            {
                $oSport->mergeMatchups();
            }
            
            //Clear correlation table so that it is not shared between parsers
            ParseTools::clearCorrelations();

            //Pre-populate correlation table with entries from database. These can be overwritten later in the processMoneylines function
            $aCorrelations = OddsHandler::getCorrelationsForBookie($a_oParser->getBookieID());
            foreach ($aCorrelations as $aCorr)
            {
                ParseTools::saveCorrelation($aCorr['correlation'], $aCorr['matchup_id']);
            }
            
            //Remove dupes - DISABLED, function must be fixed
            //$aParseResults = XMLParser::removeMoneylineDupes($aParseResults);
            
            //Pass events/matchups to storage parser
            self::processAll($aParseResults, $a_oParser->getBookieID());
            $aParseResults = self::removePropDupes($aParseResults);
            self::processProps($aParseResults, $a_oParser->getBookieID());
           
            //Store correlations that may have been added in processMoneylines
            $aCorrStoreCol = array();
            foreach ((ParseTools::getAllCorrelations()) as $sCorrKey => $sCorrVal)
            {
                $aCorrStoreCol[] = array('correlation' => $sCorrKey, 'matchup_id' => $sCorrVal);
            }
            OddsHandler::storeCorrelations($a_oParser->getBookieID(), $aCorrStoreCol);
          
            
            $oLogger->log("===== " . $a_oParser->getName() . " finished in " . round(microtime(true) - $fStartTime, 3) . " =====", 0);
            $oLogger->seperate();
            return true;
        }
        else
        {
            $oLogger->log("Warning: No data retrieved from site", -2);
            $oLogger->log("===== " . $a_oParser->getName() . " finished in " . round(microtime(true) - $fStartTime, 3) . " =====", 0);
            $oLogger->seperate();
            return false;
        }
    }

    /**
     *  Cleans dupes. If a dupe is found (two odds for the same fighters), the one with the best arbitrage is picked
     *
     * TODO Warning: This one currently removes any dupes based on only the moneyline odds and NOT spreads and totals. Should be fixed
     */
    public static function removeMoneylineDupes($a_aSports)
    {
        $oLogger = Logger::getInstance();

        foreach ($a_aSports as $oParsedSport)
        {
            $aParsedMatchups = $oParsedSport->getParsedMatchups();
            for ($iY = 0; $iY < sizeof($aParsedMatchups); $iY++)
            {
                for ($iX = 0; $iX < sizeof($aParsedMatchups); $iX++)
                {
                    if ($iX != $iY
                            && $aParsedMatchups[$iY]->getTeamName(1) == $aParsedMatchups[$iX]->getTeamName(1)
                            && $aParsedMatchups[$iY]->getTeamName(2) == $aParsedMatchups[$iX]->getTeamName(2)
                            && !($aParsedMatchups[$iY]->getTeamOdds(1) == $aParsedMatchups[$iX]->getTeamOdds(1)
                            && $aParsedMatchups[$iY]->getTeamOdds(2) == $aParsedMatchups[$iX]->getTeamOdds(2)))
                    {
                        //Found a match
                        $fArbOrig = ParseTools::getArbitrage($aParsedMatchups[$iY]->getTeamOdds(1), $aParsedMatchups[$iY]->getTeamOdds(2));
                        $fArbChal = ParseTools::getArbitrage($aParsedMatchups[$iX]->getTeamOdds(1), $aParsedMatchups[$iX]->getTeamOdds(2));

                        $oLogger->log('Removing dupe: ' . $aParsedMatchups[$iY]->getTeamName(1) . ' vs ' . $aParsedMatchups[$iY]->getTeamName(2));

                        if ($fArbOrig > $fArbChal)
                        {
                            //Original has worse arb than challenger
                            unset($aParsedMatchups[$iY]);
                            $aParsedMatchups = array_values($aParsedMatchups);
                        }
                        else if ($fArbOrig < $fArbChal)
                        {
                            //Challenger has worse arb than original
                            unset($aParsedMatchups[$iX]);
                            $aParsedMatchups = array_values($aParsedMatchups);
                        }
                        else
                        {
                            //Both has the same arb. Remove challenger although either one would do
                            unset($aParsedMatchups[$iX]);
                            $aParsedMatchups = array_values($aParsedMatchups);
                        }

                        $iY = 0;
                        break 1;
                    }
                }
            }

            $oParsedSport->setMatchupList($aParsedMatchups);
        }

        return $a_aSports;
    }

    public static function removePropDupes($a_aSports)
    {
        $oLogger = Logger::getInstance();

        foreach ($a_aSports as $oParsedSport)
        {
            $aParsedProps = $oParsedSport->getFetchedProps();
            for ($iY = 0; $iY < sizeof($aParsedProps); $iY++)
            {
                for ($iX = 0; $iX < sizeof($aParsedProps); $iX++)
                {
                    if ($iX != $iY
                            && $aParsedProps[$iY]->getCorrelationID() == $aParsedProps[$iX]->getCorrelationID()
                            && $aParsedProps[$iY]->getTeamName(1) == $aParsedProps[$iX]->getTeamName(1)
                            && $aParsedProps[$iY]->getTeamName(2) == $aParsedProps[$iX]->getTeamName(2)
                            && !($aParsedProps[$iY]->getTeamOdds(1) == $aParsedProps[$iX]->getTeamOdds(1)
                            && $aParsedProps[$iY]->getTeamOdds(2) == $aParsedProps[$iX]->getTeamOdds(2)))
                    {
                        //Found a match
                        $fArbOrig = ParseTools::getArbitrage($aParsedProps[$iY]->getTeamOdds(1), $aParsedProps[$iY]->getTeamOdds(2));
                        $fArbChal = ParseTools::getArbitrage($aParsedProps[$iX]->getTeamOdds(1), $aParsedProps[$iX]->getTeamOdds(2));

                        $oLogger->log('Removing dupe: ' . $aParsedProps[$iY]->getTeamName(1) . ' vs ' . $aParsedProps[$iY]->getTeamName(2));

                        if ($fArbOrig > $fArbChal)
                        {
                            //Original has worse arb than challenger
                            unset($aParsedProps[$iY]);
                            $aParsedProps = array_values($aParsedProps);
                        }
                        else if ($fArbOrig < $fArbChal)
                        {
                            //Challenger has worse arb than original
                            unset($aParsedProps[$iX]);
                            $aParsedProps = array_values($aParsedProps);
                        }
                        else
                        {
                            //Both has the same arb. Remove challenger although either one would do
                            unset($aParsedProps[$iX]);
                            $aParsedProps = array_values($aParsedProps);
                        }

                        $iY = 0;
                        break 1;
                    }
                }
            }

            $oParsedSport->setPropList($aParsedProps);
        }

        return $a_aSports;
    }

    public static function processAll($a_aParsedSports, $a_iBookieID)
    {
        $oLogger = Logger::getInstance();

        foreach ($a_aParsedSports as $oParsedSport)
        {
            $iMatchupCount = 0;
            $iTotalCount = 0;
            $iMoneylineCount = 0;

            $oLogger->log('-sport: ' . $oParsedSport->getName(), 1);

            //Get all matchups
            $aParsedMatchups = $oParsedSport->getParsedMatchups();

            foreach ($aParsedMatchups as $oParsedMatchup)
            {
                $iTotalCount++;

                $oTempMatchup = new Fight(0, $oParsedMatchup->getTeamName(1), $oParsedMatchup->getTeamName(2), -1);
                $oMatchedMatchup = EventHandler::getMatchingFight($oTempMatchup);


                //If enabled, perform creative matching
                if ($oMatchedMatchup == null && PARSE_CREATIVEMATCHING == true)
                {
                    $oLogger->log('--No matchup found, performing creative matching', 0);
                    $oMatchedMatchup = CreativeMatcher::checkMatching($oParsedMatchup);
                    
                }
                //If enabled, create matchup if missing
                if ($oMatchedMatchup == null && PARSE_CREATEMATCHUPS == true)
                {
                    //Create the matchup and also as a new event
                    //Get the generic event for the date of the matchup. If none can be found, create it
                    $aMeta = $oParsedMatchup->getAllMetaData();
                    $oGenericEvent = null;

                    if (isset($aMeta['gametime']))
                    {
                        $oGenericEvent = EventHandler::getGenericEventForDate(date('Y-m-d', $aMeta['gametime']));    
                    }

                    
                    $iGenericEventID = $oGenericEvent != null ? $oGenericEvent->getID() : PARSE_FUTURESEVENT_ID;

                    $oNewMatchup = new Fight(-1, $oParsedMatchup->getTeamName(1), $oParsedMatchup->getTeamName(2), $iGenericEventID);
                    //TODO: This currently creates duplicate matchups since getFight() caches current matchups in the initial search. The cache needs to be invalidated somehow I guess..

                    $oLogger->log('---Had to add a new matchup: ' . $oParsedMatchup->getTeamName(1) . ' vs. ' . $oParsedMatchup->getTeamName(2), 0);
                    $oMatchedMatchup = EventHandler::getFightByID(EventHandler::addNewFight($oNewMatchup));
                    if ($oMatchedMatchup == null)
                    {
                        $oLogger->log('New matchup was stored but no matchup was found afterwards', -2);
                    }
                }


                if ($oMatchedMatchup != null)
                {
                    $oMatchedEvent = EventHandler::getEvent($oMatchedMatchup->getEventID(), true);

                    if ($oMatchedEvent != null && $oMatchedEvent->getID() == $oMatchedMatchup->getEventID())
                    {
                        $oLogger->log("-event matched as: " . $oMatchedEvent->getName(), 1);
                        $oLogger->log("--- matched matchup: " . $oTempMatchup->getFighter(1) . " vs " . $oTempMatchup->getFighter(2) . " / " . $oMatchedMatchup->getFighter(1) . " vs " . $oMatchedMatchup->getFighter(2), 2);

                        $iMatchupCount++;

                        if (($oMatchedMatchup->getComment() == 'switched' || $oTempMatchup->hasOrderChanged()) && !$oParsedMatchup->isSwitchedFromOutside())
                        {
                            $oParsedMatchup->switchOdds();
                        }

                        // PROCESS MONEYLINES
                        if ($oParsedMatchup->hasMoneyline())
                        {
                            self::processParsedMoneyLines($oMatchedMatchup, $oParsedMatchup, $a_iBookieID);
                            $iMoneylineCount++;
                        }

                        //If parsed matchup contains a correlation ID, we store this in the correlation table so that other functions can be
                        if ($oParsedMatchup->getCorrelationID() != '')
                        {
                            ParseTools::saveCorrelation($oParsedMatchup->getCorrelationID(), $oMatchedMatchup->getID());
                            $oLogger->log("---------- storing correlation ID: " . $oParsedMatchup->getCorrelationID(), 2);
                        }

                        //Store any metadata for the matchup
                        $aMetaData = $oParsedMatchup->getAllMetaData();
                        foreach ($aMetaData as $sMetaKey => $sMetaVal)
                        {
                            EventHandler::setMetaDataForMatchup($oMatchedMatchup->getID(), $sMetaKey, $sMetaVal, $a_iBookieID);
                        }

                    }
                    else
                    {
                        $oLogger->log("--- match wrongfully matched to event OR odds are old", -2);
                    }
                }
                else
                {
                    EventHandler::logUnmatched($oTempMatchup->getFighter(1) . ' vs ' . $oTempMatchup->getFighter(2), $a_iBookieID, 0);
                    $oLogger->log('--- unmatched matchup: ' . $oTempMatchup->getFighter(1) . ' vs ' . $oTempMatchup->getFighter(2) . ' [<a href="?p=addNewFightForm&inFighter1=' . $oTempMatchup->getFighter(1) . '&inFighter2=' . $oTempMatchup->getFighter(2) . '">add</a>] [<a href="http://www.google.se/search?q=' . $oTempMatchup->getFighterAsString(1) . ' vs ' . $oTempMatchup->getFighterAsString(2) . '">google</a>]', -1);
                }
            }
            $oLogger->log("Total matched matchups: " . $iMatchupCount . ' / ' . $iTotalCount, 0);
        }
    }


    public static function processParsedMoneyLines($a_oMatchedMatchup, $a_oParsedMatchup, $a_iBookieID)
    {
        $oLogger = Logger::getInstance();

        $oTempMatchupOdds = new FightOdds($a_oMatchedMatchup->getID(), $a_iBookieID, $a_oParsedMatchup->getTeamOdds(1), $a_oParsedMatchup->getTeamOdds(2), ParseTools::standardizeDate(date('Y-m-d')));

        if (EventHandler::checkMatchingOdds($oTempMatchupOdds))
        {
            $oLogger->log("------- nothing has changed since last odds", 2);
        }
        else
        {
            $oLogger->log("------- adding new odds!", 2);
            EventHandler::addNewFightOdds($oTempMatchupOdds);
        }
    }

    public static function processProps($a_aParsedSports, $a_iBookieID)
    {
        $oLogger = Logger::getInstance();
        $oLogger->log('-props:', 0);

        foreach ($a_aParsedSports as $oParsedSport)
        {
            $oPropParser = new PropParser();
            $iMatchedPropCount = $oPropParser->parseProps($a_iBookieID, $oParsedSport->getFetchedProps());

            $oLogger->log("Total matched props: " . $iMatchedPropCount . ' / ' . $oParsedSport->getPropCount(), 0);
        }
    }

}

?>