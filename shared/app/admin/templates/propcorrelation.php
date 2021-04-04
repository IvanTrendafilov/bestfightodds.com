<?php $this->layout('base/layout', ['title' => 'Admin']) ?>

<script>

document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('create-correlation-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                bookie_id: parseInt(document.querySelector('#in_bookie_id').value),
                matchup_id: parseInt(document.querySelector('#in_matchup_id').value),
                correlation: document.querySelector('#in_correlation').value,
            })
        };
        fetch('/cnadm/api/propcorrelation', opts).then(function (response) {
            return response.json();
        })
        .then(function (body) {
            if (body.error == true) {
                alert(body.msg);
            }
            else {
                window.location.href = '/cnadm/';
            }
        });
    });
});

</script>

<form>
Correlation: <input type="text" id="in_correlation" size="70" value="<?=$input_prop?>"><br><br>
Bookie: <input type="hidden" id="in_bookie_id" size="70" value="<?=$bookie_id?>"> <?=$bookie_id?>
<br><br>
Matchup: <select id="in_matchup_id">

<?php foreach($events as $event): ?>

    <option value="-1"><?=$event['event_obj']->getName()?></option>

    <?php foreach($event['matchups'] as $matchup): ?>
        
        <option value="<?=$matchup['matchup_obj']->getID()?>">&nbsp;&nbsp;&nbsp;<?=$matchup['matchup_obj']->getTeamAsString(1)?> vs <?=$matchup['matchup_obj']->getTeamAsString(2)?></option>
        
    <?php endforeach ?>        

    <option value="0"></option>

<?php endforeach ?>

</select><br><br>

<input type="submit" id="create-correlation-button" value="Add correlation">
</form>

