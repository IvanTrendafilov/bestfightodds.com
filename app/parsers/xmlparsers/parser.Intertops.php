<?php

/**
 * XML Parser
 *
 * Bookie: Intertops
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 *
 * Comment: Dev version
 * http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true&delta=525600
 *
 */
class XMLParserIntertops
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

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->data->s->cat as $cCategory)
        {
            if ($cCategory['n'] != 'Boxing')
            {
                foreach ($cCategory->m as $cMatchup)
                {
                    foreach ($cMatchup->t as $cBet)
                    {
                        if ($cBet['n'] == 'Single Match')
                        {
                            //Regular matchup line
                            if (ParseTools::checkCorrectOdds((string) $cBet->l[0]['c']) && ParseTools::checkCorrectOdds((string) $cBet->l[1]['c']))
                            {
                                $oTempMatchup = new ParsedMatchup(
                                    (string) $cBet->l[0],
                                    (string) $cBet->l[1],
                                    (string) $cBet->l[0]['c'],
                                    (string) $cBet->l[1]['c']
                                );

                                // $oGameDate = new DateTime($cMatchup['dt']);
                                // $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());

                                //Add correlation ID to match matchups to props
                                $oTempMatchup->setCorrelationID((string) $cMatchup['id']);

                                $oParsedSport->addParsedMatchup($oTempMatchup);
                            }
                        }
                        else if ($cBet['n'] == 'Point Score')
                        {
                            //Point score (totalt rounds)
                            if (ParseTools::checkCorrectOdds((string) $cBet->l[0]['c']) && ParseTools::checkCorrectOdds((string) $cBet->l[1]['c']))
                            {
                                    $oTempProp = new ParsedProp(
                                                    (string) $cMatchup['n'] . ' : ' . $cBet->l[0],
                                                    (string) $cMatchup['n'] . ' : ' . $cBet->l[1],
                                                    (string) $cBet->l[0]['c'],
                                                    (string) $cBet->l[1]['c']
                                    );

                                    //Add correlation ID to match matchups to props
                                    $oTempProp->setCorrelationID((string) $cMatchup['id']);

                                    $oParsedSport->addFetchedProp($oTempProp);
                            }
                        }
                        else if ($cBet['n'] == 'FreeForm')
                        {
                            //Any other one line prop
                            foreach ($cBet->l as $cLine)
                            {
                                if (ParseTools::checkCorrectOdds((string) $cLine['c']))
                                {
                                        $oTempProp = new ParsedProp(
                                                        (string) $cMatchup['n'] . ' : ' . $cLine,
                                                        '',
                                                        (string) $cLine['c'],
                                                        '-99999'
                                        );

                                        //Add correlation ID to match matchups to props
                                        $oTempProp->setCorrelationID((string) $cMatchup['id']);

                                        $oParsedSport->addFetchedProp($oTempProp);
                                }
                            }
                        }
                        else
                        {
                            Logger::getInstance()->log("Unhandled category: " . $cBet['n'], -1);
                        }
                    }
                }
            }
        }
        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) >= 5)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run (but only valid if changenum is reset)", 0);
        }

        //Before finishing up, save the changenum 30 to limit not fetching the entire feed
        if (BookieHandler::saveChangeNum(16, '30'))
        {
            Logger::getInstance()->log("ChangeNum stored OK: 30", 0);
        }
        else
        {
            Logger::getInstance()->log("Error: ChangeNum was not stored", -2);
        }

        return [$oParsedSport];
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        //Only report as an authoritive run if changenum has been reset. This in combination with the number of parsed matchups declares
        if (isset($a_aMetadata['changenum']) && $a_aMetadata['changenum'] != '30')
        {
            return $this->bAuthorativeRun;
        }
        return false;
    }
}

?>