<script>
document.addEventListener("DOMContentLoaded", function(event) { 
    document.querySelectorAll('.create-event-with-matchups').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();

            event_row = document.querySelector('#' + e.target.dataset.eventlink);
            structure = JSON.parse(event_row.dataset.create);

            var opts = {
                method: 'POST',      
                headers: {
                    'Content-type': 'application/json; charset=UTF-8'
                },
                body: JSON.stringify({
                    event_name: structure.name,
                    event_date: structure.date,
                    event_hidden: false
                })
            };
            fetch('/cnadm/api/events', opts).then(function (response) {
                return response.json();
            })
            .then(function (body) {
                if (body.error == true) {
                    event_row.classList.add("failed")
                }
                else {
                    event_row.classList.add("success")
                    //Run through matchups and add them to the event that was created
                    document.querySelectorAll(".matchup-row[data-eventlink='" + e.target.dataset.eventlink + "']").forEach(function (item) {
                        structure = JSON.parse(item.dataset.create);
                        var opts = {
                            method: 'POST',      
                            headers: {
                                'Content-type': 'application/json; charset=UTF-8'
                            },
                            body: JSON.stringify({
                                event_id: body.event_id,
                                team1_name: structure.inteam1,
                                team2_name: structure.inteam2
                            })
                        };
                        fetch('/cnadm/api/matchups', opts).then(function (response) {
                            return response.json();
                        })
                        .then(function (body) {
                            if (body.error == true) {
                                item.classList.add("failed")
                            }
                            else {
                                item.classList.add("success")
                            }
                        });
                    });




                }
                
            });
		});
	});

    document.querySelectorAll('.create-matchups-for-event').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const event_id =  e.target.dataset.eventid;
            //Run through matchups and add them to the event
            document.querySelectorAll(".matchup-row[data-eventlink='" + e.target.dataset.eventlink + "']").forEach(function (item) {
                structure = JSON.parse(item.dataset.create);
                var opts = {
                    method: 'POST',      
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        event_id: event_id,
                        team1_name: structure.inteam1,
                        team2_name: structure.inteam2
                    })
                };
                fetch('/cnadm/api/matchups', opts).then(function (response) {
                    return response.json();
                })
                .then(function (body) {
                    if (body.error == true) {
                        item.classList.add("failed")
                    }
                    else {
                        item.classList.add("success")
                    }
                });
            });
		});
	});
});

/*
myNameSpace = function(){
  var current = null;
  function init(){...}
  function change(){...}
  function verify(){...}
  return{
    init:init,
    set:change
  }
}();*/


</script>


<a href="#" id="clear-unmatched-button">Clear unmatched table</a><br /><br />

<b>Matchups:</b> <br />

<table class="genericTable">

<?php foreach ($unmatched_groups as $unmatched_group): ?>
    <?php if (isset($unmatched_group[0]['view_extras'])): ?>
        <tr class="event event-group" id="event<?=isset($i) ? ++$i : $i = 1?>" data-create="<?=$this->e('{"name": "' . $unmatched_group[0]['view_extras']['event_name_reduced'] . '", "date": "' . $unmatched_group[0]['view_extras']['event_date_formatted'] . '"}')?>">
    <?php else: ?>
        <tr class="event event-group" id="event<?=isset($i) ? ++$i : $i = 1?>">
    <?php endif ?>
        <td></td><td></td>
        <td data-date=""><b><?=$unmatched_group[0]['metadata']['event_name'] ?? '' ?> / <?=$unmatched_group[0]['view_extras']['event_name_reduced'] ?? ''?></b> (<?=$unmatched_group[0]['view_extras']['event_date_formatted']?>)

            <?php if(isset($unmatched_group[0]['view_extras']['event_match']['id'])): ?>
                    Match: <?=$unmatched_group[0]['view_extras']['event_match']['name']?> (<?=$unmatched_group[0]['view_extras']['event_match']['date']?>) [<a href="#" class="create-matchups-for-event" data-eventid="<?=$unmatched_group[0]['view_extras']['event_match']['id']?>" data-eventlink="event<?=$i?>">Create all matchups below for matched event</a>]
            <?php else: ?>
                    No match.. [<a href="/cnadm/events?in_event_name=<?=$unmatched_group[0]['view_extras']['event_name_reduced']?>&in_event_date=<?=$unmatched_group[0]['view_extras']['event_date_formatted']?>">create</a>] [<a href="http://www.google.se/search?q=tapology <?=$unmatched_group[0]['view_extras']['event_name_reduced']?>">google</a>]
            <?php endif ?>

        </td>
    </tr>
    
    <?php foreach ($unmatched_group as $unmatched_item): ?>
        <?php if ($unmatched_item['type'] == 0): ?>
            <tr class="matchup-row" data-create="<?=$this->e('{"inteam1": "' . $unmatched_item['view_indata1'] . '", "inteam2": "' . $unmatched_item['view_indata2'] . '"}')?>" data-eventlink="event<?=$i?>"><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td>
            <?=$this->e($unmatched_item['matchup'], 'strtolower|ucwords')?></td><td>[<a href="/cnadm/newmatchup?inteam1=<?=$unmatched_item['view_indata1']?>&inteam2=<?=$unmatched_item['view_indata2']?>&ineventid=<?=$unmatched_group[0]['view_extras']['event_match']['id'] ?? '' ?>">add</a>] [<a href="http://www.google.se/search?q=tapology <?=$unmatched_item['matchup']?>">google</a>] 
            </td></tr>
        <?php endif ?>
    <?php endforeach ?>
    <tr><td></td><td></td><td></td>
        <td>
            <?php if (isset($unmatched_group[0]['view_extras']['event_name_reduced'])): ?>
                <a href="#" class="create-event-with-matchups" data-eventlink="event<?=$i?>">Create event and matchups</a>
            <?php else: ?>
                &nbsp;
            <?php endif ?>
        </td></tr>
<?php endforeach ?>
</table><br />

<b>Props without matchups</b>: <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
    <?php if ($unmatched_item['type'] == 1): ?>
        <tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td><?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/propcorrelation?bookie_id=<?=$unmatched_item['bookie_id']?>&input_prop=<?=urlencode($unmatched_item['matchup'])?>">link manually</a>]</td></tr>				
	<?php endif ?>
<?php endforeach ?>
</table><br />

<b>Props without templates:</b> <br />
<table class="genericTable">
<?php foreach ($unmatched as $unmatched_item): ?>
	<?php if ($unmatched_item['type'] == 2): ?>
        <tr><td><?=date("Y-m-d H:i:s", strtotime($unmatched_item['log_date']))?></td><td><b><?=$bookies[$unmatched_item['bookie_id']]?></b></td><td><?=$unmatched_item['matchup']?></td><td>[<a href="/cnadm/proptemplate?in_bookie_id=<?=$unmatched_item['bookie_id']?>&in_template=<?=urlencode($unmatched_item['view_indata1'])?>&in_negtemplate=<?=urlencode($unmatched_item['view_indata2'])?>">add</a>]</td></tr>
	<?php endif ?>
<?php endforeach ?>
</table><br />
