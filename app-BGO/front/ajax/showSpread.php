<?php

//Creates a graph for a fightodds-object

define('IMAGE_WIDTH', 187);
define('IMAGE_HEIGHT', 103);
define('DRAW_AREA_WIDTH', 160);
define('DRAW_AREA_HEIGHT', 80);
define('DRAW_AREA_OFFSETX', 26);
define('DRAW_AREA_OFFSETY', 0);
define('DRAW_AREA_MARGIN', 10);
define('DRAW_DOT_SIZE', 6);

require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

if (!isset($_GET['fightID']) || !isset($_GET['fighter']) ||
        !is_numeric($_GET['fightID']) || !is_numeric($_GET['fighter']))
{
    $im = imagecreatefrompng("../images/nograph.png");
    imagepng($im);
}

//Prepare bookie list
$aBookies = BookieHandler::getAllBookies();
if (sizeof($aBookies) == 0)
{
    echo 'No bookies found';
    exit();
}
$aBookieRefList = array();
foreach ($aBookies as $oBookie)
{
    $aBookieRefList[$oBookie->getID()] = $oBookie->getName();
}


$bShowDecimal = false;
if (isset($_GET['format']) && $_GET['format'] == "decimal")
{
    $bShowDecimal = true;
}

$iFighter = $_GET['fighter'];
$iFightID = $_GET['fightID'];


//Test http://www.bestfightodds.com/ajax/getGraph.php?bookieID=5&fighter=1&fightID=10


$aFightOdds = EventHandler::getAllLatestOddsForFight($iFightID);
if ($aFightOdds != null && sizeof($aFightOdds) > 1)
{
    $rImage = imagecreate(IMAGE_WIDTH, IMAGE_HEIGHT);
    $rColorRow1 = imagecolorallocate($rImage, 0xe7, 0xe7, 0xe7);
    $rColorRow2 = imagecolorallocate($rImage, 0xd8, 0xd8, 0xd8);
    $rColorFrame = imagecolorallocate($rImage, 0xc2, 0xc2, 0xc2);
    $rColorBlack = imagecolorallocate($rImage, 0x33, 0x33, 0x33);
    $rColorBack = imagecolorallocate($rImage, 0xee, 0xee, 0xee);
    $rColorDarkBlue = imagecolorallocate($rImage, 0x1f, 0x2a, 0x34);
    $rColorRedDot = imagecolorallocate($rImage, 0xa3, 0x00, 0x00);

    imagefilledrectangle($rImage, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, $rColorBack);


    $iRowDivider = DRAW_AREA_HEIGHT / 5;
    for ($iY = 0; $iY < 5; $iY++)
    {
        imagefilledrectangle($rImage, DRAW_AREA_OFFSETX, DRAW_AREA_OFFSETY + ($iRowDivider * $iY), DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH, DRAW_AREA_OFFSETY + ($iRowDivider * ($iY + 1)), ($iY % 2 == 0 ? $rColorRow1 : $rColorRow2));
    }


    //Convert odds to a simple array
    $aSimpleOdds = array();


    foreach ($aFightOdds as $oFightOdds)
    {
        $aSimpleOdds[] = (float) $oFightOdds->getFighterOddsAsDecimal($iFighter, true);
    }

    $iMaxValue = max($aSimpleOdds);
    $iMinValue = min($aSimpleOdds);

    $sMaxOdds = "";
    $sMinOdds = "";

    foreach ($aFightOdds as $oFightOdds)
    {
        if ($oFightOdds->getFighterOddsAsDecimal($iFighter, true) == $iMaxValue)
        {
            $sMaxOdds = $oFightOdds->getFighterOddsAsString($iFighter);
        }
        else if ($oFightOdds->getFighterOddsAsDecimal($iFighter, true) == $iMinValue)
        {
            $sMinOdds = $oFightOdds->getFighterOddsAsString($iFighter);
        }
    }

    $fFullVari = $iMaxValue - $iMinValue;


    //Draw average line
    $iAverageValue = array_sum($aSimpleOdds) / count($aSimpleOdds);
    $iRealValue1 = $iAverageValue - $iMinValue;
    $iPercent1 = 1 - ($iRealValue1 / $fFullVari);
    $iFirstValueY = DRAW_AREA_OFFSETY + ((DRAW_AREA_HEIGHT - (DRAW_AREA_MARGIN * 2)) * $iPercent1);
    imagelinedotted($rImage, DRAW_AREA_OFFSETX, DRAW_AREA_MARGIN + $iFirstValueY, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH, DRAW_AREA_MARGIN + $iFirstValueY, 1, $rColorDarkBlue);


    $iColumnWidth = (DRAW_AREA_WIDTH - (DRAW_AREA_MARGIN * 2)) / (sizeof($aSimpleOdds) - 1);

    for ($iX = 0; $iX < sizeof($aSimpleOdds); $iX++)
    {
        $iRealValue1 = $aSimpleOdds[$iX] - $iMinValue;
        $iPercent1 = 1 - ($iRealValue1 / $fFullVari);
        $iFirstValueY = DRAW_AREA_OFFSETY + ((DRAW_AREA_HEIGHT - (DRAW_AREA_MARGIN * 2)) * $iPercent1);

        imagefilledrectangle($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + ($iX * $iColumnWidth) - (DRAW_DOT_SIZE / 2), DRAW_AREA_MARGIN + $iFirstValueY, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + ($iX * $iColumnWidth) + (DRAW_DOT_SIZE / 2), DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT, $rColorRedDot);

        imagestringup($rImage, 1, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + ($iX * $iColumnWidth), DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 12, $aBookieRefList[$aFightOdds[$iX]->getBookieID()], $rColorBlack);
    }


    //Draw odds numbers
    imageline($rImage, DRAW_AREA_OFFSETX - 1, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN, DRAW_AREA_OFFSETX + 3, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN, $rColorBlack);
    imageline($rImage, DRAW_AREA_OFFSETX - 1, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - DRAW_AREA_MARGIN, DRAW_AREA_OFFSETX + 3, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - DRAW_AREA_MARGIN, $rColorBlack);
    imagestring($rImage, 2, DRAW_AREA_OFFSETX - 26, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN - 7, ($bShowDecimal ? $iMaxValue : $sMaxOdds), $rColorBlack);
    imagestring($rImage, 2, DRAW_AREA_OFFSETX - 26, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 7 - DRAW_AREA_MARGIN, ($bShowDecimal ? $iMinValue : $sMinOdds), $rColorBlack);


    //Draw decorations
    imagerectangle($rImage, DRAW_AREA_OFFSETX, DRAW_AREA_OFFSETY, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT, $rColorDarkBlue);

    
    header("Content-type: image/png");
    imagepng($rImage);
}
else
{
    $im = imagecreatefrompng("../images/nograph.png");
    imagepng($im);
}

function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
    if ($thick == 1)
    {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2)
    {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1 + $k) * $a), round($y1 + (1 - $k) * $a),
        round($x1 - (1 - $k) * $a), round($y1 - (1 + $k) * $a),
        round($x2 + (1 + $k) * $a), round($y2 - (1 - $k) * $a),
        round($x2 + (1 - $k) * $a), round($y2 + (1 + $k) * $a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}

function imagelinedotted($im, $x1, $y1, $x2, $y2, $dist, $col)
{
    $transp = imagecolortransparent($im);
    $transp = imagecolorallocate($im, 0xd8, 0xd8, 0xd8);

    $style = array($col);

    for ($i = 0; $i < $dist; $i++)
    {
        array_push($style, $transp);        // Generate style array - loop needed for customisable distance between the dots
    }

    imagesetstyle($im, $style);
    return (integer) imageline($im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
    imagesetstyle($im, array($col));        // Reset style - just in case...
}

?>