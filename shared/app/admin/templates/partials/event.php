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
                fetch('/cnadm/api/matchups/' + e.target.dataset.matchupid, opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            location.reload();
                        }
                    });
            })
        })

        //Delete matchup
        document.querySelectorAll('.delete-matchup-button').forEach(item => {
            item.addEventListener('click', e => {

                if (confirm("Really remove " + e.target.closest('tr').getElementsByTagName('td')[1].innerText + "?")) {
                    console.log(e.target);
                    e.preventDefault();
                    var opts = {
                        method: 'DELETE',
                        headers: {
                            'Content-type': 'application/json; charset=UTF-8'
                        },
                        body: JSON.stringify({
                            matchup_id: parseInt(e.target.dataset.matchupid),
                        })
                    };
                    fetch('/cnadm/api/matchups/' + e.target.dataset.matchupid, opts).then(function(response) {
                            return response.json();
                        })
                        .then(function(body) {
                            if (body.error == true) {
                                alert(body.msg);
                            } else {
                                //Successfully deleted. Hides row from table
                                e.target.closest('tr').style.display = 'none';
                            }
                        });
                } else {
                    return false;
                }
            })
        })
    });
</script>

<?php foreach ($events as $event) : ?>


    <div class="flex flex-col mt-8">
        <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200">
                <div class="bg-gray-300 flex p-3 text-gray-900">
                    <a href="#" name="event<?= $event['event_obj']->getID() ?>"></a>
                    <div style="<?= ($event['event_obj']->isDisplayed() ? '' : 'font-style: italic; color: #909090;') ?>"><?= $event['event_obj']->getName() ?>
                        <span style="color: #777777">-</span> <?= $event['event_obj']->getDate() ?> &nbsp;
                        <a href="/cnadm/events/<?= $event['event_obj']->getID() ?>">edit</a>
                    </div>
                    <div><span style="color: #ffffff"><?= sizeof($event['fights']) ?></span> <b><a href="/cnadm/events/<?= $event['event_obj']->getID() ?>">add</a></b></div>
                </div>
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Matchup</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Arbitrage</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php foreach ($event['fights'] as $fight) : ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500"><a href="/cnadm/matchups/<?= $fight['fight_obj']->getID() ?>"><?= $fight['fight_obj']->getID() ?></a></td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500 <?= $fight['fight_obj']->isMainEvent() ? ' font-bold' : '' ?>"><a href="/cnadm/fighters/<?= $fight['fight_obj']->getFighterID(1) ?>"><?= $fight['fight_obj']->getFighterAsString(1) ?></a> <span style="color: #777777">vs</span> <a href="/cnadm/fighters/<?= $fight['fight_obj']->getFighterID(2) ?>"><?= $fight['fight_obj']->getFighterAsString(2) ?></a></td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">
                                    <?php if (isset($fight['arbitrage_info'])) : ?>
                                        <?= $fight['arbitrage_info']['profit'] ?? '' ?>
                                    <?php endif ?>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">
                                    <button class="delete-matchup-button px-4 py-1 bg-red-500 text-gray-100 rounded-md hover:bg-red-400 focus:outline-none focus:bg-red-400" data-matchupid="<?= $fight['fight_obj']->getID() ?>">Delete</button>
                                    <button class="set-mainevent-button px-4 py-1 bg-blue-500 text-gray-100 rounded-md hover:bg-blue-400 focus:outline-none focus:bg-blue-400" data-matchupid="<?= $fight['fight_obj']->getID() ?>" data-mainevent="<?= $fight['fight_obj']->isMainEvent() ? '0' : '1' ?>"><?= $fight['fight_obj']->isMainEvent() ? 'Demote' : 'Promote' ?></button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endforeach ?>