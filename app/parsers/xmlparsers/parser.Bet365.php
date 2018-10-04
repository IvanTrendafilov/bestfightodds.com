<?php

/**
 * XML Parser
 *
 * Bookie: Bet365
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: Unclear
 * Totals: Yes (not multiple instances though, see below)
 * Props: Yes
* Authorative run declared: Yes (but not fully evaluated)
 *
 * Comment: Prod version
 *
 */

class XMLParserBet365
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

        foreach ($oXML->EventGroup as $cEventGroup)
        {
            if (substr((string) $cEventGroup['name'],0,3) == 'UFC' || substr((string) $cEventGroup['name'],0,3) == 'MMA' || substr((string) $cEventGroup['name'],0,8) == 'Bellator' )
            {
                foreach ($cEventGroup->Event as $cEvent)
                {
                    foreach ($cEvent->Market as $cMarket)
                    {
                        if (strtolower((string) $cMarket['Name']) == 'to win fight')
                        {
                            //Regular matchup
                            $oParsedMatchup = new ParsedMatchup(
                                            (string) $cMarket->Participant[0]['Name'],
                                            (string) $cMarket->Participant[1]['Name'],
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[0]['OddsDecimal']),
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[1]['OddsDecimal'])
                            );
                            //Add correlation ID to match matchups to props
                            $oParsedMatchup->setCorrelationID((string) $cEvent['ID']);

                            //Add time of matchup as metadata - Disabled, 5Dimes is master
                            /*
                            $oGameDate = DateTime::createFromFormat('d/m/y H:i:s', (string) $cMarket['StartTime']);
                            $oParsedMatchup->setMetaData('gametime', $oGameDate->getTimestamp());
                            */
                            $oParsedSport->addParsedMatchup($oParsedMatchup);

                        }
                        
                        else if (strtolower((string) $cMarket['Name']) == 'total rounds' && count($cMarket->Participant) == 2)
                        {
                            $oParsedProp = new ParsedProp(
                                            (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[0]['Name'] . ' rounds' ,
                                            (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[1]['Name'] . ' rounds' ,
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[0]['OddsDecimal']),
                                            OddsTools::convertDecimalToMoneyline($cMarket->Participant[1]['OddsDecimal']));

                            //Add correlation ID if available
                            $oParsedProp->setCorrelationID((string) $cEvent['ID']);

                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                        else if (strtolower((string) $cMarket['Name']) == 'total rounds' && count($cMarket->Participant) > 2)
                        {
                            //TODO: Currently no way to handle multiple over/unders on total rounds. Needs a fix
                        }
                        else if (count($cMarket->Participant) == 2 && in_array($cMarket->Participant[0]['Name'], array('Yes','No')) && in_array($cMarket->Participant[1]['Name'], array('Yes','No')))
                        {
                            //Two side prop (Yes/No)
                            $oParsedProp = new ParsedProp(
                                (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[0]['Name'],
                                (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cMarket->Participant[1]['Name'],
                                OddsTools::convertDecimalToMoneyline($cMarket->Participant[0]['OddsDecimal']),
                                OddsTools::convertDecimalToMoneyline($cMarket->Participant[1]['OddsDecimal']));

                            //Add correlation ID if available
                            $oParsedProp->setCorrelationID((string) $cEvent['ID']);

                            $oParsedSport->addFetchedProp($oParsedProp);
                        }
                        else 
                        {
                            //Probably prop, parse as such. Treat all as one-liners
                            foreach ($cMarket->Participant as $cParticipant)
                            {
                               $oParsedProp = new ParsedProp(
                                  (string) $cEvent['Name'] . ' : ' . $cMarket['Name'] . ' :: ' . $cParticipant['Name'],
                                  '',
                                  OddsTools::convertDecimalToMoneyline($cParticipant['OddsDecimal']),
                                  '-99999');
                         
                                $oParsedProp->setCorrelationID((string) $cEvent['ID']);
                                $oParsedSport->addFetchedProp($oParsedProp);
                            }
                        }
                    }
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
        return $this->bAuthorativeRun;
    }
}

?>