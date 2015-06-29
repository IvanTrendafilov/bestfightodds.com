<?php

/**
 * XML Parser
 *
 * Bookie: Bovada
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes
 *
 * Comment: Prod version
 *
 * Change log:
 * 2011-03-02: Fixed so that additional props may be parsed
 *
 */
class XMLParserBovada_boxing
{

    public function parseXML($a_sXML)
    {
        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('Boxing');

        foreach ($oXML->EventType->Date as $cDate)
        {

            foreach ($cDate->Event as $cEvent)
            {
                if ((string) $cEvent['STATUS'] != 'Closed' && (string) $cEvent['STATUS'] != 'Cancelled' && count($cEvent->Competitor) == 2)
                {
                    $cDateBanner = $cEvent->xpath('..')[0];

                    $cCompetitor1 = $cEvent->Competitor[0];
                    $cCompetitor2 = $cEvent->Competitor[1];

                    $cCompetitor1Choice = "";
                    $cCompetitor2Choice = "";

                    if ($cCompetitor1 != null && $cCompetitor2 != null)
                    {
                        foreach ($cCompetitor1->Line as $cLine1)
                        {
                            if ((string) $cLine1['TYPE'] == "Moneyline")
                            {
                                $cCompetitor1Choice = $cLine1->Choice[0]->Odds;
                            }
                        }

                        foreach ($cCompetitor2->Line as $cLine2)
                        {
                            if ((string) $cLine2['TYPE'] == "Moneyline")
                            {
                                $cCompetitor2Choice = $cLine2->Choice[0]->Odds;
                            }
                        }


                        if ((string) $cCompetitor1Choice['Line'] != "" && (string) $cCompetitor2Choice['Line'] != "")
                        {

                            //Check if bet is a prop or not
                            if (ParseTools::isProp((string) $cCompetitor1['NAME']) && ParseTools::isProp((string) $cCompetitor2['NAME']))
                            {
                                //Prop, add as such
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                                (string) $cCompetitor1['NAME'],
                                                (string) $cCompetitor2['NAME'],
                                                (string) $cCompetitor1Choice['Line'],
                                                (string) $cCompetitor2Choice['Line'],
                                                (string) $cDateBanner['DTEXT']
                                ));
                            }
                            else
                            {
                                //Not a prop, add as matchup
                                $oParsedSport->addParsedMatchup(new ParsedMatchup(
                                                (string) $cCompetitor1['NAME'],
                                                (string) $cCompetitor2['NAME'],
                                                (string) $cCompetitor1Choice['Line'],
                                                (string) $cCompetitor2Choice['Line'],
                                                (string) $cDateBanner['DTEXT']
                                ));
                            }
                        }
                    }
                }
                else if ((string) $cEvent['STATUS'] != 'Closed' && (string) $cEvent['STATUS'] != 'Cancelled' && count($cEvent->Competitor) > 2)
                {
                    //Probably a prop bet
                    foreach ($cEvent->Competitor as $cCompetitor)
                    {
                        $sProp = (string) $cEvent['NAME'] . ' : ' . (string) $cCompetitor['NAME'];
                        if (ParseTools::isProp($sProp) && isset($cCompetitor->Line->Choice->Odds['Line']) && (string) $cCompetitor->Line->Choice->Odds['Line'] != "")
                        {
                                //Prop, add as such
                                $oParsedSport->addFetchedProp(new ParsedProp(
                                                $sProp,
                                                '',
                                                (string) $cCompetitor->Line->Choice->Odds['Line'],
                                                '-99999',
                                                (string) $cDateBanner['DTEXT']
                                ));
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