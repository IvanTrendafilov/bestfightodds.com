<?php

//TODO: Implement LESS CSS
//require_once('lib/lessphp/lessc.inc.php');

header("Content-type: text/css");
//header("Cache-Control: max-age=900, must-revalidate");

/*$less = new lessc("bfo-main_2.less");
echo $less->parse();

$less = new lessc("bfo-oddstable_2.less");
echo $less->parse();*/

include_once('bfo-main_2.css');
include_once('bfo-oddstable_2.css');
include_once('bfo-responsive.css');





?>
