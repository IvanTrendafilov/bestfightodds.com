<?php $this->layout('template', ['title' => 'Admin']) ?>

<script>

document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('add-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                event_id: this.form.querySelector('#event-id').value,
                team1_name: this.form.querySelector('#team1').value,
                team2_name: this.form.querySelector('#team2').value
            })
        };
        fetch('/cnadm/api/fights', opts).then(function (response) {
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

<div style="float: left;">

    <?php $this->insert('partials/event', ['events' => $events]) ?>

    <form name="addFightForm">
    <input type="hidden" id="event-id" value="<?=$events[0]['event_obj']->getID()?>" />
    <table class="eventsOverview">
    <tr style="background-color: #dddddd; ">
        <td class="fight" colspan="2"><input type="text" id="team1" /> <span style="color: #777777">vs</span> <input type="text" id="team2" /></td>
        <td colspan="2" style="text-align: center;"><input id="add-button" type="submit" value="Add" /></td>
    </tr>

</table>            
</form>

</div>