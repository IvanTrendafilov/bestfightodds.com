<?php

/**
 */

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/db/class.DBTools.php');

class AlertDAO
{

    /**
     * Adds a new alert
     *
     * Returns
     * 		-1 = Fight not found
     * 		-2 = Fight is old
     * 		-3 = Bad fighter pick
     * 		-4 = Email wrong format
     * 		-5 = Odds wrong format
     * 		-6 = Alert limit reached for e-mail
     * 		-7 = Odds already reached
     * 		2 = Alert already exists
     */
    public static function addNewAlert($a_oAlert)
    {
        //Check that alert doesn't already exist
        if (AlertDAO::matchAlert($a_oAlert)) {
            return 2;
        }

        //Check that the email hasn't reached the current limit. Only applicable if the email is not extempt. Which is checked first
        if (!AlertDAO::hasLimitExemption($a_oAlert->getEmail()) && AlertDAO::reachedLimit($a_oAlert->getEmail())) {
            return -6;
        }

        //Check that the e-mail is in the right format
        if (!filter_var($a_oAlert->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return -4;
        }

        //Check that the limit is in the right format
        $sPattern = '/([+-]{0,1}[0-9]{3}[0-9]*)/';
        if (preg_match($sPattern, $a_oAlert->getLimit()) != 1) {
            return -5;
        }

        //Check that the fighter selection is correct
        if ($a_oAlert->getFighter() != 1 && $a_oAlert->getFighter() != 2) {
            return -3;
        }

        //Check that odds type is correct (1-3)
        if ($a_oAlert->getOddsType() != '1' && $a_oAlert->getOddsType() != '2' && $a_oAlert->getOddsType() != '3' && $a_oAlert->getOddsType() != '4') {
            return -5;
        }


        //Check that fight exists and is not outdated
        $oTempFight = EventHandler::getFightByID($a_oAlert->getFightID());
        if ($oTempFight == null) {
            return -1;
        }
        if (EventHandler::getEvent($oTempFight->getEventID(), true) == false) {
            return -2;
        }

        //Check that limit hasn't already been reached
        $oFightOdds = null;
        if ($a_oAlert->getBookieID() == -1) {
            //Check best of all
            $oFightOdds = EventHandler::getBestOddsForFight($a_oAlert->getFightID());
        } else {
            //Check best for bookie
            $oFightOdds = EventHandler::getLatestOddsForFightAndBookie($a_oAlert->getFightID(), $a_oAlert->getBookieID());
        }

        if ($oFightOdds != null) {
            if ($oFightOdds->getFighterOdds($a_oAlert->getFighter()) >= $a_oAlert->getLimit()) {
                return -7;
            }
        }

        $query = "INSERT INTO alerts(email, fight_id, fighter, bookie_id, odds, odds_type) VALUES (?, ?, ?, ?, ?, ?)";

        $params = array(
            $a_oAlert->getEmail(),
            $a_oAlert->getFightID(),
            $a_oAlert->getFighter(),
            $a_oAlert->getBookieID(),
            $a_oAlert->getLimit(),
            $a_oAlert->getOddsType()
        );

        return DBTools::doParamQuery($query, $params);
    }

    /**
     * Match the supplied alert with one in the database.
     *
     * If none exists then null is returned.
     */
    public static function matchAlert($a_oAlert)
    {
        $query = 'SELECT a.* FROM alerts a WHERE email = ? AND fight_id = ? AND fighter = ? AND bookie_id = ? AND odds = ?;';

        $params = array($a_oAlert->getEmail(), $a_oAlert->getFightID(), $a_oAlert->getFighter(), $a_oAlert->getBookieID(), $a_oAlert->getLimit());

        $rResult = DBTools::doParamQuery($query, $params);
        if ($aData = mysqli_fetch_array($rResult)) {
            return true;
        }
        return false;
    }

    /**
     * Check if an e-mail has reached the max 50 alerts limit
     */
    public static function reachedLimit($a_sEmail)
    {
        $query = "SELECT COUNT(*) AS limitcount FROM alerts WHERE email = ? GROUP BY email;";
        $params = array($a_sEmail);

        $rResult = DBTools::doParamQuery($query, $params);
        if ($aData = mysqli_fetch_array($rResult)) {
            if ($aData['limitcount'] >= 50) {
                return true;
            }
        }
        return false;
    }

    public static function clearAlert($a_iAlert)
    {
        $query = "DELETE FROM alerts WHERE id = ?";

        $params = array($a_iAlert);

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() > 0) {
            return true;
        }
        return false;
    }

    /* Could be optimized to not have to retrieve all alerts but do a smart select that checks the alert in mysql */

    public static function getReachedAlerts()
    {

        //New approach:
        // This query gives all alerts where FO exist. Needs to be updated to check also if condition is met:
        // select * from alerts a left join (select fight_id, bookie_id, MAX(date) AS maxdate from fightodds group by bookie_id) fo on a.fight_id = fo.fight_id where fo.maxdate is not null;

        $aReachedAlerts = array();

        $aAlerts = AlertDAO::getAllAlerts();
        foreach ($aAlerts as $oAlert) {
            $oFightOdds = null;
            if ($oAlert->getBookieID() == -1) {
                //Alert is not bookie specific
                $oFightOdds = EventHandler::getBestOddsForFight($oAlert->getFightID());
            } else {
                //Alert is bookie specific
                $oFightOdds = EventHandler::getLatestOddsForFightAndBookie($oAlert->getFightID(), $oAlert->getBookieID());
            }

            if ($oFightOdds != null && $oFightOdds->getFighterOdds($oAlert->getFighter()) >= $oAlert->getLimit()) {
                //Match
                $aReachedAlerts[] = $oAlert;
            }
        }

        return $aReachedAlerts;
    }

    public static function getExpiredAlerts()
    {
        $query = 'SELECT a.*
					FROM alerts a, fights f, events e 
					WHERE a.fight_id = f.id
						AND f.event_id = e.id
						AND LEFT(e.date,10) < LEFT((NOW() - INTERVAL 1 HOUR), 10);';

        $rResult = DBTools::doQuery($query);
        $aAlerts = array();
        while ($aAlert = mysqli_fetch_array($rResult)) {
            $aAlerts[] = new Alert($aAlert['email'], $aAlert['fight_id'], $aAlert['fighter'], $aAlert['bookie_id'], $aAlert['odds'], $aAlert['id'], $aAlert['odds_type']);
        }

        return $aAlerts;
    }

    public static function getAllAlerts()
    {
        $query = 'SELECT id, email, fight_id, fighter, bookie_id, odds, odds_type FROM alerts ORDER BY id ASC';

        $rResult = DBTools::doQuery($query);
        $aAlerts = array();

        while ($aAlert = mysqli_fetch_array($rResult)) {
            $aAlerts[] = new Alert($aAlert['email'], $aAlert['fight_id'], $aAlert['fighter'], $aAlert['bookie_id'], $aAlert['odds'], $aAlert['id'], $aAlert['odds_type']);
        }

        return $aAlerts;
    }

    public static function getAlertCount()
    {
        $query = 'SELECT COUNT(*) AS alertcount FROM alerts a;';

        $rResult = DBTools::doQuery($query);

        if ($aResult = mysqli_fetch_array($rResult)) {
            return $aResult['alertcount'];
        }

        return -1;
    }

    /**
     * Checks if an e-mail is exempted from the alert limit (max 50 alerts). These e-mail addresses are stored in a special table
     *
     * Tables: alerts_exemptions
     *
     * @param String $a_sEmail E-mail address to check
     * @return boolean If e-mail addresss has exemption
     */
    public static function hasLimitExemption($a_sEmail)
    {
        $query = "SELECT email FROM alerts_exemptions WHERE email = ?;";
        $params = array(strtolower($a_sEmail));

        $rResult = DBTools::doParamQuery($query, $params);

        if (DBTools::getSingleValue($rResult) != null) {
            return true;
        }
        return false;
    }
}
