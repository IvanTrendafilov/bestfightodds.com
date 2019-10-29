<?php

require_once('dao/class.SportsDAO.php');

/**
 * Description of classSportHandler
 *
 * @author Christian
 */
class SportsHandler
{
    private $a_iSports;

    public function getSportID($a_sSportName)
    {
        //Check if sport has already been looked up. If so, fetch from local cache
        if (array_key_exists($a_sSportName, $this->a_iSports))
        {
            return $this->a_iSports[$a_sSportName];
        }

        //Sport not found, fetch from DB and store in local cache
        $iSportID = SportsHandler::getSportID($a_sSportName);
        if ($iSportID != null)
        {
            $this->a_iSports[$a_sSportName] = $iSportID;
        }

        return $iSportID;
    }
}
?>
