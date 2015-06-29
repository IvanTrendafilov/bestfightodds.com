<?php

require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');

/**
 *  If the parameter f is sent, this is used to specify what is search for. e.g. t for team or e for event
 */
//Clear query if sent as " Event / Fighter" (the default value in text box)
if (isset($_GET['query']) && $_GET['query'] == 'MMA Event / Fighter')
{
    $_GET['query'] = '';
}

if (isset($_GET['query']) && strlen($_GET['query']) > 2)
{
    define('SEARCHRESULTS_QUERY', htmlentities(trim($_GET['query']), ENT_NOQUOTES));
    $aTeams = null;
    $aEvents = null;

    if (isset($_GET['f']) && $_GET['f'] == 't')
    {
        //Search only teams
        $aTeams = FighterHandler::searchFighter(SEARCHRESULTS_QUERY);
    }
    else if (isset($_GET['f']) && $_GET['f'] == 'e')
    {
        //Search only events
        $aEvents = EventHandler::searchEvent(SEARCHRESULTS_QUERY);
    }
    else
    {
        //Search all
        $aTeams = FighterHandler::searchFighter(SEARCHRESULTS_QUERY);
        $aEvents = EventHandler::searchEvent(SEARCHRESULTS_QUERY);
    }

    if ($aTeams != null || $aEvents != null)
    {
        if ((sizeof($aTeams) + sizeof($aEvents)) == 1)
        {
            //One single search result
            if (sizeof($aTeams) == 1)
            {
                $oFighter = $aTeams[0];
                header('Location: /fighters/' . $oFighter->getFighterAsLinkString());
            }
            else
            {
                $oEvent = $aEvents[0];
                header('Location: /events/' . $oEvent->getEventAsLinkString());
            }
        }
        else if (sizeof($aTeams) + sizeof($aEvents) > 1)
        {
            //Multiple search results

            //Reduce teams lists if exceeding 30
            $iOriginalTeamsSize = sizeof($aTeams);
            if (sizeof($aTeams) > 25)
            {
                $aTeams = array_slice($aTeams, 0, 25);
            }

            //Reduce events lists if exceeding 30
            $iOriginalEventsSize = sizeof($aEvents);
            if (sizeof($aEvents) > 25)
            {
                $aEvents = array_slice($aEvents, 0, 25);
            }

            $sFighterResultList = '<div class="content-header" style="margin-top: 15px;">Fighters <span style="font-weight: normal;">(displaying ' . sizeof($aTeams) . ' out of ' . $iOriginalTeamsSize . ' matches)</span></div>';
            $sFighterResultList .= '<table class="content-list">';
            foreach ($aTeams as $oFighter)
            {

                $sFighterResultList .= '<tr>';
                $sFighterResultList .= '<td class="content-list-date"></td>
                                        <td><a href="/fighters/' . $oFighter->getFighterAsLinkString() . '">' . $oFighter->getNameAsString() . '</a></td>';
                $sFighterResultList .= '</tr>';
            }
            $sFighterResultList .= '</table>';


            $sEventResultList = '<div class="content-header" style="margin-top: 15px;">Events <span style="font-weight: normal;">(displaying ' . sizeof($aEvents) . ' out of ' . $iOriginalEventsSize . ' matches)</span></div>';
            $sEventResultList .= '<table class="content-list">';
            foreach ($aEvents as $oEvent)
            {

                $sEventResultList .= '<tr>';
                $sEventResultList .= '<td class="content-list-date">' . date('M jS Y', strtotime($oEvent->getDate())) . '</td>
                                                <td><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $oEvent->getName() . '</a></td>';
                $sEventResultList .= '</tr>';
            }
            $sEventResultList .= '</table>';

            define('SEARCHRESULTS_INFOTEXT', '<p style="font-size: 12px">Showing results for search query <b><i>' . $_GET['query'] . '</i></b>:</p>' . (sizeof($aTeams) > 0 ? $sFighterResultList : '') . (sizeof($aEvents) > 0 ? $sEventResultList : ''));
        }
        else
        {
            //No search results
        }
    }
}
else
{
    define('SEARCHRESULTS_QUERY', '');
    define('SEARCHRESULTS_INFOTEXT', '<p>Search query must contain at least <b>3</b> characters. Please try again.</p>');
}

define('PAGE_OVERRIDE_TITLE', 'Search results');
define('CURRENT_PAGE', 'search_results');

include_once('app/front/pages/inc.Top.php');
include_once('app/front/pages/page.searchresults.php');
include_once('app/front/pages/inc.Bottom.php');
?>