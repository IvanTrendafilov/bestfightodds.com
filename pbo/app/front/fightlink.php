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
        if ($image_obj) {
            CacheControl::cacheImage($image_obj, $image_filename);
        }
    }

    if ($image_obj) {
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
        if ($image_obj) {
            CacheControl::cacheImage($image_obj, $image_filename);
        }
    }

    if ($image_obj) {
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
    public static function createEventLink(int $event_id, string $line_type, int $odds_format)
    {
        $matchups = EventHandler::getMatchups(event_id: $event_id, only_with_odds: true);
        return self::createLink($matchups, $line_type, $odds_format);
    }

    public static function createFightLink(int $matchup_id, string $line_type, int $odds_format)
    {
        $matchup = EventHandler::getMatchup((int) $matchup_id);
        return self::createLink([$matchup], $line_type, $odds_format);
    }

    public static function createLink(array $matchups, string $line_type, int $odds_format) //1 = Moneyline, 2 = Decimal
    {
        if (count($matchups) < 1 || $matchups[0] == null) {
            header("Content-type: image/png");
            fpassthru(fopen('img/linkout-unavail.png', 'r'));
            return false;
        }

        $team_cell_height = 20;
        $total_height = LINK_BFO_HEIGHT + count($matchups) * ($team_cell_height * 2) + count($matchups);

        $image_header_obj = imagecreatefrompng('img/linkout-back.png');

        $image_obj = imagecreatetruecolor(LINK_WIDTH, $total_height)
            or die("Cannot Initialize new GD image stream");

        $color_frame = imagecolorallocate($image_obj, 96, 98, 100);
        $color_inner_frame = imagecolorallocate($image_obj, 194, 194, 194);
        $color_top = imagecolorallocate($image_obj, 245, 247, 249);
        $color_bottom = imagecolorallocate($image_obj, 255, 255, 255);
        $color_text = imagecolorallocate($image_obj, 26, 26, 26);
        $color_seperator = imagecolorallocate($image_obj, 182, 182, 182);

        //Fighter1 Squares
        imagefill($image_obj, 0, 0, $color_top);

        //Add header
        imagecopy($image_obj, $image_header_obj, 0, 0, 0, 0, 216, 19);

        for ($i = 0; $i < count($matchups); $i++) {
            $matchup_obj = $matchups[$i];

            $odds_obj = null;
            //Fetch odds based on desired type, opening or current best (default)
            if ($line_type == 'opening') {
                $odds_obj = OddsHandler::getOpeningOddsForMatchup($matchup_obj->getID());
            } else {
                $odds_obj = OddsHandler::getBestOddsForFight($matchup_obj->getID());
            }

            $team1_odds = 'n/a';
            $team2_odds = 'n/a';

            if ($odds_obj) {
                if ($odds_format == 2) {
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
            imagefilledrectangle($image_obj, 0, 1 + LINK_BFO_HEIGHT + $team_cell_height + ($team_cell_height * ($i * 2)) + $i, LINK_WIDTH, 0 + LINK_BFO_HEIGHT + ($team_cell_height * 2) + ($team_cell_height * ($i * 2)) + $i, $color_bottom);

            //Fighter 1 Name
            //imagettftext($image_obj, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + $team_cell_height + ($team_cell_height * ($i * 2)) + $i - 5, $color_text, FONT_TYPE, $matchup_obj->getFighterAsString(1));
            self::textCustomSpacing($image_obj, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + $team_cell_height + ($team_cell_height * ($i * 2)) + $i - 5, $color_text, FONT_TYPE, $matchup_obj->getFighterAsString(1), 1);

            //Fighter 1 Odds
            $aOddsSize = imagettfbbox(FONT_SIZE, 0, FONT_TYPE, $team1_odds);
            //self::textCustomSpacing($image_obj, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + $team_cell_height + ($team_cell_height * ($i * 2)) + $i - 5, $color_text, FONT_TYPE, $team1_odds, 2);
            imagettftext($image_obj, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + $team_cell_height + ($team_cell_height * ($i * 2)) + $i - 5, $color_text, FONT_TYPE, $team1_odds);


            //Fighter 2 Name
            self::textCustomSpacing($image_obj, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + ($team_cell_height * 2) + ($team_cell_height * ($i * 2)) + $i - 6, $color_text, FONT_TYPE, $matchup_obj->getFighterAsString(2), 1);

            //Fighter 2 Odds
            $aOddsSize = imagettfbbox(FONT_SIZE, 0, FONT_TYPE, $team2_odds);
            //self::textCustomSpacing($image_obj, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + ($team_cell_height * 2) + ($team_cell_height * ($i * 2)) + $i - 6, $color_text, FONT_TYPE, $team2_odds, 2);
            imagettftext($image_obj, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + ($team_cell_height * 2) + ($team_cell_height * ($i * 2)) + $i - 6, $color_text, FONT_TYPE, $team2_odds);
        }

        //Column seperator
        imageline($image_obj, LINK_WIDTH - 55, 1 + LINK_BFO_HEIGHT, LINK_WIDTH - 55, $total_height - 2, $color_inner_frame);

        for ($i = 0; $i < count($matchups); $i++) {
            //Fight seperator
            imageline($image_obj, 1, 0 + LINK_BFO_HEIGHT + ($team_cell_height * 2) + ($team_cell_height * ($i * 2)) + $i, LINK_WIDTH - 1, 0 + LINK_BFO_HEIGHT + ($team_cell_height * 2) + ($team_cell_height * ($i * 2)) + $i, $color_seperator);
        }

        //Frame
        imagerectangle($image_obj, 0, 0, LINK_WIDTH - 1, $total_height - 1, $color_frame);

        return $image_obj;
    }

    private static function textCustomSpacing($image_obj, $font_size, $angle, $x_pos, $y_pos, $color, $font, $text, $char_spacing = 0)
    {
        $write_position = 0;
        $last_char_box = null;
        for ($i = 0; $i < strlen($text); $i++) {
            $last_char_box = imagettfbbox($font_size, $angle, $font, $text[$i]);

            if ($text[$i] != ' ') {
                imagettftext($image_obj, $font_size, $angle, $x_pos + $write_position, $y_pos, $color, $font, $text[$i]);
            }

            $write_position += $last_char_box[2] + $char_spacing;

            //Custom fixes for chars that add some spaces
            /*            switch ($text[$i])
            {
                case 'l':
                case 'n':
                    $write_position--;
                    $write_position--;
                break;
                default:
            }*/
        }
    }
}