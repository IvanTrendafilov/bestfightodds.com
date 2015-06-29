<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.FighterHandler.php');


$aEvents = EventHandler::getAllEvents();

?>

<div id="page-wrapper">

    <div id="page-header">
    </div>
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
            <select name="event_id">
<?php

foreach ($aEvents as $oEvent)
{
?>
<option value="<?=$oEvent->getID();?>"><?=$oEvent->getName();?></option> 
<?php    
}
?>
</select>

            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <span style="background-color: #585858;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> = Opening line<br /><br />
                <span style="background-color: #a30000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> = Best line at fight time<br /><br />
                Odds displayed for each matchup are the best lines available for each fighter from one or more bookies
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>

<div id="page-bottom"></div>
