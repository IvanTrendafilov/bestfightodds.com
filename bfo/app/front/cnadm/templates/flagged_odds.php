<?php $this->layout('template', ['title' => 'Admin - Events']) ?>

<script>

document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('new-event-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                event_name: this.form.querySelector('#event_name').value,
                event_date: this.form.querySelector('#event_date').value,
                event_hidden: this.form.querySelector('#event_hidden').checked
            })
        };
        fetch('/cnadm/api/events', opts).then(function (response) {
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

<div>
<form>
    New event: <input id="event_name" value="<?=$in_event_name?>"> on <input id="event_date" value="<?=$in_event_date != '' ?   $in_event_date : date('Y-m-d')?>"> Hide: <input type="checkbox" id="event_hidden"> <input type="submit" value="Add" id="new-event-button">
</form>
</div>
<div>
    <div style="float: left;">
        <?php $this->insert('partials/event', ['events' => $events]) ?>
    </div>
    <div>
        <p style="font-size: 10px; line-height: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;Quick jump to</b><br />
            <?php foreach($events as $event): ?>
                &nbsp;&nbsp;&nbsp;<a href="#event<?=$event['event_obj']->getID()?>" style="color: #000000;"><?=$event['event_obj']->getName()?></a><br />
            <?php endforeach ?>
        </p>
    </div>
</div>