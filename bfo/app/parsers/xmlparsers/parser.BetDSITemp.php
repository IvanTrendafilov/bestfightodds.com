<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

/**
 * XML Parser
 *
 * Bookie: BetDSITemp
 * Sport: MMA
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
class XMLParserBetDSITemp
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sPage)
    {
        $aSports = array();
        $oParsedSport = new ParsedSport('MMA');

        //Actually JSON
        $json = json_decode($a_sPage, true);

        foreach ($json as $matchup)
        {
            if (($matchup['SportType']['Name'] == 'MMA' || $matchup['Category']['Name'] == 'UFC') && $matchup['IsLive'] == false) 
            {
                $oParsedMatchup = new ParsedMatchup(
                    $matchup['HomeTeamName'],
                    $matchup['AwayTeamName'],
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][0]['Value']),
                    OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsMoneyLine'][1]['Value'])
                );
                $oParsedMatchup->setCorrelationID((string) $matchup['ID']);
                $oParsedSport->addParsedMatchup($oParsedMatchup);

                //Add total if available
                if (isset($matchup['PreviewOddsTotal']) && count($matchup['PreviewOddsTotal']) == 2)
                {
                    //Loop through pairs of 1.5, 2.5, ..
                    for ($i = 0; $i < count($matchup['PreviewOddsTotal']); $i += 2)
                    {
                        if ($matchup['PreviewOddsTotal'][$i]['SpecialBetValue'] == $matchup['PreviewOddsTotal'][$i + 1]['SpecialBetValue'])
                        {
                            $oParsedProp = new ParsedProp(
                                $matchup['Name'] . ' : ' . $matchup['PreviewOddsTotal'][$i]['Title'] . ' rounds',
                                $matchup['Name'] . ' : ' . $matchup['PreviewOddsTotal'][$i + 1]['Title'] . ' rounds',
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i]['Value']),
                                OddsTools::convertDecimalToMoneyline($matchup['PreviewOddsTotal'][$i + 1]['Value'])
                            );
                        }
                    }

                    //Add correlation ID
                    $oParsedProp->setCorrelationID((string) $matchup['ID']);
                    $oParsedSport->addFetchedProp($oParsedProp);
                }
            }
        }
 
        return [$oParsedSport];
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>