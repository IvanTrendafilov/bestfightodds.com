<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: BetOnline Alternative Parser (site, not xml)
 * Sport: MMA
 *
 * Comment: Not in production. Currently replaced by JSON feed parser (BetOnline)
 *          Note: In order for this to work properly you need to setup a cronjob that fetches the XML using Lynx or similar:
 *          * /3+1   *       *       *       * lynx -source "https://www.betonline.ag/sportsbook/line/retrievelinedata?param.PrdNo=-1&param.Type=Cntst&param.RequestType=Normal&param.CntstParam.Lv1=MMA+Props" > /var/www/bfo/bfo/app/front/externalfeeds/betonline-props.xml
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
                $cProp->f1 = str_replace('\u00A0', ' ', (string) $cProp->f1);
                $cProp->f2 = str_replace('\u00A0', ' ', (string) $cProp->f2);

                //Extract the correlation ID which will essentially be the matchup in order by name (Betonline does not do this be default)
                $parts = [strtoupper(trim($cProp->f1)), strtoupper(trim($cProp->f2))];
                sort($parts);
                $correlation_id = $parts[0] . ' VS ' . $parts[1];

                $oParsedMatchup = new ParsedMatchup(
                                (string) $cProp->f1,
                                (string) $cProp->f2,
                                (string) $cProp->f1_line,
                                (string) $cProp->f2_line
                );
                $oParsedMatchup->setCorrelationID($correlation_id);

                $oParsedSport->addParsedMatchup($oParsedMatchup);
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

    private function collectMMAEvents($a_sPage)
    {

        $sRetXML = '<?xml version="1.0" encoding="UTF-8"?><matchups>';
        $sEventRegexp = '/tmId\':\'(.+)\'\},\'[^>]*\'[^>]*\'aorg\':(-?[0-9]*)/';
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
