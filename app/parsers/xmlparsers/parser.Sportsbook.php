<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: Sportsbook
 * Sport: MMA
 *
 * Comment: Prod version
 * Note, since RSS is no longer working. Altmode has been changed to primary
 *
 */
class XMLParserSportsbook
{
    /* Used in case the primary parsing feature is not working */

    private function altParseXML($a_sXML)
    {

        $oXML = simplexml_load_string($a_sXML);

        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();

        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->channel->item as $cItem)
        {
            $sLine = strip_tags($cItem->description);

            $sRegExp = '/Moneylines for this game are (.*) ([+-]{0,1}[0-9]+|EV|even) and (.*) ([+-]{0,1}[0-9]+|EV|even)/';
            preg_match($sRegExp, $sLine, $aMatches);

            if (isset($aMatches) && sizeof($aMatches) > 4 &&
                    ParseTools::checkCorrectOdds(trim((string) $aMatches[2])) &&
                    ParseTools::checkCorrectOdds(trim((string) $aMatches[4])))
            {
                $oParsedMatchup = new ParsedMatchup(
                                (string) $aMatches[1],
                                (string) $aMatches[3],
                                (string) $aMatches[2],
                                (string) $aMatches[4]
                );

                $oParsedSport->addParsedMatchup($oParsedMatchup);
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

    public function parseXML($a_sXML)
    {
        $sPage = $a_sXML;

        $sRetXML = '<?xml version="1.0" encoding="UTF-8"?><fights>';

        //Clean up HTML
        $sPage = strip_tags($sPage);
        $sPage = str_replace("\r", " ", $sPage);
        $sPage = str_replace("\n", " ", $sPage);
        $sPage = str_replace("\t", " ", $sPage);
        $sPage = str_replace("&nbsp;", " ", $sPage);
        while (strpos($sPage, '  ') !== false)
        {
            $sPage = str_replace("  ", " ", $sPage);
        }
        $sPage = ParseTools::stripForeignChars($sPage);
	
        //Match fights in single page
        $sFightRegexp = '/(\\d{2}\\/\\d{2}\\/\\d{2}) \\d{1,5} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF ([0-9]{2}:[0-9]{2}) [A-Za-z]{2} ([a-zA-Z]+[a-zA-Z0-9\\s\\-.,\']+?) ([+-]{0,1}[0-9]+|EV|even) OFF OFF/';

        $aFightMatches = ParseTools::matchBlock($sPage, $sFightRegexp);

        if (count($aFightMatches) == 0)
        {
            Logger::getInstance()->log("Warning: No matchups found in alternative parser", -1);
        }

        $sTimezone = (new DateTime())->setTimezone(new DateTimeZone('America/New_York'))->format('T');

        foreach ($aFightMatches as $aFight)
        {
            //Add time of matchup
            $oGameDate = new DateTime($aFight[1] . ' ' . $aFight[4] . ' ' . $sTimezone);

            $sRetXML .= '<fight><timestamp>' . ((string) $oGameDate->getTimestamp()) . '</timestamp><f1>' . $aFight[2] . '</f1><f2>' .
                    $aFight[5] . '</f2><f1_line>' . $aFight[3] . '</f1_line><f2_line>' .
                    $aFight[6] . '</f2_line></fight>';
        }
        $sRetXML .= '</fights>';

        $oXML = simplexml_load_string($sRetXML);
        if ($oXML == false)
        {
            Logger::getInstance()->log("Warning: XML broke!!", -1);
        }

        $aSports = array();
        $oParsedSport = new ParsedSport('MMA');

        foreach ($oXML->fight as $cFight)
        {
            if (ParseTools::checkCorrectOdds(trim((string) $cFight->f1_line))
                    && ParseTools::checkCorrectOdds(trim((string) $cFight->f2_line))
            )
            {
                $oParsedMatchup = new ParsedMatchup(
                                (string) $cFight->f1,
                                (string) $cFight->f2,
                                (string) $cFight->f1_line,
                                (string) $cFight->f2_line
                );
                $oParsedMatchup->setMetaData('gametime', (string) $cFight->timestamp);

                $oParsedSport->addParsedMatchup($oParsedMatchup);
            }
        }

        $aSports[] = $oParsedSport;
        return $aSports;
    }

}

?>