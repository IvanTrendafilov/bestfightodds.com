<div class="row">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <?php foreach ($runstatus as $runstatus_entry) : ?>
                    <a href="/cnadm/parserlogs/<?= strtolower($runstatus_entry['name']) ?>"><span class="badge <?= ($runstatus_entry['average_matched'] <= 0 ? 'bg-danger' : 'bg-success') ?>"><?= $runstatus_entry['name'] ?></span></a>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</div>