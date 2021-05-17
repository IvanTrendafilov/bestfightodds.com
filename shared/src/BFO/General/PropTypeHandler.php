<?php

namespace BFO\General;

use BFO\DataTypes\PropType;
use BFO\DB\PropTypeDB;

class PropTypeHandler
{
    public static function getPropTypes($category_id = null)
    {
        return PropTypeDB::getPropTypes($category_id);
    }

    public static function createNewPropType(PropType $proptype_obj): ?int
    {
        if ($proptype_obj->getPropDesc() != '' && $proptype_obj->getPropNegDesc() != '') {
            return PropTypeDB::createNewPropType($proptype_obj);
        }
        return null;
    }
}
