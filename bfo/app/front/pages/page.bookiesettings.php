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
                            if (isset($aCookieSettings[$oBookie->getID()]) && (bool) $aCookieSettings[$oBookie->getID()] == true)
                            {
                                $foundhidden = true;
                            }
                            echo '<td class="content-list-title"><input class="bsetting" type="checkbox" ' . ($foundhidden == true ? '' : 'checked') . ' data-bookie="' . $oBookie->getID() . '"></td>';
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