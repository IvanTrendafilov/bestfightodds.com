<?php

//TODO: Remove this and make dynamic:
$event_id = 1406;

//TODO: Validate that these are all applicable
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.TeamHandler.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/general/class.GraphHandler.php');
require_once('lib/bfocore/general/class.StatsHandler.php');
require_once('lib/bfocore/utils/db/class.PDOTools.php'); //TODO: Direct database access, abstract later



$event = EventHandler::getEvent($event_id);
//Perform check to verify that the correct URL was requested. Used to prevent scraping
//TODO: Add check for event
if (false)// && !isset($_GET['fighterID']) || !is_numeric($_GET['fighterID']) || $_GET['fighterID'] < 0 || $_GET['fighterID'] > 999999 || $oFighter == null
    //|| strtolower($oFighter->getFighterAsLinkString()[0]) != strtolower(substr($_SERVER['REQUEST_URI'], 10, 1)))
{
    error_log('Invalid prefight report requested at ' . $_SERVER['REQUEST_URI']);
    //Headers already sent so redirect must be done using js
    echo '<script type="text/javascript">
        <!--
        window.location = "/"
        //-->
        </script>';
    exit();
}

$sBuffer = '';
$sLastChange = 'null';
$bCached = false;
//Check if page is cached or not. If so, fetch from cache and include

//TODO: Define cache key
$cache_key = 'TEMP';

if (CacheControl::isPageCached('prefightreport-' . $cache_key . '-' . strtotime($sLastChange)))
{
    //Retrieve cached page
    $sBuffer = CacheControl::getCachedPage('prefightreport-' . $cache_key . '-' . strtotime($sLastChange));
    $bCached = true;
    echo '<!--C:HIT-->';
}
if ($bCached == false || empty($sBuffer))
{
    $matchups = EventHandler::getAllFightsForEvent($event->getID(), true); //Limit to only matchups that have odds
    $toplist_text = getTopExpectedOutcomes($event, $matchups);
    $pickems_text = getPickems($event, $matchups);
    $fotn_text = getFOTN($event);

    ob_start();

?>

<div id="page-wrapper" style="max-width: 800px;">
    <div id="page-container">
    <div class="content-header team-stats-header"><span id="team-name">Pre-Fight Report</span></div>
        <div id="page-inner-wrapper">
            <div id="page-content">           
               Most expected outcome: <?php echo $toplist_text; ?><br /><br />
               Pickems: <?php echo $pickems_text; ?><br /><br />
               FOTN: <?php echo $fotn_text ?><br /><br />
            </div>
        </div>
    </div>
</div>

<div id="page-bottom"></div>

<?php

    $sBuffer = ob_get_clean();
    //TODO: Define cache key
    CacheControl::cleanPageCacheWC('prefightreport-' . $cache_key . '-*');
    CacheControl::cachePage($sBuffer, 'prefightreport-' . $cache_key . '-' . strtotime($sLastChange) . '.php');
    echo '<!--C:MIS-->';
}

echo $sBuffer;

function getTopExpectedOutcomes($event, $matchups) {
    //TODO: Temporary hard coded category id
    $category_id = 1;

    /* == Fetches the most likely outcome and sorts it to a toplist */
    $toplist = [];
    foreach ($matchups as $matchup)
    {
        $matchup_outcome = StatsHandler::getExpectedOutcome($matchup->getID(), $category_id);
        foreach($matchup_outcome as $key => $outcome)
        {
            $real_prop = str_replace('T1', $matchup->getTeamAsString(1), str_replace('T2', $matchup->getTeamAsString(2), $key));
            //Create a toplist by reversing  the key->value pairs. Can probably be done with a built in function in php. But what happens when two share the same value? Do we need a sort mechanism?
            $toplist[$real_prop] = $outcome;
        }
    }

    function sortTopList($a, $b)
    {
        return $a['score'] < $b['score'];
    }
    uasort($toplist, "sortTopList");

    foreach($toplist as $key => $outcome)
    {
        $toplist_text .= $key . ' = ' . $outcome['score'] . ' (' . $outcome['odds_ml'] . ')<br />';
    }
    return $toplist_text;
}

function getPickems($event, $matchups) {
    /* Pickems */
    $pickems = [];
    $pickems_text = '';
    foreach ($matchups as $matchup)
    {
        $all_odds = EventHandler::getAllLatestOddsForFight($matchup->getID());
        foreach ($all_odds as $odds)
        {
            //Considered a pickem if between -120 and +100
            if ($odds->getFighterOddsAsDecimal(1) >= 1.83 
            && $odds->getFighterOddsAsDecimal(1) <= 2 
            && $odds->getFighterOddsAsDecimal(2) >= 1.83 
            && $odds->getFighterOddsAsDecimal(2) <= 2)
            {
                $pickems[$matchup->getID()] = $matchup;
            }
        }
    }
    foreach ($pickems as $key => $matchup)
    {
        $pickems_text .= '<br />' . $matchup->getTeamAsString(1) . ' vs ' . $matchup->getTeamAsString(2);
    }
    return $pickems_text;
}

function getFOTN($event)
{
    $proptype_id = 27; //TODO: hardcoded proptype ID for fotn

    //TODO: Direct sql query here, abstract later
    $query = 'SELECT f.id
        FROM lines_props lp
        INNER JOIN fights f ON lp.matchup_id = f.id
        INNER JOIN events e ON f.event_id = e.id
        WHERE e.id = ?
        AND lp.proptype_id = ?
        ORDER BY prop_odds ASC
        LIMIT 1';
    
    $params = [$event->getID(), $proptype_id];
    $fotn = PDOTools::findOne($query, $params);
    if ($fotn != null)
    {
        $matchup = EventHandler::getFightByID($fotn->id);
        return $matchup->getTeamAsString(1) . ' vs ' . $matchup->getTeamAsString(2) . ' is expected to win the Fight Of The Night bonus';
    }
    return 'No FOTN';
    
}

function getNotables($event, $matchups)
{
    //Notables including: underdog for the first time in x fights

    //Loop through all matchups
    //Check history on each fighter

    foreach ($matchups as $matchup)
    {
        

    }

}

?>
