<?php $this->layout('base/layout', ['title' => 'Admin - Log Viewer', 'current_page' => $this->name->getName()]) ?>


<div class="card p-2">

    <div class="card-header">
        <h5 class="card-title">Other logs</h5>
    </div>
    <table class="table table-sm table-hover ">
        <thead>
            <tr>
                <th>Log</th>
                <th></th>

            </tr>
        </thead>
        <tbody class="bg-white">
        <tr>
                    <td>Scheduler</td>
                    <td><a href="/cnadm/log/scheduleparser"><button class="btn btn-primary">View log</button></a></td>
                </tr>
                <tr>
                    <td>Twitter integration</td>
                    <td><a href="/cnadm/log/twitter"><button class="btn btn-primary">View log</button></a></td>
                </tr>
                <tr>
                    <td>Facebook integration</td>
                    <td><a href="/cnadm/log/facebook"><button class="btn btn-primary">View log</button></a></td>
                </tr>

                <tr>
                    <td>SESMailer integration</td>
                    <td><a href="/cnadm/log/sesmailer"><button class="btn btn-primary">View log</button></a></td>
                </tr>

        </tbody>
    </table>
</div>