<?php

namespace BFO\DB;

use BFO\DataTypes\Alert;
use BFO\General\EventHandler;
use BFO\General\OddsHandler;

use BFO\Utils\DB\DBTools;

/**
 * Database logic to handle alerts
 */
class AlertDB
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
    public static function addNewAlert(Alert $alert_obj): int
    {
        //Check that alert doesn't already exist
        if (AlertDB::matchAlert($alert_obj)) {
            return 2;
        }

        //Check that the email hasn't reached the current limit. Only applicable if the email is not extempt. Which is checked first
        if (!AlertDB::hasLimitExemption($alert_obj->getEmail()) && AlertDB::hasReachedLimit($alert_obj->getEmail())) {
            return -6;
        }

        //Check that the e-mail is in the right format
        if (!filter_var($alert_obj->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return -4;
        }

        //Check that the limit is in the right format
        $pattern = '/([+-]{0,1}[0-9]{3}[0-9]*)/';
        if (preg_match($pattern, $alert_obj->getLimit()) != 1) {
            return -5;
        }

        //Check that the fighter selection is correct
        if ($alert_obj->getFighter() != 1 && $alert_obj->getFighter() != 2) {
            return -3;
        }

        //Check that odds type is correct (1-3)
        if ($alert_obj->getOddsType() != '1' && $alert_obj->getOddsType() != '2' && $alert_obj->getOddsType() != '3' && $alert_obj->getOddsType() != '4') {
            return -5;
        }

        //Check that fight exists and is not outdated
        $fight_obj = EventHandler::getMatchup($alert_obj->getFightID());
        if ($fight_obj == null) {
            return -1;
        }
        if (EventHandler::getEvent($fight_obj->getEventID(), true) == false) {
            return -2;
        }

        //Check that limit hasn't already been reached
        $odds_obj = null;
        if ($alert_obj->getBookieID() == -1) {
            //Check best of all
            $odds_obj = OddsHandler::getBestOddsForFight($alert_obj->getFightID());
        } else {
            //Check best for bookie
            $odds_obj = OddsHandler::getLatestOddsForFightAndBookie($alert_obj->getFightID(), $alert_obj->getBookieID());
        }

        if ($odds_obj != null) {
            if ($odds_obj->getOdds($alert_obj->getFighter()) >= $alert_obj->getLimit()) {
                return -7;
            }
        }

        $query = "INSERT INTO alerts(email, fight_id, fighter, bookie_id, odds, odds_type) VALUES (?, ?, ?, ?, ?, ?)";

        $params = array(
            $alert_obj->getEmail(),
            $alert_obj->getFightID(),
            $alert_obj->getFighter(),
            $alert_obj->getBookieID(),
            $alert_obj->getLimit(),
            $alert_obj->getOddsType()
        );

        return DBTools::doParamQuery($query, $params);
    }

    /**
     * Match the supplied alert with one in the database.
     *
     * If none exists then null is returned.
     */
    public static function matchAlert(Alert $alert_obj): bool
    {
        $query = 'SELECT a.* FROM alerts a WHERE email = ? AND fight_id = ? AND fighter = ? AND bookie_id = ? AND odds = ?;';

        $params = [$alert_obj->getEmail(), $alert_obj->getFightID(), $alert_obj->getFighter(), $alert_obj->getBookieID(), $alert_obj->getLimit()];

        $result = DBTools::doParamQuery($query, $params);
        if ($data = mysqli_fetch_array($result)) {
            return true;
        }
        return false;
    }

    /**
     * Check if an e-mail has reached the max 50 alerts limit
     */
    public static function hasReachedLimit(string $recipient_email)
    {
        $query = "SELECT COUNT(*) AS limitcount FROM alerts WHERE email = ? GROUP BY email;";
        $params = array($recipient_email);

        $result = DBTools::doParamQuery($query, $params);
        if ($data = mysqli_fetch_array($result)) {
            if ($data['limitcount'] >= 50) {
                return true;
            }
        }
        return false;
    }

    public static function clearAlert(int $alert_id): bool
    {
        $query = "DELETE FROM alerts WHERE id = ?";
        $params = [$alert_id];

        DBTools::doParamQuery($query, $params);

        if (DBTools::getAffectedRows() > 0) {
            return true;
        }
        return false;
    }

    /* Could be optimized to not have to retrieve all alerts but do a smart select that checks the alert in mysql */

    public static function getReachedAlerts(): array 
    {
        //New approach:
        // This query gives all alerts where FO exist. Needs to be updated to check also if condition is met:
        // select * from alerts a left join (select fight_id, bookie_id, MAX(date) AS maxdate from fightodds group by bookie_id) fo on a.fight_id = fo.fight_id where fo.maxdate is not null;

        $reached_alerts = [];

        $alerts = AlertDB::getAllAlerts();
        foreach ($alerts as $alert) {
            $odds_obj = null;
            if ($alert->getBookieID() == -1) {
                //Alert is not bookie specific
                $odds_obj = OddsHandler::getBestOddsForFight($alert->getFightID());
            } else {
                //Alert is bookie specific
                $odds_obj = OddsHandler::getLatestOddsForFightAndBookie($alert->getFightID(), $alert->getBookieID());
            }

            if ($odds_obj != null && $odds_obj->getOdds($alert->getFighter()) >= $alert->getLimit()) {
                //Match
                $reached_alerts[] = $alert;
            }
        }

        return $reached_alerts;
    }

    public static function getExpiredAlerts(): array
    {
        $query = 'SELECT a.*
					FROM alerts a, fights f, events e 
					WHERE a.fight_id = f.id
						AND f.event_id = e.id
						AND LEFT(e.date,10) < LEFT((NOW() - INTERVAL 1 HOUR), 10);';

        $result = DBTools::doQuery($query);
        $alerts = [];
        while ($row = mysqli_fetch_array($result)) {
            $alerts[] = new Alert((string) $row['email'], (int) $row['fight_id'], (int) $row['fighter'], (int) $row['bookie_id'], (int) $row['odds'], (int) $row['id'], (int) $row['odds_type']);
        }

        return $alerts;
    }

    public static function getAllAlerts(): array
    {
        $query = 'SELECT id, email, fight_id, fighter, bookie_id, odds, odds_type FROM alerts ORDER BY id ASC';

        $result = DBTools::doQuery($query);
        $alerts = [];

        while ($row = mysqli_fetch_array($result)) {
            $alerts[] = new Alert((string) $row['email'], (int) $row['fight_id'], (int) $row['fighter'], (int) $row['bookie_id'], (int) $row['odds'], (int) $row['id'], (int) $row['odds_type']);
        }

        return $alerts;
    }

    public static function getAlertCount(): int
    {
        $query = 'SELECT COUNT(*) AS alertcount FROM alerts a;';

        $result = DBTools::doQuery($query);

        if ($result = mysqli_fetch_array($result)) {
            return (int) $result['alertcount'];
        }

        return -1;
    }

    /**
     * Checks if an e-mail is exempted from the alert limit (max 50 alerts). These e-mail addresses are stored in a special table (alerts_exemptions)
     */
    public static function hasLimitExemption($recipient_email)
    {
        $query = "SELECT email FROM alerts_exemptions WHERE email = ?;";
        $params = array(strtolower($recipient_email));

        $result = DBTools::doParamQuery($query, $params);

        if (DBTools::getSingleValue($result) != null) {
            return true;
        }
        return false;
    }
}
