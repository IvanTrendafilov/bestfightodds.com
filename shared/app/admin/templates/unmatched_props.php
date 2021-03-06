<?php $this->layout('base/layout', ['title' => 'Admin - Unmatched Props', 'current_page' => $this->name->getName()]) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.querySelectorAll('.clear-unmatched-button').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var opts = {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    }
                };
                fetch('/cnadm/api/clearunmatched', opts).then(function(response) {
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
    });
</script>

<div class="card">
    <div class="card-header d-flex justify-content-between">
    <div class="d-flex">
        <h5 class="card-title">Props without matchups</h5>
    </div>
    <button class="clear-unmatched-button btn btn-primary pl-5">Clear all unmatched</button>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Last seen</th>
                    <th>Bookie</th>
                    <th>Prop name</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($unmatched_matchups_col as $unmatched_item) : ?>
                    <?php if ($unmatched_item['type'] == 1) : ?>
                        <tr>
                            <td><?= date("Y-m-d H:i:s", strtotime($unmatched_item['log_date'])) ?></td>
                            <td><b><?= $bookies[$unmatched_item['bookie_id']] ?></b></td>
                            <td><?= $unmatched_item['matchup'] ?></td>
                            <td><a href="/cnadm/propcorrelation?bookie_id=<?= $unmatched_item['bookie_id'] ?>&input_prop=<?= urlencode($unmatched_item['matchup']) ?>"><button class="btn btn-primary">Link manually</button></a></td>
                        </tr>
                    <?php endif ?>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Props without templates:</h5>
        </h6>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Last seen</th>
                    <th>Bookie</th>
                    <th>Prop name</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($unmatched_templates_col as $unmatched_item) : ?>
                    <?php if ($unmatched_item['type'] == 2) : ?>
                        <tr>
                            <td><?= date("Y-m-d H:i:s", strtotime($unmatched_item['log_date'])) ?></td>
                            <td><b><?= $bookies[$unmatched_item['bookie_id']] ?></b></td>
                            <td><?= $unmatched_item['matchup'] ?></td>
                            <td><a href="/cnadm/proptemplates?in_bookie_id=<?= $unmatched_item['bookie_id'] ?>&in_template=<?= urlencode($unmatched_item['view_indata1']) ?>&in_negtemplate=<?= urlencode($unmatched_item['view_indata2']) ?>"><button class="btn btn-primary">Add</button></a></td>
                        </tr>
                    <?php endif ?>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>