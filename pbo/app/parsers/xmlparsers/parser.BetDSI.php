<?php

/**
 * XML Parser
 *
 * Bookie: BetDSI
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes (confirmed)
 *
 * Comment: Dev version
 *
 */

use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Utils\Logger;
use BFO\Utils\OddsTools;
use BFO\Parser\Utils\ParseTools;

class XMLParserBetDSI
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sXML)
    {

        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('Boxing');
        
        foreach ($oXML->Sport as $sport_node)
        {
            if ($sport_node['Name'] == 'Boxing')
            {
                foreach ($sport_node->Event as $event_node)
                {        
                    foreach ($event_node->Match as $match_node)
                    {
                        $competitors = explode(' - ', $match_node['Name']);
                        $odds = [];
                        foreach ($match_node->Bet as $bet_node)
                        {                
                            if ($bet_node['Name'] == 'Fight Odds')
                            {
                                foreach($bet_node->Odd as $odd_node)
                                {                        
                                    $odds[((int) $odd_node['Name']) - 1] = OddsTools::convertDecimalToMoneyline($odd_node['Value']);
                                }

                                if (ParseTools::checkCorrectOdds((string) $odds[0]) && ParseTools::checkCorrectOdds((string) $odds[1]))
                                {
           
                                    $oParsedMatchup = new ParsedMatchup(
                                        (string) $competitors[0],
                                        (string) $competitors[1],
                                        (string) $odds[0],
                                        (string) $odds[1]
                                     );

                                    //Add time of matchup as metadata
                                    if (isset($match_node['StartDate']))
                                    {
                                        $oGameDate = new DateTime($match_node['StartDate']);
                                        $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                                    }

                                    $oParsedSport->addParsedMatchup($oParsedMatchup);
                                }
                            }
                            else if ($bet_node['Name'] == 'Total Rounds')
                            {
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                    (string) $match_node['Name'] . ' :: ' . $bet_node['Name'] . ' ' . $bet_node->Odd[0]['Name'] . ' ' . $bet_node->Odd[0]['SpecialBetValue'],
                                    (string) $match_node['Name'] . ' :: ' . $bet_node['Name'] . ' ' . $bet_node->Odd[1]['Name'] . ' ' . $bet_node->Odd[1]['SpecialBetValue'],
                                    (string) OddsTools::convertDecimalToMoneyline($bet_node->Odd[0]['Value']),
                                    (string) OddsTools::convertDecimalToMoneyline($bet_node->Odd[1]['Value'])));
                            }
                            else
                            {
                                Logger::getInstance()->log("New type of bet type: " . $bet_node['Name'] , -1);
                            }
                        }
                        

                    }


                }


            }

        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 10)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        return [$oParsedSport];
    }

    /**
     * Grabs the string 'TEAM1 vs TEAM2' from a string by using regexp
     *
     * @param String $a_sString Input string
     * @return String The vs string
     */
    private function getMatchupFromString($a_sString)
    {
        return $a_sString;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>