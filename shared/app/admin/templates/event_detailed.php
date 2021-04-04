<?php $this->layout('base/layout', ['title' => 'Admin']) ?>

<script>

document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('update-event-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'PUT',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                event_id: parseInt(this.form.querySelector('#event-id').value),
                event_name: this.form.querySelector('#event-name').value,
                event_date: this.form.querySelector('#event-date').value,
                event_display: this.form.querySelector('#event-display').checked
            })
        };
        fetch('/cnadm/api/events/' + parseInt(this.form.querySelector('#event-id').value), opts).then(function (response) {
            return response.json();
        })
        .then(function (body) {
            if (body.error == true) {
                alert(body.msg);
            }
            else {
                location.reload();
            }
        });
    });
});

</script>

<form>
    <div style="float: left; line-height: 20px;">
        Event ID:<br>
        Event name:<br>
        Event date:<br>
        Display event:
    </div>
    <div >
	    <?=$events[0]['event_obj']->getID()?><input type="hidden" id="event-id" value="<?=$events[0]['event_obj']->getID()?>"><br>
	    <input type="text" id="event-name" value="<?=$events[0]['event_obj']->getName()?>" size="40"><br>
        <input type="text" id="event-date" value="<?=$events[0]['event_obj']->getDate()?>"><br>
        <input type="checkbox" id="event-display" <?=($events[0]['event_obj']->isDisplayed() ? 'checked' : '')?>>
    </div><br>
	<input type="submit" value="Update event" id="update-event-button">
</form>
<br>
<div style="float: left;">

    <?php $this->insert('partials/event', ['events' => $events]) ?>

    <form name="addFightForm">
    <input type="hidden" id="event-id" value="<?=$events[0]['event_obj']->getID()?>" />
    <table class="eventsOverview">
    <tr style="background-color: #dddddd; ">
        <td class="fight" colspan="2"><input type="text" id="team1" /> <span style="color: #777777">vs</span> <input type="text" id="team2" /></td>
        <td colspan="2" style="text-align: center;"><input id="create-matchup-button" type="submit" value="Add" /></td>
    </tr>

</table>            
</form>

</div>