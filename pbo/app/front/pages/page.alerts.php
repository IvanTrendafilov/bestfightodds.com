<?php
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
?>

<div id="page-wrapper" style="max-width: 800px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="alert-form-il" id="alert-form-il">
                    <p>Alert me at e-mail &nbsp;<input type="text" name="alert-mail-il" id="alert-mail-il" value="" style="width: 195px;" />
                        when <select name="alert-bookie-il">
                            <option value="-1" selected>any bookie</option>
                            <?php
                            $aBookies = BookieHandler::getAllBookies();
                            foreach ($aBookies as $oBookie)
                            {
                                echo '<option value="' . $oBookie->getID() . '">' . $oBookie->getName() . '</option>';
                            }
                            ?>
                        </select>

                        posts odds for the following upcoming fight
                    </p>

                    <?php
                            $aEvents = EventHandler::getAllUpcomingEvents();
                            foreach ($aEvents as $oEvent)
                            {
                                $bOddRow = false; //Keeps track of row color in table
                                $aFights = EventHandler::getAllFightsForEventWithoutOdds($oEvent->getID());
                                if (sizeof($aFights) > 0)
                                {
                                    echo '<div class="content-header" style="margin-top: 16px;"><a href="/events/' . $oEvent->getEventAsLinkString() . '">' . strtoupper($oEvent->getName()) . '</a>';
                                    echo '</div>
                                    <table class="content-list">
                                    ';
                                    foreach ($aFights as $oFight)
                                    {
                                        echo '<tr>
                                                <td class="content-team-left"><a href="/fighters/' . $oFight->getFighterAsLinkString(1) . '">' . $oFight->getFighterAsString(1) . '</a></td><td class="content-vs-cell"> vs </td><td class="team-cell" style="text-align: left;"><a href="/fighters/' . $oFight->getFighterAsLinkString(2) . '">' . $oFight->getFighterAsString(2) . '</a></td>
                                                <td class="content-button-cell"><div class="alert-result-il"></div><div class="alert-loader alert-loader-il"></div><div class="button" data-mu="' . $oFight->getID() . '">Add alert</div></td>
                                            </tr>';
                                        $bOddRow = !$bOddRow;
                                    }
                                    echo '</table>';
                                }
                            }
                    ?>

                </form>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                To create an alert for scheduled/rumored matchups without odds, use the form to the left. To add an alert for a matchup with existing odds, click the  <img src="/img/icon-alert.png" alt="Alert symbol" style="width: 14px; height: 15px;" /> symbol on the <a href="/">front page</a>.<br /><br /><br />
                <img src="img/info-arrow.gif" class="img-note-box" /> Note that there is a limit of max 50 alerts per e-mail. When an alert is issued or expires you will be able to add a new one.<br /><br />
                To ensure that alerts show up properly in your inbox, add<br /><b>no-reply@proboxingodds.com</b> to your list of trusted senders.
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>
<script type="text/javascript">
window.onload = function () { 
    if (Cookies.get('bfo_alertmail') != null) {
        $('#alert-mail-il').val(Cookies.get('bfo_alertmail'));
    }
}
</script>
<div id="page-bottom"></div>