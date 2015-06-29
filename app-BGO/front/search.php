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
            $bOddRow = false; //Keeps track of row color in table
            $sFighterResultList = '<div class="table-header-mini" style="padding: 5px; font-weight: bold; font-size: 12px;">Fighters (' . sizeof($aTeams) . ')</div>';
            $sFighterResultList .= '<table class="alerts-matchup-table">';
            foreach ($aTeams as $oFighter)
            {

                $sFighterResultList .= '<tr ' . ($bOddRow == true ? ' class="alerts-row-odd" ' : '') . '>';
                $sFighterResultList .= '<td style="width: 120px;"></td>
                                        <td><a href="/fighters/' . $oFighter->getFighterAsLinkString() . '">' . $oFighter->getNameAsString() . '</a></td>';
                $sFighterResultList .= '</tr>';
                $bOddRow = !$bOddRow;
            }
            $sFighterResultList .= '</table>';


            $bOddRow = false; //Keeps track of row color in table
            $sEventResultList = '<div class="table-header-mini" style="padding: 5px; font-weight: bold; font-size: 12px;">Events (' . sizeof($aEvents) . ')</div>';
            $sEventResultList .= '<table class="alerts-matchup-table">';
            foreach ($aEvents as $oEvent)
            {

                $sEventResultList .= '<tr ' . ($bOddRow == true ? ' class="alerts-row-odd" ' : '') . '>';
                $sEventResultList .= '<td style="width: 120px;">' . date('F jS Y', strtotime($oEvent->getDate())) . '</td>
                                                <td><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $oEvent->getName() . '</a></td>';
                $sEventResultList .= '</tr>';
                $bOddRow = !$bOddRow;
            }
            $sEventResultList .= '</table>';

            define('SEARCHRESULTS_INFOTEXT', '<p style="font-size: 12px">Showing results for search query <b><i>' . $_GET['query'] . '</i></b>:<br /><br /></p>' . (sizeof($aTeams) > 0 ? $sFighterResultList : '') . (sizeof($aEvents) > 0 ? $sEventResultList : ''));
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