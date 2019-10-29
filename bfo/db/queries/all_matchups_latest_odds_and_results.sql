(SELECT *
FROM fights f
INNER JOIN EVENTS e ON f.event_id = e.id
INNER JOIN matchups_results mr ON mr.matchup_id = f.id
INNER JOIN (
	SELECT *, MAX(fighter1_odds) AS best_price
	FROM fightodds fo
	INNER JOIN fights f1 ON fo.fight_id = f1.id
	WHERE f1.fighter1_id = 3050 
	AND DATE in (SELECT MAX(DATE) FROM fightodds fo3 inner join fights f2 ON f2.id = fo3.fight_id WHERE f2.fighter1_id = 3050 GROUP BY fight_id, bookie_id)
	GROUP BY fight_id) fo2 ON fo2.fight_id = f.id
WHERE f.fighter1_id = 3050) 

UNION 

(SELECT *
FROM fights f
INNER JOIN EVENTS e ON f.event_id = e.id
INNER JOIN matchups_results mr ON mr.matchup_id = f.id
INNER JOIN (
	SELECT *, MAX(fighter2_odds) AS best_price
	FROM fightodds fo
	INNER JOIN fights f1 ON fo.fight_id = f1.id
	WHERE f1.fighter2_id = 3050 
	AND DATE in (SELECT MAX(DATE) FROM fightodds fo3 inner join fights f2 ON f2.id = fo3.fight_id WHERE f2.fighter2_id = 3050 GROUP BY fight_id, bookie_id)
	GROUP BY fight_id) fo2 ON fo2.fight_id = f.id
WHERE f.fighter2_id = 3050) 