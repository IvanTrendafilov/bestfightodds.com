<?php

require_once('lib/bfocore/db/class.StatsDB.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('lib/bfocore/general/class.FighterHandler.php');

$aFavourites = StatsDB::getTopFavourites();
$aDogs = StatsDB::getTopUnderdogs();

//Assemble top 5 favourites and skip dupes
$aTopFiveFavs = array();
foreach ($aFavourites as $aFav)
{
    if (count($aTopFiveFavs) < 10)
    {
        $bFound = false;
        foreach ($aTopFiveFavs as $aTopFiver)
        {
            if ($aFav['id'] == $aTopFiver['id'] && $aFav['name'] == $aTopFiver['name'])
            {
                $bFound = true;
            }
        }
        if (!$bFound)
        {
            $aTopFiveFavs[] = $aFav;
        }
    }
}

//Assemble top 5 dogs and skip dupes
$aTopFiveDogs = array();
foreach ($aDogs as $aDog)
{
    if (count($aTopFiveDogs) < 10)
    {
        $bFound = false;
        foreach ($aTopFiveDogs as $aTopFiver)
        {
            if ($aDog['id'] == $aTopFiver['id'] && $aDog['name'] == $aTopFiver['name'])
            {
                $bFound = true;
            }
        }
        if (!$bFound)
        {
            $aTopFiveDogs[] = $aDog;
        }
    }
}

echo '<div class="archiveTopList"><b>Top favourites ever</b><br />';
for ($iX = 1; $iX <= count($aTopFiveFavs); $iX++)
{
    $aTopper = $aTopFiveFavs[$iX - 1];
    $oFighter = new Fighter($aTopper['name'], $aTopper['id']);
    $oOpponent = new Fighter($aTopper['opponent_name'], $aTopper['opponent_id']);
    $oFightOdds = new FightOdds($aTopper['fight_id'], -1, $aTopper['odds'], $aTopper['opponent_odds'], -1);
    echo $iX . '. <a href="/fighters/' . $oFighter->getFighterAsLinkString() . '">' . $oFighter->getNameAsString()
        . '</a> (' . $oFightOdds->getFighterOddsAsString(1) . ') vs <a href="/fighters/' . $oOpponent->getFighterAsLinkString()
        . '">' . $oOpponent->getNameAsString() . '</a> (' . $oFightOdds->getFighterOddsAsString(2) . ')<br />';
}
echo '</div>';

echo '<div class="archiveTopList"><b>Top underdogs ever</b><br />';
for ($iX = 1; $iX <= count($aTopFiveDogs); $iX++)
{
    $aTopper = $aTopFiveDogs[$iX - 1];
    $oFighter = new Fighter($aTopper['name'], $aTopper['id']);
    $oOpponent = new Fighter($aTopper['opponent_name'], $aTopper['opponent_id']);
    $oFightOdds = new FightOdds($aTopper['fight_id'], -1, $aTopper['odds'], $aTopper['opponent_odds'], -1);
    echo $iX . '. <a href="/fighters/' . $oFighter->getFighterAsLinkString() . '">' . $oFighter->getNameAsString() 
        . '</a> (' . $oFightOdds->getFighterOddsAsString(1) . ') vs <a href="/fighters/' . $oOpponent->getFighterAsLinkString()
        . '">' . $oOpponent->getNameAsString() . '</a> (' . $oFightOdds->getFighterOddsAsString(2) . ')<br />';
}
echo '</div>';




$aBiggestChange = StatsDB::getBiggestChange();
foreach ($aBiggestChange as $aBigChange)
{
    $oFighter = FighterHandler::getFighterByID($aBigChange['fighter_id']);
    echo '' . $oFighter->getNameAsString() . ' ' . $aBigChange['odds_min'] . ' => ' . $aBigChange['odds_max'] . ' in ' .  $aBigChange['fight_id'] . '<br />';
}



?>
