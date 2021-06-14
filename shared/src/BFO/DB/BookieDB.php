<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;
use BFO\DataTypes\Bookie;
use BFO\DataTypes\PropTemplate;

class BookieDB
{
    public static function getBookiesGeneric(int $bookie_id = null, bool $exclude_inactive = false): array
    {
        $extra_where = '';
        $params = [];
        if ($bookie_id) {
            $extra_where .= ' AND id = ? ';
            $params[] = $bookie_id;
        }

        if ($exclude_inactive) {
            $extra_where .= ' AND b.active = true ';
        }

        $query = 'SELECT b.id, b.name, b.refurl, b.active
            FROM bookies b
                WHERE 1=1 
            ' . $extra_where . '
            ORDER BY position, id ASC';

        $bookies = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $bookies[] = new Bookie((int) $row['id'], $row['name'], $row['refurl'], boolval($row['active']));
            };
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Unknown error " . $e->getMessage(), 10);
            }
        }

        return $bookies;
    }

    public static function saveChangeNum(int $bookie_id, string $change_num): bool
    {
        $query = 'UPDATE bookies_changenums 
                    SET changenum = ? 
                    WHERE bookie_id = ?';

        $params = [$change_num, $bookie_id];

        try {
            $result = PDOTools::executeQuery($query, $params);
            return $result !== false ? true : false;
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
    }

    public static function getChangeNum($bookie_id)
    {
        $query = 'SELECT changenum 
                    FROM bookies_changenums 
                    WHERE bookie_id = ?';

        $params = [$bookie_id];

        $result = DBTools::doParamQuery($query, $params);
        $value = DBTools::getSingleValue($result);
        if ($value == '') {
            return -1;
        }
        return intval($value);
    }

    public static function resetChangeNums(int $bookie_id = null): int
    {
        $params = [];
        $extra_where = '';
        if ($bookie_id) {
            $extra_where = ' WHERE bookie_id = :bookie_id ';
            $params[':bookie_id'] = $bookie_id;
        }

        $query = 'UPDATE bookies_changenums bcn
                    SET bcn.changenum = bcn.initial '
                    . $extra_where . ' ';

        try {
            $result = PDOTools::executeQuery($query, $params);
            return $result->rowCount();

        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return 0;
    }

    public static function getChangeNums(int $bookie_id = null): ?array
    {
        $params = [];
        $extra_where = '';
        if ($bookie_id) {
            $extra_where = ' WHERE bookie_id = :bookie_id ';
            $params[':bookie_id'] = $bookie_id;
        }

        $query = 'SELECT bookie_id, changenum, initial 
                    FROM bookies_changenums ' . $extra_where. ' 
                    ORDER BY bookie_id ASC';
        
        try {
            return PDOTools::findMany($query, $params);
        } catch (\PDOException $e) {
            throw new \Exception("Unknown error " . $e->getMessage(), 10);
        }
        return null;
    }


    public static function getPropTemplatesForBookie(int $bookie_id): array
    {
        $query = 'SELECT bpt.id, bpt.bookie_id, bpt.template, bpt.template_neg, bpt.prop_type, bpt.fields_type, pt.is_eventprop, bpt.last_used
                    FROM bookies_proptemplates bpt, prop_types pt
                    WHERE bpt.bookie_id = ?
                        AND bpt.prop_type = pt.id';
        $params = array($bookie_id);

        $result = DBTools::doParamQuery($query, $params);

        $templates = [];
        while ($row = mysqli_fetch_array($result)) {
            $template = new PropTemplate((int) $row['id'], (int) $row['bookie_id'], $row['template'], $row['template_neg'], (int) $row['prop_type'], (int) $row['fields_type'], $row['last_used']);
            $template->setEventProp((bool) $row['is_eventprop']);
            $templates[] = $template;
        }

        return $templates;
    }

    public static function addNewPropTemplate(PropTemplate $prop_template): ?int
    {
        $query = 'INSERT INTO bookies_proptemplates(bookie_id, template, prop_type, template_neg, fields_type)
                    VALUES (?, ?, ?, ?, ?)';

        $params = [$prop_template->getBookieID(), $prop_template->getTemplate(), $prop_template->getPropTypeID(), $prop_template->getTemplateNeg(), $prop_template->getFieldsTypeID()];

        $id = null;
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            }
        }
        return (int) $id;
    }

    public static function deletePropTemplate(int $template_id): bool
    {
        $query = 'DELETE FROM bookies_proptemplates WHERE id = ?';
        $params = array($template_id);
        DBTools::doParamQuery($query, $params);
        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function getAllRunStatuses(): ?array 
    {
        $query = 'SELECT b.name, lp.bookie_id, MAX(lp.date), AVG(lp.matched_matchups) as average_matched 
                    FROM logs_parseruns lp INNER JOIN bookies b  on lp.bookie_id = b.id 
                    WHERE lp.date >= NOW() - INTERVAL 1 DAY 
                    GROUP BY lp.bookie_id;';
        return PDOTools::findMany($query);
    }

}
