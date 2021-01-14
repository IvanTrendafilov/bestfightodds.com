<?php
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
?>

<div id="page-wrapper" style="max-width: 800px;">
    <div id="page-container">
        <div id="page-inner-wrapper">
            <div id="page-content">

            <div class="content-header">Recently completed events</div>
                <table class="content-list">

                    <?php 
                    //Note that bookie handler will give us the bookies in position order
                    $aBookies = BookieHandler::getAllBookies();
                    $aCookieSettings = [];
                    if (isset($_COOKIE['bfo_hidebookies']))
                    {
                        $aCookieSettings = json_decode($_COOKIE['bfo_hidebookies']);
                    }
                    ?>

                    <?php foreach ($aBookies as $oBookie) : ?>
                        <tr>
                            <td class="content-list-date"><?php echo $oBookie->getName() . '<br />'; ?></td>
                            <?php
                            $foundhidden = false;
                            foreach($aCookieSettings as $bookieSetting)
                            {
                                if ($bookieSettings['bookie_id']['hidden'] == true)
                                {
                                    $foundhidden = true;
                                }
                            }

                            echo '<td class="content-list-title"><input type="checkbox" ' . ($foundhidden == true ? '' : 'checked') . '></td>';

                            ?>
                            
                        </tr>
                    <?php endforeach; ?>

                </table>

            </div>
        </div>

    </div>
</div>
<script type="text/javascript">
window.onload = function () { 
    if (Cookies.get('bfo_hidebookies') != null) {
        $('#alert-mail-il').val(Cookies.get('bfo_alertmail'));
    }
}
</script>
<div id="page-bottom"></div>