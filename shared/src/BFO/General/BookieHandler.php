<?php

namespace BFO\General;

use BFO\DataTypes\Bookie;
use BFO\DB\BookieDB;

class BookieHandler
{
    public static function getAllBookies() : array
    {
        return BookieDB::getBookiesGeneric();
    }

    public static function getBookieByID($bookie_id) : ?Bookie
    {
        $bookies = BookieDB::getBookiesGeneric($bookie_id);
        return $bookies[0] ?? null;
    }

    public static function saveChangeNum(int $bookie_id, string $change_num): bool
    {
        return BookieDB::saveChangeNum($bookie_id, $change_num);
    }

    public static function getChangeNum($bookie_id)
    {
        return BookieDB::getChangeNum($bookie_id);
    }

    public static function resetChangeNums(int $bookie_id = null): int
    {
        return BookieDB::resetChangeNums($bookie_id);
    }

    public static function getChangeNums(int $bookie_id = null): array
    {
        return BookieDB::getChangeNums($bookie_id);
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
