<?php
require_once('lib/bfocore/general/class.EventHandler.php');
?>
<script language="JavaScript" type="text/javascript" src="js/webmasters_4.js"></script> 

<div id="page-wrapper">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="webLink" method="get" action="" style="margin-top: 10px;">
                    Select the fight or event you want to display:  <select id="webFight" style="width: 345px;" onchange="fightSelected();"><option value="0">(select an event or a fight)</option>
                        <?php
                        $aEvents = EventHandler::getAllUpcomingEvents();
                        foreach ($aEvents as $oEvent)
                        {

                            $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
                            if (sizeof($aFights) > 0)
                            {
                                echo '<option value="-' . $oEvent->getID() . '">' . $oEvent->getName() . '</option>';
                                foreach ($aFights as $oFight)
                                {
                                    echo '<option value="' . $oFight->getID() . '">&nbsp;&nbsp;&nbsp;' . $oFight->getFighterAsString(1) . ' vs ' . $oFight->getFighterAsString(2) . '</option>';
                                }
                                echo '<option value="0"></option>';
                            }
                        }
                        ?>
                    </select>
                    <div id="webFields" style="display: none; margin-top: 25px;">
                        <div style="float: left; width: 120px; font-weight: 500; ">Type</div><div style="margin: 0px 0 15px 0;"><input type="radio" name="webLineType" id="webLineType" value="current" checked="checked" onchange="fightSelected();"> Current best odds &nbsp;&nbsp;&nbsp;<input type="radio" name="webLineType" id="webLineType" value="opening" onchange="fightSelected();"> Opening odds </div>
                        <div style="float: left; width: 120px; font-weight: 500; ">Format</div><div style="margin: 0px 0 15px 0;"><input type="radio" name="webLineFormat" id="webLineFormat" value="1" checked="checked" onchange="fightSelected();"> Moneyline &nbsp;&nbsp;&nbsp;<input type="radio" name="webLineFormat" id="webLineFormat" value="2" onchange="fightSelected();"> Decimal </div>
                        <div style="float: left; width: 120px; font-weight: 500; ">Preview</div><div style="margin: 15px 0 15px 0;"><a href="https://www.bestfightodds.com"><img name="webTestImage" id="webTestImageID" src="/img/ajax-loader.gif" alt="Preview" style="border: 0px; color: #000000; text-decoration: none; " /></a></div>
                        <div style="float: left; width: 120px; font-weight: 500; ">HTML code</div><div style="margin: 15px 0 15px 0;"><textarea readonly="readonly" id="webHTML" cols="105" rows="6"></textarea></div>
                        <div style="float: left; width: 120px; font-weight: 500; ">UBB code</div><div style="margin: 15px 0 15px 0;"><textarea readonly="readonly" id="webForum" cols="105" rows="2"></textarea></div>
                        <div style="float: left; width: 120px; font-weight: 500; ">Direct image link</div><div style="margin: 15px 0 15px 0;"><input type="text" readonly="readonly" id="webImageLink" style="width: 300px;" /></div>
                    </div>

                </form>
                <img src="/img/ajax-loader.gif" class="hidden-image" alt="loading" />
            </div>
        </div>
        <div class="content-sidebar">
            <img src="img/info.gif" class="img-info-box" /> This feature lets you to display betting lines on your own website, blog or forum posts<br /><br />Simply select the fight or event that you want to display and then copy and paste the generated url/code into the desired location<br /><br /><br /><img src="img/info-arrow.gif" class="img-note-box" /> Note that the image for the current best line will be updated automatically as the betting line changes
        </div>
        <div class="clear"></div>
    </div>
</div>
<div id="page-bottom"></div>


