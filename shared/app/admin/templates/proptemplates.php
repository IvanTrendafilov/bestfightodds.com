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

    function switchFields(field1, field2) {
        storeVal = document.getElementById(field1).value;
        document.getElementById(field1).value = document.getElementById(field2).value;
        document.getElementById(field2).value = storeVal;
        return false;
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        document.getElementById('create-template-button').addEventListener('click', function(e) {
            if (confirm("Are you sure?")) {
                e.preventDefault();
                var opts = {
                    method: 'POST',
                    headers: {
                        'Content-type': 'application/json; charset=UTF-8'
                    },
                    body: JSON.stringify({
                        bookie_id: parseInt(document.querySelector('#in_bookie_id').value),
                        proptemplate: document.querySelector('#in_proptemplate').value,
                        negproptemplate: document.querySelector('#in_negproptemplate').value,
                        proptype_id: parseInt(document.querySelector('#in_proptype_id').value),
                        fieldstype_id: parseInt(document.querySelector('#in_fieldstype_id').value),
                    })
                };
                fetch('/cnadm/api/proptemplates', opts).then(function(response) {
                        return response.json();
                    })
                    .then(function(body) {
                        if (body.error == true) {
                            alert(body.msg);
                        } else {
                            window.location.href = '/cnadm/proptemplates';
                        }
                    });
            }
        });

    });
</script>

<div class="card">
    <div class="card-body">
        <form>
            <div class="row">
                <div class="col-sm-2">
                    <label class="form-label">Bookie</label>
                    <select  class="form-control" id="in_bookie_id">
                        <option value="0" selected>- pick one -</option>
                        <?php foreach ($bookies_select as $bookie) : ?>
                            <option value="<?= $bookie->getID() ?>" <?= (isset($in_bookie_id) && $in_bookie_id == $bookie->getID()) ? 'selected' : '' ?>><?= $bookie->getName() ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-sm">
                    <label class="form-label">Prop type</label>
                    <select class="form-control"  id="in_proptype_id">
                        <option value="0" selected>- pick one -</option>
                        <?php foreach ($prop_types as $prop_type) : ?>
                            <option value="<?= $prop_type->getID() ?>"><?= $prop_type->getPropDesc() ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-sm">
                    <label class="form-label">Fields type</label>
                    <select class="form-control" id="in_fieldstype_id">
                        <option value="0" selected>- pick one -</option>
                        <option value="1">lastname vs lastname (koscheck vs miller)</option>
                        <option value="2">fullname vs fullname (e.g josh koscheck vs dan miller)</option>
                        <option value="3">single lastname (koscheck)</option>
                        <option value="4">full name (josh koscheck)</option>
                        <option value="5">first letter.lastname (e.g. j.koscheck)</option>
                        <option value="6">first letter.lastname vs first letter.lastname (e.g. j.koscheck vs d.miller)</option>
                        <option value="7">first letter lastname vs first letter lastname (e.g. j koscheck vs d miller)</option>
                        <option value="8">first letter lastname (e.g. j koscheck)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Template</label>
                    <input class="form-control" type="text" id="in_proptemplate" size="140" value="<?= isset($in_template) ? $in_template : '' ?>"> <a href="#" onclick="switchFields('templateField','templateNegField')">Switch</a>
                </div>
                <div>
                    <label class="form-label">Neg Template</label>
                    <input class="form-control" type="text" id="in_negproptemplate" size="140" value="<?= isset($in_negtemplate) ? $in_negtemplate : '' ?>" />
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary" id="create-template-button">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php foreach ($bookies as $bookie) : ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><?= $this->e($bookie['bookie']->getName()) ?></h5>
        </div>
        <div class="table-responsive p-2">
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