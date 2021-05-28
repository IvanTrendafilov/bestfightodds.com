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
    });
</script>

<div class="card">

    <div class="card-header">
        <h5 class="card-title">Reset parser changenums</h5>
        <div>
            <button class="reset-changenum-button btn btn-primary">Reset all</button>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Bookie</th>
                <th>Action</th>

            </tr>
        </thead>
        <tbody class="bg-white">
            <?php foreach ($bookies_changenums as $bookie) : ?>
                <tr>
                    <td><?= $bookie['bookie_name'] ?></td>
                    <td><button class="reset-changenum-button btn btn-primary" data-bookieid="<?= $bookie['bookie_id'] ?>">Reset</button></td>
                </tr>

            <?php endforeach ?>

        </tbody>
    </table>
</div>