<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php');

/**
 * Facebook DB
 *
 * Handles all calls to the database related to Facebook posts
 *
 * @author Christian Nordvaller
 */
class FacebookDB
{
    public static function saveMatchupAsPosted($matchup_id, $skipped)
    {
        $query = 'INSERT INTO matchups_fbposts(matchup_id, post_date, skipped)
                        VALUES (?, NOW(), ?)';
        $params = [$matchup_id, (int) $skipped];
        return PDOTools::insert($query, $params);
    }

    public static function saveEventAsPosted($event_id, $skipped)
    {
        $query = 'INSERT INTO matchups_fbposts(event_id, post_date, skipped)
                        VALUES (?, NOW(), ?)';
        $params = [$event_id, (int) $skipped];
        return PDOTools::insert($query, $params);
    }

    public static function getUnpostedMatchups()
    {
        $query = 'SELECT f.*, f1.name AS fighter1_name, f2.name AS fighter2_name 
                    FROM fights f, events e, fighters f1, fighters f2
                    WHERE NOT EXISTS
                        (SELECT ft.*
                            FROM matchups_fbposts ft
                            WHERE f.id = ft.matchup_id)
                        AND f.event_id = e.id AND LEFT(e.date, 10) >= LEFT(NOW(), 10)
                        AND EXISTS
                            (SELECT fo.*
                                FROM fightodds fo
                                WHERE f.id = fo.fight_id)
                        AND f1.id = f.fighter1_id
                        AND f2.id = f.fighter2_id
                        AND e.display = 1';

        $results = PDOTools::findMany($query);
        $matchups = [];
        foreach ($results as $row) {
            $tmp_matchup = new Fight($row['id'], $row['fighter1_name'], $row['fighter2_name'], $row['event_id']);
            $tmp_matchup->setFighterID(1, $row['fighter1_id']);
            $tmp_matchup->setFighterID(2, $row['fighter2_id']);
            $tmp_matchup->setMainEvent(($row['is_mainevent'] == 1 ? true : false));
            $matchups[] = $tmp_matchup;
        }
        return $matchups;
    }

    //Retrieves all events that have not had a preview post posted (= a summary of the current odds 24 hours prior to the event)
    public static function getUnpostedEvents()
    {
        $query = 'SELECT * FROM events e LEFT JOIN matchups_fbposts ff ON e.id = ff.event_id WHERE ff.event_id IS NULL
        			AND LEFT(NOW(), 10) = LEFT(e.date, 10)
        			AND e.display = 1';
        //AND LEFT(NOW() + INTERVAL 36 HOUR, 10) = LEFT(e.date, 10)

        $results = PDOTools::findMany($query);
        $events = [];
        foreach ($results as $row) {
            $events[] = new Event($row['id'], $row['date'], $row['name'], $row['display']);
        }
        return $events;
    }
}
