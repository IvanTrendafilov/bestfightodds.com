<?php

namespace BFO\General;

use BFO\DB\AlertDB;
use BFO\General\EventHandler;
use BFO\General\OddsHandler;
use BFO\Utils\OddsTools;
use BFO\Utils\AWS_SES\SESMailer;
use BFO\DataTypes\Alert;

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
     * @param int $matchup_id
     * @param int $team_no
     * @param string $email
     * @param int $odds_limit
     * @param int $bookie_id
     * @return boolean True if the alert was created, false if it was not
     */
    public static function addNewAlert($matchup_id, $team_no, $email, $odds_limit, $bookie_id, $odds_type = 1)
    {
        //If odds type == 3 (return on ..) then convert back to moneyline (1)
        if ($odds_type == 3) {
            $odds_type = 1;
        }

        //Override cooke set odds format if the format of the submitted odds is of a specific type. Only when limit is specified though
        if ($odds_limit != '-9999') {
            if ($odds_limit[0] == '+' || $odds_limit[0] == '-') {
                //Starts with - or +, its moneyline
                //Note: + cannot be dected right now since the javascript removes it
                $odds_type = 1;
            } elseif (strpos($odds_limit, '.') !== false) {
                //Contains a ., its decimal
                $odds_type = 2;
            } elseif (strpos($odds_limit, '/') !== false) {
                //Contains a /, its fraction
                $odds_type = 4;
            }

            //If oddstype differs from moneyline (1) then convert from the previous format
            if ($odds_type == 2) { //Decimal
                $odds_limit = OddsTools::convertDecimalToMoneyline($odds_limit);
            } elseif ($odds_type == 4) {
                //TODO: Create conversion from fractional odds
            }
        }

        $new_alert = new Alert((string) $email, (int) $matchup_id, (int) $team_no, (int) $bookie_id, (int) $odds_limit, -1, (int) $odds_type);
        return AlertDB::addNewAlert($new_alert);
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
        $count = 0;
        $alerts = AlertDB::getReachedAlerts();
        foreach ($alerts as $alert) {
            $success = Alerter::dispatchAlert($alert);
            if ($success) {
                $count++;
                $clear_success = AlertDB::clearAlert($alert->getID());
            }
        }
        return $count;
    }

    /**
     * Dispatches an alert
     *
     * @param Alert $alert_obj
     * @return boolean True if the alert was dispatched or false if it failed
     */
    public static function dispatchAlert(Alert $alert_obj): bool
    {
        $odds_obj = null;
        if ($alert_obj->getBookieID() == -1) {
            //Alert is not bookie specific
            $odds_obj = OddsHandler::getBestOddsForFight($alert_obj->getFightID());
        } else {
            //Alert is bookie specific
            $odds_obj = OddsHandler::getLatestOddsForFightAndBookie($alert_obj->getFightID(), $alert_obj->getBookieID());
        }

        $matchup = EventHandler::getMatchup($alert_obj->getFightID());
        if (!$odds_obj || !$matchup) {
            return false;
        }

        //Convert odds type if necessary
        $team_odds[1] = $odds_obj->getFighterOddsAsString(1);
        $team_odds[2] = $odds_obj->getFighterOddsAsString(2);
        if ($alert_obj->getOddsType() == 2) {
            //Decimal
            $team_odds[1] = OddsTools::convertMoneylineToDecimal($team_odds[1]);
            $team_odds[2] = OddsTools::convertMoneylineToDecimal($team_odds[2]);
        } elseif ($alert_obj->getOddsType() == 3) {
            //Fraction
            //TODO: Create this when fraction support is introduced
        }

        //If odds is set to -9999 then we just want to announce that the fight has got odds
        if ($alert_obj->getLimit() == -9999) {
            $sText = "Odds for " . $matchup->getFighterAsString(1) . " (" . $team_odds[1] . ") vs " . $matchup->getFighterAsString(2) . " (" . $team_odds[2] . ") has just been posted at " . ALERTER_SITE_NAME . "\n
Visit " . ALERTER_SITE_LINK . " to view the latest odds listings.\n
You are receiving this e-mail because you have signed up to be notified when odds were added for a certain matchup. If you did not sign up for this you don't have to do anything as your e-mail will not be stored for future use.\n
" . ALERTER_SITE_NAME;

            $sMessageHTML = "<b>Alert: New odds added</b><br><br>" . $matchup->getFighterAsString(1) . " <b>" . $team_odds[1] . "</b><br>" . $matchup->getFighterAsString(2) . " <b>" . $team_odds[2] . "</b><br>";
            $sSubject = 'Odds for ' . $matchup->getFighterAsString(1) . ' vs ' . $matchup->getFighterAsString(2) . ' available';
        } else {
            $sText = "The odds for " . $matchup->getFighterAsString($alert_obj->getFighter()) . " has reached " . $team_odds[$alert_obj->getFighter()] . " in his/her upcoming fight against " . $matchup->getFighterAsString(($alert_obj->getFighter() == 1 ? 2 : 1)) . "\n
Visit " . ALERTER_SITE_LINK . " to view the latest odds listings.\n
You are receiving this e-mail because you have signed up to be notified when the odds changed for a certain matchup. If you did not sign up for this you don't have to do anything as your e-mail will not be stored for future use.\n
" . ALERTER_SITE_NAME;


            $sMessageHTML = "<b>Alert: Odds changed</b><br><br>The odds for " . $matchup->getFighterAsString($alert_obj->getFighter()) . " has reached " . $team_odds[$alert_obj->getFighter()] . " in his/her upcoming fight against " . $matchup->getFighterAsString(($alert_obj->getFighter() == 1 ? 2 : 1)) . "<br>";
            $sSubject = 'Odds for ' . $matchup->getFighterAsString($alert_obj->getFighter()) . ' has reached your limit';
        }
        $sTo = $alert_obj->getEmail();
        $sHeaders = 'From: ' . ALERTER_MAIL_FROM;
        $sTextHTML = $sText; //Fallback to plaintext if file cannot be read below

        //Read in mail template from ALERTER_TEMPLATE_DIR/alertmail.html
        $rFile = fopen(ALERTER_TEMPLATE_DIR . '/alertmail.html', 'r');
        $sTextHTML = fread($rFile, filesize(ALERTER_TEMPLATE_DIR . '/alertmail.html'));
        fclose($rFile);

        $sTextHTML = str_replace('{{MESSAGE}}', $sMessageHTML, $sTextHTML);
        $sTextHTML = str_replace('{{SITENAME}}', ALERTER_SITE_NAME, $sTextHTML);
        $sTextHTML = str_replace('{{SUBJECT}}', $sSubject, $sTextHTML);
        $sTextHTML = str_replace('{{SITEURL}}', ALERTER_SITE_LINK, $sTextHTML);

        $success = false;
        if (ALERTER_DEV_MODE == true) {
            //If dev mode, do not send any e-mail alert
            $success = true;
            echo 'Sent one: ' . $sSubject .'
            ';
            echo 'Message:' . $sText;
        } else {
            //Send e-mail alert
            $mailer = new SESMailer(MAIL_SMTP_HOST, MAIL_SMTP_PORT, MAIL_SMTP_USERNAME, MAIL_SMTP_PASSWORD);
            $success = $mailer->sendMail(ALERTER_MAIL_SENDER_MAIL, ALERTER_MAIL_FROM, $sTo, $sSubject, $sTextHTML, $sText);
            //$success = mail($sTo, $sSubject, $sText, $sHeaders);
        }

        return $success;
    }

    /**
     * Removes all expired alerts
     *
     * @return int The number of alerts cleared
     */
    public static function cleanAlerts(): int
    {
        $alerts = AlertDB::getExpiredAlerts();

        $cleared_counter = 0;
        foreach ($alerts as $alert) {
            if (AlertDB::clearAlert($alert->getID())) {
                $cleared_counter++;
            }
        }
        return $cleared_counter;
    }

    public static function getAlertCount(): int
    {
        return AlertDB::getAlertCount();
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
     * @param int $matchup_id Fight to get arbitrage info on
     * @param int $stake The stake used as an example on how to divide the money
     * @return array Arbitrage information
     */
    public static function getArbitrageInfo($matchup_id, $stake = 100)
    {
        $odds_obj = OddsHandler::getBestOddsForFight($matchup_id);

        if ($odds_obj == null) {
            return null;
        }

        $arbit_value = (pow($odds_obj->getFighterOddsAsDecimal(1, true), -1)
                + pow($odds_obj->getFighterOddsAsDecimal(2, true), -1));

        $first_odds = $odds_obj->getFighterOddsAsDecimal(1);
        $second_odds = $odds_obj->getFighterOddsAsDecimal(2);

        $first_stake = 100;
        $second_stake = $first_stake * $first_odds / $second_odds;

        $total = $first_stake + $second_stake;
        $first_percent = $first_stake / $total;
        $second_percent = $second_stake / $total;

        $net_profit = ($first_odds * ($stake * $first_percent)) - $stake;

        return ["arbitrage" => round($arbit_value, 5),
            "fighter1bet" => round(($stake * $first_percent), 2),
            "fighter1odds" => $odds_obj->getFighterOddsAsString(1),
            "fighter2bet" => round(($stake * $second_percent), 2),
            "fighter2odds" => $odds_obj->getFighterOddsAsString(2),
            "profit" => round($net_profit, 2)];
    }

    /**
     * Gets all alerts stored
     *
     * @return array Array containing Alert objects
     */
    public static function getAllAlerts()
    {
        return AlertDB::getAllAlerts();
    }
}
