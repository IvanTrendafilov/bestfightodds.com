<?php

/**
 * XML Parser
 *
 * Bookie: BetOnline
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: No* (*except for totals, BetOnline have been contacted regarding other props)
 * Authoritative run: Yes
 *
 * Comment: Prod version
 *
 */
class XMLParserBetOnline
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

        foreach ($oXML->event as $cEvent)
        {
            //Matchups
            if ((string) $cEvent->sporttype == 'Martial Arts' 
                && count($cEvent->participant) == 2 
                && trim((string) $cEvent->scheduletext) != 'Kickboxing')
            {
                if (ParseTools::checkCorrectOdds((string) $cEvent->participant[0]->odds->moneyline)
                        && ParseTools::checkCorrectOdds((string) $cEvent->participant[1]->odds->moneyline))
                {
                    $oParsedSport->addParsedMatchup(new ParsedMatchup(
                                    (string) $cEvent->participant[0]->participant_name,
                                    (string) $cEvent->participant[1]->participant_name,
                                    (string) $cEvent->participant[0]->odds->moneyline,
                                    (string) $cEvent->participant[1]->odds->moneyline
                    ));
                }

                //Add total as prop
                if (isset($cEvent->period->total->total_points) && trim($cEvent->period->total->total_points) != '')
                {
                    $oParsedSport->addFetchedProp(new ParsedProp(
                        (string) $cEvent->participant[0]->participant_name . ' vs ' . (string) $cEvent->participant[1]->participant_name . ' over ' . $cEvent->period->total->total_points . ' rounds',
                        (string) $cEvent->participant[0]->participant_name . ' vs ' . (string) $cEvent->participant[1]->participant_name . ' under ' . $cEvent->period->total->total_points . ' rounds',
                        (string) $cEvent->period->total->over_adjust,
                        (string) $cEvent->period->total->under_adjust));
                }

            }
        }

        //Declare authorative run if we fill the criteria
        if ($oXML != false && count($oParsedSport->getParsedMatchups()) >= 5)
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