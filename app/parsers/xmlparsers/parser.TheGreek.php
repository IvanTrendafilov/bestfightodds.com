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
        $sEventRegexp = '<a[^h]+href="(\/sportsbook\/betting-odds\/Martial Arts\/\?ct=Martial\+Arts&amp;ct2=.*?[^"]+)"\s*>';
        $aMatches = ParseTools::matchBlock($a_sPage, $sEventRegexp);

        //Reduce events to only main category and remove dupes
        $aURLList = array();
        foreach ($aMatches as $aMatch)
        {
            $iPos = 0;
            $sAddURL = '';
            if (($iPos = strpos($aMatch[1], '&amp;ct3')))
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
            $sURL = str_replace(" ", "%20", $sURL);
            $sPage = ParseTools::retrievePageFromURL('http://www.thegreek.com' . $sURL);

            Logger::getInstance()->log("Fetching page: http://www.thegreek.com" . $sURL, 0);

            $sLineMatch = '/<span class=\"name\" id=\"dt\">([^<]*)<\/span>[^<]*<span class=\"odd\" id=\"m1\">([+-][^<]+)<\/span>/';
            $aLineMatches = ParseTools::matchBlock($sPage, $sLineMatch);

            if (count($aLineMatches) == 0)
            {
                Logger::getInstance()->log("Warning: Parsed an empty page", -1);
            }
            $i = 0;
            for($i = 0; $i < sizeof($aLineMatches); $i++)
            {
                if ($i % 2 == 0) {
                    $aLineMatches[$i][1] = str_replace("&nbsp;", "", $aLineMatches[$i][1]);
                    $aLineMatches[$i][1] = str_replace(" Champ ", " ", $aLineMatches[$i][1]);
                    $aLineMatches[$i + 1][1] = str_replace("&nbsp;", "", $aLineMatches[$i + 1][1]);
                    $aLineMatches[$i + 1][1] = str_replace(" Champ ", " ", $aLineMatches[$i + 1][1]);
                    $sRetXML .= '<fight><f1>' . $aLineMatches[$i][1] . '</f1><f2>' . $aLineMatches[$i + 1][1] . '</f2><f1_line>' . $aLineMatches[$i][2] . '</f1_line><f2_line>' . $aLineMatches[$i + 1][2] . '</f2_line></fight>';
                }
                $i++;
            }
/*

            OLD MATCHING:

            //Clean up HTML
            $sPage = preg_replace("/<strong>[^<]* vs [^<]*<\/strong>/", "", $sPage);
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

                    //Temporary store of XML:
            $iIterate++;
            $rStoreFile = fopen('/var/www/vhosts/bestfightodds.com/httpdocs/storedfeeds/' . 'thegreek-' . date('Ymd-Hi') . '-' . $iIterate . '.xml', 'a');
            fwrite($rStoreFile, $sPage);
            fclose($rStoreFile);

            //Match fights in single page
            $sFightRegexp = '/[0-9]:[0-9]{2}\\s(AM|PM)\\s([a-zA-Z�]+[a-zA-Z�0-9"\\s"\\&;\\.,-]+?)\\s*([+-]{0,1}[0-9]+|EV|even)\\s+([a-zA-Z�]+[a-zA-Z�0-9"\\s\\.,"\\&;-]+?)\\s*([+-]{0,1}[0-9]+|EV|even)/';
            $aFightMatches = ParseTools::matchBlock($sPage, $sFightRegexp);




            if (count($aFightMatches) == 0)
            {
                Logger::getInstance()->log("Warning: Parsed an empty page", -1);
            }

            foreach ($aFightMatches as $aFight)
            {
                $sRetXML .= '<fight><f1>' . $aFight[2] . '</f1><f2>' . $aFight[4] . '</f2><f1_line>' . $aFight[3] . '</f1_line><f2_line>' . $aFight[5] . '</f2_line></fight>';
            }*/
        }

        $sRetXML .= '</fights>';

        return $sRetXML;
    }

}
?>

