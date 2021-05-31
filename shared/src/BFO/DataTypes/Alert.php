<?php

namespace BFO\DataTypes;

use BFO\Utils\OddsTools;

/**
 * Alert Class - Represents an alert created in the system
 */
class Alert
{
    private $sEmail;
    private $iFightID;
    private $iFighter; //1 or 2
    private $iBookieID; //-1 for all
    private $iLimit; //In moneyline format (-100, +150, ..)
    private $iID;
    private $iOddsType; //1 = Moneyline, 2 = Decimal, 3 = Return on.., 4 = Fraction

    /**
     * Creates a new Alert object
     *
     * @param string $a_sEmail E-mail
     * @param int $a_iFightID Fight ID
     * @param int $a_iFighter Fighter number (1 or 2)
     * @param int $a_iBookieID Bookie ID
     * @param int $a_iLimit Limit
     * @param int $a_iID ID
     */
    public function __construct($recipient_email, $matchup_id, $team_id, $bookie_id, $odds_limit, $alert_id = -1, $odds_format = 1)
    {
        if (strtoupper($odds_limit) == 'EV' || strtoupper($odds_limit) == 'EVEN' || $odds_limit == '-100') {
            $odds_limit = '100';
        }

        $this->sEmail = trim($recipient_email);
        $this->iFightID = $matchup_id;
        $this->iFighter = $team_id;
        $this->iOddsType = $odds_format;
        $this->iID = $alert_id;
        $this->iLimit = $odds_limit;

        if (is_numeric($bookie_id)) {
            $this->iBookieID = $bookie_id;
        } else {
            $this->iBookieID = -1;
        }
    }

    /**
     * Get e-mail for the alert
     *
     * @return string E-mail adress of the user who created the alert
     */
    public function getEmail()
    {
        return $this->sEmail;
    }

    /**
     * Get Fight ID
     *
     * @return int Fight ID
     */
    public function getFightID()
    {
        return $this->iFightID;
    }

    /**
     * Get the fighter number (1 or 2) that the alert applies to
     *
     * @return int Fighter number
     */
    public function getFighter()
    {
        return $this->iFighter;
    }

    /**
     * Get the bookie ID that the alert has been created for
     *
     * @return int Bookie ID
     */
    public function getBookieID()
    {
        return $this->iBookieID;
    }

    /**
     * Get the limit that should be reached for the alert to be issued
     *
     * @return int Limit
     */
    public function getLimit()
    {
        return $this->iLimit;
    }

    /**
     * Get the limit as string that should be reached for the alert to be issued
     *
     * @return string Limit as string
     */
    public function getLimitAsString()
    {
        $sOdds = $this->iLimit;
        if ($sOdds == 0) {
            return 'error';
        } elseif ($sOdds == 100) {
            return 'EV';
        } elseif ($sOdds > 0) {
            return '+' . $sOdds;
        } else {
            return $sOdds;
        }
    }

    /**
     * Get the ID for this alert
     *
     * @return int Alert ID
     */
    public function getID()
    {
        return $this->iID;
    }

    /**
     * Gets the odds type for the alert
     * 1 = Moneyline, 2 = Decimal, 3 = Fraction
     *
     * @return int Odds type
     */
    public function getOddsType()
    {
        return $this->iOddsType;
    }
}
