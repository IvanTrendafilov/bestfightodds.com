<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/dao/class.AlertDAO.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('config/inc.alertConfig.php');
require_once('lib/bfocore/utils/class.OddsTools.php');
require_once('lib/bfocore/utils/aws-ses/class.SESMailer.php');

/**
 * Class Alerter - The alerter is a feature that warns (e-mails) a user whenever odds for a certain fight drops below a certain number
 *
 * TODO: Make generic
 */
class Alerter
{

    /**
     * Adds a new alert
     *
     * @param int $a_iFightID
     * @param int $a_iFighter
     * @param string $a_sEmail
     * @param int $a_iLimit
     * @param int $a_iBookieID
     * @return boolean True if the alert was created, false if it was not
     */
    public static function addNewAlert($a_iFightID, $a_iFighter, $a_sEmail, $a_iLimit, $a_iBookieID, $a_iOddsType = 1)
    {
        //If odds type == 3 (return on ..) then convert back to moneyline (1)
        if ($a_iOddsType == 3)
        {
            $a_iOddsType = 1;
        }

        //Override cooke set odds format if the format of the submitted odds is of a specific type. Only when limit is specified though
        if ($a_iLimit != '-9999')
        {
            if ($a_iLimit[0] == '+' || $a_iLimit[0] == '-')
            {
                //Starts with - or +, its moneyline
                //Note: + cannot be dected right now since the javascript removes it
                $a_iOddsType = 1;
            }
            else if (strpos($a_iLimit, '.') !== false)
            {
                //Contains a ., its decimal
                $a_iOddsType = 2;
            }
            else if (strpos($a_iLimit, '/') !== false)
            {
                //Contains a /, its fraction
                $a_iOddsType = 4;
            }

            //If oddstype differs from moneyline (1) then convert from the previous format
            if ($a_iOddsType == 2) //Decimal
            {
                $a_iLimit = OddsTools::convertDecimalToMoneyline($a_iLimit);
            }
            else if ($a_iOddsType == 4)
            {
                //TODO: Create conversion from fractional odds
            }
        }

        $oAlert = new Alert($a_sEmail, $a_iFightID, $a_iFighter, $a_iBookieID, $a_iLimit, -1, $a_iOddsType);
        return AlertDAO::addNewAlert($oAlert);
    }

    /**
     * Checks all alerts stored and dispatches the one that has reached the specified limit
     *
     * Alert is removed prior to dispatching to avoid people getting spammed like there is no tomorrow
     *
     * @return int The number of alerts dispatched
     */
    public static function checkAllAlerts()
    {
        $iAlertCount = 0;
        $aAlerts = AlertDAO::getReachedAlerts();
        foreach ($aAlerts as $oAlert)
        {
            $bSuccess = Alerter::dispatchAlert($oAlert);
            if ($bSuccess)
            {
                $iAlertCount++;
                $bClearSuccess = AlertDAO::clearAlert($oAlert->getID());
            }
        }
        return $iAlertCount;
    }

    /**
     * Dispatches an alert
     *
     * @param Alert $a_oAlert
     * @return boolean True if the alert was dispatched or false if it failed
     */
    public static function dispatchAlert($a_oAlert)
    {
        $oFightOdds = null;
        if ($a_oAlert->getBookieID() == -1)
        {
            //Alert is not bookie specific
            $oFightOdds = EventHandler::getBestOddsForFight($a_oAlert->getFightID());
        } else
        {
            //Alert is bookie specific
            $oFightOdds = EventHandler::getLatestOddsForFightAndBookie($a_oAlert->getFightID(), $a_oAlert->getBookieID());
        }

        $oFight = EventHandler::getFightByID($a_oAlert->getFightID());
        if ($oFightOdds == null || $oFight == null)
        {
            return false;
        }

        //Convert odds type if necessary
        $sTeamOdds[1] = $oFightOdds->getFighterOddsAsString(1);
        $sTeamOdds[2] = $oFightOdds->getFighterOddsAsString(2);
        if ($a_oAlert->getOddsType() == 2)
        {
            //Decimal
            $sTeamOdds[1] = OddsTools::convertMoneylineToDecimal($sTeamOdds[1]);
            $sTeamOdds[2] = OddsTools::convertMoneylineToDecimal($sTeamOdds[2]);
        }
        else if ($a_oAlert->getOddsType() == 3)
        {
            //Fraction
            //TODO: Create this when fraction support is introduced
        }


        //If odds is set to -9999 then we just want to announce that the fight has got odds
        if ($a_oAlert->getLimit() == -9999)
        {
            $sText = "Odds for " . $oFight->getFighterAsString(1) . " (" . $sTeamOdds[1] . ") vs " . $oFight->getFighterAsString(2) . " (" . $sTeamOdds[2] . ") has just been posted at " . ALERTER_SITE_NAME . "\n
Check out " . ALERTER_SITE_LINK . " to view the latest listings.\n
You are receiving this e-mail because you have signed up to be notified when odds were added for a certain matchup. If you did not sign up for this you don't have to do anything as your e-mail will not be stored for future use.\n
Good luck!\n
" . ALERTER_SITE_NAME;
            $sSubject = 'Odds for ' . $oFight->getFighterAsString(1) . ' vs ' . $oFight->getFighterAsString(2) . ' available';
        } else
        {
            $sText = "The odds for " . $oFight->getFighterAsString($a_oAlert->getFighter()) . " has reached " . $sTeamOdds[$a_oAlert->getFighter()] . " in his/her upcoming fight against " . $oFight->getFighterAsString(($a_oAlert->getFighter() == 1 ? 2 : 1)) . "\n
Check out " . ALERTER_SITE_LINK . " to view the latest listings.\n
You are receiving this e-mail because you have signed up to be notified when the odds changed for a certain matchup. If you did not sign up for this you don't have to do anything as your e-mail will not be stored for future use.\n
Good luck!\n
" . ALERTER_SITE_NAME;
            $sSubject = 'Odds for ' . $oFight->getFighterAsString($a_oAlert->getFighter()) . ' has reached your limit';
        }
        $sTo = $a_oAlert->getEmail();
        $sHeaders = 'From: ' . ALERTER_MAIL_FROM;


        $bSuccess = false;
        if (ALERTER_DEV_MODE == true)
        {
            //If dev mode, do not send any e-mail alert
            $bSuccess = true;
            echo 'Sent one: ' . $sSubject .'
            ';
            echo 'Message:' . $sText;
        } else
        {
            //Send e-mail alert
            $mailer = new SESMailer(MAIL_SMTP_HOST, MAIL_SMTP_PORT, MAIL_SMTP_USERNAME, MAIL_SMTP_PASSWORD);
            $bSuccess = $mailer->sendMail(ALERTER_MAIL_SENDER_MAIL, ALERTER_MAIL_FROM, 'cnordvaller@gmail.com', $sSubject, $sText, $sText);       
            //$bSuccess = mail($sTo, $sSubject, $sText, $sHeaders);

        }

        return $bSuccess;
    }

    /**
     * Removes all expired alerts
     *
     * @return int The number of alerts cleared
     */
    public static function cleanAlerts()
    {
        $aAlerts = AlertDAO::getExpiredAlerts();

        $iCleared = 0;
        foreach ($aAlerts as $oAlert)
        {
            if (AlertDAO::clearAlert($oAlert->getID()))
            {
                $iCleared++;
            }
        }
        return $iCleared;
    }

    /**
     * Get the number of alerts stored
     *
     * @return int Number of alerts stored
     */
    public static function getAlertCount()
    {
        return AlertDAO::getAlertCount();
    }

    /**
     * Gets arbitrage info for a fight. Information is stored in an array containing the following keys:
     *
     * 	fighter1bet => How much the stake that needs to be bet on fighter 1
     *  fighter1odds => At what odds you need to bet on fighter 1
     * 	fighter2bet => How much the stake that needs to be bet on fighter 2
     * 	fighter2odds => At what odds you need to bet on fighter 2
     * 	profit => The profit for betting on both
     *
     * @param int $a_iFightID Fight to get arbitrage info on
     * @param int $a_iStake The stake used as an example on how to divide the money
     * @return array Arbitrage information
     */
    public static function getArbitrageInfo($a_iFightID, $a_iStake = 100)
    {
        $oFightOdds = EventHandler::getBestOddsForFight($a_iFightID);

        if ($oFightOdds == null)
        {
            return null;
        }

        $fArbitValue = (pow($oFightOdds->getFighterOddsAsDecimal(1, true), -1)
                + pow($oFightOdds->getFighterOddsAsDecimal(2, true), -1));

        $fFirstOdds = $oFightOdds->getFighterOddsAsDecimal(1);
        $fSecondOdds = $oFightOdds->getFighterOddsAsDecimal(2);

        $fFirstStake = 100;
        $fSecondStake = $fFirstStake * $fFirstOdds / $fSecondOdds;

        $fTotal = $fFirstStake + $fSecondStake;
        $fPercentFirst = $fFirstStake / $fTotal;
        $fPercentSecond = $fSecondStake / $fTotal;

        $iNetProfitFirst = ($fFirstOdds * ($a_iStake * $fPercentFirst)) - $a_iStake;

        $aReturnArray = array("arbitrage" => round($fArbitValue, 5),
            "fighter1bet" => round(($a_iStake * $fPercentFirst), 2),
            "fighter1odds" => $oFightOdds->getFighterOddsAsString(1),
            "fighter2bet" => round(($a_iStake * $fPercentSecond), 2),
            "fighter2odds" => $oFightOdds->getFighterOddsAsString(2),
            "profit" => round($iNetProfitFirst, 2));

        return $aReturnArray;
    }

    /**
     * Gets all alerts stored
     *
     * @return array Array containing Alert objects
     */
    public static function getAllAlerts()
    {
        return AlertDAO::getAllAlerts();
    }

}

?>