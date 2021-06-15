<?php

namespace BFO\General;

use BFO\DataTypes\PropType;
use BFO\DB\PropTypeDB;

/**
 * Logic to handle storage and retrieval of prop types, generic definitions of prop bets
 */
class PropTypeHandler
{

    public static function getAllPropTypes()
    {
        return self::getPropTypes();
    }

    public static function getPropTypes(int $proptype_id = null): array
    {
        return PropTypeDB::getPropTypes($proptype_id);
    }

    public static function getAllPropTypesForMatchup(int $matchup_id): array
    {
        return PropTypeDB::getAllPropTypesForMatchup($matchup_id);
    }

    public static function getAllPropTypesForEvent($event_id)
    {
        return PropTypeDB::getAllPropTypesForEvent($event_id);
    }

    public static function createNewPropType(PropType $proptype_obj): ?int
    {
        if ($proptype_obj->getPropDesc() != '' && $proptype_obj->getPropNegDesc() != '') {
            return PropTypeDB::createNewPropType($proptype_obj);
        }
        return null;
    }
}
