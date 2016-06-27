<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

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
    private $oParsedSport;

    public function __construct()
    {
        $this->oParsedSport = new ParsedSport('MMA');
    }

    public function parseXML($a_sXML)
    {
        XMLParserTheGreek::collectMMAEvents($a_sXML);
        return [$this->oParsedSport];
    }

    private function collectMMAEvents($a_sPage)
    {
        $html = new simple_html_dom();
        $html->load($a_sPage);

        $aURLs = [];

        foreach ($html->find("a.toggle-rot90") as $sportlinesnode) 
        {
            $sTempURL = "http://www.thegreek.com" . str_replace('>', '%3E', str_replace(' ', '%20', $sportlinesnode->href));
            $aURLs[] = $sTempURL;
            Logger::getInstance()->log("Preparing fetch of " . $sTempURL, 0);
        }

        Logger::getInstance()->log("Fetching " . count($aURLs) . ' internal URLs', 0); 
        ParseTools::retrieveMultiplePagesFromURLs($aURLs);
        foreach ($aURLs as $sURL)
        {
            self::processSingleEvent(ParseTools::getStoredContentForURL($sURL));
        }
    }

    private function processSingleEvent($a_sContent)
    {
        $html = new simple_html_dom();
        $html->load($a_sContent);

        foreach ($html->find("ul.sports-lines") as $sportlinesnode) 
        {
            foreach ($sportlinesnode->find('div.collaps') as $collapsenode)
            {

                /*foreach ($collapsenode->find('span.yellow-text') as $ytn)
                {
                    echo 'once ';
                    $sib = $ytn->next_sibling();
                    echo 'next: ' . $sib->tag . '  ';
                    
                }*/

                //One entry
                $headernodes = $collapsenode->find('span.yellow-text');
                $teamnodes = $collapsenode->find('td.content a.predictions-lines');
                $oddsnodes = $collapsenode->find('td.odds a.predictions-lines');

                //If only one header node and more than two teams and odds, most likely a prop
                if (count($headernodes) == 1 && count($teamnodes) > 2 && count($oddsnodes) > 2)
                {
                    //Single line prop
                    $iX = 0;
                    foreach ($teamnodes as $teamnode)
                    {
                        $sTeam1 = trim(str_replace("&nbsp;", " ", $teamnodes[$iX]->plaintext));

                        $oParsedProp = new ParsedProp(
                                        $headernodes[0]->plaintext . ' : ' . $sTeam1,
                                        '',
                                        $oddsnodes[$iX]->plaintext,
                                        '-99999'
                        );

                        $this->oParsedSport->addFetchedProp($oParsedProp);
                        $iX++;
                    }
                }
                else if (count($headernodes) >= 1)
                {
                    //Regular matchup (with potential additional prop)
                    $iX = 0;
                    foreach ($headernodes as $headernode)
                    {
                        if (ParseTools::checkCorrectOdds(trim((string) $oddsnodes[$iX]->plaintext))
                                && ParseTools::checkCorrectOdds(trim((string) $oddsnodes[$iX + 1]->plaintext))
                        )
                        {
                            $sTeam1 = trim(str_replace(" Champ ", " ", str_replace("&nbsp;", " ", $teamnodes[$iX]->plaintext)));
                            $sTeam2 = trim(str_replace(" Champ ", " ", str_replace("&nbsp;", " ", $teamnodes[$iX + 1]->plaintext)));

                            if (substr($sTeam1, 0, strlen('Over')) === 'Over' && substr($sTeam2, 0, strlen('Under')) === 'Under')
                            {
                                //Over/under
                                $oParsedProp = new ParsedProp(
                                                $headernodes[0]->plaintext . ' : ' . $sTeam1,
                                                $headernodes[0]->plaintext . ' : ' . $sTeam2,
                                                $oddsnodes[$iX]->plaintext,
                                                $oddsnodes[$iX + 1]->plaintext
                                );

                                $this->oParsedSport->addFetchedProp($oParsedProp);
                            }
                            else
                            {
                                //Regular matchup
                                $oParsedMatchup = new ParsedMatchup(
                                                $sTeam1,
                                                $sTeam2,
                                                $oddsnodes[$iX]->plaintext,
                                                $oddsnodes[$iX + 1]->plaintext
                                );

                                $this->oParsedSport->addParsedMatchup($oParsedMatchup);
                            }
                        }
                        $iX += 2;
                    }
                }
            } 
        }
    }
}
?>

