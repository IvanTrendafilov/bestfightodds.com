<?php

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
        $rStoreFile = fopen('/var/www/vhosts/bestfightodds.com/httpdocs/app/front/externalfeeds/betdsi-latest.xml', 'w');
        fwrite($rStoreFile, $a_sXML);
        fclose($rStoreFile);

        $oXML = simplexml_load_string($a_sXML);
        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();
        $oParsedSport = new ParsedSport('MMA');
        
        foreach ($oXML->Leagues->league as $cLeague)
        {
            //Matchups (also contains some props):
            if (substr(trim((string) $cLeague['Description']), 0, 3) == 'MMA')
            {
                foreach ($cLeague->game as $cGame)
                {
                    $bSkip = false;
                    //Fetch banner for this game using xpath expression. This is done to check if this is a live bet or not. Note that an banner may not always be returned and instead a game is returned
                    $cBanner = $cGame->xpath('preceding::*[1]');
                    //Check if live betting, if so it should be skipped
                    if (substr($cBanner[0]['vtm'], 0, 21) == 'LIVE IN FIGHT BETTING')
                    {
                        $bSkip = true;
                    }

                    $cLine = $cGame->line;

                    if ($bSkip != true && ParseTools::checkCorrectOdds((string) $cLine['voddst']) && ParseTools::checkCorrectOdds((string) $cLine['hoddst']))
                    {
                        //Check if bet is a prop or not
                        if (ParseTools::isProp((string) $cGame['vtm']) && ParseTools::isProp((string) $cGame['htm']))
                        {
                            //Prop, add as such
                            $oParsedSport->addFetchedProp(new ParsedProp(
                                            (string) $cGame['vtm'],
                                            (string) $cGame['htm'],
                                            (string) $cLine['voddst'],
                                            (string) $cLine['hoddst']
                            ));
                        }
                        else
                        {
                            //Not a prop, add as matchup
                            $oParsedSport->addParsedMatchup(new ParsedMatchup(
                                            (string) $cGame['vtm'],
                                            (string) $cGame['htm'],
                                            (string) $cLine['voddst'],
                                            (string) $cLine['hoddst']
                            ));


                            //Check if a total is available, if so, add it as a prop
                            if (isset($cLine['unt']) && 
                                isset($cLine['ovoddst']) && 
                                isset($cLine['unoddst']) && 
                                trim((string) $cLine['ovoddst']) != '' && 
                                trim((string) $cLine['unoddst']) != '')
                            {
                                //Total exists, add it
                                $oParsedProp = new ParsedProp(
                                              (string) $cGame['vtm'] . ' vs ' . (string) $cGame['htm'] . ' - OVER ' . (string) $cLine['unt'],
                                              (string) $cGame['vtm'] . ' vs ' . (string) $cGame['htm'] . ' - UNDER ' . (string) $cLine['unt'],
                                              (string) $cLine['ovoddst'],
                                              (string) $cLine['unoddst']);
                          
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }

                        }
                    }
                }
            }
            //Props:
            else if (substr(trim((string) $cLeague['Description']), 0, 18) == 'MARTIAL ARTS PROPS')
            {
                foreach ($cLeague->game as $cGame)
                {
                    //Check if prop is a Yes/No prop, if so we add both sides as options
                    if (count($cGame->line == 2) && strcasecmp($cGame->line[0]['tmname'], 'Yes') == 0 && strcasecmp($cGame->line[1]['tmname'], 'No') == 0)
                    {
                        //Multi line prop (Yes/No)
                        if (ParseTools::checkCorrectOdds((string) $cGame->line[0]['odds']) && ParseTools::checkCorrectOdds((string) $cGame->line[1]['odds']))
                        {
                            $oParsedSport->addFetchedProp(new ParsedProp(
                                            str_replace(' VS.', ' VS. ', (string) (string) trim($cGame['htm'], " -") . ' ' . $cGame->line[0]['tmname']),
                                            str_replace(' VS.', ' VS. ', (string) (string) trim($cGame['htm'], " -") . ' ' . $cGame->line[1]['tmname']),
                                            (string) $cGame->line[0]['odds'],
                                            (string) $cGame->line[1]['odds']
                            ));
                        }
                    }
                    else
                    {
                        //Single line props
                        foreach ($cGame->line as $cLine)
                        {
                            if (ParseTools::checkCorrectOdds((string) $cLine['odds']))
                            {
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                                str_replace(' VS.', ' VS. ', (string) (string) trim($cGame['htm'], " -") . ' ' . $cLine['tmname']),
                                                '',
                                                (string) $cLine['odds'],
                                                '-99999'
                                ));
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

    /**
     * Grabs the string 'TEAM1 vs TEAM2' from a string by using regexp
     *
     * @param String $a_sString Input string
     * @return String The vs string
     */
    private function getMatchupFromString($a_sString)
    {
        return $sMatchup;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>