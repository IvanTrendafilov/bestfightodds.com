<?php
//This class handles the model for Alerts. To be called from AlerterV2 only
require_once('config/inc.config.php');
require_once('lib/bfocore/alerter/class.AlertV2.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php');

class AlertsModel
{
    public function addAlert($email, $oddstype, $criterias)
    {
        //Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid e-mail format", 30);
        }
        if (!$this->isJson($criterias) || strlen($criterias) > 1000) {
            throw new \Exception("Invalid criterias", 20);
        }
        if (!is_int($oddstype) || ($oddstype > 4 || $oddstype < 1)) {
            throw new \Exception("Invalid odds type", 40);
        }

        //Validate proper combinations
        $json = json_decode($criterias, true);
        if (!(isset($criterias['matchup_id']) && isset($criterias['team_num'])
            || !(isset($criterias['proptype_id']) && (isset($criterias['matchup_id']) || isset($criterias['event_id']))))) {
            throw new \Exception("Invalid field combinations", 50);
        }

        //Validate event/matchup/proptype_id (maybe)

        $query = "INSERT INTO alerts_entries(email, oddstype, criterias) VALUES (?,?,?)";
        $params = [$email, $oddstype, $criterias];
        $id = null;
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            }
        }
        return $id;
    }

    public function deleteAlert($id)
    {
        if (!is_int($id)) {
            throw new \Exception("Invalid ID", 10);
        }
        $query = "DELETE FROM alerts_entries WHERE id = ?";
        $params = [$id];
        return PDOTools::delete($query, $params);
    }

    public function getAllAlerts()
    {
        $query = "SELECT * FROM alerts_entries ORDER BY id ASC";
        $result = PDOTools::findMany($query);
        $alerts = [];
        foreach ($result as $alert) {
            $alerts[] = new AlertV2($alert['id'], $alert['email'], $alert['oddstype'], $alert['criterias']);
        }

        return $alerts;
    }

    public function isAlertReached($criterias)
    {
        if (isset($criterias['matchup_id']) && !isset($criterias['proptype_id'])) {
            return $this->isMatchupAlertReached($criterias);
        } elseif (isset($criterias['proptype_id'])) {
            return $this->isPropAlertReached($criterias);
        } elseif (isset($criterias['is_eventprop'])) {
            return $this->isEventPropAlertReached($criterias);
        }
        return false;
    }

    private function isMatchupAlertReached($criterias)
    {
        $query_checks = '';
        $query_params = [];
        foreach ($criterias as $criteria => $val) {
            switch ($criteria) {
                case 'matchup_id': //Required
                    $query_params[':matchup_id'] = $val;
                    break;
                case 'line_limit': //Optional
                    $query_checks .= ' AND fo.' . ($criterias['team_num'] == 1 ? 'fighter1_odds' : 'fighter2_odds') . ' >= :line_limit';
                    $query_params[':line_limit'] = $val;
                    break;
                case 'bookie_id': //Optional
                    $query_checks .= ' AND tmp_fo.bookie_id = :bookie_id';
                    $query_params[':bookie_id'] = $val;
                    break;
            }
        }

        $query = "SELECT * FROM (SELECT bookie_id, fight_id, MAX(date) as maxdate FROM fightodds fo WHERE fo.fight_id = :matchup_id GROUP BY fo.bookie_id) tmp_fo 
						INNER JOIN fightodds fo ON fo.fight_id = tmp_fo.fight_id AND tmp_fo.maxdate = fo.date AND tmp_fo.bookie_id = fo.bookie_id
						INNER JOIN fights f ON fo.fight_id = f.id 
						INNER JOIN events e ON f.event_id = e.id 
						WHERE LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL " . GENERAL_GRACEPERIOD_SHOW . " HOUR), 10)
						 " . $query_checks;

        return (count(PDOTools::findMany($query, $query_params)) > 0);
    }

    private function isPropAlertReached($criterias)
    {
        $query_checks = '';
        $query_params = [];
        $query_match_type = '';
        foreach ($criterias as $criteria => $val) {
            switch ($criteria) {
                case 'matchup_id': //Required (or event_id)
                    $query_match_type = 'lp.matchup_id = :matchup_id';
                    $join_addition = 'lp.matchup_id = tmp_lp.matchup_id AND';
                    $query_params[':matchup_id'] = $val;
                    break;
                case 'event_id': //Required (or matchup_id)
                    $query_match_type = 'lp.matchup_id IN (SELECT f2.id FROM fights f2 WHERE f2.event_id = :event_id)';
                    $query_params[':event_id'] = $val;
                    break;
                case 'proptype_id': //Required
                    $query_params[':proptype_id'] = $val;
                    $join_addition = 'lp.proptype_id = tmp_lp.proptype_id AND';
                    break;
                case 'team_num': //Required (but can be set to 0)
                    $query_params[':team_num'] = $val;
                    break;
                case 'line_limit': //Optional
                    $query_checks .= ' AND lp.' . ($criterias['line_side'] == 1 ? 'prop_odds' : 'negprop_odds') . ' >= :line_limit';
                    $query_params[':line_limit'] = $val;
                    // no break
                case 'bookie_id': //Optional
                    $query_checks .= ' AND lp.bookie_id = :bookied_id';
                    $query_params[':bookie_id'] = $val;
                    break;
            }
        }

        $query = "SELECT * FROM (SELECT bookie_id, matchup_id, MAX(date) as maxdate, proptype_id FROM lines_props lp WHERE " . $query_match_type . " AND lp.team_num = :team_num AND lp.proptype_id = :proptype_id GROUP BY lp.bookie_id) tmp_lp
						INNER JOIN lines_props lp ON " . $join_addition . " tmp_lp.maxdate = lp.date AND tmp_lp.bookie_id = lp.bookie_id
						INNER JOIN fights f ON lp.matchup_id = f.id 
						INNER JOIN events e ON f.event_id = e.id 
						WHERE LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL " . GENERAL_GRACEPERIOD_SHOW . " HOUR), 10)
						 " . $query_checks;

        return (count(PDOTools::findMany($query, $query_params)) > 0);
    }

    private function isEventPropAlertReached($criterias)
    {
        $query_checks = '';
        $query_params = [];
        foreach ($criterias as $criteria => $val) {
            switch ($criteria) {
                case 'event_id': //Required (or matchup)
                    $query_params[':event_id'] = $val;
                    break;
                case 'proptype_id': //Required
                    $query_params[':proptype_id'] = $val;
                    break;
                case 'line_limit': //Optional
                    $query_checks .= ' AND lep.' . ($criterias['line_side'] == 1 ? 'prop_odds' : 'negprop_odds') . ' >= :line_limit';
                    $query_params[':line_limit'] = $val;
                    // no break
                case 'bookie_id': //Optional
                    $query_checks .= ' AND lep.bookie_id = :bookied_id';
                    $query_params[':bookie_id'] = $val;
                    break;
            }
        }

        $query = "SELECT * FROM (SELECT bookie_id, event_id, MAX(date) as maxdate FROM lines_eventprops lep WHERE lep.event_id = :event_id AND lep.proptype_id = :proptype_id GROUP BY lep.bookie_id) tmp_lep
						INNER JOIN lines_eventprops lep ON lep.event_id = tmp_lep.event_id AND tmp_lep.maxdate = lep.date AND tmp_lep.bookie_id = lep.bookie_id
						INNER JOIN events e ON lep.event_id = e.id 
						WHERE LEFT(e.date, 10) >= LEFT((NOW() - INTERVAL " . GENERAL_GRACEPERIOD_SHOW . " HOUR), 10)
						 " . $query_checks;

        return (count(PDOTools::findMany($query, $query_params)) > 0);
    }


    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
