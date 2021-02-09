<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

class BookieDAO
{

    public static function getAllBookies()
    {
        $sQuery = 'SELECT id, name, url, refurl
					FROM bookies 
					WHERE active = true 
					ORDER BY position, id  ASC';

        $rResult = DBTools::getCachedQuery($sQuery);
        if ($rResult == null)
        {
            $rResult = DBTools::doQuery($sQuery);
            DBTools::cacheQueryResults($sQuery, $rResult);
        }

        $aBookies = array();

        while ($aBookie = mysqli_fetch_array($rResult))
        {
            $aBookies[] = new Bookie($aBookie['id'], $aBookie['name'], $aBookie['url'], $aBookie['refurl']);
        }

        return $aBookies;
    }

    public static function getBookieByName($a_sBookieName)
    {
        $sQuery = 'SELECT id, name, url, refurl
					FROM bookies 
					WHERE active = true 
					AND name = \'' . $a_sBookieName . '\'  
					ORDER BY id ASC';
        $rResult = DBTools::doQuery($sQuery);

        $aBookies = array();

        while ($aBookie = mysqli_fetch_array($rResult))
        {
            $aBookies[] = new Bookie($aBookie['id'], $aBookie['name'], $aBookie['url'], $aBookie['refurl']);
        }
        if (sizeof($aBookies) > 0)
        {
            return $aBookies[0];
        }
        return null;
    }

    public static function getBookieByID($a_iBookieID)
    {
        $sQuery = 'SELECT id, name, url, refurl
					FROM bookies 
					WHERE active = true 
					AND id = \'' . $a_iBookieID . '\'  
					ORDER BY id ASC';
        $rResult = DBTools::doQuery($sQuery);

        $aBookies = array();

        while ($aBookie = mysqli_fetch_array($rResult))
        {
            $aBookies[] = new Bookie($aBookie['id'], $aBookie['name'], $aBookie['url'], $aBookie['refurl']);
        }
        if (sizeof($aBookies) > 0)
        {
            return $aBookies[0];
        }
        return null;
    }

    public static function saveChangeNum($a_iBookieID, $a_sChangeNum)
    {
        $sQuery = 'UPDATE bookies_changenums SET changenum = ? WHERE bookie_id = ?';

        $aParams = array($a_sChangeNum, $a_iBookieID);

        $bResult = DBTools::doParamQuery($sQuery, $aParams);

        if ($bResult == false)
        {
            return false;
        }
        return true;
    }

    public static function getChangeNum($a_iBookieID)
    {
        $sQuery = 'SELECT changenum FROM bookies_changenums WHERE bookie_id = ?';

        $aParams = array($a_iBookieID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        return DBTools::getSingleValue($rResult);
    }

    public static function resetChangenum($a_iBookieID)
    {
        $sQuery = 'UPDATE bookies_changenums bcn
                    INNER JOIN bookies_parsers bp ON bcn.bookie_id = bp.bookie_id
                    SET bcn.changenum = bp.cn_initial WHERE bcn.bookie_id = ?';

        $aParams = array($a_iBookieID);
        $bResult = DBTools::doParamQuery($sQuery, $aParams);

        if ($bResult == false)
        {
            return false;
        }
        return true;
    }

    public static function resetAllChangeNums()
    {
        $sQuery = 'UPDATE bookies_changenums bcn
                    INNER JOIN bookies_parsers bp ON bcn.bookie_id = bp.bookie_id
                    SET bcn.changenum = bp.cn_initial';
        $bResult = DBTools::doQuery($sQuery);
        if ($bResult == false)
        {
            return false;
        }
        return true;
    }

    public static function getParsers($a_iBookieID = -1)
    {
        $aParams = array();
        $sExtraWhere = '';
        //If argument is passed as -1 we fetch all parsers
        if ($a_iBookieID != -1)
        {
            $aParams[] = $a_iBookieID;
            $sExtraWhere = ' WHERE bookie_id = ?';
        }

        $sQuery = 'SELECT id, bookie_id, name, parse_url, cn_inuse, mockfile, cn_urlsuffix FROM bookies_parsers b ' . $sExtraWhere;

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aParsers = array();
        while ($aParser = mysqli_fetch_array($rResult))
        {
            $aParsers[] = new BookieParser($aParser['id'], $aParser['bookie_id'], $aParser['name'], $aParser['parse_url'], $aParser['mockfile'], $aParser['cn_inuse'], $aParser['cn_urlsuffix']);
        }
        return $aParsers;
    }


    public static function getPropTemplatesForBookie($a_iBookieID)
    {
        $sQuery = 'SELECT bpt.id, bpt.bookie_id, bpt.template, bpt.template_neg, bpt.prop_type, bpt.fields_type, pt.is_eventprop, bpt.last_used
                    FROM bookies_proptemplates bpt, prop_types pt
                    WHERE bpt.bookie_id = ?
                        AND bpt.prop_type = pt.id';
        $aParams = array($a_iBookieID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aTemplates = array();
        while ($aTemplate = mysqli_fetch_array($rResult))
        {
            $oTempObj = new PropTemplate($aTemplate['id'], $aTemplate['bookie_id'], $aTemplate['template'], $aTemplate['template_neg'], $aTemplate['prop_type'], $aTemplate['fields_type'], $aTemplate['last_used']);
            $oTempObj->setEventProp($aTemplate['is_eventprop']);
            $aTemplates[] = $oTempObj;
        }

        return $aTemplates;
    }

    public static function addNewPropTemplate($a_oPropTemplate)
    {
        $sQuery = 'INSERT INTO bookies_proptemplates(bookie_id, template, prop_type, template_neg, fields_type)
                    VALUES (?, ?, ?, ?, ?)';

        $aParams = array($a_oPropTemplate->getBookieID(), $a_oPropTemplate->getTemplate(), $a_oPropTemplate->getPropTypeID(), $a_oPropTemplate->getTemplateNeg(), $a_oPropTemplate->getFieldsTypeID());

    	$id = null;
		try 
		{
			$id = PDOTools::insert($sQuery, $aParams);
		}
		catch(PDOException $e)
		{
		    if($e->getCode() == 23000){
				throw new Exception("Duplicate entry", 10);	
		    }
		}
		return $id;
    }

    public static function updateTemplateLastUsed($a_iTemplateID)
    {
        $sQuery = 'UPDATE bookies_proptemplates SET last_used = NOW() WHERE id = ?';
        $aParams = array($a_iTemplateID);
        DBTools::doParamQuery($sQuery, $aParams);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function deleteTemplate($a_iTemplateID)
    {
        $sQuery = 'DELETE FROM bookies_proptemplates WHERE id = ?';
        $aParams = array($a_iTemplateID);
        DBTools::doParamQuery($sQuery, $aParams);
        return (DBTools::getAffectedRows() > 0 ? true : false);
    }

    public static function getAllRunStatuses()
    {
        $sQuery = 'SELECT b.name, lp.bookie_id, MAX(lp.date), AVG(lp.matched_matchups) as average_matched 
                    FROM logs_parseruns lp INNER JOIN bookies b  on lp.bookie_id = b.id 
                    WHERE lp.date >= NOW() - INTERVAL 1 DAY 
                    GROUP BY lp.bookie_id;';
	    return PDOTools::findMany($sQuery);
    }
}

?>