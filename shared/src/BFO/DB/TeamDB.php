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
            $fighters[] = new Fighter((string) $row['name'], (int) $row['id']);
        }

        return $fighters;
    }

    public static function getTeams(int $team_id = null): array
    {
        $extra_where = '';
        $params = [];
        if ($team_id) {
            $extra_where .= ' AND f.id = :team_id';
            $params[':team_id'] = $team_id;
        }

        $query = 'SELECT f.id, f.name 
						FROM fighters f 
                            WHERE 1=1 
                            ' . $extra_where . '
						ORDER BY f.name ASC';

        $teams = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $teams[] = new Fighter((string) $row['name'], (int) $row['id']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $teams;
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

    public static function createTeam(string $team_name): ?int
    {
        $params = [strtoupper($team_name)];
        $query = 'INSERT INTO fighters(name)
                        VALUES(?)';
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return null;
        }
        return $id;
    }

    public static function getTeamIDByName($fighter_name)
    {
        $query = 'SELECT fn.id
                    FROM (SELECT f.id as id, f.name as name FROM fighters f
                        UNION
                        SELECT fa.fighter_id as id, fa.altname as name FROM fighters_altnames fa
                        ) AS fn
                    WHERE fn.name = ?';

                    //New Query (to be combined with find figher above): 
                    //SELECT distinct(id), name FROM fighters f left join fighters_altnames fa ON fa.fighter_id = f.id WHERE name = 'CRO COP' OR altname like '%CRO COP%';

        $params = [strtoupper($fighter_name)];

        $result = DBTools::doParamQuery($query, $params);

        $fighters = array();
        while ($row = mysqli_fetch_array($result)) {
            $fighters[] = $row['id'];
        }
        if (sizeof($fighters) > 0) {
            return $fighters[0];
        }
        return null;
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
            $fighters[] = new Fighter((string) $row['name'], (int) $row['id']);
        }
        return $fighters;
    }

    public static function addTeamAltName($team_id, $alt_name)
    {
        if ($team_id == "" || $alt_name == "") {
            return false;
        }

        $query = 'INSERT INTO fighters_altnames(fighter_id, altname)
                    VALUES (?,?)';

        $params = [$team_id, strtoupper($alt_name)];
        $result = DBTools::doParamQuery($query, $params);
        if ($result == false) {
            return false;
        }

        return true;
    }
}
