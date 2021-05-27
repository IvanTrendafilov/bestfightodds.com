<?php

namespace BFO\DB;

use BFO\Utils\DB\DBTools;
use BFO\Utils\DB\PDOTools;
use BFO\DataTypes\Bookie;
use BFO\DataTypes\BookieParser;
use BFO\DataTypes\PropTemplate;

class BookieDB
{
    public static function getBookiesGeneric(int $bookie_id = null): array
    {
        $extra_where = '';
        $params = [];
        if ($bookie_id) {
            $extra_where .= ' AND id = ? ';
            $params[] = $bookie_id;
        }

        $query = 'SELECT b.id, b.name, b.url, b.refurl
            FROM bookies b
            WHERE active = true 
            ' . $extra_where . '
            ORDER BY position, id ASC';

        $bookies = [];
        try {
            foreach (PDOTools::findMany($query, $params) as $row) {
                $bookies[] = new Bookie((int) $row['id'], $row['name'], $row['url'], $row['refurl']);
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
            return $result->rowCount() > 0 ? true : false;
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

        return DBTools::getSingleValue($result);
    }

    public static function resetChangenum($a_iBookieID)
    {
        $query = 'UPDATE bookies_changenums bcn
                    INNER JOIN bookies_parsers bp ON bcn.bookie_id = bp.bookie_id
                    SET bcn.changenum = bp.cn_initial WHERE bcn.bookie_id = ?';

        $params = array($a_iBookieID);
        $result = DBTools::doParamQuery($query, $params);

        if ($result == false) {
            return false;
        }
        return true;
    }

    public static function resetAllChangeNums()
    {
        $query = 'UPDATE bookies_changenums bcn
                    INNER JOIN bookies_parsers bp ON bcn.bookie_id = bp.bookie_id
                    SET bcn.changenum = bp.cn_initial';
        $result = DBTools::doQuery($query);
        if ($result == false) {
            return false;
        }
        return true;
    }

    public static function getParsers($bookie_id = null)
    {
        $params = [];
        $extra_where = '';
        if ($bookie_id != null) {
            $params[] = $bookie_id;
            $extra_where = ' WHERE bookie_id = ?';
        }

        $query = 'SELECT id, bookie_id, name, parse_url, cn_inuse, mockfile, cn_urlsuffix 
                    FROM bookies_parsers b ' . $extra_where;

        $result = DBTools::doParamQuery($query, $params);

        $parsers = [];
        while ($row = mysqli_fetch_array($result)) {
            $parsers[] = new BookieParser($row['id'], $row['bookie_id'], $row['name'], $row['parse_url'], $row['mockfile'], $row['cn_inuse'], $row['cn_urlsuffix']);
        }
        return $parsers;
    }


    public static function getPropTemplatesForBookie($bookie_id)
    {
        $query = 'SELECT bpt.id, bpt.bookie_id, bpt.template, bpt.template_neg, bpt.prop_type, bpt.fields_type, pt.is_eventprop, bpt.last_used
                    FROM bookies_proptemplates bpt, prop_types pt
                    WHERE bpt.bookie_id = ?
                        AND bpt.prop_type = pt.id';
        $params = array($bookie_id);

        $result = DBTools::doParamQuery($query, $params);

        $templates = [];
        while ($row = mysqli_fetch_array($result)) {
            $template = new PropTemplate($row['id'], $row['bookie_id'], $row['template'], $row['template_neg'], $row['prop_type'], $row['fields_type'], $row['last_used']);
            $template->setEventProp($row['is_eventprop']);
            $templates[] = $template;
        }

        return $templates;
    }

    public static function addNewPropTemplate($prop_template)
    {
        $query = 'INSERT INTO bookies_proptemplates(bookie_id, template, prop_type, template_neg, fields_type)
                    VALUES (?, ?, ?, ?, ?)';

        $params = array($prop_template->getBookieID(), $prop_template->getTemplate(), $prop_template->getPropTypeID(), $prop_template->getTemplateNeg(), $prop_template->getFieldsTypeID());

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

    public static function updateTemplateLastUsed($template_id)
    {
        $query = 'UPDATE bookies_proptemplates SET last_used = NOW() WHERE id = ?';
        $params = array($template_id);
        DBTools::doParamQuery($query, $params);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function deleteTemplate($template_id)
    {
        $query = 'DELETE FROM bookies_proptemplates WHERE id = ?';
        $params = array($template_id);
        DBTools::doParamQuery($query, $params);
        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function getAllRunStatuses()
    {
        $query = 'SELECT b.name, lp.bookie_id, MAX(lp.date), AVG(lp.matched_matchups) as average_matched 
                    FROM logs_parseruns lp INNER JOIN bookies b  on lp.bookie_id = b.id 
                    WHERE lp.date >= NOW() - INTERVAL 1 DAY 
                    GROUP BY lp.bookie_id;';
        return PDOTools::findMany($query);
    }
}
