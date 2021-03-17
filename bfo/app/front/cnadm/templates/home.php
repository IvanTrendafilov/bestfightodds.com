<?php $this->layout('template', ['title' => 'Admin']) ?>

<script>
document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('clear-unmatched-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            }
        };
        fetch('/cnadm/api/clearunmatched', opts).then(function (response) {
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

<a href="/cnadm/alerts">Alerts stored: <?=$alertcount?></a>

<br><br>

<table><b>Average matched in the last 24 hours:</b> <br /><div style="font-size: 12px; display: inline"><tr>

<?php foreach($runstatus as $runstatus_entry): ?>

    <td><?=$runstatus_entry['name']?>: <div style="font-weight: bold; display: inline; color: <?=($runstatus_entry['average_matched'] <= 0 ? 'red;' : 'green;')?>"><?=$runstatus_entry['average_matched']?></div></td>

<?php endforeach ?>

</tr></div></table>

<br>

<?php $this->insert('partials/unmatched', ['bookies' => $bookies, 'unmatched_matchup_groups' => $unmatched_matchup_groups, 'unmatched_groups' => $unmatched_groups, 'unmatched' => $unmatched]) ?>