<?php

/**
 * XML Parser
 *
 * Bookie: William Hill
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: No
 * Authorative run declared: Yes
 *
 * Comment: Prod version but based on web interface. Temporary while feed is unavailable
 *
 * https://eu-offering.kambicdn.org/offering/v2018/rbse/listView/boxing.json?lang=sv_SE&market=SE&client_id=2&channel_id=1&useCombined=true
 *
 */
class XMLParserWilliamHillWeb
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sXML)
    {
        $json = json_decode($a_sXML);

        if ($json == false)
        {
            Logger::getInstance()->log("Warning: JSON broke!!", -1);
        }

        $aSports = array();
        $oParsedSport = new ParsedSport('Boxing');

        foreach ($json->events as $event)
        {
            if ($event->sport = 'BOXING')
            {
                $team1name = $event->event->homeName;
                if (strpos($event->event->homeName, ', ') !== false) {
                $homeparts = explode(', ', $event->event->homeName);
                $team1name = $homeparts[1] . ' ' . $homeparts[0];
                }
            
                $team2name = $event->event->awayName;
                if (strpos($event->event->awayName, ', ') !== false) {
                $awayparts = explode(', ', $event->event->awayName);
                $team2name = $awayparts[1] . ' ' . $awayparts[0];
                }
            
                $team1odds = $event->betOffers[0]->outcomes[0]->oddsAmerican;
                $team2odds = $event->betOffers[0]->outcomes[2]->oddsAmerican;
                 
                if (ParseTools::checkCorrectOdds($team1odds) && ParseTools::checkCorrectOdds($team2odds))
                {
                    $oTempMatchup = new ParsedMatchup(
                        $team1name,
                        $team2name,
                        $team1odds,
                        $team2odds
                    );

                    //Add time of matchup as metadata
                    $oGameDate = null;
                    if ($event->groupId == '2000091527')
                    {
                        $oGameDate = new DateTime('2019-12-31 00:00:00');
                    }
                    else
                    {
                        $oGameDate = new DateTime($event->start);    
                    }
                    $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());

                    $oParsedSport->addParsedMatchup($oTempMatchup);
                }
                else
                {
                    Logger::getInstance()->log("Warning: Invalid odds format: " . $team1odds . "/" . $team2odds, -1);
                }
                
            }
        }

        //Declare authorative run if we fill the criteria
        if ($json != false && count($oParsedSport->getParsedMatchups()) > 7)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        $aSports[] = $oParsedSport;
        return $aSports;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>