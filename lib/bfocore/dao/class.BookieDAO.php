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
        $rResult = DBTools::doQuery($sQuery);

        $aBookies = array();

        while ($aBookie = mysql_fetch_array($rResult))
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

        while ($aBookie = mysql_fetch_array($rResult))
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

        while ($aBookie = mysql_fetch_array($rResult))
        {
            $aBookies[] = new Bookie($aBookie['id'], $aBookie['name'], $aBookie['url'], $aBookie['refurl']);
        }
        if (sizeof($aBookies) > 0)
        {
            return $aBookies[0];
        }
        return null;
    }

    public static function getAllLinkouts()
    {
        $sQuery = 'SELECT b.name AS name, count(*) AS clicks
					FROM linkouts_ext le, bookies b 
					WHERE le.bookie_id = b.id 
					GROUP BY le.bookie_id;';

        $rResult = DBTools::doQuery($sQuery);

        $aLinkouts = array();

        while ($aResult = mysql_fetch_array($rResult))
        {
            $aLinkouts[$aResult['name']] = $aResult['clicks'];
        }

        return $aLinkouts;
    }

    public static function getAllDetailedLinkouts($a_iLimit = 15)
    {
        if (!is_integer($a_iLimit))
        {
            return false;
        }
        $sQuery = 'SELECT le.bookie_id AS bookie_id, le.event_id AS event_id, le.click_date AS click_date, b.name AS bookie_name, e.name AS event_name, le.visitor_ip as visitor_ip
					FROM linkouts_ext le, bookies b, events e 
					WHERE le.bookie_id = b.id 
            AND le.event_id = e.id 
					ORDER BY le.click_date DESC LIMIT 0,' . $a_iLimit;

        $rResult = DBTools::doQuery($sQuery);

        $aLinkouts = array();

        while ($aLinkout = mysql_fetch_array($rResult))
        {
            $aLinkouts[] = new Linkout($aLinkout['bookie_id'], $aLinkout['bookie_name'], $aLinkout['event_id'], $aLinkout['event_name'], $aLinkout['click_date'], $aLinkout['visitor_ip']);
        }

        return $aLinkouts;
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

    public static function resetAllChangeNums()
    {
        $sQuery = 'UPDATE bookies_changenums SET changenum = -1';
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

        $sQuery = 'SELECT id, bookie_id, name, parse_url, cn_inuse, mockfile, cn_urlsuffix FROM bets.bookies_parsers b ' . $sExtraWhere;

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aParsers = array();
        while ($aParser = mysql_fetch_array($rResult))
        {
            $aParsers[] = new BookieParser($aParser['id'], $aParser['bookie_id'], $aParser['name'], $aParser['parse_url'], $aParser['mockfile'], $aParser['cn_inuse'], $aParser['cn_urlsuffix']);
        }
        return $aParsers;
    }


    public static function getPropTemplatesForBookie($a_iBookieID)
    {
        $sQuery = 'SELECT bpt.id, bpt.bookie_id, bpt.template, bpt.template_neg, bpt.prop_type, bpt.fields_type, pt.is_eventprop
                    FROM bookies_proptemplates bpt, prop_types pt
                    WHERE bpt.bookie_id = ?
                        AND bpt.prop_type = pt.id';
        $aParams = array($a_iBookieID);

        $rResult = DBTools::doParamQuery($sQuery, $aParams);

        $aTemplates = array();
        while ($aTemplate = mysql_fetch_array($rResult))
        {
            $oTempObj = new PropTemplate($aTemplate['id'], $aTemplate['bookie_id'], $aTemplate['template'], $aTemplate['template_neg'], $aTemplate['prop_type'], $aTemplate['fields_type']);
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

        DBTools::doParamQuery($sQuery, $aParams);

        return (DBTools::getAffectedRows() > 0 ? true : false);
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
        $aParams = array($a_iBookieID, $a_iEventID, $a_sIP);

        $sQuery = 'INSERT INTO linkouts_ext(bookie_id, event_id, click_date, visitor_ip) VALUES (?, ?, NOW(), ?)';

        DBTools::doParamQuery($sQuery, $aParams);

        return (DBTools::getAffectedRows() > 0 ? true : false);
    }


}

?>