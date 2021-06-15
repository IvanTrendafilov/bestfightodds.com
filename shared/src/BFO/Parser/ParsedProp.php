<?php

namespace BFO\Parser;

/**
 * ParsedProp Class - Represents a parsed prop bet from a sportsbook
 */
class ParsedProp extends ParsedMatchup
{
    private $matched_matchup_id = null;
    private $matched_team_no = null;
    private $prop_values = null;
    private $main_prop_position = null;

    public function __construct($team1, $team2, $team1_odds, $team2_odds, $date = '')
    {
        parent::__construct($team1, $team2, $team1_odds, $team2_odds, $date);
    }

    public function setMatchedMatchupID($matched_matchup_id)
    {
        $this->matched_matchup_id = $matched_matchup_id;
    }

    public function getMatchedMatchupID()
    {
        return $this->matched_matchup_id;
    }

    public function setMatchedTeamNumber($team_no)
    {
        $this->matched_team_no = $team_no;
    }

    public function getMatchedTeamNumber()
    {
        return $this->matched_team_no;
    }

    /**
     * Get prop values
     *
     * Prop values are the values that will be used to replace the variables in the template
     * For example: %F1L%
     *
     * @return Array Collection of prop values in string format in order they appear in the prop
     *
     */
    public function getPropValues()
    {
        return $this->prop_values;
    }

    /**
     * Set prop values
     *
     * See getPropValues() for explanation
     *
     * @param Array $prop_values Collection of prop values
     *
     */
    public function setPropValues($prop_values)
    {
        //Trim values before adding
        foreach ($prop_values as &$prop_value) {
            $prop_value = trim($prop_value, ' -\t');
        }

        $this->prop_values = $prop_values;
    }

    /**
     * Function used to set which of the team names that contains the main prop. The other will be the negative prop
     *
     * @return int Team 1 or 2
     */
    public function getMainProp()
    {
        return $this->main_prop_position;
    }

    /**
     * Function used to set which of the team names that contains the main prop. The other will be the negative prop
     *
     * @param int $a_iProp Team 1 or 2
     */
    public function setMainProp($team_no)
    {
        $this->main_prop_position = $team_no;
    }
}
