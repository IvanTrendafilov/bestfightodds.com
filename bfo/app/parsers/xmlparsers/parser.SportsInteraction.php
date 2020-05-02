<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: SportsInteraction
 * Sport: MMA
 *
 * Authorative run declared: Yes 
 *
 * Comment: Prod version
 *
 */
class XMLParserSportsInteraction
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sXML)
    {
        //Store as latest feed available for ProBoxingOdds.com
        $rStoreFile = fopen(GENERAL_BASEDIR . '/app/front/externalfeeds/sportsint-latest.xml', 'w');
        fwrite($rStoreFile, $a_sXML);
        fclose($rStoreFile);

        $a_sXML = preg_replace("<SportsInteractionLines>", "<SportsInteractionLines>\n", $a_sXML);
        $a_sXML = preg_replace("</SportsInteractionLines>", "\n</SportsInteractionLines>", $a_sXML);

        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }
        if (isset($oXML['reason']))
        {
            Logger::getInstance()->log("Error: " . $oXML['reason'], -2);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('MMA');

        if (isset($oXML->EventType))
        {
            foreach ($oXML->EventType as $cEventType)
            {
                if (trim((string) $cEventType['NAME']) == 'MMA')
                {
                    foreach ($cEventType->Event as $cEvent)
                    {
                        if (strpos(strtoupper($cEvent->Name), 'FIGHT OF THE NIGHT') !== false)
                        {
                            //Fight of the night prop
                            foreach ($this->parseFOTN($cEvent) as $oParsedProp)
                            {
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                        else if ($cEvent->Bet[0]['TYPE'] == "" && !(strpos($cEvent->Name, 'Total Event') !== false))
                        {
                            //Regular matchup
                            $oParsedMatchup = null;
                            if (isset($cEvent->Bet[2]))
                            {
                                if (ParseTools::checkCorrectOdds((string) $cEvent->Bet[0]->Price)
                                        && ParseTools::checkCorrectOdds((string) $cEvent->Bet[2]->Price)
                                        && !isset($cEvent->Bet[3]) //Temporary fix to remove props such as FOTN
                                        && !isset($cEvent->Bet[4]) //Temporary fix to remove props such as FOTN
				                        && !((string) $cEvent->Bet[0]->Price == '-10000' && (string) $cEvent->Bet[2]->Price == '-10000'))
                                {
                                    $oParsedMatchup = new ParsedMatchup(
                                                    (string) $cEvent->Bet[0]->Runner,
                                                    (string) $cEvent->Bet[2]->Runner,
                                                    (string) $cEvent->Bet[0]->Price,
                                                    (string) $cEvent->Bet[2]->Price);
                                }
                            }
                            else
                            {
                                if (ParseTools::checkCorrectOdds((string) $cEvent->Bet[0]->Price)
                                        && ParseTools::checkCorrectOdds((string) $cEvent->Bet[1]->Price)
                                        && !isset($cEvent->Bet[3]) //Temporary fix to remove props such as FOTN
                                        && !isset($cEvent->Bet[4]) //Temporary fix to remove props such as FOTN
				                        && !((string) $cEvent->Bet[0]->Price == '-10000' && (string) $cEvent->Bet[1]->Price == '-10000'))
                                {
                                    $oParsedMatchup = new ParsedMatchup(
                                                    (string) $cEvent->Bet[0]->Runner,
                                                    (string) $cEvent->Bet[1]->Runner,
                                                    (string) $cEvent->Bet[0]->Price,
                                                    (string) $cEvent->Bet[1]->Price);
                                }
                            }
                            if ($oParsedMatchup != null)
                            {
                                $oParsedMatchup->setCorrelationID(trim($cEvent->Name));
                                $oParsedSport->addParsedMatchup($oParsedMatchup);
                            }
                        }
                        else if ($cEvent->Bet[0]['TYPE'] != "" && (count(array_intersect(['yes','no','over','under'], 
                                                                   [strtolower($cEvent->Bet[0]->Runner), strtolower($cEvent->Bet[1]->Runner)])) > 0))
                        {
                            //Two side prop bet since bet 1 or 2 contains the words yes,no,over or under
                            $oParsedProp = $this->parseTwoSideProp($cEvent);
                            if ($oParsedProp != null)
                            {
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                        else
                        {
                            //Prop - All other
                            foreach ($cEvent->Bet as $cBet)
                            {
                                if (ParseTools::checkCorrectOdds((string) $cBet->Price))
                                {
                                    $oTempProp = new ParsedProp(
                                                    (string) $cEvent->Name . ' ::: ' . $cBet['TYPE'] . ' :: ' . $cBet->BetTypeExtraInfo . ' : ' . $cBet->Runner,
                                                    '',
                                                    (string) $cBet->Price,
                                                    '-99999');
                                    $oTempProp->setCorrelationID(trim($cEvent->Name));
                                    $oParsedSport->addFetchedProp($oTempProp);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 5 && $oXML->getName() != 'feed-unchanged')
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        $aSports[] = $oParsedSport;
        return $aSports;
    }


    private function parseFOTN($a_cEvent)
    {
        $aRet = [];
        foreach ($a_cEvent->Bet as $cBet)
        {
            $oTempProp = new ParsedProp(
                (string) $a_cEvent->Name . ' - ' . $cBet->Runner,
                '',
                (string) $cBet->Price,
                '-99999');

            $oTempProp->setCorrelationID(trim($a_cEvent->Name));
            $aRet[] = $oTempProp;
        }
        return $aRet;
    }

    private function parseTwoSideProp($a_cEvent)
    {
        //Find tie or draw and exclude it
        $aBets = [];
        foreach ($a_cEvent->Bet as $key => $cBet)
        {
            if ($cBet->Runner != 'Tie' && $cBet->Runner != 'Draw')
            {
                $aBets[] = $cBet;
            }
        }

        if (count($aBets) == 2)
        {
            $oTempProp = new ParsedProp(
                            (string) $a_cEvent->Name . ' ::: ' . $aBets[0]['TYPE'] . ' :: ' . $aBets[0]->Handicap . ' ' . $aBets[0]->BetTypeExtraInfo . ' : ' . $aBets[0]->Runner,
                            (string) $a_cEvent->Name . ' ::: ' . $aBets[1]['TYPE'] . ' :: ' . $aBets[1]->Handicap . ' ' . $aBets[1]->BetTypeExtraInfo . ' : ' . $aBets[1]->Runner,
                            (string) $aBets[0]->Price,
                            (string) $aBets[1]->Price);
            $oTempProp->setCorrelationID(trim($a_cEvent->Name));
            return $oTempProp;
        }
        Logger::getInstance()->log("Invalid special two side prop: " . $a_cEvent->Name . ' ::: ' . $aBets[0]['TYPE'] . ' :: ' .  $aBets[0]->Handicap . ' ' . $aBets[0]->BetTypeExtraInfo . ' : ' . $aBets[0]->Runner, -2);
        return null;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        //Only report as an authoritive run if changenum has been reset. This in combination with the number of parsed matchups declares
        if (isset($a_aMetadata['changenum']) && $a_aMetadata['changenum'] == -1)
        {
            return $this->bAuthorativeRun;
        }
        return false;
    }
}

?>