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
    private $bAuthorativeRun = false;

    public function __construct()
    {
        $this->oParsedSport = new ParsedSport('MMA');
    }

    public function parseXML($a_sXML)
    {
        XMLParserTheGreek::collectMMAEvents($a_sXML);

        //Declare authorative run if we fill the criteria
        if (count($this->oParsedSport->getParsedMatchups()) >= 25)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

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

                foreach ($collapsenode->find('span.yellow-text') as $headernode)
                {
                    //One header node
                    $sib = $headernode->next_sibling();
                    $teams = [];
                    $odds = [];
                    //Loop through and find all 'table' occurences. These contain the matchup and odds
                    while (trim(strtolower($sib->tag)) == 'table')
                    {
                        $teams[] = trim(str_replace(["&nbsp;", " Champ "], " ", $sib->find('td.content a.predictions-lines')[0]->plaintext));
                        $odds[] = trim(str_replace(["&nbsp;", " Champ "], " ", $sib->find('td.odds a.predictions-lines')[0]->plaintext));
                        $sib = $sib->next_sibling();
                    }
                    
                    if (count($teams) > 2 && count($odds) > 2) //If more than two teams and odds, most likely a prop
                    {
                        for ($x = 0; $x <= count($teams); $x++)
                        {
                            if (ParseTools::checkCorrectOdds($odds[$x]))
                            {
                                //One entry
                                $oParsedProp = new ParsedProp(
                                    $headernode[0]->plaintext . ' : ' . $teams[$x],
                                    '',
                                    $odds[$x],
                                    '-99999'
                                );
                                $this->oParsedSport->addFetchedProp($oParsedProp);
                            }
                        } 
                    }
                    else //Regular matchup (with potential additional prop)
                    {
                        if (ParseTools::checkCorrectOdds($odds[0]) && ParseTools::checkCorrectOdds($odds[1]))
                        {
                            if (substr($teams[0], 0, strlen('Over')) === 'Over' && substr($teams[1], 0, strlen('Under')) === 'Under')
                            {
                                //Over/under
                                $oParsedProp = new ParsedProp(
                                                $headernode[0]->plaintext . ' : ' . $teams[0],
                                                $headernode[0]->plaintext . ' : ' . $teams[1],
                                                $odds[0],
                                                $odds[1]
                                );

                                $this->oParsedSport->addFetchedProp($oParsedProp);
                            }
                            else
                            {
                                //Regular matchup
                                $oParsedMatchup = new ParsedMatchup(
                                                $teams[0],
                                                $teams[1],
                                                $odds[0],
                                                $odds[1]
                                );

                                $this->oParsedSport->addParsedMatchup($oParsedMatchup);
                            }
                        }
                    }
                }
            } 
        }
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }
}
?>

