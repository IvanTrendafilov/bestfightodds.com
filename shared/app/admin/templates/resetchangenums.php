<?php $this->layout('base/layout', ['title' => 'Admin', 'current_page' => $this->name->getName()]) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.querySelectorAll('.reset-changenum-button').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();
                var opts = {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        bookie_id: parseInt(e.target.dataset.bookieid)
                    })
                };
                fetch('/cnadm/api/resetchangenums', opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            e.target.classList.add("btn-success");
                        }

                    });
            });
        });
        document.querySelectorAll('.save-bookie-button').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();
                var opts = {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        bookie_id: parseInt(e.target.dataset.bookieid),
                        url: document.getElementById('bookieurl-' + e.target.dataset.bookieid).value
                    })
                };
                fetch('/cnadm/api/updatebookie', opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            e.target.classList.add("btn-success");
                        }

                    });
            });
        });
    });
</script>

<div class="card p-2">
    <div class="card-header d-flex justify-content-between">
        <div class="d-flex">
            <h5 class="card-title align-self-center pr-5">Bookie settings</h5>
        </div>

        <div>
            <button class="reset-changenum-button btn btn-primary">Reset all changenums</button>
        </div>
    </div>
    <table class="table table-sm table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>URL</th>
                <th>Update</th>
                <th>Reset changenum</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            <?php foreach ($bookies as $bookie) : ?>
                <tr>
                    <td style="width: 5%"><?= $bookie['bookie_obj']->getID() ?></td>
                    <td style="width: 10%"><?= $bookie['bookie_obj']->getName() ?> 
                        <?php if (!$bookie['bookie_obj']->isActive()): ?>
                             (inactive)
                            <?php endif ?>
                    </td>
                    <td>
                        <input class="form-control" id="bookieurl-<?= $bookie['bookie_obj']->getID() ?>" type="text" value="<?= $bookie['bookie_obj']->getRefURL() ?>">
                    </td>
                    <td style="width: 15%"><button class="save-bookie-button btn btn-primary" data-bookieid="<?= $bookie['bookie_obj']->getID() ?>">Save URL</button></td>
                    <?php if (isset($bookie['changenum'])): ?>
                        <td style="width: 15%"><button class="reset-changenum-button btn btn-primary" data-bookieid="<?= $bookie['bookie_obj']->getID() ?>">Reset changenum</button></td>
                    <?php else: ?>
                        <td style="width: 15%"></td>
                    <?php endif ?>
                </tr>

            <?php endforeach ?>

        </tbody>
    </table>
</div>