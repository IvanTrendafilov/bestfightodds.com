<?php

namespace BFO\General;

require_once('lib/bfocore/db/class.PropTypeDB.php');

class PropTypeHandler
{
    public static function getPropTypes($category_id = null)
    {
        return PropTypeDB::getPropTypes($category_id);
    }
}
