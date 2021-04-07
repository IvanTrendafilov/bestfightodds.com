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
            <form class="w-full max-w-xl">
                <div class="flex flex-wrap -mx-3 ">
                    <div class="w-full md:w-1/2 px-3 md:mb-0">
                        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-first-name">
                            Name
                        </label>
                        <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white" id="event_name" type="text" placeholder="UFC Fight Night" value="<?= $in_event_name ?>">
                    </div>
                    <div class="w-full md:w-1/4 px-3">
                        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                            Date
                        </label>
                        <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="event_date" type="text" value="<?= $in_event_date != '' ?   $in_event_date : date('Y-m-d') ?>">
                    </div>
                    <div class="w-full md:w-1/12 px-3">
                        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                            Hidden
                        </label>
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-red-600 mt-3" id="event_hidden">
                    </div>
                    <div class="w-full md:w-1/12 px-3">
                        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                        </label>
                        <button class="px-4 py-2 bg-gray-800 text-gray-200 rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700 mt-4" id="new-event-button">Add</button>
                    </div>
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