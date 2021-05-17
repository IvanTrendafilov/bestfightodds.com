<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer', 'current_page' => $this->name->getName()]) ?>

<script>
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
Add new prop template (use &#60;T&#62; to specify team names (or event name) and &#60;*&#62; for wildcards (must exist) or &#60;?&#62; for optional wildcards (optional). Use the latter two with caution!)<br><br>

<form>

    Bookie:
    <select id="in_bookie_id">
        <option value="0" selected>- pick one -</option>
        <?php foreach ($bookies as $bookie) : ?>
            <option value="<?= $bookie->getID() ?>" <?= (isset($in_bookie_id) && $in_bookie_id == $bookie->getID()) ? 'selected' : '' ?>><?= $bookie->getName() ?></option>
        <?php endforeach ?>
    </select><br><br>

    Prop Type:
    <select id="in_proptype_id">
        <option value="0" selected>- pick one -</option>
        <?php foreach ($prop_types as $prop_type) : ?>
            <option value="<?= $prop_type->getID() ?>"><?= $prop_type->getPropDesc() ?></option>
        <?php endforeach ?>
    </select><br><br>

    Fields Type:
    <select id="in_fieldstype_id">
        <option value="0" selected>- pick one -</option>
        <option value="1">lastname vs lastname (koscheck vs miller)</option>
        <option value="2">fullname vs fullname (e.g josh koscheck vs dan miller)</option>
        <option value="3">single lastname (koscheck)</option>
        <option value="4">full name (josh koscheck)</option>
        <option value="5">first letter.lastname (e.g. j.koscheck)</option>
        <option value="6">first letter.lastname vs first letter.lastname (e.g. j.koscheck vs d.miller)</option>
        <option value="7">first letter lastname vs first letter lastname (e.g. j koscheck vs d miller)</option>
        <option value="8">first letter lastname (e.g. j koscheck)</option>
    </select><br><br>

    Template: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type="text" id="in_proptemplate" size="140" value="<?= isset($in_template) ? $in_template : '' ?>"> <a href="#" onclick="switchFields('templateField','templateNegField')">Switch</a><br>
    Neg Template: <input type="text" id="in_negproptemplate" size="140" value="<?= isset($in_negtemplate) ? $in_negtemplate : '' ?>" /><br><br>

    <input type="submit" id="create-template-button" value="Add template" onclick="javascript:return confirm('Are you sure?')" />

</form>