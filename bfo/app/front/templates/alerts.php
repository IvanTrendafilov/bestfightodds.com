<?php $this->layout('template', ['title' => 'Alerts', 'current_page' => 'alerts']) ?>

<div id="page-wrapper" style="max-width: 800px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="alert-form-il" id="alert-form-il">
                    <p>Alert me at e-mail &nbsp;<input type="text" name="alert-mail-il" id="alert-mail-il" value="<?=$in_alertmail?>" style="width: 195px;" />
                        when <select name="alert-bookie-il">
                            <option value="-1" selected>any bookie</option>
                            <?php foreach ($bookies as $bookie): ?>
                                <option value="<?=$bookie->getID()?>"><?=$bookie->getName()?></option>
                            <?php endforeach ?>
                        </select>

                        posts odds for the following upcoming fight
                    </p>
                            <?php foreach ($events as $event): ?>
                                    <div class="content-header" style="margin-top: 16px;"><a href="/events/<?=$event['event_obj']->getEventAsLinkString()?>"><?=strtoupper($event['event_obj']->getName())?></a>

                                    <?php if (strtoupper($event['event_obj']->getName()) != 'FUTURE EVENTS'): //If name is FUTURE EVENTS, do not add date ?>
                                        <span style="font-weight: normal;"> - <?=date('M jS', strtotime($event['event_obj']->getDate()))?></span>
                                    <?php endif ?>
                                    </div>

                                    <table class="content-list">
                                        <?php foreach ($event['matchups'] as $matchup): ?>
                                            <tr>
                                                <td class="content-team-left"><a href="/fighters/<?=$matchup->getFighterAsLinkString(1)?>"><?=$matchup->getFighterAsString(1)?></a></td><td class="content-vs-cell"> vs </td><td class="team-cell" style="text-align: left;"><a href="/fighters/<?=$matchup->getFighterAsLinkString(2)?>"><?=$matchup->getFighterAsString(2)?></a></td>
                                                <td class="content-button-cell"><div class="alert-result-il"></div><div class="alert-loader alert-loader-il"></div><div class="button" data-mu="<?=$matchup->getID()?>">Add alert</div></td>
                                            </tr>
                                        <?php endforeach ?>
                                    </table>
                            <?php endforeach ?>
                </form>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                To create an alert for scheduled/rumored matchups without odds, use the form above. To add an alert for a matchup with existing odds, click the bell symbol on the <a href="/">front page</a>.<br /><br />
                Note that there is a limit of max 50 alerts per e-mail. When an alert is issued or expires you will be able to add a new one.<br /><br />
                To ensure that alerts show up properly in your inbox, add <b>no-reply@bestfightodds.com</b> to your list of trusted senders.
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>
<div id="page-bottom"></div>