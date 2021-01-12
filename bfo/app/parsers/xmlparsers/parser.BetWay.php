<?php

/**
 * XML Parser
 *
 * Bookie: BetWay
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes
 * Authoritative run: Yes
 *
 * Comment: Prod version
 * URL: https://feeds.betway.com/sbeventsen?key=1E557772&keywords=ufc---martial-arts
 *
 */
require_once('lib/bfocore/general/class.BookieHandler.php');

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
            // Start time: $cEvent['start_at']
            //Event name:

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
                if ($cMarket->Names->Name == 'Fight Winner') 
                {
                    //Regular matchup
                    $oParsedMatchup = new ParsedMatchup(
                        $cOutcome[0]->Names->Name,
                        $cOutcome[1]->Names->Name,
                        OddsTools::convertDecimalToMoneyline($cOutcome[0]['price_dec']),
                        OddsTools::convertDecimalToMoneyline($cOutcome[1]['price_dec'])
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
                else if (count($cMarket->Outcomes->Outcome) % 2 == 0)
                {
                    //Props. Props are typically ordered as positive, negative, positive, negative, etc. Or over, under
                    for ($i = 0; $i < count($cMarket->Outcomes->Outcome); $i += 2)
                    {
                        $oParsedProp = new ParsedProp(
                            $cEvent->Names->Name ' :: ' . $cMarket->Names->Name . ' : ' . $cOutcome[$i]->Names->Name,
                            $cEvent->Names->Name ' :: ' . $cMarket->Names->Name . ' : ' . $cOutcome[$i + 1]->Names->Name,
                            OddsTools::convertDecimalToMoneyline($cOutcome[$i]['price_dec']),
                            OddsTools::convertDecimalToMoneyline($cOutcome[$i + 1]['price_dec']));

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
                    Logger::getInstance()->log("Unhandled market name, maybe add to parser?", -1);
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

