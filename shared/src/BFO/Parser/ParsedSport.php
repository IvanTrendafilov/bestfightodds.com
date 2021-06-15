<?php

namespace BFO\Parser;

/**
 * ParsedSport Class - Represents a parsed sport from a sportsbook containing multiple ParsedMatchups and ParsedProps
 */
class ParsedSport
{
    private string $name;
    private array $matchups;
    private array $props;

    public function __construct(string $name)
    {
        $this->name = trim($name);
        $this->matchups = [];
        $this->props = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParsedMatchups(): array
    {
        return $this->matchups;
    }

    public function addParsedMatchup(ParsedMatchup $parsed_matchup_obj): void
    {
        if (
            trim($parsed_matchup_obj->getTeamName(1)) != '' &&
            trim($parsed_matchup_obj->getTeamName(2)) != '' &&
            trim(strtoupper($parsed_matchup_obj->getTeamName(1))) != 'OVER' &&
            trim(strtoupper($parsed_matchup_obj->getTeamName(1))) != 'UNDER' && 
            trim(strtoupper($parsed_matchup_obj->getTeamName(2))) != 'OVER' &&
            trim(strtoupper($parsed_matchup_obj->getTeamName(2))) != 'UNDER'
        ) {
            $this->matchups[] = $parsed_matchup_obj;
        }
    }

    public function setMatchupList(array $matchups): void
    {
        $this->matchups = $matchups;
    }

    public function getFetchedProps(): array
    {
        return $this->props;
    }

    public function addFetchedProp(ParsedProp $parsed_prop_obj): void
    {
        $this->props[] = $parsed_prop_obj;
    }

    public function getPropCount(): int
    {
        return count($this->props);
    }

    public function setPropList(array $props): void
    {
        $this->props = $props;
    }
}
