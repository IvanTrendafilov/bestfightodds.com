<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

/**
 * XML Parser
 *
 * Bookie: BetDSITemp
 * Sport: MMA
 *
 * Moneylines: Yes
 * Spreads: No
 * Totals: No
 * Props: Yes (confirmed)
 * Authoritative run: Yes
 *
 * Comment: Temporary version when regular is unavailable
 *
 */
class XMLParserBetDSITemp
{
    private $bAuthorativeRun = false;

    public function parseXML($a_sPage)
    {
        $html = new simple_html_dom();
        $html->load($a_sPage);

        $oParsedSport = new ParsedSport('MMA');

        if ($html == false)
        {
            Logger::getInstance()->log("Warning: HTML broke!!", -1);
        }

        foreach ($html->find("table.tableLines") as $tables) 
        {
            $rows = $tables->find("tr");
            $team1name = (string) trim($rows[1]->find('td.team')[0]->plaintext);
            $team2name = (string) trim($rows[2]->find('td.team')[0]->plaintext);
            
            $team1odds = (string) $rows[1]->find('td.total')[0]->plaintext;
            $team2odds = (string) $rows[2]->find('td.total')[0]->plaintext;

            if (ParseTools::checkCorrectOdds((string) $team1odds) &&
                ParseTools::checkCorrectOdds((string) $team2odds))
            {
                $oParsedSport->addParsedMatchup(new ParsedMatchup(
                                $team1name,
                                $team2name,
                                $team1odds,
                                $team2odds
                ));
            }
               
        }
        
        //Declare authorative run if we fill the criteria
        if (false)
        {
            $this->bAuthorativeRun = true;
            Logger::getInstance()->log("Declared authoritive run", 0);
        }

        return [$oParsedSport];
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }

}

?>