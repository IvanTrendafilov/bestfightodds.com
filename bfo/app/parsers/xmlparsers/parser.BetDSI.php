<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: BetDSI
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes (confirmed)
 * Authoritative run: Yes
 *
 * Comment: Prod version
 *
 */
class XMLParserBetDSI
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sXML)
    {
        //Store as latest feed available for ProBoxingOdds.com
        $rStoreFile = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/betdsi-latest.xml', 'w');
        fwrite($rStoreFile, $a_sXML);
        fclose($rStoreFile);

        $oXML = simplexml_load_string($a_sXML);
        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();
        $oParsedSport = new ParsedSport('MMA');
        
        foreach ($oXML->Sport as $sport_node)
        {
            if ($sport_node['Name'] == 'MMA')
            {
                foreach ($sport_node->Event as $event_node)
                {
                    foreach ($event_node->Match as $match_node)
                    {
                        $competitors = explode(' - ', $match_node['Name']);
                        $odds = [];
                        foreach ($match_node->Bet as $bet_node)
                        {
                            if ($bet_node['Name'] == 'Bout Odds' || $bet_node['Name'] == 'Match Winner')
                            {
                                //Regular matchup odds
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
                                    $oParsedMatchup->setCorrelationID((string) $match_node['Name']);

                                    //Add header of matchup as metadata
                                    if (isset($event_node['Name']))
                                    {
                                        $oParsedMatchup->setMetaData('event_name', (string) $event_node['Name']);
                                    }

                                    $oParsedSport->addParsedMatchup($oParsedMatchup);    
                                }
                            }
                            else
                            {
                                //Prop bet
                                if (count($bet_node->Odd) == 2)
                                {
                                    //Probably two way bet (e.g. Yes/No, Over/Under)
                                    $oParsedProp = new ParsedProp(
                                        (string) $competitors[0] . ' vs ' . $competitors[1] . ' :: ' . $bet_node->Odd[0]['Name'] . (isset($bet_node->Odd[0]['SpecialBetValue']) ? ' ' . $bet_node->Odd[0]['SpecialBetValue'] : ''),
                                        (string) $competitors[0] . ' vs ' . $competitors[1] . ' :: ' . $bet_node->Odd[1]['Name'] . (isset($bet_node->Odd[1]['SpecialBetValue']) ? ' ' . $bet_node->Odd[1]['SpecialBetValue'] : ''),
                                        OddsTools::convertDecimalToMoneyline($bet_node->Odd[0]['Value']),
                                        OddsTools::convertDecimalToMoneyline($bet_node->Odd[1]['Value'])
                                    );
                                    $oParsedProp->setCorrelationID((string) $match_node['Name']);
                                    $oParsedSport->addFetchedProp($oParsedProp);
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
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 5 && $oParsedSport->getPropCount() >= 2)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        return [$oParsedSport];
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>