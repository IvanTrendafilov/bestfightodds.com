<?php

/**
 * XML Parser
 *
 * Bookie: BookMaker
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes (confirmed)
 * Authoritiative run: Yes
 *
 * Comment: Prod version
 *
 */
class XMLParserBookMakerLoggedIn
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sXML)
    {
        //Store as latest feed available for ProBoxingOdds.com
        $rStoreFile = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/bookmaker-latest.xml', 'w');
        fwrite($rStoreFile, $a_sXML);
        fclose($rStoreFile);

        //Fetch logged in URL instead and parse that
        $a_sXML = file_get_contents(GENERAL_BASEDIR . '/app/front/externalfeeds/bm_internal.json');
        //Actually JSON
        $json = json_decode($a_sXML, true);

        $aSports = array();
        $oParsedSport = new ParsedSport('MMA');

        foreach ($json['Schedule']['Data']['Leagues']['League'][0]['dateGroup'] as $dategroup)
        {
            foreach ($dategroup['game'] as $game)
            {
                //Regular matchup
                $oParsedSport->addParsedMatchup(new ParsedMatchup(
                    $game['htm'],
                    $game['vtm'],
                    $game['Derivatives']['line'][0]['hoddst'],
                    $game['Derivatives']['line'][0]['voddst']
                ));

                //Totals prop
                if ($game['unt'] != '' && $game['ovt'] != '' && $game['ovoddst'] != '' && $game['unoddst'] != '')
                {
                    $oParsedSport->addFetchedProp(new ParsedProp(
                        $game['htm'] . ' VS ' . $game['vtm'] . ' - OVER ' . $game['Derivatives']['line'][0]['unt'],
                        $game['htm'] . ' VS ' . $game['vtm'] . ' - UNDER ' . $game['Derivatives']['line'][0]['unt'],
                        $game['Derivatives']['line'][0]['ovoddst'],
                        $game['Derivatives']['line'][0]['unoddst']
                    ));
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