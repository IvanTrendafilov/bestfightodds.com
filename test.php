<?php

$string = 'BABILON MMA 22: BŁESZYŃSKI VS. WAWRZYNIAK';
echo iconv('UTF-8', 'Windows-1250//IGNORE', $string);