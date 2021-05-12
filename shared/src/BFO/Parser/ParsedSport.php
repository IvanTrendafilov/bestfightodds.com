<?php

namespace BFO\Parser;

class ParsedSport
{
    private $name;
    private $matchups;
    private $props;

    public function __construct($name)
    {
        $this->name = trim($name);
        $this->matchups = array();
        $this->props = array();
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParsedMatchups()
    {
        return $this->matchups;
    }

    public function addParsedMatchup($parsed_matchup_obj)
    {
        if (
            trim($parsed_matchup_obj->getTeamName(1)) != '' &&
            trim($parsed_matchup_obj->getTeamName(2)) != ''
        ) {
            $this->matchups[] = $parsed_matchup_obj;
        }
    }

    public function setMatchupList($matchups)
    {
        $this->matchups = $matchups;
    }

    /**
     * Combines separate lines, spreads and totals for the same matchup into one
     */
    public function mergeMatchups()
    {
        $original_count = count($this->matchups);
        for ($i = 0; $i < $original_count; $i++) {
            if (isset($this->matchups[$i]) && $this->matchups[$i] != null) {
                $matchup_obj = $this->matchups[$i];

                for ($j = $i + 1; $j < $original_count; $j++) {
                    if (isset($this->matchups[$j])) {
                        $other_matchup_obj = $this->matchups[$j];

                        if (
                            $matchup_obj->getTeamName(1) == $other_matchup_obj->getTeamName(1) &&
                            $matchup_obj->getTeamName(2) == $other_matchup_obj->getTeamName(2)
                        ) {
                            $has_changed = false;

                            //Check if a moneyline exists in both. In that case just skip
                            //TODO: Maybe handle this in some way.. Maybe just check best arbitrage and then store that one?
                            if ($matchup_obj->hasMoneyline() && $other_matchup_obj->hasMoneyline()) {
                                //Do nothing for now
                            } elseif (!$matchup_obj->hasMoneyline() && $other_matchup_obj->hasMoneyline()) {
                                $matchup_obj->addMoneyLineObj($other_matchup_obj->getMoneyLineObj());
                            }

                            //Remove secondary matchup
                            if ($has_changed == true) {
                                unset($this->matchups[$j]);
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
        return $this->props;
    }

    public function addFetchedProp($parsed_prop_obj)
    {
        $this->props[] = $parsed_prop_obj;
    }

    public function getPropCount()
    {
        return count($this->props);
    }

    public function setPropList($props)
    {
        $this->props = $props;
    }
}
