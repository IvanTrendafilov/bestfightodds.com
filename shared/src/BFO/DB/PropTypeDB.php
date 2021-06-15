<?php

namespace BFO\DB;

use BFO\Utils\DB\PDOTools;
use BFO\Utils\DB\DBTools;
use BFO\DataTypes\PropType;

/**
 * Database logic to handle storage and retrieval of prop types, generic definitions of prop bets
 */
class PropTypeDB
{
    public static function createNewPropType(PropType $proptype_obj) : ?int
    {
        $query = "INSERT INTO prop_types(prop_desc, negprop_desc, is_eventprop) VALUES (?,?,?)";
        $params = [$proptype_obj->getPropDesc(), $proptype_obj->getPropNegDesc(), $proptype_obj->isEventProp()];
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            }
        }
        return $id;
    }

    public static function getPropTypes(int $proptype_id = null): array
    {
        $extra_where = '';
        $params = [];
        if ($proptype_id) {
            $extra_where .= ' AND pt.id = :proptype_id';
            $params[':proptype_id'] = $proptype_id;
        }

        $query = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, pt.is_eventprop
                    FROM prop_types pt
                        WHERE 1=1 
                        ' . $extra_where . ' 
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC';

        $prop_types = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $prop_type = new PropType(
                    (int) $row['id'],
                    $row['prop_desc'],
                    $row['negprop_desc']
                );
                $prop_type->setEventProp((bool) $row['is_eventprop']);
                $prop_types[] = $prop_type;
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
        }
        return $prop_types;
    }

    /**
     * Retrieves the prop types that a certain matchup has props and odds for
     *
     * Since these are matchup specific prop types we will go ahead and replace
     * the <T> variables with the actual team name
     */
    public static function getAllPropTypesForMatchup(int $matchup_id): array
    {
        $query = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc, lp.team_num
                    FROM prop_types pt, lines_props lp
                    WHERE lp.proptype_id = pt.id
                        AND  lp.matchup_id = ?
                        GROUP BY lp.matchup_id, lp.team_num, pt.id
                    ORDER BY LEFT(pt.prop_desc,4) = "Over" DESC, id ASC, lp.team_num ASC';

        $params = [$matchup_id];

        $result = DBTools::doParamQuery($query, $params);

        $prop_types = [];
        while ($row = mysqli_fetch_array($result)) {
            $prop_types[] = new PropType(
                (int) $row['id'],
                $row['prop_desc'],
                $row['negprop_desc'],
                (int) $row['team_num']
            );
        }

        return $prop_types;
    }

    /**
     * Retrieves the prop types that a certain event has props and odds for
     *
     * @param int $event_id Matchup ID
     * @return Array Collection of PropType objects
     */
    public static function getAllPropTypesForEvent(int $event_id): array
    {
        $query = 'SELECT pt.id, pt.prop_desc, pt.negprop_desc
                    FROM prop_types pt, lines_eventprops lep
                    WHERE lep.proptype_id = pt.id
                        AND  lep.event_id = ?
                        GROUP BY lep.event_id, pt.id
                    ORDER BY id ASC';

        $params = array($event_id);

        $result = DBTools::doParamQuery($query, $params);

        $prop_types = [];
        while ($row = mysqli_fetch_array($result)) {
            $prop_types[] = new PropType(
                (int) $row['id'],
                $row['prop_desc'],
                $row['negprop_desc'],
                0
            );
        }

        return $prop_types;
    }
}
