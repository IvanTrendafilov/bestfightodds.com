<?php $this->layout('base/layout', ['title' => 'Admin']) ?>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.getElementById('clear-unmatched-button').addEventListener('click', function(e) {
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
</script>

<a href="/cnadm/alerts">Alerts stored: <?= $alertcount ?></a>

<br><br>

<?php $this->insert('parser_status', ['runstatus' => $runstatus]) ?>

<br><br>

<?php $this->insert('partials/unmatched', ['bookies' => $bookies, 'unmatched_matchup_groups' => $unmatched_matchup_groups, 'unmatched_groups' => $unmatched_groups, 'unmatched' => $unmatched]) ?>