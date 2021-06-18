<?php foreach ($events as $event_data): ?>

    <?php $this->insert('partials/event', array_merge($event_data, ['bookies' => $bookies, 'alerts_enabled' => true])) ?>

<?php endforeach ?>

<div id="alert-window" class="popup-window"><div class="popup-header" id="alert-header"><div></div><a href="#" class="cd-popup-close">&#10005;</a></div><div id="alert-area">
        <form id="alert-form">Alert me at e-mail <input type="text" name="alert-mail" id="alert-mail"><br>when the odds reaches <input type="text" name="alert-odds" id="alert-odds"> or better<br>at <select name="alert-bookie">
            <option value="-1">any bookie</option>
            <?php foreach ($bookies as $bookie): ?>
                <option value="<?=$bookie->getID()?>"><?=$bookie->getName()?></option>
            <?php endforeach ?>
        </select><br><div id="alert-button-container"><input type="hidden" name="tn"><input type="hidden" name="m">
            <div class="alert-loader"></div>
            <div class="alert-result">&nbsp;</div>
        <input type="submit" value="Add alert" id="alert-submit"></div></form></div>
    </div>