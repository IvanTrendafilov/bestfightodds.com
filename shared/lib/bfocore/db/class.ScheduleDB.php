<?php

require_once('lib/bfocore/utils/db/class.DBTools.php');

class ScheduleDB
{
	public static function storeManualAction($message, $type)
	{
		$query = 'INSERT INTO schedule_manualactions(description, type) VALUES (?,?)';
		$params = array($message, $type);

		return DBTools::doParamQuery($query, $params);
	}

	public static function getAllManualActions($type = -1)
	{
        $params = [];
		$extra_where = '';
        if ((1 <= $type) && ($type <= 8)) {
            $extra_where = ' WHERE type = ? ';
            $params[] = $type;
        }

		$query = 'SELECT id, description, type 
					FROM schedule_manualactions
					' . $extra_where . ' ';
		$result = DBTools::doParamQuery($query, $params);

		$return = array();
        while ($row = mysqli_fetch_array($result))
        {
        	$return[] = $row;
        }
        if (sizeof($return) == 0)
        {
        	return false;
        }
        return $return;
	}

	public static function clearAllManualActions()
	{
		$query = 'TRUNCATE schedule_manualactions';
		DBTools::doQuery($query);
		return true;
	}

	public static function clearManualAction($action_id)
	{
		$query = 'DELETE FROM schedule_manualactions WHERE id = ?';
		DBTools::doParamQuery($query, array($action_id));
		return DBTools::getAffectedRows();
	}
}

?>