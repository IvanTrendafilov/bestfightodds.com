<?php

/* 	This script will auto generate an image that shows the current odds for a fight
  an be placed on other websites than BestFightOdds to show odds.

  Future plans are to make this cachable by storing images in a certain folder and
  regenerating them if the are missing. These are removed whenever the odds are
  updated. Therefor it would need to work with the parsing-engine. */


require_once('lib/bfocore/general/caching/class.CacheControl.php');

define('LINK_HEIGHT', 65);  //Only used for single fights - Events use dynamic height
define('LINK_WIDTH', 216);
define('LINK_BFO_HEIGHT', 18); //Indicates header size
define('FONT_SIZE', 8);
define('FONT_SIZE_BIG', 9);
define('FONT_TYPE', dirname(__FILE__) . "/micross.ttf");


$sLineType = isset($_GET['type']) ? $_GET['type'] : 'current';
$iFormatType = isset($_GET['format']) ? $_GET['format'] : 1;

if (isset($_GET['fight']) && is_numeric($_GET['fight']) && $_GET['fight'] > 0 && $_GET['fight'] < 99999)
{
    $sImageName = 'link-fight_' . $_GET['fight'] . '_' . $sLineType . '_' . $iFormatType;
    $rShowImage = null;

    if (CacheControl::isCached($sImageName))
    {
        $rShowImage = CacheControl::getCachedImage($sImageName);
    }
    else
    {
        require_once('lib/bfocore/general/inc.GlobalTypes.php');
        require_once('lib/bfocore/general/class.EventHandler.php');
        require_once('lib/bfocore/general/class.OddsHandler.php');

        $rShowImage = FightLinkCreator::createFightLink($_GET['fight'], $sLineType, $iFormatType);
        if ($rShowImage != false)
        {
           CacheControl::cacheImage($rShowImage, $sImageName);
        }
    }

    if ($rShowImage != false)
    {
        header("Content-type: image/png");
        imagepng($rShowImage);
        imagedestroy($rShowImage);
    }
}
else if (isset($_GET['event']) && is_numeric($_GET['event']) && $_GET['event'] > 0 && $_GET['event'] < 99999)
{
    $sImageName = 'link-event_' . $_GET['event'] . '_' . $sLineType . '_' . $iFormatType;
    $rShowImage = null;

    if (CacheControl::isCached($sImageName))
    {
        $rShowImage = CacheControl::getCachedImage($sImageName);
    }
    else
    {
        require_once('lib/bfocore/general/inc.GlobalTypes.php');
        require_once('lib/bfocore/general/class.EventHandler.php');
        require_once('lib/bfocore/general/class.OddsHandler.php');

        $rShowImage = FightLinkCreator::createEventLink($_GET['event'], $sLineType, $iFormatType);
        if ($rShowImage != false)
        {
            CacheControl::cacheImage($rShowImage, $sImageName);
        }
    }

    if ($rShowImage != false)
    {
        header("Content-type: image/png");
        imagepng($rShowImage);
        imagedestroy($rShowImage);    
    }
}
else
{
    header("Content-type: image/png");
    fpassthru(fopen('img/linkout-unavail.png','r'));
}

class FightLinkCreator
{

    public static function createEventLink($a_iEventID, $a_iLineType, $a_iFormat)
    {
        $aMatchups = EventHandler::getAllFightsForEvent($a_iEventID, true);
        return self::createLink($aMatchups, $a_iLineType, $a_iFormat);
    }

    public static function createFightLink($a_iFightID, $a_iLineType, $a_iFormat)
    {
        $oMatchup = EventHandler::getFightByID($a_iFightID);
        return self::createLink(array($oMatchup), $a_iLineType, $a_iFormat);
    }

    public static function createLink($aFights, $a_iLineType, $a_iFormat) //1 = Moneyline, 2 = Decimal
    {
        if (count($aFights) < 1 || $aFights[0] == null)
        {
            header("Content-type: image/png");
            fpassthru(fopen('img/linkout-unavail.png','r'));
            return false;
        }

        $iFighterCellHeight = 20;
        $iCalculatedHeight = LINK_BFO_HEIGHT + count($aFights) * ($iFighterCellHeight * 2) + count($aFights);

        $rImageHeader = imagecreatefrompng('img/linkout-back.png');

        $rImage = imagecreatetruecolor(LINK_WIDTH, $iCalculatedHeight)
                or die("Cannot Initialize new GD image stream");
        imageantialias($rImage, false);

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

        for ($iFightX = 0; $iFightX < count($aFights); $iFightX++)
        {
            $oFight = $aFights[$iFightX];


            $oFightOdds = null;
            //Fetch odds based on desired type, opening or current best (default)
            if ($a_iLineType == 'opening')
            {
                $oFightOdds = OddsHandler::getOpeningOddsForMatchup($oFight->getID());
            }
            else
            {
                $oFightOdds = EventHandler::getBestOddsForFight($oFight->getID());
            }
            
            $sFighter1Odds = 'n/a';
            $sFighter2Odds = 'n/a';

            if ($oFightOdds != null)
            {
                if ($a_iFormat == 2)
                {
                    //Decimal
                    $sFighter1Odds = sprintf("%1\$.2f", $oFightOdds->getFighterOddsAsDecimal(1));
                    $sFighter2Odds = sprintf("%1\$.2f", $oFightOdds->getFighterOddsAsDecimal(2));
                }
                else
                {
                    //Moneyline
                    $sFighter1Odds = $oFightOdds->getFighterOddsAsString(1);
                    $sFighter2Odds = $oFightOdds->getFighterOddsAsString(2);
                }
            }

            //Fighter2 Square
            imagefilledrectangle($rImage, 0, 1 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX, LINK_WIDTH, 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX, $rBottomColor);

            //Fighter 1 Name
            //imagettftext($rImage, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $oFight->getFighterAsString(1));
            self::textCustomSpacing($rImage, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $oFight->getFighterAsString(1), 1);

            //Fighter 1 Odds
            $aOddsSize = imagettfbbox(FONT_SIZE, 0, FONT_TYPE, $sFighter1Odds);
            //self::textCustomSpacing($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $sFighter1Odds, 2);
    	    imagettftext($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + $iFighterCellHeight + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 5, $rTextColor, FONT_TYPE, $sFighter1Odds);


            //Fighter 2 Name
            self::textCustomSpacing($rImage, FONT_SIZE, 0, 6, 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 6, $rTextColor, FONT_TYPE, $oFight->getFighterAsString(2), 1);

            //Fighter 2 Odds
            $aOddsSize = imagettfbbox(FONT_SIZE, 0, FONT_TYPE, $sFighter2Odds);
            //self::textCustomSpacing($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 6, $rTextColor, FONT_TYPE, $sFighter2Odds, 2);
	        imagettftext($rImage, FONT_SIZE, 0, LINK_WIDTH - (15 + ($aOddsSize[2] - $aOddsSize[0])), 0 + LINK_BFO_HEIGHT + ($iFighterCellHeight * 2) + ($iFighterCellHeight * ($iFightX * 2)) + $iFightX - 6, $rTextColor, FONT_TYPE, $sFighter2Odds);
       }

        //Column seperator
        imageline($rImage, LINK_WIDTH - 55, 1 + LINK_BFO_HEIGHT, LINK_WIDTH - 55, $iCalculatedHeight - 2, $rMiddleFrameColor);

        for ($iFightX = 0; $iFightX < count($aFights); $iFightX++)
        {
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
        for ($iX = 0; $iX < strlen($a_sText); $iX++)
        {
            $aLastBox = imagettfbbox($a_iFontSize, $a_iAngle, $a_rFont, $a_sText[$iX]);

            if ($a_sText[$iX] != ' ')
            {
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

?>