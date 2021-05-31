<?php

namespace BFO\DB;

use BFO\Utils\DB\PDOTools;
use BFO\DataTypes\Fight;

/**
 * Twitter DB access
 *
 * Handles all calls to the database related to tweets
 *
 */
class TwitterDB
{
    public static function saveFightAsTweeted(int $fight_id): bool
    {
        $query = 'INSERT INTO fight_twits(fight_id, twitdate)
                        VALUES (?, NOW())';

        $params = array($fight_id);

        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return false;
        }
        return true;
    }

    public static function getUntweetedMatchups(): array
    {
        $query = 'SELECT f.*, f1.name AS fighter1_name, f2.name AS fighter2_name 
                    FROM fights f, events e, fighters f1, fighters f2
                    WHERE NOT EXISTS
                        (SELECT ft.*
                            FROM fight_twits ft
                            WHERE f.id = ft.fight_id)
                        AND f.event_id = e.id AND LEFT(e.date, 10) >= LEFT(NOW(), 10)
                        AND EXISTS
                            (SELECT fo.*
                                FROM fightodds fo
                                WHERE f.id = fo.fight_id)
                        AND f1.id = f.fighter1_id
                        AND f2.id = f.fighter2_id
                        AND e.display = 1';

        $fights = [];
        $results = PDOTools::findMany($query);
        foreach ($results as $row) {
            $fight = new Fight((int) $row['id'], $row['fighter1_name'], $row['fighter2_name'], (int) $row['event_id']);
            $fight->setFighterID(1, (int) $row['fighter1_id']);
            $fight->setFighterID(2, (int) $row['fighter2_id']);
            $fight->setMainEvent((bool) $row['is_mainevent']);
            $fights[] = $fight;
        }
        return $fights;
    }

    public static function addTwitterHandle(int $team_id, string $handle): bool
    {
        $query = 'INSERT INTO teams_twitterhandles(team_id, handle)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE team_id = ?, handle = ?';
        $params = [$team_id, $handle, $team_id, $handle];

        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            } else {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
            return false;
        }
        return true;
    }

    public static function getTwitterHandle($team_id): mixed
    {
        $query = 'SELECT * FROM teams_twitterhandles WHERE team_id = ?';
        $params = [$team_id];

        try {
            return PDOTools::findOne($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
        }
        return null;
    }
}
