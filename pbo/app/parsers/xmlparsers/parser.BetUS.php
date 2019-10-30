<?php

require_once('lib/bfocore/utils/class.OddsTools.php');

/**
 * XML Parser
 *
 * Bookie: BetUS
 * Sport: Boxing
 *
 * Comment: Dev version
 *
 */
class XMLParserBetUS
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

        $oParsedSport = new ParsedSport('Boxing');

        foreach ($oXML->Game as $cGame)
        {
            if (trim((string) $cGame['SportSubType']) == 'Boxing')
            {

                $oParsedMatchup = new ParsedMatchup(
                                (string) $cGame['Team1ID'],
                                (string) $cGame['Team2ID'],
                                (string) $cGame['MoneyLine1'],
                                (string) $cGame['MoneyLine2']
                );

                $oParsedSport->addParsedMatchup($oParsedMatchup);

                //Check if totals are available, if so, add as prop
                if (isset($cGame['TotalPoints']) && $cGame['TotalPoints'] != ''
                    && isset($cGame['TtlPtsAdj1']) && isset($cGame['TtlPtsAdj2'])
                    && OddsTools::checkCorrectOdds($cGame['TtlPtsAdj1']) && OddsTools::checkCorrectOdds($cGame['TtlPtsAdj2']))
                {
                    $oParsedSport->addFetchedProp(new ParsedProp(
                                    (string) $cGame['Team1ID'] . ' vs. ' . $cGame['Team2ID'] . ' OVER ' . $cGame['TotalPoints'],
                                    (string) $cGame['Team1ID'] . ' vs. ' . $cGame['Team2ID'] . ' UNDER ' . $cGame['TotalPoints'],
                                    (string) $cGame['TtlPtsAdj1'],
                                    (string) $cGame['TtlPtsAdj2']
                    ));
                }


            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

}

?>