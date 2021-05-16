<?php

/**
 * XML Parser
 *
 * Bookie: SportBet
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: Yes 
 * Totals: Yes
 * Props: Yes
 * Authoritive run declared: Yes
 *
 * Comment: Dev version
 *
 */
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Utils\Logger;
use BFO\General\BookieHandler;

class XMLParserSportBet
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

        foreach ($oXML->NewDataSet->GameLines as $cEvent)
        {
            if ((trim((string) $cEvent->SportType) == 'Fighting'
                    && ((trim((string) $cEvent->SportSubType) == 'Boxing') || (trim((string) $cEvent->SportSubType) == 'Boxing Props') || (trim((string) $cEvent->SportSubType) == 'Olympic Boxing'))
                    && ((int) $cEvent->IsCancelled) != 1
                    && ((int) $cEvent->isGraded) != 1)
                    && !((trim((string) $cEvent->HomeMoneyLine) == '-99999') && (trim((string) $cEvent->VisitorMoneyLine) == '-99999'))
                    && !strpos(strtolower((string)$cEvent->Header), 'mma propositions')
            )
            {

                //Check if entry is a prop, if so add it as a parsed prop
                if (trim((string) $cEvent->SportSubType) == 'Props' || trim((string) $cEvent->SportSubType) == 'Boxing Props')
                {
                    $oParsedProp = null;

                    if ((trim((string) $cEvent->HomeMoneyLine) != '')
                    && (trim((string) $cEvent->VisitorMoneyLine) != ''))
                    {
                        //Regular prop

                        //Workaround for props that are not sent in the correct order:
                        if (strtoupper(substr(trim((string) $cEvent->HomeTeamID), 0, 4)) == 'NOT ' || strtoupper(substr(trim((string) $cEvent->HomeTeamID), 0, 4)) == 'ANY ')
                        {
                            //Prop starts with NOT, switch home and visitor fields
                            $oParsedProp = new ParsedProp(
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->VisitorTeamID,
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->HomeTeamID,
                                            (string) $cEvent->VisitorMoneyLine,
                                            (string) $cEvent->HomeMoneyLine);
                        }
                        else
                        {
                            $oParsedProp = new ParsedProp(
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->HomeTeamID,
                                            (string) ':: ' . $cEvent->Header . ' : ' . $cEvent->VisitorTeamID,
                                            (string) $cEvent->HomeMoneyLine,
                                            (string) $cEvent->VisitorMoneyLine);
                        }

                        //Add correlation ID if available
                        if (isset($cEvent->CorrelationId) && trim((string) $cEvent->CorrelationId) != '')
                        {
                            $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        }

                        $oParsedSport->addFetchedProp($oParsedProp);

                    }
                    else if ((trim((string) $cEvent->HomeSpreadPrice) != '')
                    && (trim((string) $cEvent->VisitorSpreadPrice) != '')
                    && (trim((string) $cEvent->HomeSpread) != '')
                    && (trim((string) $cEvent->VisitorSpread) != ''))
                    {
                        //One combined:
                        $oParsedProp = new ParsedProp(
                            (string) $cEvent->HomeTeamID . ' ' . (string) $cEvent->HomeSpread,
                            (string) $cEvent->VisitorTeamID . ' ' . (string) $cEvent->VisitorSpread,
                            (string) $cEvent->HomeSpreadPrice,
                            (string) $cEvent->VisitorSpreadPrice);

                        //Add correlation ID if available
                        if (isset($cEvent->CorrelationId) && trim((string) $cEvent->CorrelationId) != '')
                        {
                            $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        }
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                    else if (!empty($cEvent->TotalPoints) && !empty($cEvent->TotalPointsOverPrice) && !empty($cEvent->TotalPointsUnderPrice))
                    {
                        //Custom totals prop bet
                        $oParsedProp = new ParsedProp(
                                      (string) $cEvent->HomeTeamID . ' - OVER ' . (string) $cEvent->TotalPoints,
                                      (string) $cEvent->VisitorTeamID . ' - UNDER ' . (string) $cEvent->TotalPoints,
                                      (string) $cEvent->TotalPointsOverPrice,
                                      (string) $cEvent->TotalPointsUnderPrice);
                        $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                    else
                    {
                        //Unhandled prop
                        Logger::getInstance()->log("Unhandled prop: " . (string) $cEvent->HomeTeamID . " / " . (string) $cEvent->VisitorTeamID . ", check parser", -1);
                    }

                    $oParsedProp = null;
                }
                //Entry is a regular matchup, add as one
                else
                {
                   if ((trim((string) $cEvent->HomeMoneyLine) != '')
                    && (trim((string) $cEvent->VisitorMoneyLine) != '')
                    && !preg_match("/ DECISION/", strtoupper($cEvent->HomeTeamID))
                    && !preg_match("/ DRAW/", strtoupper($cEvent->HomeTeamID))
                    && !preg_match("/ DISTANCE/", strtoupper($cEvent->HomeTeamID)))
                        {
                        $oParsedMatchup = new ParsedMatchup(
                                        (string) $cEvent->HomeTeamID,
                                        (string) $cEvent->VisitorTeamID,
                                        (string) $cEvent->HomeMoneyLine,
                                        (string) $cEvent->VisitorMoneyLine
                        );
                        //Add correlation ID to match matchups to props
                        $oParsedMatchup->setCorrelationID((string) $cEvent->CorrelationId);

                        //Add time of matchup as metadata
                        if (isset($cEvent->GameDateTime))
                        {
                            $oGameDate = new DateTime($cEvent->GameDateTime);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                        }

                        $oParsedSport->addParsedMatchup($oParsedMatchup);

    		          //Check if a total is available, if so, add it as a prop
                        if ( isset($cEvent->TotalPoints) && trim((string) $cEvent->TotalPoints) != '')
                          {
                            //Total exists, add it
                            $oParsedProp = new ParsedProp(
                                                          (string) $cEvent->HomeTeamID . ' vs ' . (string) $cEvent->VisitorTeamID . ' - OVER ' . (string) $cEvent->TotalPoints,
                                                          (string) $cEvent->HomeTeamID . ' vs ' . (string) $cEvent->VisitorTeamID . ' - UNDER ' . (string) $cEvent->TotalPoints,
                                                          (string) $cEvent->TotalPointsOverPrice,
                                                          (string) $cEvent->TotalPointsUnderPrice);
                            $oParsedProp->setCorrelationID((string) $cEvent->CorrelationId);
                            
                            $oParsedSport->addFetchedProp($oParsedProp);
                          }
                    }
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 10 && $oParsedSport->getPropCount() > 10)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run (but only valid if changenum is reset)", 0);
        }

        $aSports[] = $oParsedSport;

        //Before finishing up, save the changenum to be able to fetch future feeds
        $sCN = trim((string) $oXML->NewDataSet->LastChange->ChangeNum);
        if ($sCN != '-1' && $sCN != null && $sCN != '')
        {
            //Store the changenum - WARNING, bookie_id is hardcoded here, should be fixed..
            $sCN = ((float) $sCN) - 1000;
            if (BookieHandler::saveChangeNum(2, $sCN))
            {
                Logger::getInstance()->log("ChangeNum stored OK: " . $sCN, 0);
            }
            else
            {
                Logger::getInstance()->log("Error: ChangeNum was not stored", -2);
            }
        }
        else
        {
            Logger::getInstance()->log("Error: Bad ChangeNum in feed. Message: " . $oXML->Error->ErrorMessage, -2);
        }

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
