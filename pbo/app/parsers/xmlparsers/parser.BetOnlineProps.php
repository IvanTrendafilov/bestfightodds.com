<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: BetOnlineProps
 * Sport: Boxing
 *
 * Comment: Dev version
 *
 */
class XMLParserBetOnlineProps
{

    public function parseXML($a_sXML)
    {
        $sXML = self::collectMMAEvents($a_sXML);
        $oXML = simplexml_load_string($sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('Boxing');

        foreach ($oXML->prop as $cProp)
        {
            if (ParseTools::checkCorrectOdds(trim((string) $cProp->f1_line)))
            {
                $oParsedProp = new ParsedProp(
                                (string) $cProp->f1,
                                '',
                                (string) $cProp->f1_line,
                                -99999
                );

                $oParsedSport->addFetchedProp($oParsedProp);
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

    private function collectMMAEvents($a_sPage)
    {

        $sRetXML = '<?xml version="1.0" encoding="UTF-8"?><matchups>';
        $sEventRegexp = '/cntstnm\':\'([^\']*)\'[^>]*\'nm\':\'([^\']*)\'[^>]*\'aorg\':(-?[0-9]*)/';
        $aMatches = ParseTools::matchBlock($a_sPage, $sEventRegexp);
        foreach ($aMatches as $aMatch)
        {
            $sRetXML .= '<prop><f1>' . $aMatch[1] . ' : ' . $aMatch[2]  . '</f1><f2></f2><f1_line>' . $aMatch[3] . '</f1_line><f2_line></f2_line></prop>';
        }
        $sRetXML .= '</matchups>';

        return $sRetXML;
    }

}
?>

