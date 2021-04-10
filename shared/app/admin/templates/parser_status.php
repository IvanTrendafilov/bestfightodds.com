<h3>Parser status</h3>
<?php foreach ($runstatus as $runstatus_entry) : ?>
    <a href="/cnadm/parserlogs/<?= strtolower($runstatus_entry['name']) ?>">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= $runstatus_entry['name'] ?></h5>
            </div>
            <div class="card-body">
                <p class="card-text"><?= $runstatus_entry['average_matched'] ?></p>
                <span class="badge <?= ($runstatus_entry['average_matched'] <= 0 ? 'bg-danger' : 'bg-success') ?>">Status</span>
            </div>
        </div>

    </a>

<?php endforeach ?>