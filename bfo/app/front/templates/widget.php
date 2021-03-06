<?php $this->layout('template', ['title' => 'On your website/blog', 'current_page' => 'widget']) ?>

<div id="page-wrapper" style="max-width: 850px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="webLink" method="get" action="" style="margin-top: 10px;">
                    Select the fight or event you want to display on your website, blog or anywhere else:  <select id="webFight" style="width: 305px;" onchange="fightSelected();"><option value="0">(select an event or a fight)</option>
                        <?php foreach ($events as $event): ?>
                            <option value="-<?=$event['event_obj']->getID()?>"><?=$event['event_obj']->getName()?></option>
                                <?php foreach ($event['matchups'] as $matchup): ?>
                                    <option value="<?=$matchup->getID()?>">&nbsp;&nbsp;<?=$matchup->getFighterAsString(1)?> vs <?=$matchup->getFighterAsString(2)?></option>
                                <?php endforeach ?>
                            <option value="0"></option>
                        <?php endforeach ?>
                    </select>
                    <div id="webFields" style="display: none; margin-top: 25px;">
                        <div class="links-line">Type</div><div style="margin: 0px 0 15px 0;"><input type="radio" name="webLineType" id="webLineType" value="current" checked="checked" onchange="fightSelected();"> Current best odds &nbsp;&nbsp;&nbsp;<input type="radio" name="webLineType" id="webLineType" value="opening" onchange="fightSelected();"> Opening odds </div>
                        <div class="links-line">Format</div><div style="margin: 0px 0 15px 0;"><input type="radio" name="webLineFormat" id="webLineFormat" value="1" checked="checked" onchange="fightSelected();"> Moneyline &nbsp;&nbsp;&nbsp;<input type="radio" name="webLineFormat" id="webLineFormat" value="2" onchange="fightSelected();"> Decimal </div>
                        <div class="links-line">Preview</div><div style="margin: 15px 0 15px 0;"><a href="https://www.bestfightodds.com"><img name="webTestImage" id="webTestImageID" alt="Preview" style="border: 0px; color: #000000; text-decoration: none; " /></a></div>
                        <div class="links-line">HTML code</div><div style="margin: 15px 0 15px 0;"><textarea readonly="readonly" id="webHTML" cols="105" rows="6"></textarea></div>
                        <div class="links-line">UBB code</div><div style="margin: 15px 0 15px 0;"><textarea readonly="readonly" id="webForum" cols="105" rows="2"></textarea></div>
                        <div class="links-line">Direct image link</div><div style="margin: 15px 0 15px 0;"><input type="text" readonly="readonly" id="webImageLink" style="width: 300px;" /></div>
                    </div>

                </form>
                <img src="/img/loading.gif" class="hidden-image" alt="loading" />
            </div>
        </div>
        <div class="content-sidebar">
            Note that the image for the current best odds will be updated automatically as the betting line changes
        </div>
        <div class="clear"></div>
    </div>
</div>
<div id="page-bottom"></div>


