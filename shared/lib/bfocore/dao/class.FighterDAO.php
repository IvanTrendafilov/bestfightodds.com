<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

class FighterDAO
{

    public static function getAllFighters($a_bOnlyWithFights)
    {
        if ($a_bOnlyWithFights == true)
        {
            $sQuery = 'SELECT DISTINCT fi.id, fi.name
					FROM fighters fi 
						LEFT JOIN fights f ON (fi.id = f.fighter1_id OR fi.id = f.fighter2_id), fighters f1, fighters f2
					WHERE f.fighter1_id = f1.id
						AND f.fighter2_id = f2.id
					ORDER BY fi.name ASC';
        }
        else
        {
            $sQuery = 'SELECT f.id, f.name 
						FROM fighters f 
						ORDER BY f.name ASC';
        }

        $rResult = DBTools::doQuery($sQuery);

        $aFighters = array();

        while ($aFighter = mysqli_fetch_array($rResult))
        {
            $aFighters[] = new Fighter($aFighter['name'], $aFighter['id']);
        }

        return $aFighters;
    }

    public static function searchFighter($a_sFighterName)
    {
        $sQuery = "SELECT f.id, f.name, MATCH(f.name) AGAINST (?) AS score  
					FROM fighters f
					WHERE f.name LIKE ?
                        OR MATCH(f.name) AGAINST (?) 
					ORDER BY score DESC, f.name ASC";

        $aParams = array($a_sFighterName, '%' . $a_sFighterName . '%', $a_sFighterName);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aFighters = array();

        while ($aFighter = mysqli_fetch_array($rResult))
        {
            $aFighters[] = new Fighter($aFighter['name'], $aFighter['id']);
        }

        return $aFighters;
    }

    public static function getFighterByID($a_iID)
    {
        $sQuery = 'SELECT f.name, f.id FROM fighters f WHERE f.id = ?';
        $aParams = array($a_iID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        if ($aFighter = mysqli_fetch_array($rResult))
        {
            return new Fighter($aFighter['name'], $aFighter['id']);
        }
        return null;
    }

}

?>