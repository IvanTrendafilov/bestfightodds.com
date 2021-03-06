<?php $this->layout('base/layout', ['title' => 'Admin', 'current_page' => $this->name->getName()]) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.getElementById('update-event-button').addEventListener('click', function(e) {
            e.preventDefault();
            var opts = {
                method: 'PUT',
                headers: {
                    'Content-type': 'application/json; charset=UTF-8'
                },
                body: JSON.stringify({
                    event_id: parseInt(this.form.querySelector('#event-id').value),
                    event_name: this.form.querySelector('#event-name').value,
                    event_date: this.form.querySelector('#event-date').value,
                    event_display: this.form.querySelector('#event-display').checked
                })
            };
            fetch('/cnadm/api/events/' + parseInt(this.form.querySelector('#event-id').value), opts).then(function(response) {
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
        document.getElementById('delete-event-button').addEventListener('click', function(e) {
            if (confirm("Are you sure you want to delete this event?")) {
                e.preventDefault();
                var opts = {
                    method: 'DELETE',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        event_id: parseInt(e.target.dataset.eventid),
                    })
                };
                fetch('/cnadm/api/events/' + e.target.dataset.eventid, opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            //Successfully deleted. Hides row from table
                            window.location = '/cnadm/events';
                        }
                    });
            } else {
                return false;
            }
        });
    });
</script>


<div class="card">
    <div class="card-body">
        <form>
            <div class="row">
                <div class="col-sm">
                    <input type="hidden" id="event-id" value="<?= $events[0]['event_obj']->getID() ?>">
                    <input class="form-control" id="event-name" type="text" placeholder="Event name" value="<?= $events[0]['event_obj']->getName() ?>">
                </div>
                <div class="col-sm">
                    <input class="form-control" type="text" placeholder="Date" id="event-date" value="<?= $events[0]['event_obj']->getDate() ?>">
                </div>
                <div class="col-sm-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="event-display" <?= ($events[0]['event_obj']->isDisplayed() ? 'checked' : '') ?>>
                        <label class="form-check-label" for="event-display">Display</label>
                    </div>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary" id="update-event-button">Save changes</button>
                </div>

            </div>
        </form>
    </div>
</div>

<?php $this->insert('partials/event', ['events' => $events, 'hide_header' => true]) ?>

<div class="card">
    <div class="card-body">
        <form name="addFightForm">
            <input type="hidden" id="event-id" value="<?= $events[0]['event_obj']->getID() ?>" />
            <div class="row">
                <div class="col-sm">
                    <input class="form-control" id="team1" type="text">
                </div>
                <div class="col-sm-1">
                    vs.
                </div>

                <div class="col-sm">
                    <input class="form-control" id="team2" type="text">
                </div>

                <div class="col-sm-3">
                    <button class="btn btn-primary" id="create-matchup-button">Add matchup</button>
                </div>
            </div>

        </form>

    </div>
</div>

<div class="card">
    <div class="card-body">
        <form name="deleteEventForm">
            <button class="btn btn-danger" id="delete-event-button" data-eventid="<?= $events[0]['event_obj']->getID() ?>">Delete event</button>
        </form>
    </div>
</div>