<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>

<div class="flex flex-col mt-8">
    <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Event Date</th>
                        <th>Matchup</th>
                        <th>Bookie</th>
                        <th>First seen</th>
                        <th>Last seen</th>
                        <th>Hour diff</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody class="bg-white">
                    <?php foreach ($flagged as $flagged_item) : ?>
                        <tr>
                            <td><?= $flagged_item['event_obj']->getName() ?></td>
                            <td><?= $flagged_item['event_obj']->getDate() ?></td>
                            <td><?= $flagged_item['fight_obj']->getTeamAsString(1) ?> vs. <?= $flagged_item['fight_obj']->getTeamAsString(2) ?></td>
                            <td>
                                <?php foreach ($flagged_item['bookies'] as $bookie) : ?>
                                    <?= $bookie ?>,
                                <?php endforeach ?>

                            </td>
                            <td><?= $flagged_item['initial_flagdate'] ?></td>
                            <td><?= $flagged_item['last_flagdate'] ?></td>
                            <td><?= $flagged_item['hours_diff'] ?></td>
                            <td>
                                <a href="#">Action</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>