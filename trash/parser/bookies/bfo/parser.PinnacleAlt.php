<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');
require_once('lib/simple_html_dom/simple_html_dom.php');

/**
 * XML Parser
 *
 * Bookie: Pinnacle HTML parser
 * Sport: MMA
 *
 * Comment: Used when API us unavailable. Currently limited to Bellator lines since UFC line page redirects
 *
 */
class XMLParserPinnacleAlt
{
    private $oParsedSport;
    private $bAuthorativeRun = false;

    public function __construct()
    {
        $this->oParsedSport = new ParsedSport('MMA');
    }

    public function parseXML($a_sXML)
    {
        $html = new simple_html_dom();
        $html->load($a_sXML);

        $rows = $html->find("table.linesTbl tr");

        for ($i =  0; $i < count($rows); $i = $i + 2) 
        {
            $odds1 = OddsTools::convertDecimalToMoneyline(str_replace('&nbsp;','', $rows[$i]->find("td.linesMLine")[0]->plaintext));
            $odds2 = OddsTools::convertDecimalToMoneyline(str_replace('&nbsp;','', $rows[$i + 1]->find("td.linesMLine")[0]->plaintext));

            if (ParseTools::checkCorrectOdds($odds1) && ParseTools::checkCorrectOdds($odds2) && $rows[$i]->find("td.linesTeam")[0]->plaintext != '' && $rows[$i + 1]->find("td.linesTeam")[0]->plaintext != '')
            {
                //Regular matchup
                $oParsedMatchup = new ParsedMatchup(
                    $rows[$i]->find("td.linesTeam")[0]->plaintext,
                    $rows[$i + 1]->find("td.linesTeam")[0]->plaintext,
                    $odds1,
                    $odds2
                );

                $this->oParsedSport->addParsedMatchup($oParsedMatchup);
            }
        }

        return [$this->oParsedSport];
    }

    public function checkAuthoritiveRun($a_aMetadata)
    {
        return $this->bAuthorativeRun;
    }
}
?>

