<script>
document.addEventListener("DOMContentLoaded", function(event) { 
    //Set matchup as main event
    document.querySelectorAll('.set-mainevent-button').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            var opts = {
                method: 'PUT',
                headers: {
                    'Content-type': 'application/json; charset=UTF-8'
                },
                body: JSON.stringify({
                    matchup_id: parseInt(e.target.dataset.matchupid),
                    is_main_event: parseInt(e.target.dataset.mainevent),
                })
            };
            fetch('/cnadm/api/matchups/' + e.target.dataset.matchupid, opts).then(function (response) {
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
        })
    })

    //Delete matchup
    document.querySelectorAll('.delete-matchup-button').forEach(item => {
        item.addEventListener('click', e => {
            console.log(e.target.parentNode);
            e.preventDefault();
            var opts = {
                method: 'DELETE',
                headers: {
                    'Content-type': 'application/json; charset=UTF-8'
                },
                body: JSON.stringify({
                    matchup_id: parseInt(e.target.parentNode.dataset.matchupid),
                })
            };
            fetch('/cnadm/api/matchups/' + e.target.parentNode.dataset.matchupid, opts).then(function (response) {
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
        })
    })
});
</script>

<?php foreach($events as $event): ?>
   
    <table class="eventsOverview">

   <tr>
       <th colspan="6"><a href="#" name="event<?=$event['event_obj']->getID()?>"></a><div style="float: left; <?=($event['event_obj']->isDisplayed() ? '' : 'font-style: italic; color: #909090;')?>"><?=$event['event_obj']->getName()?> 
       <span style="color: #777777">-</span> <?=$event['event_obj']->getDate()?> &nbsp;
       <a href="/cnadm/events/<?=$event['event_obj']->getID()?>">edit</a></div>
       <div style="float: right; padding-right: 5px;"><span style="color: #ffffff"><?=sizeof($event['fights'])?></span> <b><a href="/cnadm/events/<?=$event['event_obj']->getID()?>">add</a></b></div>
   </th>
   </tr>

   <?php foreach($event['fights'] as $fight): ?>

           <tr>
               <td class="eventID"><a href="/cnadm/matchups/<?=$fight['fight_obj']->getID()?>"><?=$fight['fight_obj']->getID()?></a></td>
               <td class="fight <?=$fight['fight_obj']->isMainEvent() ? ' main-event' : ''?>"><a href="/cnadm/fighters/<?=$fight['fight_obj']->getFighterID(1)?>"><?=$fight['fight_obj']->getFighterAsString(1)?></a> <span style="color: #777777">vs</span> <a href="/cnadm/fighters/<?=$fight['fight_obj']->getFighterID(2)?>"><?=$fight['fight_obj']->getFighterAsString(2)?></a></td>
               <?php if (isset($fight['arbitrage_info'])): ?>
                    <td class="arbitrage <?=($fight['arbitrage_info']['profit'] && $fight['arbitrage_info']['profit'] > 1) ? 'positive' : ''?>"><?=$fight['arbitrage_info']['profit'] ?? ''?></td>
                <?php else: ?>
                    <td class="arbitrage"></td>
                <?php endif ?>
               <td class="imageLink"><a href="https://www.bestfightodds.com/fights/<?=$fight['fight_obj']->getID()?>.png">o</a></td>
               <td><a href="#" class="delete-matchup-button" data-matchupid="<?=$fight['fight_obj']->getID()?>" onclick="javascript:return confirm('Really remove <?=$fight['fight_obj']->getFighterAsString(1)?> vs <?=$fight['fight_obj']->getFighterAsString(2)?>?')"><b>x</b></a></td>
               <td class="mainEvent"><a href="#" class="set-mainevent-button" data-matchupid="<?=$fight['fight_obj']->getID()?>" data-mainevent="<?=$fight['fight_obj']->isMainEvent() ? '0' : '1'?>"><?=$fight['fight_obj']->isMainEvent() ? 'v' : '^'?></a></td>
           </tr>
   
   <?php endforeach ?>

</table>

<?php endforeach ?>

