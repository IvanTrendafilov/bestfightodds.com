select fo1.fight_id, fo1.bookie_id, 
    fo4.fighter1_odds as fiveodds,
    fo4.fighter2_odds as fiveodds2,
    abs(fo4.fighter1_odds/fo1.fighter1_odds) as fivediff,
    abs(fo4.fighter2_odds/fo1.fighter2_odds) as fivediff2,
    fo4.fighter1_odds - fo1.fighter1_odds as fivediffnum,
    fo4.fighter2_odds - fo1.fighter2_odds as fivediffnum2,
	fo1.fighter1_odds,
    fo1.fighter2_odds,
    fo2.fighter1_odds,
    fo2.fighter2_odds,
    fo3.fighter1_odds,
    fo3.fighter2_odds,
    fo1.date,
    fo2.date,
    fo3.date,
    abs(fo1.fighter1_odds/fo2.fighter1_odds) as abs_part,
    abs(fo2.fighter1_odds/fo3.fighter1_odds) as abs_part2
    from (SELECT 
    fight_id, 
    date,
    fighter1_odds,
    fighter2_odds,
    bookie_id
FROM 
    (SELECT 
        t.fight_id, 
        t.date,
        t.fighter1_odds,
        t.fighter2_odds,
        t.bookie_id,
        @rn := if(@prev = t.fight_id, @rn + 1,1) as rn,
        @prev:=t.fight_id

    FROM
        fightodds as t 
        JOIN (SELECT @Prev:= Null, @Rn := 0) as v
       where bookie_id = 13
    ORDER BY 
        t.fight_id,
        T.date desc) as t
WHERE
    rn = 1
    and bookie_id = 13) as fo1 inner join 
    


(SELECT 
    fight_id, 
    date,
    fighter1_odds,
    fighter2_odds,
    bookie_id
FROM 
    (SELECT 
        t.fight_id, 
        t.date,
        t.fighter1_odds,
        t.fighter2_odds,
        t.bookie_id,
        @rn := if(@prev = t.fight_id, @rn + 1,1) as rn,
        @prev:=t.fight_id

    FROM
        fightodds as t 
        JOIN (SELECT @Prev:= Null, @Rn := 0) as v
       where bookie_id = 13
    ORDER BY 
        t.fight_id,
        T.date desc) as t
WHERE
    rn = 2
    and bookie_id = 13) as fo2 on fo1.fight_id = fo2.fight_id and fo1.bookie_id = fo2.bookie_id 
inner join 
    


(SELECT 
    fight_id, 
    date,
    fighter1_odds,
    fighter2_odds,
    bookie_id
FROM 
    (SELECT 
        t.fight_id, 
        t.date,
        t.fighter1_odds,
        t.fighter2_odds,
        t.bookie_id,
        @rn := if(@prev = t.fight_id, @rn + 1,1) as rn,
        @prev:=t.fight_id

    FROM
        fightodds as t 
        JOIN (SELECT @Prev:= Null, @Rn := 0) as v
       where bookie_id = 13
    ORDER BY 
        t.fight_id,
        T.date desc) as t
WHERE
    rn = 3
    and bookie_id = 13) as fo3 on fo2.fight_id = fo3.fight_id and fo2.bookie_id = fo3.bookie_id 
    
    
    inner join 
    


(SELECT 
    fight_id, 
    date,
    fighter1_odds,
    fighter2_odds,
    bookie_id
FROM 
    (SELECT 
        t.fight_id, 
        t.date,
        t.fighter1_odds,
        t.fighter2_odds,
        t.bookie_id,
        @rn := if(@prev = t.fight_id, @rn + 1,1) as rn,
        @prev:=t.fight_id

    FROM
        fightodds as t 
        JOIN (SELECT @Prev:= Null, @Rn := 0) as v
       where bookie_id = 1
    ORDER BY 
        t.fight_id,
        T.date desc) as t
WHERE
    rn = 1
    and bookie_id = 1) as fo4 on fo3.fight_id = fo4.fight_id 
	where (abs(fo1.fighter1_odds/fo2.fighter1_odds) > 1.1 OR abs(fo1.fighter1_odds/fo2.fighter1_odds) < 0.95 OR abs(fo1.fighter2_odds/fo2.fighter2_odds) > 1.1 OR abs(fo1.fighter2_odds/fo2.fighter2_odds) < 0.95)
    and (fo4.fighter1_odds - fo1.fighter1_odds > 20 OR fo4.fighter1_odds - fo1.fighter1_odds < -20 OR fo4.fighter2_odds - fo1.fighter2_odds > 20 OR fo4.fighter2_odds - fo1.fighter2_odds < -20 )
    and fo1.fight_id > 16000
    and fo1.fight_id in (select fox1.fight_id from fightodds fox1 inner join (select fight_id, max(date) as maxdate from fightodds group by fight_id) as fox2 on fox1.fight_id = fox2.fight_id and fox1.date = fox2.maxdate where fox1.bookie_id = 13)
    order by fight_id desc;
