<?php

/**
 * XML Parser
 *
 * Bookie: William Hill
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Yes
 * Totals: Yes
 * Props: Yes
 * Authorative run declared: Yes
 *
 * Comment: Prod version
 *
 * http://whdn.williamhill.com/pricefeed/openbet_cdn?action=template&template=getHierarchyByMarketType&classId=402&filterBIR=N
 *
 */
class XMLParserWilliamHill
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

        foreach ($oXML->response->williamhill->class->type as $cType)
        {
            foreach ($cType->market as $cMarket)
            {
                $sType = substr(strrchr($cMarket['name'], "-"), 2);
                if ($sType == 'Bout Betting')
                {
                    //Normal matchup
                    //Find draw and ignore it
                    $aParticipants = [];
                    foreach ($cMarket->participant as $cParticipant)
                    {
                        if ($cParticipant['name'] != 'Draw')
                        {
                            $aParticipants[] = $cParticipant;
                        }
                    }

                    if (ParseTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($aParticipants[0]['oddsDecimal'])) && ParseTools::checkCorrectOdds(OddsTools::convertDecimalToMoneyline($aParticipants[1]['oddsDecimal'])))
                    {
                        $oTempMatchup = new ParsedMatchup(
                            $aParticipants[0]['name'],
                            $aParticipants[1]['name'],
                            OddsTools::convertDecimalToMoneyline($aParticipants[0]['oddsDecimal']),
                            OddsTools::convertDecimalToMoneyline($aParticipants[1]['oddsDecimal'])
                        );

                        //Add time of matchup as metadata
                        $oGameDate = null;
                        if ($cType['name'] == 'Potential Fights')
                        {
                            $oGameDate = new DateTime('2019-12-31 00:00:00');
                        }
                        else
                        {
                            $oGameDate = new DateTime($cMarket['date'] . ' ' . $cMarket['time']);    
                        }
                        $oTempMatchup->setMetaData('gametime', $oGameDate->getTimestamp());

                        $oTempMatchup->setCorrelationID($this->getCorrelationID($cMarket));
                        
                        $oParsedSport->addParsedMatchup($oTempMatchup);
                    }
                }
                else 
                {
                    //Prop bet
                    if ($sType == 'Fight to go the Distance' || $sType == 'Total Rounds')
                    {
                        //Two option bet
                        $oParsedProp = new ParsedProp(
                                      $cMarket['name'] . ' : ' .  $cMarket->participant[0]['name'] . ' ' . $cMarket->participant[0]['handicap'],
                                      $cMarket['name'] . ' : ' .  $cMarket->participant[1]['name'] . ' ' . $cMarket->participant[1]['handicap'],
                                      OddsTools::convertDecimalToMoneyline($cMarket->participant[0]['oddsDecimal']),
                                      OddsTools::convertDecimalToMoneyline($cMarket->participant[1]['oddsDecimal']));
                     
                        $oParsedProp->setCorrelationID($this->getCorrelationID($cMarket));
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                    else
                    {
                        //One line prop bet
                        foreach ($cMarket->participant as $cParticipant)
                        {
                            $oParsedProp = new ParsedProp(
                                      $cMarket['name'] . ' : ' .  $cParticipant['name'] . ' ' . $cParticipant['handicap'],
                                      '',
                                      OddsTools::convertDecimalToMoneyline($cParticipant['oddsDecimal']),
                                      '-99999');
                     
                            $oParsedProp->setCorrelationID($this->getCorrelationID($cMarket));
                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if ($oXML != false && count($oParsedSport->getParsedMatchups()) > 10 && $oParsedSport->getPropCount() > 10)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        $aSports[] = $oParsedSport;
        return $aSports;
    }

    private function getCorrelationID($a_cMarket)
    {
        $sCorrelation = '';
        if ($iPos = strpos($a_cMarket['name'], "-"))
        {
            $sCorrelation = substr($a_cMarket['name'], 0, $iPos - 1);
        }
        else
        {
            Logger::getInstance()->log("Warning: Unable to set correlation ID: " . $a_cMarket['name'], -1);
        }
        return $sCorrelation;
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }


}

?>