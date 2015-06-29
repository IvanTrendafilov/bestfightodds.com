<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

/**
 * XML Parser
 *
 * Bookie: TheGreek
 * Sport: MMA
 *
 * Comment: In production
 *
 */
class XMLParserTheGreek
{

    public function parseXML($a_sXML)
    {
        $sXML = XMLParserTheGreek::collectMMAEvents($a_sXML);
        $oXML = simplexml_load_string($sXML);

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

                $oParsedSport->addParsedMatchup($oParsedMatchup);
            }
        }

        $aSports[] = $oParsedSport;

        return $aSports;
    }

    private function collectMMAEvents($a_sPage)
    {

        $sRetXML = '<?xml version="1.0" encoding="UTF-8"?><fights>';

        //Match events in main page
        $sEventRegexp = '<a href="(\/sportsbook\/betting-odds\/Boxing\/\?ct=Boxing&amp;ct2=.*?((MMA)|(UFC)|(Ultimate))[^"]+)"\s*>';
        $aMatches = ParseTools::matchBlock($a_sPage, $sEventRegexp);

        //Reduce events to only main category and remove dupes
        $aURLList = array();
        foreach ($aMatches as $aMatch)
        {
            $iPos = 0;
            $sAddURL = '';
            if (($iPos = strpos($aMatch[1], '&ContestType3')))
            {
                $sAddURL = substr($aMatch[1], 0, $iPos);
            } else
            {
                $sAddURL = $aMatch[1];
            }
            if ($sAddURL != '' && !in_array($sAddURL, $aURLList))
            {
                $aURLList[] = $sAddURL;
            }
        }

        Logger::getInstance()->log("Parsing " . count($aURLList) . ' internal URLs', 0);

        foreach ($aURLList as $sURL)
        {
            $sPage = ParseTools::retrievePageFromURL('http://www.thegreek.com' . $sURL);

            //Clean up HTML
            $sPage = strip_tags($sPage);
            $sPage = str_replace("\r", " ", $sPage);
            $sPage = str_replace("\n", " ", $sPage);
            $sPage = str_replace("\t", "", $sPage);
            $sPage = str_replace("&nbsp;", "", $sPage);
            $sPage = str_replace(" Champ ", " ", $sPage);
            while (strpos($sPage, '  ') !== false)
            {
                $sPage = str_replace("  ", " ", $sPage);
            }


            //Match fights in single page
            $sFightRegexp = '/[0-9]:[0-9]{2}\\s(AM|PM)\\s([a-zA-Z�]+[a-zA-Z�0-9"\\s\\.,]+?)\\s*([+-]{0,1}[0-9]+|EV|even)\\s+([a-zA-Z�]+[a-zA-Z�0-9"\\s\\.,]+?)\\s*([+-]{0,1}[0-9]+|EV|even)/';
            $aFightMatches = ParseTools::matchBlock($sPage, $sFightRegexp);


            if (count($aFightMatches) == 0)
            {
                Logger::getInstance()->log("Warning: Parsed an empty page", -1);
            }

            foreach ($aFightMatches as $aFight)
            {
                $sRetXML .= '<fight><f1>' . $aFight[2] . '</f1><f2>' . $aFight[4] . '</f2><f1_line>' . $aFight[3] . '</f1_line><f2_line>' . $aFight[5] . '</f2_line></fight>';
            }
        }

        $sRetXML .= '</fights>';

        return $sRetXML;
    }

}
?>

