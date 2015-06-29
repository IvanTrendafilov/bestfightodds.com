<?php

require_once('lib/bfocore/dao/class.ScheduleDAO.php');

class ScheduleHandler
{
/**
     * Types:
     *
     * 1: Unmatched event to be created
     * 2: Event to be renamed
     * 3: Event to be moved to another date
     * 4: Event to be deleted
     * 5: Matchup to be created
     * 6: Matchup to be moved to different event
     * 7: Matchup to be deleted
     * 8: Matchup to be moved to a different not yet created event
     */
	public static function storeManualAction($a_sMessage, $a_iType)
	{
		if ($a_sMessage == '' || !is_integer($a_iType))
		{
			return false;
		}
		return ScheduleDAO::storeManualAction($a_sMessage, $a_iType);
	}

	public static function getAllManualActions()
	{
		return ScheduleDAO::getAllManualActions();
	}

	public static function clearAllManualActions()
	{
		return ScheduleDAO::clearAllManualActions();
	}

	public static function clearManualAction($a_iManualActionID)
	{
		return ScheduleDAO::clearManualAction($a_iManualActionID);
	}

}


?>