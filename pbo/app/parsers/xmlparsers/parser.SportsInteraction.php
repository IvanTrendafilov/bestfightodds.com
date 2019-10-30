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
      //Temporary store of XML:
      //$rStoreFile = fopen('/var/www/vhosts/bestfightodds.com/httpdocs/storedfeeds/' . 'sportsint-' . date('Ymd-Hi') . '.xml', 'a');
      //fwrite($rStoreFile, $a_sXML);
      //fclose($rStoreFile);


        $a_sXML = ereg_replace("<SportsInteractionLines>", "<SportsInteractionLines>\n", $a_sXML);
        $a_sXML = ereg_replace("</SportsInteractionLines>", "\n</SportsInteractionLines>", $a_sXML);

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

        $oParsedSport = new ParsedSport('Boxing');

        if (isset($oXML->EventType))
        {
            foreach ($oXML->EventType as $cEventType)
            {
                if (trim((string) $cEventType['NAME']) == 'Boxing')
                {
                    foreach ($cEventType->Event as $cEvent)
                    {

                        if ($cEvent->Bet[0]['TYPE'] == "" && !ParseTools::isProp($cEvent->Bet[0]->Runner))
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

                                    //Check if a draw is available, if so, add as prop
                                    if (ParseTools::formatName((string) $cEvent->Bet[1]->Runner) == 'DRAW')
                                    {
                                        $oParsedSport->addFetchedProp(new ParsedProp(
                                                (string) $cEvent->Bet[0]->Runner . ' VS. ' . (string) $cEvent->Bet[2]->Runner . ' DRAW',
                                                (string) $cEvent->Bet[0]->Runner . ' VS. ' . (string) $cEvent->Bet[2]->Runner . ' IS NOT A DRAW',
                                                (string) $cEvent->Bet[1]->Price,
                                                -99999));
                                    }
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
                        else if ($cEvent->Bet[0]['TYPE'] == "Total Rounds")
                        {
                            //Prop - Total rounds
                            if (ParseTools::checkCorrectOdds((string) $cEvent->Bet[0]->Price)
                                    && ParseTools::checkCorrectOdds((string) $cEvent->Bet[1]->Price))
                            {
                                $oTempProp = new ParsedProp(
                                                (string) $cEvent->Name . ' Total Rounds over ' . $cEvent->Bet[0]->Handicap,
                                                'Total Rounds under ' . $cEvent->Bet[1]->Handicap,
                                                (string) $cEvent->Bet[0]->Price,
                                                (string) $cEvent->Bet[1]->Price);

                                $oTempProp->setCorrelationID(trim($cEvent->Name));
                                $oParsedSport->addFetchedProp($oTempProp);
                            }
                        }
                        else if ($cEvent->Bet[0]['TYPE'] == "How will the Fight Finish")
                        {
                            //Prop - Fight finish
                            //TODO: There is currently no way to handle props that say if the fight ends in e.g. KO/TKO no matter what fighter gets the finish
                        }
                        else
                        {
                            //Prop - All other
                            foreach ($cEvent->Bet as $cBet)
                            {
                                if (ParseTools::checkCorrectOdds((string) $cBet->Price))
                                {
                                    $oTempProp = new ParsedProp(
                                                    (string) $cBet->Runner,
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