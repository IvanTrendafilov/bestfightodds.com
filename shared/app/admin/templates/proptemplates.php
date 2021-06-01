<?php $this->layout('base/layout', ['title' => 'Admin - View Prop Templates', 'current_page' => $this->name->getName()]) ?>

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
                            <td><button class="btn btn-danger" data-li="<?= $template->getID() ?>">Delete</button></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endforeach ?>