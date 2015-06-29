<?php

require_once('lib/bfocore/utils/class.OddsTools.php');

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
    public function __construct($a_sEmail, $a_iFightID, $a_iFighter, $a_iBookieID, $a_iLimit, $a_iID = -1, $a_iOddsType = 1)
    {
        if (strtoupper($a_iLimit) == 'EV' || strtoupper($a_iLimit) == 'EVEN' || $a_iLimit == '-100')
        {
            $a_iLimit = '100';
        }

        $this->sEmail = $a_sEmail;
        $this->iFightID = $a_iFightID;
        $this->iFighter = $a_iFighter;
        $this->iOddsType = $a_iOddsType;
        $this->iID = $a_iID;
        $this->iLimit = $a_iLimit;

        if (is_numeric($a_iBookieID))
        {
            $this->iBookieID = $a_iBookieID;
        } else
        {
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
        if ($sOdds == 0)
        {
            return 'error';
        } else if ($sOdds == 100)
        {
            return 'EV';
        } else if ($sOdds > 0)
        {
            return '+' . $sOdds;
        } else
        {
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

?>