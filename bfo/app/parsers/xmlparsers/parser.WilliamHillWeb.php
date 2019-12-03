<?php

/**
 * XML Parser
 *
 * Bookie: William Hill
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 * Authorative run declared: No
 *
 * Comment: Prod version but based on web interface. Temporary while feed is unavailable
 *
 * https://eu-offering.kambicdn.org/offering/v2018/rbse/listView/ufc_mma.json?lang=sv_SE&market=SE&client_id=2&channel_id=1&useCombined=true
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
        $oParsedSport = new ParsedSport('MMA');

        foreach ($json->events as $event)
        {
            if ($event->sport = 'MARTIAL_ARTS')
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
                $team2odds = $event->betOffers[0]->outcomes[1]->oddsAmerican;
                 
                if (ParseTools::checkCorrectOdds($team1odds) && ParseTools::checkCorrectOdds($team2odds))
                {
                    $oTempMatchup = new ParsedMatchup(
                        $team1name,
                        $team2name,
                        $team1odds,
                        $team2odds
                    );
                    $oParsedSport->addParsedMatchup($oTempMatchup);
                }
                else
                {
                    Logger::getInstance()->log("Warning: Invalid odds format: " . $team1odds . "/" . $team2odds, -1);
                }
                
            }
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