<?php


/**
 * XML Parser
 *
 * Bookie: BetOnlineProps
 * Sport: Boxing
 *
 * Comment: Dev version
 *
 */

use BFO\Parser\ParsedSport;
use BFO\Parser\ParsedProp;
use BFO\Parser\Utils\Logger;
use BFO\Parser\Utils\ParseTools;

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
                //One side prop
                $oParsedProp = new ParsedProp(
                    (string) $cProp->f1,
                    (string) $cProp->f2,
                    (string) $cProp->f1_line,
                    (string) ($cProp->f2_line == '' ? '-99999' : $cProp->f2_line) 
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
        $sEventRegexp = '/dlv2\':\'([^\']*)\'[^>]*\'cntstnm\':\'([^\']*)\'[^>]*\'nm\':\'([^\']*)\'[^>]*\'aorg\':(-?[0-9]*)/';
        $aMatches = ParseTools::matchBlock($a_sPage, $sEventRegexp);

        //Loop through matches, combine Yes & No props to one single prop
        for ($i=0; $i < count($aMatches); $i++) 
        {
            if (strtolower($aMatches[$i][3]) == 'yes' && isset($aMatches[$i + 1]) && strtolower($aMatches[$i + 1][3]) == 'no')
            {
                //Yes/No Prop, combine into two and skip next row
                $sRetXML .= '<prop><f1>' . $aMatches[$i][1] . ' : ' . $aMatches[$i][2] . ' - ' . $aMatches[$i][3]  . '</f1><f2>' . $aMatches[$i + 1][1] . ' : '  . $aMatches[$i + 1][2] . ' - ' . $aMatches[$i + 1][3]  . '</f2><f1_line>' . $aMatches[$i][4] . '</f1_line><f2_line>' . $aMatches[$i + 1][4] . '</f2_line></prop>';
                $i++;
            }
            else
            {
                //Regular prop
                $sRetXML .= '<prop><f1>' . $aMatches[$i][1] . ' : ' . $aMatches[$i][2] . ' - ' . $aMatches[$i][3]  . '</f1><f2></f2><f1_line>' . $aMatches[$i][4] . '</f1_line><f2_line></f2_line></prop>';
            }
        }
        $sRetXML .= '</matchups>';

        return $sRetXML;
    }

}
?>
