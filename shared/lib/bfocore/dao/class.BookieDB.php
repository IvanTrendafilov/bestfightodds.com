<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

class BookieDB
{
    public static function getAllBookies()
    {
        $query = 'SELECT id, name, url, refurl
					FROM bookies 
					WHERE active = true 
					ORDER BY position, id  ASC';

        $result = DBTools::getCachedQuery($query);
        if ($result == null) {
            $result = DBTools::doQuery($query);
            DBTools::cacheQueryResults($query, $result);
        }

        $bookies = array();

        while ($row = mysqli_fetch_array($result)) {
            $bookies[] = new Bookie($row['id'], $row['name'], $row['url'], $row['refurl']);
        }

        return $bookies;
    }

    public static function getBookieByID($bookie_id)
    {
        $query = 'SELECT id, name, url, refurl
					FROM bookies 
					WHERE active = true 
					AND id = ? ';

        $params = [$bookie_id];

        $result = DBTools::doParamQuery($query, $params);
        $bookies = array();

        while ($row = mysqli_fetch_array($result)) {
            $bookies[] = new Bookie($row['id'], $row['name'], $row['url'], $row['refurl']);
        }
        if (sizeof($bookies) > 0) {
            return $bookies[0];
        }
        return null;
    }

    public static function saveChangeNum($bookie_id, $change_num)
    {
        $query = 'UPDATE bookies_changenums 
                    SET changenum = ? 
                    WHERE bookie_id = ?';

        $params = array($change_num, $bookie_id);

        $result = DBTools::doParamQuery($query, $params);

        if ($result == false) {
            return false;
        }
        return true;
    }

    public static function getChangeNum($bookie_id)
    {
        $query = 'SELECT changenum 
                    FROM bookies_changenums 
                    WHERE bookie_id = ?';

        $params = array($bookie_id);

        $result = DBTools::doParamQuery($query, $params);

        return DBTools::getSingleValue($result);
    }

    public static function resetChangenum($a_iBookieID)
    {
        $query = 'UPDATE bookies_changenums bcn
                    INNER JOIN bookies_parsers bp ON bcn.bookie_id = bp.bookie_id
                    SET bcn.changenum = bp.cn_initial WHERE bcn.bookie_id = ?';

        $params = array($a_iBookieID);
        $bResult = DBTools::doParamQuery($query, $params);

        if ($bResult == false) {
            return false;
        }
        return true;
    }

    public static function resetAllChangeNums()
    {
        $query = 'UPDATE bookies_changenums bcn
                    INNER JOIN bookies_parsers bp ON bcn.bookie_id = bp.bookie_id
                    SET bcn.changenum = bp.cn_initial';
        $bResult = DBTools::doQuery($query);
        if ($bResult == false) {
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


    public static function getPropTemplatesForBookie($a_iBookieID)
    {
        $query = 'SELECT bpt.id, bpt.bookie_id, bpt.template, bpt.template_neg, bpt.prop_type, bpt.fields_type, pt.is_eventprop, bpt.last_used
                    FROM bookies_proptemplates bpt, prop_types pt
                    WHERE bpt.bookie_id = ?
                        AND bpt.prop_type = pt.id';
        $params = array($a_iBookieID);

        $rResult = DBTools::doParamQuery($query, $params);

        $aTemplates = array();
        while ($aTemplate = mysqli_fetch_array($rResult)) {
            $oTempObj = new PropTemplate($aTemplate['id'], $aTemplate['bookie_id'], $aTemplate['template'], $aTemplate['template_neg'], $aTemplate['prop_type'], $aTemplate['fields_type'], $aTemplate['last_used']);
            $oTempObj->setEventProp($aTemplate['is_eventprop']);
            $aTemplates[] = $oTempObj;
        }

        return $aTemplates;
    }

    public static function addNewPropTemplate($a_oPropTemplate)
    {
        $query = 'INSERT INTO bookies_proptemplates(bookie_id, template, prop_type, template_neg, fields_type)
                    VALUES (?, ?, ?, ?, ?)';

        $params = array($a_oPropTemplate->getBookieID(), $a_oPropTemplate->getTemplate(), $a_oPropTemplate->getPropTypeID(), $a_oPropTemplate->getTemplateNeg(), $a_oPropTemplate->getFieldsTypeID());

        $id = null;
        try {
            $id = PDOTools::insert($query, $params);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Duplicate entry", 10);
            }
        }
        return $id;
    }

    public static function updateTemplateLastUsed($a_iTemplateID)
    {
        $query = 'UPDATE bookies_proptemplates SET last_used = NOW() WHERE id = ?';
        $params = array($a_iTemplateID);
        DBTools::doParamQuery($query, $params);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function deleteTemplate($a_iTemplateID)
    {
        $query = 'DELETE FROM bookies_proptemplates WHERE id = ?';
        $params = array($a_iTemplateID);
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
