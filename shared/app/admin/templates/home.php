<?php $this->layout('base/layout', ['title' => 'Admin', 'current_page' => $this->name->getName()]) ?>

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

<?php $this->insert('parser_status', ['runstatus' => $runstatus, 'lastfinishes' => $lastfinishes, 'oddsjob_finished' => $oddsjob_finished]) ?>

<?php $this->insert('partials/unmatched', ['bookies' => $bookies, 'unmatched_matchup_groups' => $unmatched_matchup_groups, 'unmatched_groups' => $unmatched_groups, 'unmatched' => $unmatched, 'unmatched_props_matchups_count' => $unmatched_props_matchups_count, 'unmatched_props_templates_count' => $unmatched_props_templates_count]) ?>

<a href="/cnadm/alerts">Alerts stored: <?= $alertcount ?></a>