<?php

/* 	This script will auto generate an image that shows the current odds for a fight
  an be placed on other websites than BestFightOdds to show odds.

  Future plans are to make this cachable by storing images in a certain folder and
  regenerating them if the are missing. These are removed whenever the odds are
  updated. Therefor it would need to work with the parsing-engine. */

require_once __DIR__ . "/../../bootstrap.php";

use BFO\General\EventHandler;
use BFO\General\OddsHandler;
use BFO\Caching\CacheControl;

define('LINK_HEIGHT', 65);  //Only used for single fights - Events use dynamic height
define('LINK_WIDTH', 216);
define('LINK_BFO_HEIGHT', 18); //Indicates header size
define('FONT_SIZE', 8);
define('FONT_SIZE_BIG', 9);
define('FONT_TYPE', dirname(__FILE__) . "/micross.ttf");

$sLineType = isset($_GET['type']) ? $_GET['type'] : 'current';
$iFormatType = isset($_GET['format']) ? $_GET['format'] : 1;

if (isset($_GET['fight']) && is_numeric($_GET['fight']) && $_GET['fight'] > 0 && $_GET['fight'] < 99999) {
    $image_filename = 'link-fight_' . $_GET['fight'] . '_' . $sLineType . '_' . $iFormatType;
    $image_obj = null;

    if (CacheControl::isCached($image_filename)) {
        $image_obj = CacheControl::getCachedImage($image_filename);
    } else {
        $image_obj = FightLinkCreator::createFightLink($_GET['fight'], $sLineType, $iFormatType);
        if ($image_obj != false) {
            CacheControl::cacheImage($image_obj, $image_filename);
        }
    }

    if ($image_obj != false) {
        header("Content-type: image/png");
        imagepng($image_obj);
        imagedestroy($image_obj);
    }
} else if (isset($_GET['event']) && is_numeric($_GET['event']) && $_GET['event'] > 0 && $_GET['event'] < 99999) {
    $image_filename = 'link-event_' . $_GET['event'] . '_' . $sLineType . '_' . $iFormatType;
    $image_obj = null;

    if (CacheControl::isCached($image_filename)) {
        $image_obj = CacheControl::getCachedImage($image_filename);
    } else {
        $image_obj = FightLinkCreator::createEventLink($_GET['event'], $sLineType, $iFormatType);
        if ($image_obj != false) {
            CacheControl::cacheImage($image_obj, $image_filename);
        }
    }

    if ($image_obj != false) {
        header("Content-type: image/png");
        imagepng($image_obj);
        imagedestroy($image_obj);
    }
} else {
    header("Content-type: image/png");
    fpassthru(fopen('img/linkout-unavail.png', 'r'));
}

class FightLinkCreator
{

    public static function createEventLink($event_id, $line_type, $format)
    {
        $matchups = EventHandler::getMatchups(event_id: $event_id, only_with_odds: true);
        return self::createLink($matchups, $line_type, $format);
    }

    public static function createFightLink($matchup_id, $line_type, $format)
    {
        $matchup = EventHandler::getMatchup($matchup_id);
        return self::createLink([$matchup], $line_type, $format);
    }

    public static function createLink($aFights, $line_type, $format) //1 = Moneyline, 2 = Decimal
    {
        if (count($aFights) < 1 || $aFights[0] == null) {
            header("Content-type: image/png");
            fpassthru(fopen('img/linkout-unavail.png', 'r'));
            return false;
        }

        $iFighterCellHeight = 20;
        $iCalculatedHeight = LINK_BFO_HEIGHT + count($aFights) * ($iFighterCellHeight * 2) + count($aFights);

        $rImageHeader = imagecreatefrompng('img/linkout-back.png');

        $rImage = imagecreatetruecolor(LINK_WIDTH, $iCalculatedHeight)
            or die("Cannot Initialize new GD image stream");

        $rFrameColor = imagecolorallocate($rImage, 96, 98, 100);
        $rMiddleFrameColor = imagecolorallocate($rImage, 194, 194, 194);
        $rTopColor = imagecolorallocate($rImage, 234, 236, 238);
        $rBottomColor = imagecolorallocate($rImage, 255, 255, 255);
        $rTextColor = imagecolorallocate($rImage, 26, 26, 26);

        $rSeperatorColor = imagecolorallocate($rImage, 182, 182, 182);

        //Fighter1 Squares
        imagefill($rImage, 0, 0, $rTopColor);

        //Add header
        imagecopy($rImage, $rImageHeader, 0, 0, 0, 0, 216, 19);

        for ($iFightX = 0; $iFightX < count($aFights); $iFightX++) {
            $oFight = $aFights[$iFightX];


            $odds_obj = null;
            //Fetch odds based on desired type, opening or current best (default)
            if ($line_type == 'opening') {
                $odds_obj = OddsHandler::getOpeningOddsForMatchup($oFight->getID());
            } else {
                $odds_obj = EventHandler::getBestOddsForFight($oFight->getID());
            }

            $team1_odds = 'n/a';
            $team2_odds = 'n/a';

            if ($odds_obj != null) {
                if ($format == 2) {
                    //Decimal
                    $team1_odds = sprintf("%1\$.2f", $odds_obj->getFighterOddsAsDecimal(1));
                    $team2_odds = sprintf("%1\$.2f", $odds_obj->getFighterOddsAsDecimal(2));
                } else {
                    //Moneyline
                    $team1_odds = $odds_obj->getFighterOddsAsString(1);
                    $team2_odds = $odds_obj->getFighterOddsAsString(2);
                }
            }

            //Fighter2 Square
            imagefilledrectangle($rImage, 0, 1 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX, LINK_WIDTH, 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX, $rBottomColor);

            //Fighter 1 Name
            //imagettftext($rImage, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $oFight->getFighterAsString(1));
            self::textCustomSpacing($rImage, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $oFight->getFighterAsString(1), 1);

            //Fighter 1 Odds
            $aOddsSize = imagettfbbox(FONT_SIZE, 0, FONT_TYPE, $team1_odds);
            //self::textCustomSpacing($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $team1_odds, 2);
            imagettftext($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $team1_odds);


            //Fighter 2 Name
            self::textCustomSpacing($rImage, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 6, $rTextColor, FONT_TYPE, $oFight->getFighterAsString(2), 1);

            //Fighter 2 Odds
            $aOddsSize = imagettfbbox(FONT_SIZE, 0, FONT_TYPE, $team2_odds);
            //self::textCustomSpacing($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 6, $rTextColor, FONT_TYPE, $team2_odds, 2);
            imagettftext($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 6, $rTextColor, FONT_TYPE, $team2_odds);
        }

        //Column seperator
        imageline($rImage, LINK_WIDTH - 55, 1 + LINK_BFO_HEIGHT, LINK_WIDTH - 55, $iCalculatedHeight - 2, $rMiddleFrameColor);

        for ($iFightX = 0; $iFightX < count($aFights); $iFightX++) {
            //Fight seperator
            imageline($rImage, 1, 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX, LINK_WIDTH - 1, 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX, $rSeperatorColor);
        }

        //Frame
        imagerectangle($rImage, 0, 0, LINK_WIDTH - 1, $iCalculatedHeight - 1, $rFrameColor);

        return $rImage;
    }

    private static function textCustomSpacing($a_rImage, $a_iFontSize, $a_iAngle, $a_iX, $a_iY, $a_rColor, $a_rFont, $a_sText, $a_iSpacing = 0)
    {
        $iWritePos = 0;
        $aLastBox = null;
        for ($iX = 0; $iX < strlen($a_sText); $iX++) {
            $aLastBox = imagettfbbox($a_iFontSize, $a_iAngle, $a_rFont, $a_sText[$iX]);

            if ($a_sText[$iX] != ' ') {
                imagettftext($a_rImage, $a_iFontSize, $a_iAngle, $a_iX + $iWritePos, $a_iY, $a_rColor, $a_rFont, $a_sText[$iX]);
            }

            $iWritePos += $aLastBox[2] + $a_iSpacing;

            //Custom fixes for chars that add some spaces
            /*            switch ($a_sText[$iX])
            {
                case 'l':
                case 'n':
                    $iWritePos--;
                    $iWritePos--;
                break;
                default:
            }*/
        }
    }
}
