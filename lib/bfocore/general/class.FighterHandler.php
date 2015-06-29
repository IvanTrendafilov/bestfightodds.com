<?php

//TODO: Rename to TeamHandler (make generic)

require_once('lib/bfocore/dao/class.FighterDAO.php');

class FighterHandler
{

    public static function getAllFighters($a_bOnlyWithFightOdds = false)
    {
        return FighterDAO::getAllFighters($a_bOnlyWithFightOdds);
    }

    public static function searchFighter($a_sFighterName)
    {
        if (strlen($a_sFighterName) < 2)
        {
            return false;
        }
        return FighterDAO::searchFighter($a_sFighterName);
    }

    public static function getFighterByID($a_iFighterID)
    {
        return FighterDAO::getFighterByID($a_iFighterID);
    }
}

?>