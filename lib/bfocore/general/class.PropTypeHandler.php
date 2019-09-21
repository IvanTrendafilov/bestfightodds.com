<?php

require_once('lib/bfocore/dao/class.PropTypeDAO.php');

class PropTypeHandler
{
    public static function getPropTypes($category_id = null)
    {
        return PropTypeDAO::getPropTypes($category_id);
    }

}