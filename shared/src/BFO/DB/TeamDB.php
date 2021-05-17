<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;

use BFO\DataTypes\Fighter;

/**
 * TeamDB
 */
class TeamDB
{
    public static function getAllFighters($only_with_fights)
    {
        if ($only_with_fights == true) {
            $query = 'SELECT DISTINCT fi.id, fi.name
					FROM fighters fi 
						LEFT JOIN fights f ON (fi.id = f.fighter1_id OR fi.id = f.fighter2_id), fighters f1, fighters f2
					WHERE f.fighter1_id = f1.id
						AND f.fighter2_id = f2.id
					ORDER BY fi.name ASC';
        } else {
            $query = 'SELECT f.id, f.name 
						FROM fighters f 
						ORDER BY f.name ASC';
        }

        $result = DBTools::doQuery($query);

        $fighters = [];
        while ($row = mysqli_fetch_array($result)) {
            $fighters[] = new Fighter($row['name'], $row['id']);
        }

        return $fighters;
    }

    public static function searchFighter($fighter_name)
    {
        $query = "SELECT f.id, f.name, MATCH(f.name) AGAINST (?) AS score  
					FROM fighters f
					WHERE f.name LIKE ?
                        OR MATCH(f.name) AGAINST (?) 
					ORDER BY score DESC, f.name ASC";

        $params = [$fighter_name, '%' . $fighter_name . '%', $fighter_name];

        $result = DBTools::doParamQuery($query, $params);

        $fighters = [];
        while ($row = mysqli_fetch_array($result)) {
            $fighters[] = new Fighter($row['name'], $row['id']);
        }

        return $fighters;
    }

    public static function getFighterByID($id)
    {
        $query = 'SELECT f.name, f.id FROM fighters f WHERE f.id = ?';
        $params = [$id];
        $result = DBTools::doParamQuery($query, $params);

        if ($row = mysqli_fetch_array($result)) {
            return new Fighter($row['name'], $row['id']);
        }
        return null;
    }

    public static function getAltNamesForTeamByID($team_id)
    {
        $query = "SELECT fa.altname FROM fighters_altnames fa WHERE fa.fighter_id = ?";
        $rows = PDOTools::findMany($query, [$team_id]);

        $names = [];
        foreach ($rows as $row) {
            $names[] = $row['altname'];
        }
        if (count($names) > 0) {
            return $names;
        }

        return null;
    }

    /**
     * Gets the latest date when the fighter received an odds update
     */
    public static function getLastChangeDate($team_id)
    {
        $query = 'SELECT Max(fo3.date) 
                    FROM   (SELECT fo1.date 
                                FROM   fighters f1 
                                        INNER JOIN fights fi1 
                                                ON f1.id = fi1.fighter1_id 
                                        INNER JOIN fightodds fo1 
                                                ON fi1.id = fo1.fight_id 
                                WHERE  f1.id = ? 
                                UNION ALL 
                                SELECT fo2.date 
                                FROM   fighters f2 
                                        INNER JOIN fights fi2 
                                                ON f2.id = fi2.fighter2_id 
                                        INNER JOIN fightodds fo2 
                                                ON fi2.id = fo2.fight_id 
                                WHERE  f2.id = ?) fo3; ';

        $params = [$team_id, $team_id];

        $result = DBTools::doParamQuery($query, $params);
        return DBTools::getSingleValue($result);
    }


    public static function getAllTeamsWithMissingResults()
    {
        $query = 'SELECT DISTINCT f.* FROM fighters f 
                        INNER JOIN fights fi ON (fi.fighter1_id = f.id OR fi.fighter2_id = f.id) 
                        INNER JOIN events e ON fi.event_id = e.id 
                    WHERE fi.id NOT IN (SELECT mr.matchup_id FROM matchups_results mr)
                        AND LEFT(e.date, 10) < LEFT((NOW() - INTERVAL ' . GENERAL_GRACEPERIOD_SHOW . ' HOUR), 10)';
        $result = DBTools::doQuery($query);
        $fighters = [];
        while ($row = mysqli_fetch_array($result)) {
            $fighters[] = new Fighter($row['name'], $row['id']);
        }
        return $fighters;
    }

    public static function addFighterAltName($fighter_id, $alt_name)
    {
        if ($fighter_id == "" || $alt_name == "") {
            return false;
        }

        $query = 'INSERT INTO fighters_altnames(fighter_id, altname)
                    VALUES (?,?)';

        $params = array($fighter_id, strtoupper($alt_name));
        $result = DBTools::doParamQuery($query, $params);
        if ($result == false) {
            return false;
        }

        return true;
    }
}
