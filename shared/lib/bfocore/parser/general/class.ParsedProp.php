<?php

require_once('lib/bfocore/parser/general/class.ParsedMatchup.php');

class ParsedProp extends ParsedMatchup
{
    private $iMatchedMatchupID = null;
    private $iMatchedTeamNumber = null;
    private $aPropValues = null;
    private $iMainProp = null;

    public function __construct($a_sTeam1, $a_sTeam2, $a_sTeam1Odds, $a_sTeam2Odds, $a_sDate = '')
    {
        parent::__construct($a_sTeam1, $a_sTeam2, $a_sTeam1Odds, $a_sTeam2Odds, $a_sDate);
    }

    public function setMatchedMatchupID($a_iMatchedMatchupID)
    {
        $this->iMatchedMatchupID = $a_iMatchedMatchupID;
    }

    public function getMatchedMatchupID()
    {
        return $this->iMatchedMatchupID;
    }

    public function setMatchedTeamNumber($a_iTeamNumber)
    {
        $this->iMatchedTeamNumber = $a_iTeamNumber;
    }

    public function getMatchedTeamNumber()
    {
        return $this->iMatchedTeamNumber;
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
        return $this->aPropValues;
    }

    /**
     * Set prop values
     *
     * See getPropValues() for explanation
     *
     * @param Array $aPropValues Collection of prop values
     *
     */
    public function setPropValues($aPropValues)
    {
        //Trim values before adding
        foreach ($aPropValues as &$sPropValue) {
            $sPropValue = trim($sPropValue, ' -\t');
        }

        $this->aPropValues = $aPropValues;
    }

    /**
     * Function used to set which of the team names that contains the main prop. The other will be the negative prop
     *
     * @return int Team 1 or 2
     */
    public function getMainProp()
    {
        return $this->iMainProp;
    }

    /**
     * Function used to set which of the team names that contains the main prop. The other will be the negative prop
     *
     * @param int $a_iProp Team 1 or 2
     */
    public function setMainProp($a_iProp)
    {
        $this->iMainProp = $a_iProp;
    }
}
