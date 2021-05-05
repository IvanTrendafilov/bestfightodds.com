<?php

/**
 * XML Parser
 *
 * Bookie: BetDSITemp
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes (confirmed)
 * Authoritative run: Yes
 *
 * Comment: Temporary version when lines.betdsi.eu is not available. This one uses the JSON feed provided when logging in
 *
 */

use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Utils\Logger;
use BFO\Utils\OddsTools;

class XMLParserBetDSITemp
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sPage)
    {
        $aSports = array();
        $oParsedSport = new ParsedSport('Boxing');

        //Actually JSON
        $json = json_decode($a_sPage, true);

        foreach ($json as $matchup)
        {
            if ($matchup['Category']['Name'] == 'Boxing Matches' && $matchup['IsLive'] == false) 
            {
                //Fixes flipped names like Gastelum K. into K Gastelum
                //$matchup['HomeTeamName'] = preg_replace('/([a-zA-Z\-\s]+)\s([a-zA-Z])\./', '$2 $1', $matchup['HomeTeamName']);
                //$matchup['AwayTeamName'] = preg_replace('/([a-zA-Z\-\s]+)\s([a-zA-Z])\./', '$2 $1', $matchup['AwayTeamName']);

                $oParsedMatchup = new ParsedMatchup(
                    $matchup['HomeTeamName'],
                    $matchup['AwayTeamName'],
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][0]['Value']),
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][1]['Value'])
                );
                $oParsedMatchup->setCorrelationID((string) $matchup['ID']);

                //Add game time metadata
                $date_obj = new DateTime((string) $matchup['DateOfMatch']);
                $oParsedMatchup->setMetaData('gametime', $date_obj->getTimestamp());

                $oParsedSport->addParsedMatchup($oParsedMatchup);

                //Add total if available
                if (isset($matchup['PreviewOddsTotal']) && count($matchup['PreviewOddsTotal']) >= 2)
                {
                    //Loop through pairs of 1.5, 2.5, ..
                    for ($i = 0; $i < count($matchup['PreviewOddsTotal']); $i += 2)
                    {
                        if ($matchup['PreviewOddsTotal'][$i]['SpecialBetValue'] == $matchup['PreviewOddsTotal'][$i + 1]['SpecialBetValue'])
                        {
                            $oParsedProp = new ParsedProp(
                                $matchup['HomeTeamName'] . ' - ' . $matchup['AwayTeamName'] . ' : ' . $matchup['PreviewOddsTotal'][$i]['Title'] . ' rounds',
                                $matchup['HomeTeamName'] . ' - ' . $matchup['AwayTeamName'] . ' : ' . $matchup['PreviewOddsTotal'][$i + 1]['Title'] . ' rounds',
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i]['Value']),
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i + 1]['Value'])
                            );
                            //Add correlation ID
                            $oParsedProp->setCorrelationID((string) $matchup['ID']);
                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                    }


                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 3)
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