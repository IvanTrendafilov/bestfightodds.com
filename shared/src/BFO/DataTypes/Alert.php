<?php

namespace BFO\DataTypes;

use BFO\Utils\OddsTools;

/**
 * Alert Class - Represents an alert created in the system
 */
class Alert
{
    private int $id;
    private string $recipient_email;
    private int $matchup_id;
    private int $team_num; //1 or 2
    private int $bookie_id; //-1 for all
    private int $limit; //In moneyline format (-100, +150, ..)
    
    private int $odds_format; //1 = Moneyline, 2 = Decimal, 3 = Return on.., 4 = Fraction

    public function __construct(string $recipient_email, int $matchup_id, int $team_id, int $bookie_id, mixed $odds_limit, int $alert_id = -1, int $odds_format = 1)
    {
        if (strtoupper($odds_limit) == 'EV' || strtoupper($odds_limit) == 'EVEN' || $odds_limit == '-100') {
            $odds_limit = '100';
        }

        $this->recipient_email = trim($recipient_email);
        $this->matchup_id = $matchup_id;
        $this->team_num = $team_id;
        $this->odds_format = $odds_format;
        $this->id = $alert_id;
        $this->limit = intval($odds_limit);

        if (is_numeric($bookie_id)) {
            $this->bookie_id = $bookie_id;
        } else {
            $this->bookie_id = -1;
        }
    }

    /**
     * Get e-mail for the alert
     *
     * @return string E-mail adress of the user who created the alert
     */
    public function getEmail(): string
    {
        return $this->recipient_email;
    }

    /**
     * Get Fight ID
     *
     * @return int Fight ID
     */
    public function getFightID(): int
    {
        return $this->matchup_id;
    }

    /**
     * Get the fighter number (1 or 2) that the alert applies to
     *
     * @return int Fighter number
     */
    public function getFighter(): int
    {
        return $this->team_num;
    }

    public function getBookieID(): int
    {
        return $this->bookie_id;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Get the limit as string that should be reached for the alert to be issued
     *
     * @return string Limit as string
     */
    public function getLimitAsString()
    {
        $odds = $this->limit;
        if ($odds == 0) {
            return 'error';
        } elseif ($odds == 100) {
            return 'EV';
        } elseif ($odds > 0) {
            return '+' . $odds;
        } else {
            return $odds;
        }
    }

    /**
     * Get the ID for this alert
     *
     * @return int Alert ID
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Gets the odds type for the alert
     * 1 = Moneyline, 2 = Decimal, 3 = Fraction
     *
     * @return int Odds type
     */
    public function getOddsType()
    {
        return $this->odds_format;
    }
}
