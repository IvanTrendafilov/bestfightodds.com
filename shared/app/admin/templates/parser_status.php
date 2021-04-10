<h3>Parser status</h3>
<div class="row">
    <?php foreach ($runstatus as $runstatus_entry) : ?>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="/cnadm/parserlogs/<?= strtolower($runstatus_entry['name']) ?>">
                <div class="card">
                    <div class="card-body">
                        <p class="card-text"><?= $runstatus_entry['name'] ?> - <?= $runstatus_entry['average_matched'] ?></p>
                        <span class="badge <?= ($runstatus_entry['average_matched'] <= 0 ? 'bg-danger' : 'bg-success') ?>">Status</span>
                    </div>
                </div>

            </a>
        </div>
    <?php endforeach ?>
</div>