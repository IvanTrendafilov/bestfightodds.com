<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/dao/class.BookieDAO.php');

class BookieHandler
{

    public static function getAllBookies()
    {
        return BookieDAO::getAllBookies();
    }

    public static function getBookieByName($a_sBookieName)
    {
        return BookieDAO::getBookieByName($a_sBookieName);
    }

    public static function getAllLinkouts()
    {
        return BookieDAO::getAllLinkouts();
    }

    public static function getAllDetailedLinkouts($a_iLimit = 15)
    {
        return BookieDAO::getAllDetailedLinkouts($a_iLimit);
    }

    public static function getBookieByID($a_iBookieID)
    {
        return BookieDAO::getBookieByID($a_iBookieID);
    }

    public static function saveChangeNum($a_iBookieID, $a_sChangeNum)
    {
        return BookieDAO::saveChangeNum($a_iBookieID, $a_sChangeNum);
    }

    public static function getChangeNum($a_iBookieID)
    {
        return BookieDAO::getChangeNum($a_iBookieID);
    }

    public static function resetAllChangeNums()
    {
        return BookieDAO::resetAllChangeNums();
    }

    public static function getParsers($a_iBookieID = -1)
    {
        return BookieDAO::getParsers($a_iBookieID);
    }


    public static function getPropTemplatesForBookie($a_iBookieID)
    {
        return BookieDAO::getPropTemplatesForBookie($a_iBookieID);
    }

    public static function addNewPropTemplate($a_oPropTemplate)
    {
        //TODO: Add check to see if we are trying to add a duplicate

        //Check if fields type ID is within the span 1-6
        if ($a_oPropTemplate->getFieldsTypeID() < 1 || $a_oPropTemplate->getFieldsTypeID() > 6)
        {
            return false;
        }

        return BookieDAO::addNewPropTemplate($a_oPropTemplate);
    }

    /**
     * Saves a linkout (log of user clicking an affiliate link)
     * 
     * @param int $a_iBookieID Bookie ID
     * @param int $a_iEventID ID for event for where link appears
     * @param string $a_sIP IP of user that clicked
     * @return boolean If linkout was saved or not
     */
    public static function saveLinkout($a_iBookieID, $a_iEventID, $a_sIP)
    {
        /* The followin check has been disabled due to missing support in prod
        if (!filter_var($a_iBookieID, FILTER_VALIDATE_INT) || !filter_var($a_iEventID, FILTER_VALIDATE_INT) || !filter_var($a_sIP, FILTER_VALIDATE_IP))
        {
            return false;
        }*/
        return BookieDAO::saveLinkout($a_iBookieID, $a_iEventID, $a_sIP);
    }

}

?>