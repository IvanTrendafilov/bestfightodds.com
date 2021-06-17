<?php

namespace BFO\General;

use BFO\DataTypes\Bookie;
use BFO\DataTypes\PropTemplate;
use BFO\DB\BookieDB;
use Exception;

/**
 * Logic to handle sportsbooks (bookies)
 */
class BookieHandler
{
    public static function getAllBookies(bool $exclude_inactive = false): array
    {
        return BookieDB::getBookiesGeneric(exclude_inactive: $exclude_inactive);
    }

    public static function getBookieByID(int $bookie_id): ?Bookie
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

    public static function getPropTemplatesForBookie(int $bookie_id): array
    {
        return BookieDB::getPropTemplatesForBookie($bookie_id);
    }

    public static function addNewPropTemplate(PropTemplate $proptemplate_obj): ?int
    {
        $existing_templates = BookieHandler::getPropTemplatesForBookie($proptemplate_obj->getBookieID());
        foreach ($existing_templates as $template) {
            if ($template->equals($proptemplate_obj)) {
                throw new Exception("Duplicate entry", 10);
                return null;
            }
        }

        //Check if fields type ID is within the span 1-6
        if ($proptemplate_obj->getFieldsTypeID() < 1 || $proptemplate_obj->getFieldsTypeID() > 8) {
            throw new Exception("Invalid fields type", 11);
            return null;
        }

        //Check if there an occurence of <T>. Add a template cannot be added without indicating at least one team/event in the template
        if (strpos($proptemplate_obj->getTemplate(), '<T>') === false) {
            throw new Exception("Missing team or event (<T>) indicator", 12);
            return null;
        }

        return BookieDB::addNewPropTemplate($proptemplate_obj);
    }

    public static function deletePropTemplate(int $template_id): bool
    {
        if (!is_int($template_id)) {
            return false;
        }
        return BookieDB::deletePropTemplate($template_id);
    }

    public static function getAllRunStatuses()
    {
        return BookieDB::getAllRunStatuses();
    }

    public static function updateBookieURL(int $bookie_id, string $url): bool
    {
        return BookieDB::updateBookieURL($bookie_id, $url);
    }
}
