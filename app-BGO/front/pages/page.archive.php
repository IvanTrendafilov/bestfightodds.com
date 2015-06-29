<?php
require_once('lib/bfocore/general/class.EventHandler.php');
?>
<div id="page-wrapper">
    <div id="page-header">
        Archive
    </div>
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="search_fighters" style="margin-top: 10px; margin-bottom: 22px;" method="get" action="/search">
                    <p>Search the archive for events/fighters: &nbsp;<input type="text" name="query" value="" style="width: 195px;" class="archive_search_field" />&nbsp;&nbsp;<input type="submit" value="Search" class="archive_search_button" /></p>
                </form>
                <div class="table-header-mini">Recently completed events</div>
                <table class="alerts-matchup-table">

                    <?php
                    $bOddRow = false; //Keeps track of row color in table
                    //$aEvents = EventHandler::getRecentEvents(10, (isset($_GET['o']) ? $_GET['o'] : 0));
                    $aEvents = EventHandler::getRecentEvents(20, 0);
                    foreach ($aEvents as $oEvent)
                    {
                        echo '<tr ' . ($bOddRow == true ? ' class="alerts-row-odd" ' : '') . '>
                                                <td style="width: 110px;">' . date('F jS Y', strtotime($oEvent->getDate())) . '</td>
                                                <td><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . $oEvent->getName() . '</a></td>
                                            </tr>';
                        $bOddRow = !$bOddRow;
                    }

                    //echo '<tr><td colspan="2">' . ((isset($_GET['o']) && $_GET['o'] != '0') ? ('<input type="submit" value="Newer" class="abutton" style="float: left;" onclick="window.location=\'/archive?o=' . (isset($_GET['o']) ? ((int) $_GET['o']) - 10 : '10') . '\';"/>') : '') . (count($aEvents) == 10 ? ('<input type="submit" value="Older" class="abutton" style="float: right;" onclick="window.location=\'/archive?o=' . (isset($_GET['o']) ? ((int) $_GET['o']) + 10 : '10') . '\';"/>') : '') . '</td></tr>';
                    ?>

                </table>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <img src="img/info.gif" class="img-info-box" /> All odds posted on the site are stored in the archive. The archive contains thousands of matchups and fighter profiles dating back to 2007 when the site was launched.
            </p>
        </div>
        <div class="clear"></div>
    </div>

</div>

<div id="page-bottom"></div>