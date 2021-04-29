<?php
require_once('lib/bfocore/general/class.EventHandler.php');
?>
<div id="page-wrapper" style="max-width: 750px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="search_fighters" style="margin-bottom: 14px;" method="get" action="/search">
                    <p>Search the archive for events/fighters:<input type="text" name="query" value="" style="width: 195px; margin-left: 5px;" /><input type="submit" value="&#128269;" class="archive_search_button" style="margin-left: -1px;"/></p>
                </form>
                <div class="content-header">Recently concluded fights</div>
                <table class="content-list">

                    <?php 
                    $aEvents = EventHandler::getRecentEvents(30, 0);
                    ?>

                    <?php foreach($aEvents as $oEvent): ?>
                        <?php $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);

                        for ($iX = 0; $iX < count($aFights); $iX++) : ?>
                        <tr>
                            <td class="content-list-title" style="width: 100px;"><?php if ($iX == 0) : ?><a href="/events/<?php echo $oEvent->getEventAsLinkString() ?>"><?php echo date('F jS', strtotime($oEvent->getDate())); ?></a><?php endif; ?></td>
                            <td class="content-list-title"><a href="/events/<?php echo $oEvent->getEventAsLinkString() ?>" style="font-weight: 400"><?php echo $aFights[$iX]->getTeamAsString(1); ?> vs. <?php echo $aFights[$iX]->getTeamAsString(2); ?></a></td>
                        </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>

                </table>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <img src="img/info.gif" class="img-info-box" /> All odds posted on the site are stored in the archive. The archive contains thousands of matchups and fighter profiles dating back to 2016 when the site was launched.
            </p>
        </div>
        <div class="clear"></div>
    </div>

</div>

<div id="page-bottom"></div>