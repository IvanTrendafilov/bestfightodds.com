<?php

/*
 * TODO: This is somewhat app-specific and should be moved to /app
 */

require_once('config/inc.generalConfig.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');

define('IMAGE_WIDTH', 255);
define('IMAGE_HEIGHT', 98);
define('DRAW_AREA_WIDTH', 212);
define('DRAW_AREA_HEIGHT', 85);
define('DRAW_AREA_OFFSETX', 31);
define('DRAW_AREA_OFFSETY', 0);
define('DRAW_AREA_MARGIN', 8);
define('DRAW_DOT_SIZE', 6);
define('FONT_SIZE', 2);
define('FONT_TTF_TYPE', 'arial.ttf');
define('FONT_TTF_SIZE', 8);

//Computed
define('ACTUAL_DRAW_AREA_X', DRAW_AREA_WIDTH - (DRAW_AREA_MARGIN * 2));
define('ACTUAL_DRAW_AREA_Y', DRAW_AREA_HEIGHT - (DRAW_AREA_MARGIN * 2));
define('FONT_WIDTH', imagefontwidth(FONT_SIZE));

class GraphTool
{

    public static function showCachedGraphToUser($a_sName)
    {
        $rImage = CacheControl::getCachedImage($a_sName);
        GraphTool::showGraphToUser($rImage);
    }

    /**
     * Takes two parameter, the first is a list of fight odds that needs to be graphed.
     * 	The second is which fighter the graph should be drawn for
     *
     * 	Return a reference to an image
     *
     *  Odds types:
     *  1> Moneyline
     *  2> Decimal
     */
    public static function createGraph($a_aFightOdds, $a_iFighter, $a_sName, $a_iOddsType, $a_sEventDate)
    {
        error_reporting(0);
        
        //Check if graph cannot be created, if so display a "No graph available image"
        if ($a_aFightOdds == null || sizeof($a_aFightOdds) <= 1)
        {
            $rImage = imagecreatefrompng(GENERAL_IMAGE_DIRECTORY . "/nograph.png");
            return $rImage;
        }

        $oLatestStoredLine = $a_aFightOdds[count($a_aFightOdds) - 1];
        //Before proceeding we add a dummy line to the end of the array that contains the current line with current date. If the event is old, we instead add the event time as the last piece

        if (strtotime(GENERAL_TIMEZONE . ' hours') < strtotime(date('Y-m-d 23:59:59', strtotime($a_sEventDate))))
        {
            //Event is upcoming
            $a_aFightOdds[] = new FightOdds($oLatestStoredLine->getFightID(),
                            $oLatestStoredLine->getBookieID(),
                            $oLatestStoredLine->getFighterOdds(1),
                            $oLatestStoredLine->getFighterOdds(2),
                            date('Y-m-d H:i:s', strtotime(GENERAL_TIMEZONE . ' hours')));
        }
        else
        {
            //Event is old
            $a_aFightOdds[] = new FightOdds($oLatestStoredLine->getFightID(),
                            $oLatestStoredLine->getBookieID(),
                            $oLatestStoredLine->getFighterOdds(1),
                            $oLatestStoredLine->getFighterOdds(2),
                            date('Y-m-d 23:59:59', strtotime($a_sEventDate)));
        }

        //Initiate image and colors
        $rImage = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);
        imageantialias($rImage, true);
        $rColorBlack = imagecolorallocate($rImage, 0x33, 0x33, 0x33);

        //Create standard decorations
        $rImage = GraphTool::addStandardDecorations($rImage);

        //Get start and end dates
        $iStartDate = strtotime($a_aFightOdds[0]->getDate());
        $iEndDate = strtotime($a_aFightOdds[count($a_aFightOdds) - 1]->getDate());

        //Convert odds to a simple array and set min/max value
        $aSimpleOdds = array();
        $fMaxOdds = 0;
        $fMinOdds = 0;
        $sMaxOdds = '';
        $sMinOdds = '';
        foreach ($a_aFightOdds as $oFightOdds)
        {
            $aSimpleOdds[] = array('date' => strtotime($oFightOdds->getDate()), 'odds' => (float) $oFightOdds->getFighterOddsAsDecimal($a_iFighter, true));
            if ($fMaxOdds == 0 || (float) $oFightOdds->getFighterOddsAsDecimal($a_iFighter, true) > $fMaxOdds)
            {
                $fMaxOdds = (float) $oFightOdds->getFighterOddsAsDecimal($a_iFighter, true);
                $sMaxOdds = $oFightOdds->getFighterOddsAsString($a_iFighter);
            }
            if ($fMinOdds == 0 || (float) $oFightOdds->getFighterOddsAsDecimal($a_iFighter, true) < $fMinOdds)
            {
                $fMinOdds = (float) $oFightOdds->getFighterOddsAsDecimal($a_iFighter, true);
                $sMinOdds = $oFightOdds->getFighterOddsAsString($a_iFighter);
            }
        }
        $iDateVar = $iEndDate - $iStartDate;
        $fOddsVar = $fMaxOdds - $fMinOdds;



        for ($iX = 0; $iX < (count($aSimpleOdds) - 1); $iX++)
        {
            $fNextXPos = 0;
            $fNextYPos = 0;

            $fXPos = round((($aSimpleOdds[$iX]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
            if ($iX < count($aSimpleOdds) - 1)
            {
                $fNextXPos = round((($aSimpleOdds[$iX + 1]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
            }
            $fYPos = round((($fMaxOdds - $aSimpleOdds[$iX]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
            if ($iX < count($aSimpleOdds) - 1)
            {
                $fNextYPos = round((($fMaxOdds - $aSimpleOdds[$iX + 1]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
            }

            GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fNextXPos, DRAW_AREA_MARGIN + $fYPos, $rColorBlack, 2);
            GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fNextXPos, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fNextXPos, DRAW_AREA_MARGIN + $fNextYPos, $rColorBlack, 2);
        }

        //Add 2 pixel starting and finishing lines to make it look a bit nicer
        $fXPos = round((($aSimpleOdds[0]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
        $fYPos = round((($fMaxOdds - $aSimpleOdds[0]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
        GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos - 2, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos, DRAW_AREA_MARGIN + $fYPos, $rColorBlack, 2);
        $fXPos = round((($aSimpleOdds[count($aSimpleOdds) - 1]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
        $fYPos = round((($fMaxOdds - $aSimpleOdds[count($aSimpleOdds) - 1]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
        GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos + 2, DRAW_AREA_MARGIN + $fYPos, $rColorBlack, 2);

        //Draw meta data (e.g. min/max)
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX - (FONT_WIDTH * strlen(($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMaxOdds) : $sMaxOdds))) - 1, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN - 7, ($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMaxOdds) : $sMaxOdds), $rColorBlack);
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX - (FONT_WIDTH * strlen(($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMinOdds) : $sMinOdds))) - 1, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 7 - DRAW_AREA_MARGIN, ($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMinOdds) : $sMinOdds), $rColorBlack);

        //TTF Disabled, looks strange
        //imagettftext($rImage, FONT_SIZE, 0, DRAW_AREA_OFFSETX - 28, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN + 4, $rColorBlack, FONT_TYPE, ($a_iOddsType == 2 ? round($fMaxOdds, 2) : $sMaxOdds));
        //imagettftext($rImage, FONT_SIZE, 0, DRAW_AREA_OFFSETX - 28, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 4, $rColorBlack, FONT_TYPE, ($a_iOddsType == 2 ? round($fMinOdds, 2) : $sMinOdds));
        //
        //Draw dates
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 1, date('Y-m-d', $iStartDate), $rColorBlack);
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH - 59, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 1, date('Y-m-d', $iEndDate), $rColorBlack);

        error_reporting(E_ALL);

        //Cache the image
        if (!CacheControl::isCached($a_sName))
        {
            CacheControl::cacheImage($rImage, $a_sName);
        }

        return $rImage;
    }

    public static function createPropGraph($a_aOdds, $a_iPropPos, $a_sName, $a_iOddsType, $a_sEventDate)
    {
        error_reporting(0);
        
        //Check if graph cannot be created, if so display a "No graph available image"
        if ($a_aOdds == null || sizeof($a_aOdds) <= 1)
        {
            $rImage = imagecreatefrompng(GENERAL_IMAGE_DIRECTORY . "/nograph.png");
            return $rImage;
        }


        $oLatestStoredLine = $a_aOdds[count($a_aOdds) - 1];
        //Before proceeding we add a dummy line to the end of the array that contains the current line with current date. If the event is old, we instead add the event time as the last piece
        if (strtotime(GENERAL_TIMEZONE . ' hours') < strtotime(date('Y-m-d 23:59:59', strtotime($a_sEventDate))))
        {
            //Event is upcoming
            $a_aOdds[] = new PropBet($oLatestStoredLine->getMatchupID(),
                            $oLatestStoredLine->getBookieID(),
                            '',
                            $oLatestStoredLine->getPropOdds(),
                            '',
                            $oLatestStoredLine->getNegPropOdds(),
                            -1,
                            date('Y-m-d H:i:s', strtotime(GENERAL_TIMEZONE . ' hours')));
        }
        else
        {
            //Event is old
            $a_aOdds[] = new PropBet($oLatestStoredLine->getMatchupID(),
                            $oLatestStoredLine->getBookieID(),
                            '',
                            $oLatestStoredLine->getPropOdds(),
                            '',
                            $oLatestStoredLine->getNegPropOdds(),
                            -1,
                            date('Y-m-d 23:59:59', strtotime($a_sEventDate)));
        }

        //Initiate image and colors
        $rImage = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);
        imageantialias($rImage, true);
        $rColorBlack = imagecolorallocate($rImage, 0x33, 0x33, 0x33);

        //Create standard decorations
        $rImage = GraphTool::addStandardDecorations($rImage);

        //Get start and end dates
        $iStartDate = strtotime($a_aOdds[0]->getDate());
        $iEndDate = strtotime($a_aOdds[count($a_aOdds) - 1]->getDate());

        //Convert odds to a simple array and set min/max value
        $aSimpleOdds = array();
        $fMaxOdds = 0;
        $fMinOdds = 0;
        $sMaxOdds = '';
        $sMinOdds = '';

        if ($a_iPropPos == 1)
        {
            foreach ($a_aOdds as $oPropBet)
            {
                $aSimpleOdds[] = array('date' => strtotime($oPropBet->getDate()), 'odds' => (float) PropBet::moneylineToDecimal($oPropBet->getPropOdds(), true));
                if ($fMaxOdds == 0 || (float) PropBet::moneylineToDecimal($oPropBet->getPropOdds(), true) > $fMaxOdds)
                {
                    $fMaxOdds = (float) PropBet::moneylineToDecimal($oPropBet->getPropOdds(), true);
                    $sMaxOdds = $oPropBet->getPropOddsAsString();
                }
                if ($fMinOdds == 0 || (float) PropBet::moneylineToDecimal($oPropBet->getPropOdds(), true) < $fMinOdds)
                {
                    $fMinOdds = (float) PropBet::moneylineToDecimal($oPropBet->getPropOdds(), true);
                    $sMinOdds = $oPropBet->getPropOddsAsString();
                }
            }
        }
        else //$a_iPropPos == 2
        {
            foreach ($a_aOdds as $oPropBet)
            {
                $aSimpleOdds[] = array('date' => strtotime($oPropBet->getDate()), 'odds' => (float) PropBet::moneylineToDecimal($oPropBet->getNegPropOdds(), true));
                if ($fMaxOdds == 0 || (float) PropBet::moneylineToDecimal($oPropBet->getNegPropOdds(), true) > $fMaxOdds)
                {
                    $fMaxOdds = (float) PropBet::moneylineToDecimal($oPropBet->getNegPropOdds(), true);
                    $sMaxOdds = $oPropBet->getNegPropOddsAsString();
                }
                if ($fMinOdds == 0 || (float) PropBet::moneylineToDecimal($oPropBet->getNegPropOdds(), true) < $fMinOdds)
                {
                    $fMinOdds = (float) PropBet::moneylineToDecimal($oPropBet->getNegPropOdds(), true);
                    $sMinOdds = $oPropBet->getNegPropOddsAsString();
                }
            }
        }

        $iDateVar = $iEndDate - $iStartDate;
        $fOddsVar = $fMaxOdds - $fMinOdds;

        for ($iX = 0; $iX < (count($aSimpleOdds) - 1); $iX++)
        {
            $fNextXPos = 0;
            $fNextYPos = 0;

            $fXPos = round((($aSimpleOdds[$iX]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
            if ($iX < count($aSimpleOdds) - 1)
            {
                $fNextXPos = round((($aSimpleOdds[$iX + 1]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
            }
            $fYPos = round((($fMaxOdds - $aSimpleOdds[$iX]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
            if ($iX < count($aSimpleOdds) - 1)
            {
                $fNextYPos = round((($fMaxOdds - $aSimpleOdds[$iX + 1]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
            }

            GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fNextXPos, DRAW_AREA_MARGIN + $fYPos, $rColorBlack, 2);
            GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fNextXPos, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fNextXPos, DRAW_AREA_MARGIN + $fNextYPos, $rColorBlack, 2);
        }

        //Add 2 pixel starting and finishing lines to make it look a bit nicer
        $fXPos = round((($aSimpleOdds[0]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
        $fYPos = round((($fMaxOdds - $aSimpleOdds[0]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
        GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos - 2, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos, DRAW_AREA_MARGIN + $fYPos, $rColorBlack, 2);
        $fXPos = round((($aSimpleOdds[count($aSimpleOdds) - 1]['date'] - $iStartDate) / $iDateVar) * ACTUAL_DRAW_AREA_X);
        $fYPos = round((($fMaxOdds - $aSimpleOdds[count($aSimpleOdds) - 1]['odds']) / $fOddsVar) * ACTUAL_DRAW_AREA_Y);
        GraphTool::imagelinethick($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos, DRAW_AREA_MARGIN + $fYPos, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN + $fXPos + 2, DRAW_AREA_MARGIN + $fYPos, $rColorBlack, 2);

        //Draw meta data (e.g. min/max)
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX - (FONT_WIDTH * strlen(($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMaxOdds) : $sMaxOdds))) - 1, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN - 7, ($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMaxOdds) : $sMaxOdds), $rColorBlack);
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX - (FONT_WIDTH * strlen(($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMinOdds) : $sMinOdds))) - 1, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 7 - DRAW_AREA_MARGIN, ($a_iOddsType == 2 ? sprintf("%1\$.2f", $fMinOdds) : $sMinOdds), $rColorBlack);

        //TTF Disabled, looks strange..
        //imagettftext($rImage, FONT_SIZE, 0, DRAW_AREA_OFFSETX - 28, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN + 4, $rColorBlack, FONT_TYPE, ($a_iOddsType == 2 ? round($fMaxOdds, 2) : $sMaxOdds));
        //imagettftext($rImage, FONT_SIZE, 0, DRAW_AREA_OFFSETX - 28, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 4, $rColorBlack, FONT_TYPE, ($a_iOddsType == 2 ? round($fMinOdds, 2) : $sMinOdds));
        //Draw dates
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 1, date('Y-m-d', $iStartDate), $rColorBlack);
        imagestring($rImage, FONT_SIZE, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH - 59, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 1, date('Y-m-d', $iEndDate), $rColorBlack);

        error_reporting(E_ALL);

        //Cache the image
        if (!CacheControl::isCached($a_sName))
        {
            CacheControl::cacheImage($rImage, $a_sName);
        }

        return $rImage;
    }

    //Adds decorations and such
    private static function addStandardDecorations(&$rImage)
    {
        $rColorRow1 = imagecolorallocate($rImage, 0xf8, 0xf8, 0xf8);
        $rColorRow2 = imagecolorallocate($rImage, 0xe8, 0xe8, 0xe8);
        $rColorBlack = imagecolorallocate($rImage, 0x33, 0x33, 0x33);
        $rColorBack = imagecolorallocate($rImage, 0xff, 0xff, 0xff);
        $rColorDarkBlue = imagecolorallocate($rImage, 0x33, 0x33, 0x33); //imagecolorallocate($rImage, 0x1f, 0x2a, 0x34);
        //Create background
        imagefilledrectangle($rImage, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, $rColorBack);

        //Create row dividers
        $iRowDivider = DRAW_AREA_HEIGHT / 5;
        for ($iY = 0; $iY < 5; $iY++)
        {
            imagefilledrectangle($rImage, DRAW_AREA_OFFSETX, DRAW_AREA_OFFSETY + ($iRowDivider * $iY), DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH, DRAW_AREA_OFFSETY + ($iRowDivider * ($iY + 1)), ($iY % 2 == 0 ? $rColorRow1 : $rColorRow2));
        }

        //Create inne frame
        imageline($rImage, DRAW_AREA_OFFSETX - 1, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN, DRAW_AREA_OFFSETX + 3, DRAW_AREA_OFFSETY + DRAW_AREA_MARGIN, $rColorBlack);
        imageline($rImage, DRAW_AREA_OFFSETX - 1, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - DRAW_AREA_MARGIN, DRAW_AREA_OFFSETX + 3, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - DRAW_AREA_MARGIN, $rColorBlack);
        imageline($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN - 2, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 1, DRAW_AREA_OFFSETX + DRAW_AREA_MARGIN - 2, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 3, $rColorBlack);
        imageline($rImage, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH - DRAW_AREA_MARGIN + 2, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT - 1, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH - DRAW_AREA_MARGIN + 2, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT + 3, $rColorBlack);
        imagerectangle($rImage, DRAW_AREA_OFFSETX, DRAW_AREA_OFFSETY, DRAW_AREA_OFFSETX + DRAW_AREA_WIDTH, DRAW_AREA_OFFSETY + DRAW_AREA_HEIGHT, $rColorDarkBlue);

        return $rImage;
    }

    private static function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
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

    public static function showGraphToUser($rImage)
    {
        if ($rImage != false)
        {
            header("Content-type: image/png");
            imagepng($rImage);    
        }
    }

    public static function showNoGraphToUser()
    {
        $rImage = imagecreatefrompng(GENERAL_IMAGE_DIRECTORY . "/nograph.png");
        GraphTool::showGraphToUser($rImage);
    }

}

?>