<?php

namespace BFO\General;

use BFO\DB\BookieDB;

class BookieHandler
{
    public static function getAllBookies()
    {
        return BookieDB::getAllBookies();
    }

    public static function getBookieByID($id)
    {
        return BookieDB::getBookieByID($id);
    }

    public static function saveChangeNum($bookie_id, $change_num)
    {
        return BookieDB::saveChangeNum($bookie_id, $change_num);
    }

    public static function getChangeNum($bookie_id)
    {
        return BookieDB::getChangeNum($bookie_id);
    }

    public static function resetAllChangeNums()
    {
        return BookieDB::resetAllChangeNums();
    }

    public static function resetChangenum($bookie_id)
    {
        return BookieDB::resetChangenum($bookie_id);
    }

    public static function getParsers($bookie_id = null)
    {
        return BookieDB::getParsers($bookie_id);
    }


    public static function getPropTemplatesForBookie($bookie_id)
    {
        return BookieDB::getPropTemplatesForBookie($bookie_id);
    }

    public static function addNewPropTemplate($proptemplate_obj)
    {
        //TODO: Add check to see if we are trying to add a duplicate

        //Check if fields type ID is within the span 1-6
        if ($proptemplate_obj->getFieldsTypeID() < 1 || $proptemplate_obj->getFieldsTypeID() > 8) {
            return false;
        }

        //Check if there an occurence of <T>. Add a template cannot be added without indicating at least one team/event in the template
        if (strpos($proptemplate_obj->getTemplate(), '<T>') === false) {
            return false;
        }

        return BookieDB::addNewPropTemplate($proptemplate_obj);
    }

    public static function updateTemplateLastUsed($template_id)
    {
        return BookieDB::updateTemplateLastUsed($template_id);
    }

    public static function deleteTemplate($template_id)
    {
        if (!is_int($template_id)) {
            return false;
        }
        return BookieDB::deleteTemplate($template_id);
    }

    public static function getAllRunStatuses()
    {
        return BookieDB::getAllRunStatuses();
    }
}
