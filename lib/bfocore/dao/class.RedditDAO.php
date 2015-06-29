<?php

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/utils/db/class.DBTools.php');

/**
 * Reddit DAO
 *
 * Handles all calls to the database related to reddit
 *
 * @author Christian
 */
class RedditDAO
{
    public static function getLatestRedditDetails($a_iEventID)
    {
        $sQuery = 'SELECT rp.event_id, rp.reddit_id, rp.last_change
                    FROM events_redditposts rp
                    WHERE rp.event_id = ?';

        $aParams = array($a_iEventID);
        $rResult = DBTools::doParamQuery($sQuery, $aParams);
        $aData = mysql_fetch_array($rResult);
        if (isset($aData['reddit_id']) && isset($aData['last_change']))
        {
            return $aData;
        }
        return false;
    }

    public static function upsertRedditPost($a_iEventID, $a_iRedditID, $a_i)

}
?>
