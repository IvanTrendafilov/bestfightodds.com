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
        
        foreach ($oXML->DSI as $cDSI)
        {
            //Matchups (also contains some props):
            if ($cDSI->IDLeague == 'Fighting')
            {
                    if (ParseTools::checkCorrectOdds((string) $cDSI->HomeMoneyLine) && ParseTools::checkCorrectOdds((string) $cDSI->AwayMoneyLine))
                    {
                        //Check if bet is a prop or not
                        if (ParseTools::isProp((string) $cDSI->HomeTeamName) && ParseTools::isProp((string) $cDSI->AwayTeamName))
                        {
                            //Prop, add as such
                            $oParsedSport->addFetchedProp(new ParsedProp(
                                            (string) $cDSI->HomeTeamName,
                                            (string) $cDSI->AwayTeamName,
                                            (string) $cDSI->HomeMoneyLine,
                                            (string) $cDSI->AwayMoneyLine
                            ));
                        }
                        else
                        {
                            //Not a prop, add as matchup
                            $oParsedSport->addParsedMatchup(new ParsedMatchup(
                                            (string) $cDSI->HomeTeamName,
                                            (string) $cDSI->AwayTeamName,
                                            (string) $cDSI->HomeMoneyLine,
                                            (string) $cDSI->AwayMoneyLine
                            ));


                            //Check if a total is available, if so, add it as a prop
                            if (isset($cDSI->AwayTotal) && 
                                isset($cDSI->HomeTotal) && 
                                isset($cDSI->AwayTotalJuice) && 
                                isset($cDSI->HomeTotalJuice) && 
                                trim((string) $cDSI->AwayTotalJuice) != '' && 
                                trim((string) $cDSI->HomeTotalJuice) != '')
                            {
                                //Total exists, add it
                                $oParsedProp = new ParsedProp(
                                              (string) $cDSI->HomeTeamName . ' vs ' . (string) $cDSI->AwayTeamName . ' - OVER ' . $cDSI->AwayTotal,
                                              (string) $cDSI->HomeTeamName . ' vs ' . (string) $cDSI->AwayTeamName . ' - UNDER ' . $cDSI->AwayTotal,
                                              (string) $cDSI->AwayTotalJuice,
                                              (string) $cDSI->HomeTotalJuice);
                          
                                $oParsedSport->addFetchedProp($oParsedProp);
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