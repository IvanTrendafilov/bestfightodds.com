<?php

/**
 * XML Parser
 *
 * Bookie: BovadaProps
 * Sport: MMA
 *
 * Moneylines: No
 * Spreads: No
 * Totals: No
 * Props: Yes
 *
 * Comment: Prod version
 *          Currently not grabbing totalt rounds (TODO)
 *
 * Change log:
 * 2011-03-02: Fixed so that additional props may be parsed
 *
 */
class XMLParserBovadaProps
{

    public function parseXML($a_sXML)
    {
        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->EventType->Date as $cDate)
        {

            foreach ($cDate->Event as $cEvent)
            {
                if ((string) $cEvent['STATUS'] != 'Closed' && (string) $cEvent['STATUS'] != 'Cancelled')
                {
                    $cCompetitor1 = $cEvent->Competitor[0];
                    $cCompetitor2 = $cEvent->Competitor[1];

                    if (count($cEvent->Competitor) == 2 && $cCompetitor1 != null && $cCompetitor2 != null)
                    {
                        //Probably a x vs x prop
                        if ((string) $cEvent->Line['TYPE'] != "Total" && 
                            (string) $cCompetitor1->Line[0]->Choice[0]->Odds['Line'] != "" && 
                            (string) $cCompetitor2->Line[0]->Choice[0]->Odds['Line'] != "")
                        {
                            $sProp1 = str_replace(array('(',')'), '', (string) $cEvent['NAME'] . ' : ' . (string) $cCompetitor1['NAME']);   
                            $sProp2 = str_replace(array('(',')'), '', (string) $cEvent['NAME'] . ' : ' . (string) $cCompetitor2['NAME']);   

                            //Check if bet is a prop or not
                            if (ParseTools::isProp((string) $sProp1) && ParseTools::isProp((string) $sProp2))
                            {
                            
                                //Prop, add as such
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                                (string) $sProp1,
                                                (string) $sProp2,
                                                (string) $cCompetitor1->Line[0]->Choice[0]->Odds['Line'],
                                                (string) $cCompetitor2->Line[0]->Choice[0]->Odds['Line']
                                ));
                            }
                        }
                        //Probably a total
                        else if ((string) $cEvent->Line['TYPE'] == "Total" &&
                                (string) $cEvent->Line->Choice['NUMBER'] != "" &&
                                (string) $cEvent->Line->Choice[0]->Odds['Line'] != "" &&
                                (string) $cEvent->Line->Choice[1]->Odds['Line'] != "")
                        {
                            //Total exists, add it
                            $oParsedProp = new ParsedProp(
                                          (string) str_replace(array('(',')'), '', (string) $cEvent['NAME']) . ' - OVER ' . (string) $cEvent->Line->Choice['NUMBER'],
                                          (string) str_replace(array('(',')'), '', (string) $cEvent['NAME']) . ' - UNDER ' . (string) $cEvent->Line->Choice['NUMBER'],
                                          (string) $cEvent->Line->Choice[0]->Odds['Line'],
                                          (string) $cEvent->Line->Choice[1]->Odds['Line']);
                      
                            $oParsedSport->addFetchedProp($oParsedProp);
                        }

                    }
                    else if (count($cEvent->Competitor) >= 1)
                    {
                        //Probably a single type prop
                        foreach ($cEvent->Competitor as $cCompetitor)
                        {
                            $sProp1 = str_replace(array('(',')'), '', (string) $cEvent['NAME'] . ' : ' . (string) $cCompetitor['NAME']);   

                            if (ParseTools::isProp($sProp1) && isset($cCompetitor->Line->Choice->Odds['Line']) && (string) $cCompetitor->Line->Choice->Odds['Line'] != "")
                            {
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                                        $sProp1,
                                                        '',
                                                        (string) $cCompetitor->Line->Choice->Odds['Line'],
                                                        '-99999'
                                        ));
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