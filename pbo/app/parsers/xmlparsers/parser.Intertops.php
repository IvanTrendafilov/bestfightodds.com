<?php

/**
 * XML Parser
 *
 * Bookie: Intertops
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 *
 * Comment: Prod version
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

        $oParsedSport = new ParsedSport('Boxing');
        foreach ($oXML->data->s->cat as $cCategory)
        {
            if ($cCategory['n'] == 'Boxing' 
            || substr($cCategory['n'], 0, strlen('Boxing')) === 'Boxing'
            || $cCategory['n'] == 'Conor McGregor v Floyd Mayweather Jr')
            {
                foreach ($cCategory->m as $cMatchup)
                {
                    foreach ($cMatchup->t as $cBet)
                    {
                        if ($cBet['n'] == 'Moving Line')
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

                                $oGameDate = new DateTime($cMatchup['dt']);
                                $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());

                                $oParsedSport->addParsedMatchup($oTempMatchup);
                            }
                        }
                        else if ($cBet['n'] == 'Point Score')
                        {
                            //Point score (totalt rounds)
                            if (ParseTools::checkCorrectOdds((string) $cBet->l[0]['c']) && ParseTools::checkCorrectOdds((string) $cBet->l[1]['c']))
                            {
                                    $oParsedSport->addFetchedProp(new ParsedProp(
                                                    (string) $cMatchup['n'] . ' : ' . $cBet->l[0],
                                                    (string) $cMatchup['n'] . ' : ' . $cBet->l[1],
                                                    (string) $cBet->l[0]['c'],
                                                    (string) $cBet->l[1]['c']
                                    ));
                            }
                        }
                        else if ($cBet['n'] == 'FreeForm')
                        {
                            //Any other one line prop
                            foreach ($cBet->l as $cLine)
                            {
                                if (ParseTools::checkCorrectOdds((string) $cLine['c']))
                                {
                                        $oParsedSport->addFetchedProp(new ParsedProp(
                                                        (string) $cMatchup['n'] . ' : ' . $cLine,
                                                        '',
                                                        (string) $cLine['c'],
                                                        '-99999'
                                        ));
                                }
                            }
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
        //TODO: Bookie ID is hardcoded here, should be fixed
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