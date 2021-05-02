<?php

namespace BFO\General;

use BFO\DB\PropTypeDB;

class PropTypeHandler
{
    public static function getPropTypes($category_id = null)
    {
        return PropTypeDB::getPropTypes($category_id);
    }
}
