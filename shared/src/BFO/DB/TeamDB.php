<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;

use BFO\DataTypes\Team;

/**
 * Database logic to handle retrieval and storage of teams (aka fighters)
 */
class TeamDB
{
    public static function searchTeam(string $team_name): array
    {
        $query = "SELECT f.id, f.name, MATCH(f.name) AGAINST (?) AS score  
					FROM fighters f
					WHERE f.name LIKE ?
                        OR MATCH(f.name) AGAINST (?) 
					ORDER BY score DESC, f.name ASC";

        $params = [$team_name, '%' . $team_name . '%', $team_name];

        $result = DBTools::doParamQuery($query, $params);

        $teams = [];
        while ($row = mysqli_fetch_array($result)) {
            $teams[] = new Team((string) $row['name'], (int) $row['id']);
        }
        return $teams;
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
                $teams[] = new Team((string) $row['name'], (int) $row['id']);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }

        return $teams;
    }

    public static function getAltNamesForTeamByID(int $team_id): ?array
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
    public static function getLastChangeDate(int $team_id): ?string
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

    public static function getTeamIDByName(string $team_name): ?int
    {
        $query = 'SELECT fn.id
                    FROM (SELECT f.id as id, f.name as name FROM fighters f
                        UNION
                        SELECT fa.fighter_id as id, fa.altname as name FROM fighters_altnames fa
                        ) AS fn
                    WHERE fn.name = ?';

        $params = [strtoupper($team_name)];

        $result = DBTools::doParamQuery($query, $params);

        $teams = [];
        while ($row = mysqli_fetch_array($result)) {
            $teams[] = (int) $row['id'];
        }
        if (sizeof($teams) > 0) {
            return $teams[0];
        }
        return null;
    }

    public static function addTeamAltName(int $team_id, string $alt_name): bool
    {
        if (empty($team_id) || empty($alt_name)) {
            return false;
        }

        $query = 'INSERT INTO fighters_altnames(fighter_id, altname)
                    VALUES (?,?)';

        $params = [$team_id, strtoupper($alt_name)];
        $result = DBTools::doParamQuery($query, $params);
        if (!$result) {
            return false;
        }

        return true;
    }
}
