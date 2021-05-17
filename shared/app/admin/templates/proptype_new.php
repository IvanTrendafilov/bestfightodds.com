<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer', 'current_page' => $this->name->getName()]) ?>

<script>
    function switchFields(field1, field2) {
        storeVal = document.getElementById(field1).value;
        document.getElementById(field1).value = document.getElementById(field2).value;
        document.getElementById(field2).value = storeVal;
        return false;
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        document.getElementById('create-proptype-button').addEventListener('click', function(e) {
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
        });
    });
</script>


<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">New prop type - (use &#60;T&#62; to indicate team name if needed)</h5>
    </div>
    <div class="card-body">
        <form>
            <div class="row">
            <div class="col-6">
                <input class="form-control" id="propdesc" type="text" placeholder="" value="">
            </div>
            </div>
            <div class="row">
            <div class="col-6">
                <input class="form-control" id="propdesc" type="text" placeholder="" value="">
                </div>
            </div>
            <div class="row">
            <div class="col-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="event_hidden">
                    <label class="form-check-label" for="event_hidden">Event prop type</label>
                </div>
            </div>
            <div class="col-2">
                    <button class="btn btn-primary" id="create-proptype-button">Add</button>
                </div>
            </div>
            <div class="row">

            </div>
        </form>
    </div>
</div>
