<?php $this->layout('template', ['title' => 'Admin']) ?>

<script>
    document.querySelectorAll('.reset-changenum-button').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                bookie_id: this.dataset.bookieid
            })
        };
        fetch('/cnadm/api/resetchangenums', opts).then(function (response) {
            return response.json();
        })
        .then(function (body) {
            if (body.error == true) {
                alert(body.msg);
            }
            else {
				alert('ok!');
                location.reload();
            }
        });
    });
});
</script>

<?=date('H:i:s')?>
<br><br>

All <a href="/cnadm/api/resetchangenums" class="reset-changenum-button">Reset</a><br><br>
<br><br>

<?php foreach ($bookies as $bookie_id => $bookie_name): ?>

	<?=$bookie_name?> 
	<a href="#" class="reset-changenum-button" data-bookieid="<?=$bookie_id?>">Reset</a><br><br>

<?php endforeach ?>
