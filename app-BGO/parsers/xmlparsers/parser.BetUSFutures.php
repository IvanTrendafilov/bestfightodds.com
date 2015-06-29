<?php

/**
 * XML Parser
 *
 * Bookie: BetUS (Futures)
 * Sport: MMA
 *
 * Comment: Prod version
 *
 */
class XMLParserBetUSFutures
{

    public function parseXML($a_sXML)
    {
        //Strip schema and namespaces
        $a_sXML = preg_replace('/<Schema[^.]*<\/Schema>/', '', $a_sXML);
        $a_sXML = preg_replace('/xmlns="[^"]*"/', '', $a_sXML);

        //SimpleXML will complain if the root-element does not have a newline after it. Silly, yes i know..
        $a_sXML = preg_replace("/<root>/", "<root>\n", $a_sXML);
        $a_sXML = preg_replace("/<\/root>/", "\n</root>", $a_sXML);

        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->Proposition_Future as $cPropFuture)
        {
            if ((trim((string) $cPropFuture['ContestType']) == 'U.F.C. Futures' || trim((string) $cPropFuture['ContestType']) == 'M.M.A. Futures') &&
                    substr(trim((string) $cPropFuture['Description']), -7) != 'Special')
            {

                if (!isset($cPropFuture->Contestant[2]))
                {
                    //Matchup
                    $cContestant1 = $cPropFuture->Contestant[0];
                    $cContestant2 = $cPropFuture->Contestant[1];

                    $oParsedMatchup = new ParsedMatchup(
                                    (string) $cContestant1['ContestantName'],
                                    (string) $cContestant2['ContestantName'],
                                    (string) $cContestant1['MoneyLine'],
                                    (string) $cContestant2['MoneyLine']
                    );

                    $oParsedSport->addParsedMatchup($oParsedMatchup);
                }
                else
                {
                    //Prop
                    foreach ($cPropFuture->Contestant as $cContestant)
                    {
                        $oParsedProp = new ParsedProp(
                                        (string) $cPropFuture['ContestDesc'] . ' ' . (string) $cContestant['ContestantName'],
                                        '',
                                        (string) $cContestant['MoneyLine'],
                                        '-99999'
                        );

                        $oParsedSport->addFetchedProp($oParsedProp);
                    }
                }
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

}

?>