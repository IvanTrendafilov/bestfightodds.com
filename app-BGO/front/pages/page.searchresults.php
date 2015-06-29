<?php
/** The following constants can be passed to this page to override content:
  SEARCHRESULTS_INFOTEXT
  SEARCHRESULTS_QUERY (must be set)
 *
 *
 */
?>
<div id="page-wrapper">

    <div id="page-header">
        Archive - Search results
    </div>
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">
                <form name="search_fighters" style="margin-top: 10px; margin-bottom: 22px;" method="get" action="/search">
                    <p>Search for archive for events/fighters: &nbsp;<input type="text" name="query" value="" style="width: 195px;" class="archive_search_field" />&nbsp;&nbsp;<input type="submit" value="Search" class="archive_search_button" /></p>
                </form>
                <?php
                if (@defined('SEARCHRESULTS_INFOTEXT'))
                {
                    echo SEARCHRESULTS_INFOTEXT;
                }
                else
                {
                    echo '<p style="">No matching fighters or events found for search query <b><i>' . SEARCHRESULTS_QUERY . '</i></b><br /><br /></p>';
                }
                ?>
            </div>
        </div>
        <div class="content-sidebar">
            <p>
                <img src="img/info.gif" class="img-info-box" /> All odds posted on the site is stored in the archive. The archive contains thousands of matchups dating back to 2007 when the site was first launched.
            </p>
        </div>
        <div class="clear"></div>
    </div>
</div>

<div id="page-bottom"></div>