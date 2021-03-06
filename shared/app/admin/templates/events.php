<?php $this->layout('base/layout', ['title' => 'Admin - Events', 'current_page' => $this->name->getName()]) ?>

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
            fetch('/cnadm/api/events', opts).then(function(response) {
                    return response.json();
                })
                .then(function(body) {
                    if (body.error == true) {
                        alert(body.msg);
                    } else {
                        location.reload();
                    }
                });
        });
    });
</script>

<div class="card">
    <div class="card-body">
        <form>
            <div class="row">
                <div class="col-sm">
                    <input class="form-control" id="event_name" type="text" placeholder="New event name" value="<?= $in_event_name ?>">
                </div>
                <div class="col-sm">
                    <input class="form-control" type="text" placeholder="Date" id="event_date" value="<?= $in_event_date != '' ?   $in_event_date : date('Y-m-d') ?>">
                </div>
                <div class="col-sm-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="event_hidden">
                        <label class="form-check-label" for="event_hidden">Hidden</label>
                    </div>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary" id="new-event-button">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">

    <div class="card col-xl-4 col-xxl-4">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Quick jump to</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach ($events as $event) : ?>
                    <tr>
                        <td><a href="#event<?= $event['event_obj']->getID() ?>" ><?= $event['event_obj']->getName() ?></a></td>
                        <td><?= $event['event_obj']->getDate() ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <div class="col-xl-8 col-xxl-8">
        <div>
            <?php $this->insert('partials/event', ['events' => $events]) ?>
        </div>
    </div>

</div>