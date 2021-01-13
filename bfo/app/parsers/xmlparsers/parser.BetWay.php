<?php

/**
 * XML Parser
 *
 * Bookie: BetWay
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: Yes
 * Props: Yes
 * Authoritative run: Yes
 *
 * Comment: Prod version
 * URL: https://feeds.betway.com/sbeventsen?key=1E557772&keywords=ufc---martial-arts
 *
 */

class XMLParserBetWay
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

        foreach ($oXML->Event as $cEvent)
        {
            $event_name = '';
            foreach ($cEvent->Keywords->Keyword as $cKeyword)
            {
                if ($cKeyword['type_cname'] == 'league') //Indicates event name
                {
                    $event_name = $cKeyword;
                }
            }

            foreach ($cEvent->Markets->Market as $cMarket)
            {
                if ($cMarket['cname'] == 'fight-winner') 
                {
                    //Regular matchup
                    $oParsedMatchup = new ParsedMatchup(
                        $cMarket->Outcomes->Outcome[0]->Names->Name,
                        $cMarket->Outcomes->Outcome[1]->Names->Name,
                        OddsTools::convertDecimalToMoneyline($cMarket->Outcomes->Outcome[0]['price_dec']),
                        OddsTools::convertDecimalToMoneyline($cMarket->Outcomes->Outcome[1]['price_dec'])
                    );

                    //Add correlation
                    $oParsedMatchup->setCorrelationID($cEvent['id']);

                    //Add metadata
                    $oGameDate = new DateTime($cEvent['start_at']);
                    $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                    if ($event_name != '')
                    {
                        $oParsedMatchup->setMetaData('event_name', $event_name);
                    }
                    
                    $oParsedSport->addParsedMatchup($oParsedMatchup);


                }
                else if ($cMarket['cname'] == 'to-win-by-decision' ||
                        $cMarket['cname'] == 'to-win-by-finish' || 
                        $cMarket['cname'] == 'will-the-fight-go-the-distance' || 
                        $cMarket['cname'] == 'handicap-goals-over')
                {
                    //Ordered props. These props are typically ordered as positive, negative, positive, negative, etc. Or over, under
                    for ($i = 0; $i < count($cMarket->Outcomes->Outcome); $i += 2)
                    {
                        //Add handicap figure if available
                        $handicap = '';
                        if ($cMarket['handicap'] != 0)
                        {
                            $handicap = ' ' . $cMarket['handicap'];
                        }

                        $oParsedProp = new ParsedProp(
                            $cEvent->Names->Name . ' :: ' . $cMarket->Names->Name . ' : ' . $cMarket->Outcomes->Outcome[$i]->Names->Name . $handicap,
                            $cEvent->Names->Name . ' :: ' . $cMarket->Names->Name . ' : ' . $cMarket->Outcomes->Outcome[$i + 1]->Names->Name . $handicap,
                            OddsTools::convertDecimalToMoneyline($cMarket->Outcomes->Outcome[$i]['price_dec']),
                            OddsTools::convertDecimalToMoneyline($cMarket->Outcomes->Outcome[$i + 1]['price_dec']));

                        //Add correlation
                        $oParsedProp->setCorrelationID($cEvent['id']);

                        //Add metadata
                        $oGameDate = new DateTime($cEvent['start_at']);
                        $oParsedProp->setMetaData('gametime', $oGameDate->getTimestamp());
                        if ($event_name != '')
                        {
                            $oParsedProp->setMetaData('event_name', $event_name);
                        }

                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                }
                else  if ($cMarket['cname'] == 'round-betting' ||
                        $cMarket['cname'] == 'method-of-victory')
                {
                    //Single line prop
                    for ($i = 0; $i < count($cMarket->Outcomes->Outcome); $i++)
                    {
                        //Add handicap figure if available
                        $handicap = '';
                        if ($cMarket['handicap'] != 0)
                        {
                            $handicap = ' ' . $cMarket['handicap'];
                        }

                        $oParsedProp = new ParsedProp(
                            $cEvent->Names->Name . ' :: ' . $cMarket->Names->Name . ' : ' . $cMarket->Outcomes->Outcome[$i]->Names->Name . $handicap,
                            '',
                            OddsTools::convertDecimalToMoneyline($cMarket->Outcomes->Outcome[$i]['price_dec']),
                            -99999);

                        //Add correlation
                        $oParsedProp->setCorrelationID($cEvent['id']);

                        //Add metadata
                        $oGameDate = new DateTime($cEvent['start_at']);
                        $oParsedProp->setMetaData('gametime', $oGameDate->getTimestamp());
                        if ($event_name != '')
                        {
                            $oParsedProp->setMetaData('event_name', $event_name);
                        }

                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                }
                else
                {
                    Logger::getInstance()->log("Unhandled market name " . $cMarket->Names->Name . " (" . $cMarket['cname'] . "), maybe add to parser?", -1);
                }
            }
        }

        //Declare authorative run if we fill the criteria
        if (count($oParsedSport->getParsedMatchups()) > 10 && $oParsedSport->getPropCount() > 10)
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

