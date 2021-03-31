<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>

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


<div class="mt-8">
    <div class="mt-4">
        <div class="p-6 bg-white rounded-md shadow-md">
            <h2 class="text-lg text-gray-700 font-semibold capitalize">New event</h2>

            <form>
                <div class="grid grid-cols-3 sm:grid-cols-3 gap-6 mt-4">
                    <div>
                        <label class="text-gray-700" for="name">Name</label>
                        <input id="event_name" value="<?= $in_event_name ?>" class="form-input w-full mt-2 rounded-md focus:border-indigo-600" type="text">
                    </div>

                    <div>
                        <label class="text-gray-700" for="email">Date</label>
                        <input class="form-input w-full mt-2 rounded-md focus:border-indigo-600" type="text" id="event_date" value="<?= $in_event_date != '' ?   $in_event_date : date('Y-m-d') ?>">
                    </div>

                    <div>
                        <label class="inline-flex items-center ml-3">
                            <input type="checkbox" class="form-checkbox h-5 w-5 text-red-600" checked="" id="event_hidden"><span class="ml-2 text-gray-700">Hidden</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button class="px-4 py-2 bg-gray-800 text-gray-200 rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700" id="new-event-button">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div>
    <div style="float: left;">
        <?php $this->insert('partials/event', ['events' => $events]) ?>
    </div>
    <div>
        <p style="font-size: 10px; line-height: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;Quick jump to</b><br />
            <?php foreach ($events as $event) : ?>
                &nbsp;&nbsp;&nbsp;<a href="#event<?= $event['event_obj']->getID() ?>" style="color: #000000;"><?= $event['event_obj']->getName() ?></a><br />
            <?php endforeach ?>
        </p>
    </div>
</div>