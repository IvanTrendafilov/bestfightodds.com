<?php

session_start();

if ($_POST['aupwd'] == 'Free123!')
{
    $_SESSION['user_id'] = 'cnadm';
    header("Location: /cnadm/index.php");
}
else
{
    ?>
    <html>
        <form method="post" action="/cnadm/login.php">
            <input type="password" name="aupwd">
            <input type="submit">
        </form>
    </html>
    <?php
}


?>