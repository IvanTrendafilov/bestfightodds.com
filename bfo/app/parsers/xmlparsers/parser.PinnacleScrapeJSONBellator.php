<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

/**
 * XML Parser
 *
 * Bookie: Pinnacle Site JSON parser
 * Sport: MMA
 *
 * Comment: Used when API us unavailable. Identical to its twin but this is for Bellator
 *
 */
class XMLParserPinnacleScrapeJSONBellator
{
    private $oParsedSport;
    private $bAuthorativeRun = false;

    public function __construct()
    {
        $this->oParsedSport = new ParsedSport('MMA');
    }

    public function parseXML($a_sXML)
    {
        //Actually JSON
        $json = json_decode($a_sXML, true);

        foreach ($json['Leagues'] as $league);
        {
            foreach ($league['Events'] as $event)
            {
                if (count($event['Participants']) == 2)
                {
                    //Regular matchup

                    //Replace anylocation indicator with blank
                    $event['Participants'][0]['Name'] = str_replace('(AnyLocation=Action)', '', $event['Participants'][0]['Name']);
                    $event['Participants'][1]['Name'] = str_replace('(AnyLocation=Action)', '', $event['Participants'][1]['Name']);
                    $event['Participants'][0]['Name'] = str_replace('(Any Location=Action)', '', $event['Participants'][0]['Name']);
                    $event['Participants'][1]['Name'] = str_replace('(Any Location=Action)', '', $event['Participants'][1]['Name']);
                    $event['Participants'][0]['Name'] = str_replace('(AnyLocation=Action', '', $event['Participants'][0]['Name']);
                    $event['Participants'][1]['Name'] = str_replace('(AnyLocation=Action', '', $event['Participants'][1]['Name']);


                    $oParsedMatchup = new ParsedMatchup(
                        $event['Participants'][0]['Name'],
                        $event['Participants'][1]['Name'],
                        round($event['Participants'][0]['MoneyLine']),
                        round($event['Participants'][1]['MoneyLine'])
                    );
                    $oParsedMatchup->setCorrelationID((string) $event['EventId']);
                    $this->oParsedSport->addParsedMatchup($oParsedMatchup);

                    //Adds over/under if available
                    if (isset($event['Totals']))
                    {
                        $oParsedProp = new ParsedProp(
                            (string) $event['Participants'][0]['Name'] . ' vs ' . $event['Participants'][1]['Name'] . ' :: Over ' . $event['Totals']['Min'] . ' rounds',
                            (string) $event['Participants'][0]['Name'] . ' vs ' . $event['Participants'][1]['Name'] . ' :: Under ' . $event['Totals']['Min'] . ' rounds',
                            round($event['Totals']['OverPrice']),
                            round($event['Totals']['UnderPrice'])
                        );
                        $oParsedProp->setCorrelationID((string) $event['EventId']);
                        $this->oParsedSport->addFetchedProp($oParsedProp);
                    }
                }
            }
        }

        return [$this->oParsedSport];
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }
}
?>

