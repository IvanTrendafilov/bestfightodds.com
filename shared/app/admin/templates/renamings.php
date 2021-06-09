<?php $this->layout('base/layout', ['title' => 'Admin - Renamings', 'current_page' => $this->name->getName()]) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.querySelectorAll('.update-event-button').forEach(item => {
            item.addEventListener('click', e => {
                var input = JSON.parse(e.target.dataset.event);
                e.preventDefault();
                var opts = {
                    method: 'PUT',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        event_id: parseInt(input.eventid),
                        event_name: input.eventname
                    })
                };
                fetch('/cnadm/api/events/' + parseInt(input.eventid), opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            e.target.closest('tr').style.color = '#ddd';
                            e.target.disabled = true;
                        }
                    });
            })
        })
    });
</script>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Event renamings</h5>
        <h6 class="card-subtitle text-muted">Sportsbooks metadata has suggested the following renamings
        </h6>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Event Date</th>
                    <th>Current name</th>
                    <th>New name</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody class="bg-white">
                <?php foreach ($recommendations as $recommendation) : ?>
                    <?php if ($recommendation['change']) : ?>
                        <tr>
                            <td><?= $recommendation['event']->getDate() ?></td>
                            <td><?= $recommendation['event']->getName() ?></td>
                            <td><?= $recommendation['new_name'] ?></td>
                            <td>
                                <button class="btn btn-primary update-event-button" data-event="<?= $this->e('{"eventid": "' . $recommendation['event']->getID() . '", "eventname": "' . $recommendation['new_name'] . '"}') ?>">Accept</button>
                            </td>
                        </tr>
                    <?php endif ?>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>