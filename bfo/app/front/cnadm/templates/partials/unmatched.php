<script>
document.addEventListener("DOMContentLoaded", function(event) { 
    document.querySelectorAll('.create-event-with-matchups').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();

            var structure = JSON.parse(e.target.dataset.create);
                     

        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                bookie_id: e.target.dataset.bookieid
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
                //Run through matchups and add them to the event that was created
                
                location.reload();
            }
        	
    		});



            
		});
	});
});

</script>


<a href="#" id="clear-unmatched-button">Clear unmatched table</a><br /><br />

<b>Matchups:</b> <br />

<table class="genericTable">

<?php foreach ($unmatched_groups as $unmatched_group): ?>
    <tr class="event">
        <td></td><td></td>
        <td data-date=""><b><?=$unmatched_group[0]['metadata']['event_name']?> / <?=$unmatched_group[0]['view_extras']['event_name_reduced']?></b> (<?=$unmatched_group[0]['view_extras']['event_date_formatted']?>)

            <?php if(isset($unmatched_group[0]['view_extras']['event_match']['id'])): ?>
                    Match: <?=$unmatched_group[0]['view_extras']['event_match']['name']?> (<?=$unmatched_group[0]['view_extras']['event_match']['date']?>) [<a href="/cnadm/?p=addNewFightForm&inEventID=<?=$unmatched_group[0]['view_extras']['event_match']['id']?>&inFighter1=<?=$unmatched_group[0]['view_indata1']?>&inFighter2=<?=$unmatched_group[0]['view_indata2']?> . '">add</a>]
            <?php else: ?>
                    No match.. [<a href="/cnadm/?p=addNewEventForm&eventName=<?=$unmatched_group[0]['view_extras']['event_name_reduced']?>&eventDate=<?=$unmatched_group[0]['view_extras']['event_date_formatted']?>">create</a>] [<a href="http://www.google.se/search?q=tapology <?=$unmatched_group[0]['view_extras']['event_name_reduced']?>">google</a>]
            <?php endif ?>

        </td>
    </tr>
    <?php foreach ($unmatched_group as $unmatched_item): ?>
        <?php if ($unmatched_item['type'] == 0): ?>
            <tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td>
            <?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/newmatchup?inteam1=<?=$unmatched_item['view_indata1']?>&inteam2=<?=$unmatched_item['view_indata2']?>">add</a>] [<a href="http://www.google.se/search?q=tapology <?=$unmatched_item['matchup']?>">google</a>] 

            </td></tr>
        <?php endif ?>
    <?php endforeach ?>
    <tr><td></td><td></td><td></td><td>Create all</td></tr>
<?php endforeach ?>
</table><br />

<b>Props without matchups</b>: <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
    <?php if ($unmatched_item['type'] == 1): ?>
        <tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td><?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/propcorrelation?bookie_id=<?=$unmatched_item['bookie_id']?>&input_prop=<?=$unmatched_item['matchup']?>">link manually</a>]</td></tr>				
	<?php endif ?>
<?php endforeach ?>
</table><br />

<b>Props without templates:</b> <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
	<?php if ($unmatched_item['type'] == 2): ?>
        <tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td><?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/protemplate?in_bookie_id=<?=$unmatched_item['bookie_id']?>&in_template=<?=$unmatched_item['view_indata1']?>&in_negtemplate=<?=$unmatched_item['view_indata1']?>">add</a>]</td></tr>
	<?php endif ?>
<?php endforeach ?>
</table><br />
