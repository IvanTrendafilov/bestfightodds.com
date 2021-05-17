<?php $this->layout('base/layout', ['title' => 'Admin', 'current_page' => $this->name->getName()]) ?>

<script>
document.addEventListener("DOMContentLoaded", function(event) { 
    document.getElementById('update-fighter-button').addEventListener('click', function(e) {
        e.preventDefault();
        var opts = {
            method: 'PUT',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                fighter_id: parseInt(this.form.querySelector('#fighter_id').value),
                alt_name: document.querySelector('#alt_name').value,
				twitter_handle: document.querySelector('#twitter_handle').value,
            })
        };
        fetch('/cnadm/api/fighters/' + this.form.querySelector('#fighter_id').value, opts).then(function (response) {
            return response.json();
        })
        .then(function (body) {
            if (body.error == true) {
                alert(body.msg);
            }
            else {
                window.location.href = '/cnadm/fighters/' + document.querySelector('#fighter_id').value;
            }
        });
    });
});
</script>

<form>
	Fighter: <?=$fighter_obj->getNameAsString()?> &nbsp; aka &nbsp;
	<input type="hidden" id="fighter_id" value="<?=$fighter_obj->getID()?>"/>	
	<input type="text" id="alt_name" style="width: 200px;"/>
	<br>
	Existing alt names:<br>
	<?php foreach ($altnames as $altname): ?>
		<?=$altname?><br>
	<?php endforeach ?>

	<br>
	Twitter handle 
		[<a href="http://www.google.se/search?q=site:twitter.com <?=$fighter_obj->getName()?>">google</a>]
		 &nbsp;
	<input type="text" id="twitter_handle" style="width: 200px;" value="<?=$twitter_handle ?? ''?>"/>
	<br><br>
	<input type="submit" value="Save" id="update-fighter-button">
</form>
