<?php $this->layout('base/layout', ['title' => 'Admin - View Prop Templates', 'current_page' => $this->name->getName()]) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        //Delete prop template
        document.querySelectorAll('.delete-pt-button').forEach(item => {
            item.addEventListener('click', e => {

                if (confirm("Are you sure?")) {

                    var input = JSON.parse(e.target.dataset.li);
                    e.preventDefault();
                    var opts = {
                        method: 'DELETE',
                        headers: {
                            'Content-type': 'application/json; charset=UTF-8'
                        },
                        body: JSON.stringify({
                            template_id: parseInt(e.target.dataset.li)
                        })
                    };
                    fetch('/cnadm/api/proptemplates/' + e.target.dataset.li, opts).then(function(response) {
                            return response.json();
                        })
                        .then(function(body) {
                            if (body.error == true) {
                                alert(body.msg);
                            } else {
                                //Successfully deleted. Hides row from table
                                e.target.closest('tr').style.color = '#ddd';
                                e.target.disabled = true;
                            }
                        });
                }
            })
        })
    });
</script>

<?php foreach ($bookies as $bookie) : ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><?= $this->e($bookie['bookie']->getName()) ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Template</th>
                        <th>Proptype ID</th>
                        <th>Field Type</th>
                        <th>Last used</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody class="bg-white">
                    <?php foreach ($bookie['templates'] as $template) : ?>
                        <tr>
                            <td><?= $template->getID() ?></td>
                            <td>
                                <div><?= str_replace(' / ', '</div><div>', $template->toString()) ?></div>
                            </td>
                            <td><?= $template->getPropTypeID() ?></td>
                            <td>e.g: <?= $template->getFieldsTypeAsExample() ?></td>
                            <td><?= $template->getLastUsedDate() ?></td>
                            <td><button class="btn btn-danger delete-pt-button" data-li="<?= $template->getID() ?>">Delete</button></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endforeach ?>