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
 *
 * Comment: Prod version
 *
 */
class XMLParserBookMaker
{

    public function parseXML($a_sXML)
    {

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


                            //Check if a total is available, if so, add it as a prop. line[0] is always over and line[1] always under
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
                    //Grab matchup from header, will be part of prop


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

        $aSports[] = $oParsedSport;

        return $aSports;
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

}

?>