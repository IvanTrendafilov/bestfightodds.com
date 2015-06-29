<?php

/**
 * XML Parser
 *
 * Bookie: Pinnacle
 * Sport: MMA
 *
 * Comment: Prod version
 *
 */
class XMLParserPinnacle
{

    public function parseXML($a_sXML)
    {
        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }
        if (isset($oXML->err))
        {
            Logger::getInstance()->log("Error: " . $oXML->err, -2);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('MMA');

        //Loop through leagues
        foreach ($oXML->fd->sports->sport->leagues->league as $cLeague)
        {
            foreach ($cLeague->events->event as $cEvent)
            {
                if ($cEvent->IsLive == 'No' && //Avoid parsing live odds
                        ParseTools::checkCorrectOdds($cEvent->periods->period->moneyLine->awayPrice) && //Check odds format
                        ParseTools::checkCorrectOdds($cEvent->periods->period->moneyLine->homePrice)) //Check odds format
                {
                    $oParsedMatchup = new ParsedMatchup(
                                    (string) $cEvent->homeTeam->name,
                                    (string) $cEvent->awayTeam->name,
                                    (string) $cEvent->periods->period->moneyLine->homePrice,
                                    (string) $cEvent->periods->period->moneyLine->awayPrice
                    );

                    $oParsedSport->addParsedMatchup($oParsedMatchup);

                    //Add total if one exists
                    if (isset($cEvent->periods->period->totals) && trim((string) $cEvent->periods->period->totals->total->points) != '')
                    {
                        //Total exists, add it
                        $oParsedProp = new ParsedProp(
                                          (string) $cEvent->homeTeam->name . ' vs ' . (string) $cEvent->awayTeam->name . ' - OVER ' . (string) $cEvent->periods->period->totals->total->points,
                                          (string) $cEvent->homeTeam->name . ' vs ' . (string) $cEvent->awayTeam->name . ' - UNDER ' . (string) $cEvent->periods->period->totals->total->points,
                                          (string) $cEvent->periods->period->totals->total->overPrice,
                                          (string) $cEvent->periods->period->totals->total->underPrice);
                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                }
            }
        }


        $aSports[] = $oParsedSport;

        //Before finishing up, save the changenum to be able to fetch future feeds
        $sCN = trim((string) $oXML->fd->fdTime);
        if ($sCN != '-1' && $sCN != null && $sCN != '')
        {
            //Store the changenum - WARNING, bookie_id is hardcoded here, should be fixed..
            $sCN = ((float) $sCN) - 5000;
            if (BookieHandler::saveChangeNum(9, $sCN))
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
            Logger::getInstance()->log("Error: Bad ChangeNum in feed. Message: " . $oXML->err, -2);
        }

        return $aSports;
    }

}
?>

