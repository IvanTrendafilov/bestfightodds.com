<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                Parsed new matchups/props in last 24 hours:
                <?php foreach ($runstatus as $runstatus_entry) : ?>
                    <a href="/cnadm/parserlogs/<?= strtolower($runstatus_entry['name']) ?>"><span class="badge <?= ($runstatus_entry['average_matched'] <= 0 ? 'bg-danger' : 'bg-success') ?>"><?= $runstatus_entry['name'] ?></span></a>
                <?php endforeach ?><br>
                Has finished in last 5 minutes:
                <?php foreach ($lastfinishes as $bookie => $status) : ?>
                    <a href="/cnadm/parserlogs/<?= strtolower($bookie) ?>"><span class="badge <?= ($status == false ? 'bg-danger' : 'bg-success') ?>"><?= $bookie ?></span></a>
                <?php endforeach ?><br>
                Odds job finished in last 5 minutes:
                <a href="/cnadm/logs/latest">
                    <?php if ($oddsjob_finished) : ?>
                </a><span class="badge bg-success">Yes</span>
            <?php else : ?>
                <span class="badge bg-danger">No</span>
            <?php endif ?>
            </a>
            </div>
        </div>
    </div>
</div>