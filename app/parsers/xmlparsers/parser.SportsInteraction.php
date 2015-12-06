<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: SportsInteraction
 * Sport: MMA
 *
 * Comment: Prod version
 *
 */
class XMLParserSportsInteraction
{

    public function parseXML($a_sXML)
    {
      //Temporary store of XML:
      //$rStoreFile = fopen('/var/www/vhosts/bestfightodds.com/httpdocs/storedfeeds/' . 'sportsint-' . date('Ymd-Hi') . '.xml', 'a');
      //fwrite($rStoreFile, $a_sXML);
      //fclose($rStoreFile);


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

                        if ($cEvent->Bet[0]['TYPE'] == "")
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
                                $oParsedSport->addParsedMatchup($oParsedMatchup);
                            }
                        }
                        else if ($cEvent->Bet[0]['TYPE'] == "Total Rounds")
                        {
                            //Prop - Total rounds
                            if (ParseTools::checkCorrectOdds((string) $cEvent->Bet[0]->Price)
                                    && ParseTools::checkCorrectOdds((string) $cEvent->Bet[1]->Price))
                            {
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                                (string) $cEvent->Name . ' Total Rounds over ' . $cEvent->Bet[0]->Handicap,
                                                'Total Rounds under ' . $cEvent->Bet[1]->Handicap,
                                                (string) $cEvent->Bet[0]->Price,
                                                (string) $cEvent->Bet[1]->Price));
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
                                    //Draw does not automatically indicate the matchup so we must add it manually
                                    if (strtoupper((string) $cBet->Runner) == 'DRAW' || strtoupper((string) $cBet->Runner) == '_DRWTXT_')
                                    {
                                        $cBet->Runner = (string) $cEvent->Name . ' - ' . $cBet->Runner;
                                    }
                                    $oParsedSport->addFetchedProp(new ParsedProp(
                                                    (string) $cBet->Runner,
                                                    '',
                                                    (string) $cBet->Price,
                                                    '-99999'));
                                }
                            }
                        }
                    }
                }
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

}

?>