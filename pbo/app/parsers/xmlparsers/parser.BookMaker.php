<?php

/**
 * XML Parser
 *
 * Bookie: BookMaker
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
class XMLParserBookMaker
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

        foreach ($oXML->Leagues->league as $cLeague)
        {
            //Matchups (also contains some props):
            if (substr(trim((string) $cLeague['Description']), 0, 6) == 'BOXING' || 
                $cLeague['Description'] == 'MC GREGOR VS MAYWEATHER JR FIGHT')
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

                            $oParsedMatchup = new ParsedMatchup(
                                            (string) $cGame['vtm'],
                                            (string) $cGame['htm'],
                                            (string) $cLine['voddst'],
                                            (string) $cLine['hoddst']
                            );

                            $oGameDate = new DateTime((string) $cGame['gmdt'] . ' ' . (string) $cGame['gmtm'] . ' EST');
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                            $oParsedSport->addParsedMatchup($oParsedMatchup);


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
            else if (substr(trim((string) $cLeague['Description']), 0, 12) == 'BOXING PROPS')
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
        return $sMatchup;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>