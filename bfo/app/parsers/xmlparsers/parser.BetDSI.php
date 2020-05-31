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
        $oXML = simplexml_load_string($a_sXML);
        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        //Custom sort function to arrange bet nodes
        function odd_node_sort($a, $b)
        {
            if ((int) $a['Name'] == (int) $b['Name']) return 0;
            return ((int)$a['Name'] < (int)$b['Name'])?-1:1;
        }

        $aSports = array();
        $oParsedSport = new ParsedSport('MMA');

        Logger::getInstance()->log("Feed date: " . trim($oXML->Date), 0);
        if (trim($oXML->Date) == 'Fri May  1 13:50:02 CST 2020' || trim($oXML->Date) == 'Fri May  8 04:25:01 CST 2020')
        
        {
            Logger::getInstance()->log("Old feed detected. (" . trim($oXML->Date) . ") Bailing", -1);
            return [$oParsedSport];
        }

        //Store as latest feed available for ProBoxingOdds.com
        $rStoreFile = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/betdsi-latest.xml', 'w');
        fwrite($rStoreFile, $a_sXML);
        fclose($rStoreFile);
        
        foreach ($oXML->Sport as $sport_node)
        {
            if ($sport_node['Name'] == 'MMA')
            {
                foreach ($sport_node->Event as $event_node)
                {
                    foreach ($event_node->Match as $match_node)
                    {
                        if ($match_node['MatchType'] != 'Live')
                        {

                            $competitors = explode(' - ', $match_node['Name']);
                            $odds = [];
                            foreach ($match_node->Bet as $bet_node)
                            {
                                if ($bet_node['Name'] == 'Bout Odds' || $bet_node['Name'] == 'Match Winner')
                                {
                                    //Sort Odd nodes
                                    $bet_node->Odd = usort($bet_node->Odd,"odd_node_sort");

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

                                        //Add time of matchup as metadata
                                        if (isset($match_node['StartDate']))
                                        {
                                            $oGameDate = new DateTime($match_node['StartDate']);
                                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                                        }

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
                                        foreach ($bet_node->Odd as $odd_node)
                                        {
                                            $oParsedProp = new ParsedProp(
                                                (string) $competitors[0] . ' vs ' . $competitors[1] . ' :: ' . $odd_node['Name'],
                                                '',
                                                OddsTools::convertDecimalToMoneyline($odd_node['Value']),
                                                '-99999');
                                            $oParsedProp->setCorrelationID((string) $match_node['Name']);
                                            $oParsedSport->addFetchedProp($oParsedProp);
                                        }
                                    }
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