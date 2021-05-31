<?php

namespace BFO\DataTypes;

use BFO\Utils\LinkTools;

class Fight
{
    private int $id;
    private int $event_id;

    private string $team1;
    private string $team2;
    private int $team1_id;
    private int $team2_id;
    
    private bool $internal_order_changed;
    private bool $external_order_changed = false;
    private bool $is_main_event = false;
    private bool $is_future = false;
    private array $metadata;

    public function __construct(int $id, string $team1_name, string $team2_name, int $event_id)
    {
        $this->id = $id;
        if (trim($team1_name) > trim($team2_name)) {
            $this->team1 = trim(strtoupper($team2_name));
            $this->team2 = trim(strtoupper($team1_name));
            $this->internal_order_changed = true;
        } else {
            $this->team1 = trim(strtoupper($team1_name));
            $this->team2 = trim(strtoupper($team2_name));
            $this->internal_order_changed = false;
        }

        if ($event_id == '') {
            $this->event_id = -1;
        } else {
            $this->event_id = $event_id;
        }
        $this->metadata = [];
    }

    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @deprecated Use getTeam instead
     */
    public function getFighter(int $fighter_num): string
    {
        return $this->getTeam($fighter_num);
    }

    /**
     * See getTeamAsStringFromString below
     *
     * @deprecated Use getTeamAsString instead
     */
    public function getFighterAsString(int $fighter_num): string
    {
        return $this->getTeamAsString($fighter_num);
    }

    public function getTeamAsString(int $team_num): string
    {
        return $this->getTeamAsStringFromString($this->getTeam($team_num));
    }

    public function getTeamLastNameAsString(int $team_num): string
    {
        //Gets everything after first name: $aParts = explode(' ', $a_sName, 2); return $aParts[1];
        $aParts = explode(' ', $this->getTeamAsStringFromString($this->getTeam($team_num)));
        return $aParts[count($aParts) - 1];
    }

    public function getFighterAsLinkString(int $team_num): string
    {
        $slug = LinkTools::slugString($this->getTeamAsString($team_num));
        return $slug . '-' . ($team_num == 1 ? $this->team1_id : $this->team2_id);
    }

    public function getFightAsLinkString(): string
    {
        $slug = LinkTools::slugString($this->getTeamAsString(1)) . '-vs-' . LinkTools::slugString($this->getTeamAsString(2));
        return $slug . '-' . $this->id;
    }

    /**
     * Returns a more nicer looking representation of the fighter
     * Warning: Use only when displaying fighter name directly to user and NOT when working with the name.
     *
     * TODO: Currently fixes if a fighter has a name like B.J. Penn.
     *		 However does not fix if a name has 3 or more letter like
     *		 'R.K.B Whatever' which would look like 'R.k.B Whatever'
     *
     */
    public function getTeamAsStringFromString(string $fighter_name): string
    {
        $word_splitters = array(' ', '.', '-', "O'", "L'", "D'", 'St.', 'Mc');
        $lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
        $uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');

        $fighter_name = strtolower($fighter_name);
        foreach ($word_splitters as $delimiter) {
            $words = explode($delimiter, $fighter_name);
            $newwords = array();
            foreach ($words as $word) {
                if (in_array(strtoupper($word), $uppercase_exceptions)) {
                    $word = strtoupper($word);
                } elseif (!in_array($word, $lowercase_exceptions)) {
                    $word = ucfirst($word);
                }

                $newwords[] = $word;
            }

            if (in_array(strtolower($delimiter), $lowercase_exceptions)) {
                $delimiter = strtolower($delimiter);
            }

            $fighter_name = join($delimiter, $newwords);
        }
        return trim($fighter_name);
    }

    public function getTeamID(int $team_num): int
    {
        if ($team_num == 1 && isset($this->team1_id)) {
            return $this->team1_id;
        }
        if ($team_num == 2 && isset($this->team2_id)) {
            return $this->team1_id;
        }
        return -1;
    }

    /**
     * @depcrecated Use getTeamID() instead
     */
    public function getFighterID(int $team_num): int
    {
        return $this->getTeamID($team_num);
    }

    public function getEventID(): int
    {
        return $this->event_id;
    }

    public function hasOrderChanged(): bool
    {
        return $this->internal_order_changed;
    }

    public function setExternalOrderChanged(bool $changed): void
    {
        $this->external_order_changed = $changed;
    }

    public function hasExternalOrderChanged(): bool
    {
        return $this->external_order_changed;
    }

    public function setFighterID(int $team_num, int $team_id): void
    {
        if ($this->internal_order_changed == true) {
            if ($team_num == 1) {
                $this->team2_id = $team_id;
            } elseif ($team_num == 2) {
                $this->team1_id = $team_id;
            }
        } else {
            if ($team_num == 1) {
                $this->team1_id = $team_id;
            } elseif ($team_num == 2) {
                $this->team2_id = $team_id;
            }
        }
    }

    public function setMainEvent(bool $is_main_event): void
    {
        $this->is_main_event = $is_main_event;
    }

    public function isMainEvent(): bool
    {
        return $this->is_main_event;
    }

    public function setIsFuture(bool $is_future): void
    {
        $this->is_future = $is_future;
    }

    public function isFuture(): bool
    {
        return $this->is_future;
    }

    public function setEventID(int $event_id): void
    {
        $this->event_id = $event_id;
    }

    public function getTeam(int $team_num): ?string
    {
        if ($team_num == 1) {
            return $this->team1;
        }
        if ($team_num == 2) {
            return $this->team2;
        }
        return null;
    }

    public function setMetadata(string $attribute, mixed $value): void
    {
        $this->metadata[(string) $attribute] = (string) $value;
    }

    public function getMetadata(mixed $attribute): ?string
    {
        return $this->metadata[(string) $attribute] ?? null;
    }
}
