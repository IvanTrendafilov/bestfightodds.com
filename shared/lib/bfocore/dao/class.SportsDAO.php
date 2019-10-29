<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');

/**
 * Description of classSportsDAO
 *
 * @author Christian
 */
class SportsDAO
{
    public function getSportID($a_sSportName)
    {
        $sQuery = "SELECT s.id WHERE name = ? LIMIT 0,1";

        $rResult = DBTools::doParamQuery($sQuery, array($a_sSportName));

        return DBTools::getSingleValue($rResult);
    }
}

?>
