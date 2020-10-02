<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: BetOnnline Alternative Parser (site, not xml)
 * Sport: MMA
 *
 * Comment: In production
 *
 */
class XMLParserBetOnlineAlt
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

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->matchup as $cProp)
        {
            if (ParseTools::checkCorrectOdds(trim((string) $cProp->f1_line)) && ParseTools::checkCorrectOdds(trim((string) $cProp->f2_line)))
            {
                $oParsedMatchup = new ParsedMatchup(
                                (string) $cProp->f1,
                                (string) $cProp->f2,
                                (string) $cProp->f1_line,
                                (string) $cProp->f2_line
                );

                $oParsedSport->addParsedMatchup($oParsedMatchup);
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

    private function collectMMAEvents($a_sPage)
    {

        $sRetXML = '<?xml version="1.0" encoding="UTF-8"?><matchups>';
        $sEventRegexp = '/tmId\':\'([^\']+)\'[^>]*\'[^>]*\'aorg\':(-?[0-9]*)/';
        $aMatches = ParseTools::matchBlock($a_sPage, $sEventRegexp);
        for ($x = 0; $x <= count($aMatches); $x += 2)
        {
            //Check if participant name contains a comma, if so restructure the name
            for ($y = 0; $y <= 1; $y++)
            {
                if(isset($aMatches[$x + $y][1]) && strpos($aMatches[$x + $y][1], ',') !== false ) {
                    //Name contains a comma, split and restructure
                    $sploded = explode(',', $aMatches[$x + $y][1]);
                    $aMatches[$x + $y][1] = $sploded[1] . ' ' . $sploded[0];
                }
            }

            $sRetXML .= '<matchup><f1>' . $aMatches[$x][1] . '</f1><f2>' . $aMatches[$x + 1][1] . '</f2><f1_line>' . $aMatches[$x][2] . '</f1_line><f2_line>' . $aMatches[$x + 1][2] . '</f2_line></matchup>';
        }

        $sRetXML .= '</matchups>';

        return $sRetXML;
    }

}
?>

