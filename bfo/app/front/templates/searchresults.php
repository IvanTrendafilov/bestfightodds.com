<?php $this->layout('template', ['title' => 'Search results', 'current_page' => 'archive']) ?>

<div id="page-wrapper"  style="max-width: 700px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="search_fighters" style="margin-top: 10px; margin-bottom: 22px;" method="get" action="/search">
                    <p>Search the archive for events/fighters:<input type="text" name="query" value="" style="width: 195px; margin-left: 5px;" /><input type="submit" value="&#128269;" class="archive_search_button" style="margin-left: -1px;"/></p>
                </form>
    
                <?php if(strlen($search_query) >= 3): ?>

                    <?php if(count($teams_results) + count($events_results) > 1): ?>

                        <p style="font-size: 12px">Showing results for search query <b><i><?=$search_query?></i></b>:</p>

                        <?php if(count($teams_results) > 0): ?>

                            <div class="content-header" style="margin-top: 15px;">Fighters <span style="font-weight: normal;">(displaying <?=count($teams_results)?> out of <?=$teams_totalsize?> matches)</span></div>
                            <table class="content-list">
                            <?php foreach ($teams_results as $team): ?>
                
                                <tr>
                                    <td class="content-list-date"></td>
                                    <td><a href="/fighters/<?=$team->getFighterAsLinkString()?>"><?=$team->getNameAsString()?></a></td>
                                </tr>

                            <?php endforeach ?>
                            </table>

                        <?php endif ?>

                        <?php if(count($events_results) > 0): ?>

                            <div class="content-header" style="margin-top: 15px;">Events <span style="font-weight: normal;">(displaying <?=count($events_results)?> out of <?=$events_totalsize?> matches)</span></div>
                            <table class="content-list">
                            <?php foreach ($events_results as $event): ?>
                                <tr>
                                    <td class="content-list-date"><?=date('M jS Y', strtotime($event->getDate()))?></td>
                                    <td><a href="/events/<?=$event->getEventAsLinkString()?>"><?=$event->getName()?></a></td>
                                </tr>
                            <?php endforeach ?>
                            </table>

                        <?php endif ?>

                    <?php else: ?>
                        <p style="">No matching fighters or events found for search query <b><i><?=$search_query?></i></b></p>

                    <?php endif ?>

                <?php else: ?>

                    <p>Search query must contain at least <b>3</b> characters. Please try again.</p>

                <?php endif ?>

            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <img src="img/info.gif" class="img-info-box" /> Results are limited to 25 events/fighters. Refine your search criteria to narrow down specific results
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>

<div id="page-bottom"></div>