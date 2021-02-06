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

    public static function resetChangenum($a_iBookieID)
    {
        return BookieDAO::resetChangenum($a_iBookieID);
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
        if ($a_oPropTemplate->getFieldsTypeID() < 1 || $a_oPropTemplate->getFieldsTypeID() > 8)
        {
            return false;
        }

        return BookieDAO::addNewPropTemplate($a_oPropTemplate);
    }

    public static function updateTemplateLastUsed($a_iTemplateID)
    {
        return BookieDAO::updateTemplateLastUsed($a_iTemplateID);
    }

    public static function deleteTemplate($a_iID)
    {
        if (!is_int($a_iID))
        {
            return false;
        }
        return BookieDAO::deleteTemplate($a_iID);
    }


    public static function getAllRunStatuses()
    {
        return BookieDAO::getAllRunStatuses();
    }

}

?>