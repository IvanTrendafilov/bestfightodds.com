<?php

namespace BFO\Parser;

class ParsedSport
{
    private $sName;
    private $aMatchups;
    private $aProps;

    public function __construct($a_sName)
    {
        $this->sName = trim($a_sName);
        $this->aMatchups = array();
        $this->aProps = array();
    }

    public function getName()
    {
        return $this->sName;
    }

    public function getParsedMatchups()
    {
        return $this->aMatchups;
    }

    public function addParsedMatchup($a_oParsedMatchup)
    {
        $this->aMatchups[] = $a_oParsedMatchup;
    }

    public function setMatchupList($a_aMatchups)
    {
        $this->aMatchups = $a_aMatchups;
    }

    /**
     * Combines separate lines, spreads and totals for the same matchup into one
     */
    public function mergeMatchups()
    {



        //Just for DEBUG
        /*echo "Pre:
";
        foreach ($this->aMatchups as $oMatchup)
        {
            echo $oMatchup->getTeamName(1) . ' - ' . $oMatchup->getTeamName(2) . "
";
        }*/

        $iOrigCount = count($this->aMatchups);
        for ($iY = 0; $iY < $iOrigCount; $iY++) {
            if (isset($this->aMatchups[$iY]) && $this->aMatchups[$iY] != null) {
                $oMatchup = $this->aMatchups[$iY];

                for ($iX = $iY + 1; $iX < $iOrigCount; $iX++) {
                    if (isset($this->aMatchups[$iX])) {
                        $oTempMatchup = $this->aMatchups[$iX];

                        if ($oMatchup->getTeamName(1) == $oTempMatchup->getTeamName(1) &&
                            $oMatchup->getTeamName(2) == $oTempMatchup->getTeamName(2)) {
                            $bChanged = false;

                            //Check if a moneyline exists in both. In that case just skip
                            //TODO: Maybe handle this in some way.. Maybe just check best arbitrage and then store that one?
                            if ($oMatchup->hasMoneyline() && $oTempMatchup->hasMoneyline()) {
                                //Do nothing for now
                            } elseif (!$oMatchup->hasMoneyline() && $oTempMatchup->hasMoneyline()) {
                                $oMatchup->addMoneyLineObj($oTempMatchup->getMoneyLineObj());
                            }

                            //Remove secondary matchup
                            if ($bChanged == true) {
                                unset($this->aMatchups[$iX]);
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function getFetchedProps()
    {
        return $this->aProps;
    }

    public function addFetchedProp($a_oParsedProp)
    {
        $this->aProps[] = $a_oParsedProp;
    }

    public function getPropCount()
    {
        return count($this->aProps);
    }

    public function setPropList($a_aProps)
    {
        $this->aProps = $a_aProps;
    }
}
