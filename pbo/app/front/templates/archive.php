<?php $this->layout('template', ['title' => 'Archive', 'current_page' => 'archive']) ?>

<div id="page-wrapper" style="max-width: 750px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="search_fighters" style="margin-bottom: 14px;" method="get" action="/search">
                    <p>Search the archive for events/fighters:<input type="text" name="query" value="" style="width: 195px; margin-left: 5px;" /><input type="submit" value="&#128269;" class="archive_search_button" style="margin-left: -1px;" /></p>
                </form>
                <div class="content-header">Recently concluded matchups</div>
                <table class="content-list">
                    <?php foreach ($recent_events as $event) : ?>
                        <?php for ($i = 0; $i < count($event_matchups[$event->getID()]); $i++) : ?>
                            <tr>
                                <td class="content-list-title" style="width: 100px;"><?php if ($i == 0) : ?><a href="/events/<?= $event->getEventAsLinkString() ?>"><?= date('F jS', strtotime($event->getDate())); ?></a><?php endif; ?></td>
                                <td class="content-list-title"><a href="/events/<?= $event->getEventAsLinkString() ?>" style="font-weight: 400"><?= $event_matchups[$event->getID()][$i]->getTeamAsString(1); ?> vs. <?= $event_matchups[$event->getID()][$i]->getTeamAsString(2); ?></a></td>
                            </tr>
                        <?php endfor ?>
                    <?php endforeach ?>
                </table>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <img src="img/info.gif" class="img-info-box" /> All odds posted on the site are stored in the archive. The archive contains thousands of matchups and fighter profiles dating back to 2016 when the site was launched.
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>

<div id="page-bottom"></div>