<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');

class ScheduleDAO
{
	public static function storeManualAction($a_sMessage, $a_iType)
	{
		$sQuery = 'INSERT INTO schedule_manualactions(description, type) VALUES (?,?)';
		$aParams = array($a_sMessage, $a_iType);

		return DBTools::doParamQuery($sQuery, $aParams);
	}

	public static function getAllManualActions()
	{
		$sQuery = 'SELECT id, description, type FROM schedule_manualactions';
		$rResult = DBTools::doQuery($sQuery);

		$aReturn = array();
        while ($aRow = mysql_fetch_array($rResult))
        {
        	$aReturn[] = $aRow;
        }
        if (sizeof($aReturn) == 0)
        {
        	return false;
        }
        return $aReturn;
	}

	public static function clearAllManualActions()
	{
		$sQuery = 'TRUNCATE schedule_manualactions';
		DBTools::doQuery($sQuery);
		return true;
	}

	public static function clearManualAction($a_iManualActionID)
	{
		$sQuery = 'DELETE FROM schedule_manualactions WHERE id = ?';
		DBTools::doParamQuery($sQuery, array($a_iManualActionID));
		return DBTools::getAffectedRows();
	}
}

?>