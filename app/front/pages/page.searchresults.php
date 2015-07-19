<?php
/** The following constants can be passed to this page to override content:
  SEARCHRESULTS_INFOTEXT
  SEARCHRESULTS_QUERY (must be set)
 *
 *
 */
?>
<div id="page-wrapper"  style="max-width: 800px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="search_fighters" style="margin-top: 10px; margin-bottom: 22px;" method="get" action="/search">
                    <p>Search the archive for events/fighters:<input type="text" name="query" value="" style="width: 195px; margin-left: 5px;" /><input type="submit" value="&#128269;" class="archive_search_button" style="margin-left: -1px;"/></p>
                </form>
                <?php
                if (@defined('SEARCHRESULTS_INFOTEXT'))
                {
                    echo SEARCHRESULTS_INFOTEXT;
                }
                else
                {
                    echo '<p style="">No matching fighters or events found for search query <b><i>' . SEARCHRESULTS_QUERY . '</i></b></p>';
                }
                ?>
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