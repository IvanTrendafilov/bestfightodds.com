<?php

//TODO: Move these to TeamHandler (make generic)

require_once('lib/bfocore/db/class.TeamDB.php');

class FighterHandler
{
    public static function getAllFighters($only_with_odds = false)
    {
        return TeamDB::getAllFighters($only_with_odds);
    }

    public static function searchFighter($name)
    {
        if (strlen($name) < 2) {
            return false;
        }
        return TeamDB::searchFighter($name);
    }

    public static function getFighterByID($id)
    {
        return TeamDB::getFighterByID($id);
    }
}
