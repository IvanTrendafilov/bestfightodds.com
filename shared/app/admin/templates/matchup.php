<?php $this->layout('base/layout', ['title' => 'Admin']) ?>

<script>
document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('update-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'PUT',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                matchup_id: document.querySelector('#matchup_id').value,
                event_id: document.querySelector('#event_id').value,
            })
        };
        fetch('/cnadm/api/matchups/' + document.querySelector('#matchup_id').value, opts).then(function (response) {
            return response.json();
        })
        .then(function (body) {
            if (body.error == true) {
                alert(body.msg);
            }
            else {
                window.location.href = '/cnadm/events/' + document.querySelector('#event_id').value;
                
            }
        });
    });
});
</script>

<form method="put">
  <input type="hidden" id="matchup_id" value="<?=$matchup->getID()?>" />
    Fight: <?=$matchup->getFighterAsString(1)?> vs <?=$matchup->getFighterAsString(2)?><br />
	Fight ID: <?=$matchup->getID()?><br />
	Fight event: <select id="event_id">

    <?php foreach ($events as $event): ?>
        <option value="<?=$event->getID()?>" <?=($event->getID() == $matchup->getEventID() ? 'selected' : '')?>><?=$event->getName()?> - <?=$event->getDate()?></option>
    <?php endforeach ?>

    </select><br /><br />
	<input type="submit" id="update-button" value="Update fight" />
</form>
