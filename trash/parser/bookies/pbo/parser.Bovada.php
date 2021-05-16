<?php

/**
 * XML Parser
 *
 * Bookie: Bovada
 * Sport: Boxing
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes
 *
 * Comment: Prod version
 *
 * Change log:
 * 2017-08-28 - New JSON version based on latest updates from Bovada
 *
 */
use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedMatchup;
use BFO\Parser\ParsedProp;
use BFO\Parser\Utils\Logger;

class XMLParserBovada
{
    private $auth_run = false;

    //Actually JSON
    public function parseXML($json)
    {
        $feed = json_decode($json, true);
        $parsed_sports = array();
        $parsed_sport = new ParsedSport('Boxing');

        foreach ($feed['events'] as $event)
        {
            //Store metadata and correlation ID
            $correlation_id = $event['id'];
            $date = $event['startTime'];

            foreach ($event['markets'] as $market)
            {
                if ($market['status'] == 'OPEN')
                {
                    if ($market['description'] == 'To Win the Bout')
                    {
                        //Regular matchup
                        $parsed_matchup = new ParsedMatchup(
                                        $market['outcomes'][0]['description'],
                                        $market['outcomes'][1]['description'],
                                        $market['outcomes'][0]['price']['american'],
                                        $market['outcomes'][1]['price']['american']);
                        $parsed_matchup->setCorrelationID($correlation_id);

                        $date_obj = new DateTime($date);
                        $parsed_matchup->setMetaData('gametime', $date_obj->getTimestamp());

                        $parsed_sport->addParsedMatchup($parsed_matchup);
                    }
                    else
                    {
                        //Prop bet
                        if (count($market['outcomes']) > 2)
                        {
                            //Single line prop
                            foreach ($market['outcomes'] as $outcome)
                            {
                                $parsed_prop = new ParsedProp(
                                    $market['description'] . ' :: ' . $outcome['description'],
                                    '',
                                    $outcome['price']['american'],
                                    '-99999');
                                $parsed_prop->setCorrelationID($correlation_id);
                                $parsed_sport->addFetchedProp($parsed_prop);
                            }
                        }
                        else
                        {
                            //Two sided prop
                            $parsed_prop = new ParsedProp(
                                $market['description'] . ' :: ' . $market['outcomes'][0]['description'],
                                $market['description'] . ' :: ' . $market['outcomes'][1]['description'],
                                $market['outcomes'][0]['price']['american'],
                                $market['outcomes'][1]['price']['american']);
                            $parsed_prop->setCorrelationID($correlation_id);
                            $parsed_sport->addFetchedProp($parsed_prop);
                        }
                    }
                }
            }

        }
        
        //Declare authorative run if we fill the criteria
        if (count($parsed_sport->getParsedMatchups()) > 10)
        {
            $this->auth_run = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        $parsed_sports[] = $parsed_sport;
        return $parsed_sports;
    }

    public function checkAuthoritiveRun($metadata)
    {
        return $this->auth_run;
    }

}

?>