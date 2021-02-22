<?php $this->layout('template', ['title' => 'Event']) ?>


/*require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');
require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/inc.GlobalTypes.php');
require_once('config/inc.config.php');
require_once('lib/bfocore/general/caching/class.CacheControl.php');
require_once('lib/bfocore/utils/class.LinkTools.php');
require_once('lib/bfocore/utils/class.OddsTools.php');
require_once('lib/bfocore/general/class.StatsHandler.php');

require_once('app/front/pages/inc.FrontLogic.php');

$oEvent = EventHandler::getEvent((int) $_GET['eventID']);

$iCellCounter = 0;*/

       

        <div class="table-outer-wrapper"><div class="table-div" id="event<?=$event->getID()?>"><div class="table-header"><a href="/events/<?=$event->getEventAsLinkString()?>"><h1><?=$event->getName()?></h1></a>
        
            <?php if (strtoupper($event->getName()) != 'FUTURE EVENTS'): //Add date for all events except for FUTURE EVENTS?>
                <span class="table-header-date"><?=date('F jS', strtotime($event->getDate()))?></span>
            <?php endif ?>
            <div class="share-area">
                <div class="share-button"></div>
            </div>
            <div class="share-window">
                <div data-href="https://twitter.com/intent/tweet?text=<?=urlencode($event->getName() . ' betting lines')?>&amp;url=<?=urlencode('https://www.bestfightodds.com/events/' . $event->getEventAsLinkString())?>" class="share-item share-twitter"></div>
                <div data-href="https://www.facebook.com/sharer/sharer.php?u=<?=urlencode('https://www.bestfightodds.com/events/' . $event->getEventAsLinkString())?>" class="share-item share-facebook"></div>
                <div data-href="whatsapp://send?text=<?=urlencode($event->getName() . ' betting lines')?> <?=urlencode('https://www.bestfightodds.com/events/' . $event->getEventAsLinkString())?>" data-action="share/whatsapp/share" class="share-item share-whatsapp item-mobile-only"></div>
            </div>

        </div>
        <?php /*
        <table class="odds-table odds-table-responsive-header">
            <thead>
                <tr>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($matchups as $matchup): ?>


            <?php endforeach ?>
      <?php /*  $iFightCounter = 0;
        foreach ($aFights as $oFight)
        {
            $iCurrentOperatorColumn = 0;
            for ($iX = 1; $iX <= 2; $iX++)
            {
                echo '<tr id="mu-' . $oFight->getID() . '" ' . (($iX == 2 && $iFightCounter == count($aFights) - 1) ? ' style="border-bottom: 0;" ' : '') . '>'; //If this is the last matchup, add style for it
                echo '<th scope="row"><a href="/fighters/' . $oFight->getFighterAsLinkString($iX) . '"><span class="t-b-fcc">' . $oFight->getFighterAsString($iX) . '</span></a></th>';

                echo '</tr>';
            }

            //Add prop rows
            $aPropTypes = OddsHandler::getAllPropTypesForMatchup($oFight->getID());

            if (count($aPropTypes) > 0)
            {
                $iPropCounter = 0;
                $iPropRowCounter = 0;
                foreach ($aPropTypes as $oPropType)
                {
                    $iCurrentOperatorColumn = 0;
                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        //If order has changed in the database we need to switch the odds
                        if ($oFight->hasOrderChanged())
                        {
                            $oPropType->invertTeamNum();
                        }
                        
                        $iPropRowCounter++;
                        //Replace template placeholders for team names
                        $oPropType->setPropDesc(str_replace('<T>', $oFight->getTeamLastNameAsString($oPropType->getTeamNum()), $oPropType->getPropDesc()));
                        $oPropType->setPropNegDesc(str_replace('<T>', $oFight->getTeamLastNameAsString($oPropType->getTeamNum()), $oPropType->getPropNegDesc()));
                        $oPropType->getPropDesc(str_replace('<T2>', $oFight->getTeamLastNameAsString(($oPropType->getTeamNum() % 2) + 1), $oPropType->getPropDesc()));
                        $oPropType->setPropNegDesc(str_replace('<T2>', $oFight->getTeamLastNameAsString(($oPropType->getTeamNum() % 2) + 1), $oPropType->getPropNegDesc()));

                        echo '<tr class="pr"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iFightCounter == count($aFights) - 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '</th>';
                        echo '</tr>';
                    }

                    $iPropCounter++;
                }
            }

            //Finished adding props

            $iFightCounter++;
        }

        //Add event prop rows
        $aPropTypes = OddsHandler::getAllPropTypesForEvent($event->getID());
        if (count($aPropTypes) > 0)
        {
            echo '<tr class="eventprop" id="mu-e' . $event->getID() . '">';
            echo '<th scope="row" style="font-weight: 400"><a href="#" data-mu="' . $event->getID() . '">Event props</a></th>';
            echo '</tr>';

            $iPropCounter = 0;
            $iPropRowCounter = 0;
            foreach ($aPropTypes as $oPropType)
            {
                    $iCurrentOperatorColumn = 0;
                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        $iPropRowCounter++;
                        echo '<tr class="pr"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '&nbsp;</th>';

                        echo '</tr>';
                    }
                    $iPropCounter++;
            }
        }



        echo '</tbody>'
        . '</table>';
    */?>

        $sLastChange = EventHandler::getLatestChangeDate($event->getID());

        <div class="table-inner-wrapper"><div class="table-inner-shadow-left"></div><div class="table-inner-shadow-right"></div>
            <div class="table-scroller">
                <table class="odds-table">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <?php foreach ($bookies as $bookie): ?>
                            <th scope="col" data-b="<?=$bookie->getID()?>">
                            <a href="/out/<?=$bookie->getID()?>" onclick="lO(<?=$bookie->getID()?>,<?=$event->getID()?>);">
                                <?=str_replace(' ', '&nbsp;', (strlen($oBookie->getName()) > 10 ? (substr($oBookie->getName(), 0, 9) . '.') : $oBookie->getName()))?>
                            </a></th>
                        <?php endforeach ?>
                        <th scope="col" colspan="3" class="table-prop-header">Props</th>
                    </tr>
                </thead>
                <tbody>';
                <?php foreach ($matchups as $matchup_id => $matchup): ?>

                    <?php for ($i = 1; $i <= 2; $i++): ?>

                        <tr <?=(($i == 2 && $matchup_id == array_key_last($matchups)) ? ' style="border-bottom: 0;" ' : '')?>>
                        <th scope="row"><a href="/fighters/<?=$matchup->getFighterAsLinkString($i)?>"><span class="t-b-fcc"><?=$matchup->getFighterAsString($i)?></span></a></th>

                        <?php foreach ($bookies as $bookie): ?>

                            <?php $odds = $matchup_odds[$event->getID()][$matchup->getID()][$bookie->getID()]; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>

                            <?php if (isset($odds['odds_obj'])): ?>

                                <td class="but-sg" data-li="[<?=$odds['odds_obj']->getBookieID()?>,<?=$i?>,<?=$odds['odds_obj']->getFightID()?>]">
                                    <span id="oID<?=('1' . sprintf("%06d", $odds['odds_obj']->getFightID()) . sprintf("%02d", $odds['odds_obj']->getBookieID()) . $i)?>" <?=$sClassName?>>
                                        <?=$odds['odds_obj']->getFighterOddsAsString($i)?>
                                    </span>
                                    <?php if (isset($odds['previous_team' . $i . '_odds'])): ?>
                                        <?php if ($odds['odds_obj']->getFighterOdds($iX) > $odds['previous_team' . $i . '_odds']): ?>
                                            <span class="aru changedate-<?=$odds['odds_obj']->getDate()?>">▲</span>
                                        <?php elseif ($odds['odds_obj']->getFighterOdds($iX) < $odds['previous_team' . $i . '_odds']): ?>
                                            <span class="ard changedate-<?=$odds['odds_obj']->getDate()?>">▼</span>
                                        <?php endif ?>
                                    <?php endif ?>
                                </td>

                            <?php else: ?>    

                                <td></td>

                            <?php endif ?>

                        <?php endforeach ?>

                        <td class="button-cell"><a href="#" class="but-al" data-li="[<?=$matchup_id?>,<?=$i?>]"><div class="but-img i-a" title="Add alert"></div></a></td>

                        //Add index graph button
                        <?php if (count($matchup_odds[$event->getID()][$matchup->getID()]) >= 1): //TODO: Needs check here to check if old odds was found?>
                            <td class="button-cell but-si" data-li="[<?=$i?>,<?=$matchup_id?>]">
                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                            </td>
                        <?php else: ?>
                            <td class="button-cell but-si">
                                <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                            </td>

                        <?php endif ?>

                        <td class="prop-cell prop-cell-exp" data-mu="<?=$matchup_id?>">
                        <?php //TODO: Need to populate this from the controller 
                        /*$iPropCount = OddsHandler::getPropCountForMatchup($oFight->getID());
                        if ($iPropCount > 0)
                        {
                            echo $iPropCount . '&nbsp;<span class="exp-ard"></span>';
                        }
                        else
                        {
                            echo '&nbsp;';
                        }*/ ?>
                        </td>

                        </tr>

                    <?php endfor ?>
                <?php endforeach ?>
           
            

            //Add prop rows

            $iPropCounter = 0;
            $iPropRowCounter = 0;
            foreach ($prop_odds[$event->getID()][$oFight->getID()] as $proptype_id => $team_num_row)
            {
                foreach ($team_num_row as $team_num => $prop)
                {
                    for ($i = 1; $i <= 2; $i++)
                    {
                        //Adjust prop name description
                        $desc = $i == 1 ?  $prop[array_key_first($prop)]['prop_desc'] : $prop[array_key_first($prop)]['negprop_desc'];
                        $desc = str_replace('<T>', $oFight->getTeamLastNameAsString($team_num), $desc);
                        $desc = str_replace('<T2>', $oFight->getTeamLastNameAsString(($team_num % 2) + 1), $desc);

                        //Determine best odds
                        $best_odds = -99999;
                        foreach ($aBookies as $bookie)
                        {
                            if (isset($prop[$bookie->getID()]))
                            {
                                $odds = $i == 1 ?  $prop[$bookie->getID()]['prop_odds'] : $prop[$bookie->getID()]['negprop_odds'];
                                if (intval($odds) > $best_odds)
                                {
                                    $best_odds = intval($odds);
                                }
                            }
                        }

                        echo '<tr class="pr"' . (($i == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($i == 1 && $iFightCounter == count($aFights) - 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . $desc . '</th>';
                       
                        foreach ($aBookies as $bookie)
                        {
                            if (isset($prop[$bookie->getID()]))
                            {
                                $odds = $i == 1 ?  $prop[$bookie->getID()]['prop_odds'] : $prop[$bookie->getID()]['negprop_odds'];
                                $previous_odds = $i == 1 ?  $prop[$bookie->getID()]['previous_prop_odds'] : $prop[$bookie->getID()]['previous_negprop_odds'];


                                $date = 'nope'; //TODO: FIX

                                $class_extra = '';
                                if ($odds == $best_odds)
                                {
                                    $class_extra = ' class="bestbet"';
                                }

                                if ($odds != '-99999')
                                {
                                    echo '<td class="but-sgp" data-li="[' . $bookie->getID() . ',' . $i . ',' . $oFight->getID() . ',' . $proptype_id . ',' . $team_num . ']"><span id="oID' . ('2' . sprintf("%06d", $oFight->getID()) . sprintf("%02d", $bookie->getID()) . $i . sprintf("%03d", $proptype_id) . $team_num) . '" ' . $class_extra . '>' . $odds . '</span>';
                                    if ($previous_odds != '' && $odds > $previous_odds)
                                    {
                                        echo '<span class="aru changedate-' . $date .'">▲</span>';
                                    }
                                    else if ($previous_odds != '' && $odds < $previous_odds)
                                    {
                                        echo '<span class="ard changedate-' . $date .'">▼</span>';
                                    }
                                    else
                                    {
                                        echo '';
                                    }
                                    echo '</td>';
                                }
                                else
                                {
                                    echo '<td><span class="na">n/a</span></td>';
                                }
                                
                            }
                            else
                            {
                                echo '<td></td>';
                            }
                        }

                        echo '</tr>';
                    }

                }

            }


            /*if (count($aPropTypes) > 0)
            {
                $aAllPropOdds = OddsHandler::getCompletePropsForMatchup($oFight->getID());
                $aAllOldPropOdds = OddsHandler::getCompletePropsForMatchup($oFight->getID(), 1);

                $iPropCounter = 0;
                $iPropRowCounter = 0;
                foreach ($aPropTypes as $oPropType)
                {
                    //From previously fetech props, grab all for that specific proptype
                    $aPropsOdds = array();
                    foreach ($aAllPropOdds as $oTempPropOdds)
                    {
                        if ($oTempPropOdds->getPropTypeID() == $oPropType->getID() && $oTempPropOdds->getTeamNumber() == $oPropType->getTeamNum())
                        {
                            $aPropsOdds[] = $oTempPropOdds;
                        }
                    }

                    $aOldPropOdds = array();
                    if ($aAllOldPropOdds != null)
                    {
                        foreach ($aAllOldPropOdds as $oTempPropOdds)
                        {
                            if ($oTempPropOdds->getPropTypeID() == $oPropType->getID() && $oTempPropOdds->getTeamNumber() == $oPropType->getTeamNum())
                            {
                                $aOldPropOdds[] = $oTempPropOdds;
                            }
                        }
                    }

                    $oBestOdds = OddsHandler::getBestPropOddsForMatchup($oFight->getID(), $oPropType->getID(), $oPropType->getTeamNum());

                    $iProcessedProps = 0;
                    $iCurrentOperatorColumn = 0;

                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        //If order has changed in the database we need to switch the odds
                        if ($oFight->hasOrderChanged())
                        {
                            $oPropType->invertTeamNum();
                        }
                        
                        $iPropRowCounter++;
                        //Replace template placeholders for team names
                        $oPropType->setPropDesc(str_replace('<T>', $oFight->getTeamLastNameAsString($oPropType->getTeamNum()), $oPropType->getPropDesc()));
                        $oPropType->setPropNegDesc(str_replace('<T>', $oFight->getTeamLastNameAsString($oPropType->getTeamNum()), $oPropType->getPropNegDesc()));
                        $oPropType->getPropDesc(str_replace('<T2>', $oFight->getTeamLastNameAsString(($oPropType->getTeamNum() % 2) + 1), $oPropType->getPropDesc()));
                        $oPropType->setPropNegDesc(str_replace('<T2>', $oFight->getTeamLastNameAsString(($oPropType->getTeamNum() % 2) + 1), $oPropType->getPropNegDesc()));

                        echo '<tr class="pr"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iFightCounter == count($aFights) - 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '</th>';

                        $iProcessedProps = 0;
                        $bEverFoundOldOdds = false;

                        foreach ($aPropsOdds as $oPropOdds)
                        {
                            $iCurrentOperatorColumn = $iProcessedProps;
                            while (isset($aBookieRefList[$iCurrentOperatorColumn]) && $aBookieRefList[$iCurrentOperatorColumn] != $oPropOdds->getBookieID())
                            {
                                echo '<td></td>';
                                $iCurrentOperatorColumn++;
                                $iProcessedProps++;
                            }

                            $sClassName = '';
                            if (($iX == 1 && $oPropOdds->getPropOdds() == $oBestOdds->getPropOdds()) ||
                                    ($iX == 2 && $oPropOdds->getNegPropOdds() == $oBestOdds->getNegPropOdds()))
                            {
                                $sClassName = 'class="bestbet"';
                            }

                            //Loop through the previous odds and check if odds is higher or lower or non-existant (kinda ugly, needs a fix)
                            $iCurrentOperatorID = $oPropOdds->getBookieID();
                            $bFoundOldOdds = false;

                            foreach ($aOldPropOdds as $oOldPropOdds)
                            {
                                //Determine if the prop or negative prop is the one to compare
                                $iCompareOddsNew = 0;
                                $iCompareOddsOld = 0;
                                if ($iX == 1)
                                {
                                    $iCompareOddsNew = $oPropOdds->getPropOdds();
                                    $iCompareOddsOld = $oOldPropOdds->getPropOdds();
                                }
                                else
                                {
                                    $iCompareOddsNew = $oPropOdds->getNegPropOdds();
                                    $iCompareOddsOld = $oOldPropOdds->getNegPropOdds();
                                }

                                if ($oOldPropOdds->getBookieID() == $iCurrentOperatorID)
                                {
                                    if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                    {
                                        echo '<td class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
                                        if ($iCompareOddsNew > $iCompareOddsOld)
                                        {
                                            echo '<span class="aru changedate-' . $oPropOdds->getDate() .'">▲</span>';
                                        }
                                        else if ($iCompareOddsNew < $iCompareOddsOld)
                                        {
                                            echo '<span class="ard changedate-' . $oPropOdds->getDate() .'">▼</span>';
                                        }
                                        else
                                        {
                                            echo '';
                                        }
                                        echo '</td>';
                                    }
                                    else
                                    {
                                        echo '<td><span class="na">n/a</span></td>';
                                    }
                                    $bFoundOldOdds = true;
                                    $bEverFoundOldOdds = true;
                                }
                            }
                            if (!$bFoundOldOdds)
                            {
                                if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                {
                                    echo '<td class="but-sgp" data-li="[' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span></td>';
                                }
                                else
                                {
                                    echo '<td><span class="na">n/a</span>';
                                }
                            }

                            $iProcessedProps++;
                        }

                        //Fill empty cells
                        for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                        {
                            echo '<td></td>';
                        }

                        //Add alert cell - Functionality should be disabled however
                        //echo '<td class="button-cell"><div class="but-img i-na"></div></td>';

                        //Add index graph
                        if ($bEverFoundOldOdds || count($aPropsOdds) > 1)
                        {
                            $oCurrentPropOddsIndex = OddsHandler::getCurrentPropIndex($oPropOdds->getMatchupID(), $iX, $oPropOdds->getPropTypeID(), $oPropOdds->getTeamNumber());
                            if (($iX == 1 ? $oCurrentPropOddsIndex->getPropOdds() : $oCurrentPropOddsIndex->getNegPropOdds()) > ($iX == 1 ? $oBestOdds->getPropOdds() : $oBestOdds->getNegPropOdds()))
                            {
                                $oCurrentPropOddsIndex = $oBestOdds;
                            }
                            echo '<td class="button-cell but-sip" data-li="[' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']">
                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                            </td>';
                        }
                        else
                        {
                            echo '<td class="button-cell">
                                <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                            </td>';
                        }

                        //Add empty cell normally used for props
                        echo '<td class="prop-cell"></td>';
                        echo '</tr>';
                    }

                    $iPropCounter++;
                }
            }*/

            //Finished adding props

            $iFightCounter++;
        }


        //Add event prop rows
        $aPropTypes = OddsHandler::getAllPropTypesForEvent($event->getID());
        if (count($aPropTypes) > 0)
        {
            echo '<tr class="eventprop" id="mu-' . $event->getID() . '">';
            echo '<th scope="row" style="font-weight: 400"><a href="#" data-mu="' . $event->getID() . '">Event props</a></th>';

            //Fill empty cells
            for ($iY = 0; $iY < (sizeof($aBookieRefList)); $iY++)
            {
                echo '<td></td>';
            }
            echo '<td class="button-cell"></td>';

            echo '<td class="prop-cell prop-cell-exp" data-mu="e' . $event->getID() . '">';
            echo count($aPropTypes) . '&nbsp;<span class="exp-ard"></span>';
            echo '</td>';


            echo '</tr>';


            $aAllPropOdds = OddsHandler::getCompletePropsForEvent($event->getID());
            $aAllOldPropOdds = OddsHandler::getCompletePropsForEvent($event->getID(), 1);

            $iPropCounter = 0;
            $iPropRowCounter = 0;
            foreach ($aPropTypes as $oPropType)
            {
                //From previously fetech props, grab all for that specific proptype
                    $aPropsOdds = array();
                    foreach ($aAllPropOdds as $oTempPropOdds)
                    {
                        if ($oTempPropOdds->getPropTypeID() == $oPropType->getID())
                        {
                            $aPropsOdds[] = $oTempPropOdds;
                        }
                    }

                    $aOldPropOdds = array();
                    if ($aAllOldPropOdds != null)
                    {
                        foreach ($aAllOldPropOdds as $oTempPropOdds)
                        {
                            if ($oTempPropOdds->getPropTypeID() == $oPropType->getID())
                            {
                                $aOldPropOdds[] = $oTempPropOdds;
                            }
                        }
                    }

                    $oBestOdds = OddsHandler::getBestPropOddsForEvent($event->getID(), $oPropType->getID());

                    $iProcessedProps = 0;
                    $iCurrentOperatorColumn = 0;

                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        $iPropRowCounter++;
                        echo '<tr class="pr"' . (($iX == 2 && $iPropCounter == count($aPropTypes) - 1) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($iX == 1 && $iPropCounter == 0) ? ' style="border-top: 1px solid #C6C6C6;"' : '')) . '>';
                        echo '<th scope="row">' . ($iX == 1 ? $oPropType->getPropDesc() : $oPropType->getPropNegDesc()) . '&nbsp;</th>';

                        $iProcessedProps = 0;
                        $bEverFoundOldOdds = false;

                        foreach ($aPropsOdds as $oPropOdds)
                        {
                            $iCurrentOperatorColumn = $iProcessedProps;
                            while (isset($aBookieRefList[$iCurrentOperatorColumn]) && $aBookieRefList[$iCurrentOperatorColumn] != $oPropOdds->getBookieID())
                            {
                                echo '<td></td>';
                                $iCurrentOperatorColumn++;
                                $iProcessedProps++;
                            }

                            $sClassName = '';
                            if (($iX == 1 && $oPropOdds->getPropOdds() == $oBestOdds->getPropOdds()) ||
                                    ($iX == 2 && $oPropOdds->getNegPropOdds() == $oBestOdds->getNegPropOdds()))
                            {
                                $sClassName = 'class="bestbet"';
                            }

                            //Loop through the previous odds and check if odds is higher or lower or non-existant (kinda ugly, needs a fix)
                            $iCurrentOperatorID = $oPropOdds->getBookieID();
                            $bFoundOldOdds = false;

                            foreach ($aOldPropOdds as $oOldPropOdds)
                            {
                                //Determine if the prop or negative prop is the one to compare
                                $iCompareOddsNew = 0;
                                $iCompareOddsOld = 0;
                                if ($iX == 1)
                                {
                                    $iCompareOddsNew = $oPropOdds->getPropOdds();
                                    $iCompareOddsOld = $oOldPropOdds->getPropOdds();
                                }
                                else
                                {
                                    $iCompareOddsNew = $oPropOdds->getNegPropOdds();
                                    $iCompareOddsOld = $oOldPropOdds->getNegPropOdds();
                                }

                                if ($oOldPropOdds->getBookieID() == $iCurrentOperatorID)
                                {
                                    echo '';
                                    if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                    {
                                        echo '<td class="but-sgep" data-li="[' . $oPropOdds->getEventID() . ',' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']"><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';

                                        if ($iCompareOddsNew > $iCompareOddsOld)
                                        {
                                            echo '<span class="aru changedate-' . $oPropOdds->getDate() .'">▲</span>';
                                        }
                                        else if ($iCompareOddsNew < $iCompareOddsOld)
                                        {
                                            echo '<span class="ard changedate-' . $oPropOdds->getDate() .'">▼</span>';
                                        }
                                        else
                                        {
                                            echo '';
                                        }
                                        echo '</td>';
                                    }
                                    else
                                    {
                                        echo '<td><span class="na">n/a</span></td>';
                                    }
                                    $bFoundOldOdds = true;
                                    $bEverFoundOldOdds = true;

                                    echo '';
                                }
                            }
                            if (!$bFoundOldOdds)
                            {
                                if (($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) != '-99999')
                                {
                                    echo '<td class="but-sgep" data-li="[' . $oPropOdds->getEventID() . ',' . $oPropOdds->getBookieID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']" ><span id="oID' . ('2' . sprintf("%06d", $oPropOdds->getMatchupID()) . sprintf("%02d", $oPropOdds->getBookieID()) . $iX . sprintf("%03d", $oPropOdds->getPropTypeID()) . $oPropOdds->getTeamNumber()) . '" ' . $sClassName . '>' . ($iX == 1 ? $oPropOdds->getPropOddsAsString() : $oPropOdds->getNegPropOddsAsString()) . '</span>';
                                }
                                else
                                {
                                    echo '<td><span class="na">n/a</span></td>';
                                }
                            }

                            $iProcessedProps++;
                        }

                        //Fill empty cells
                        for ($iY = $iCurrentOperatorColumn; $iY < (sizeof($aBookieRefList) - 1); $iY++)
                        {
                            echo '<td></td>';
                        }

                        //Add alert cell
                        /*echo '<td class="button-cell"><span class="but-img i-na"></span></td>';*/

                        //Add index graph
                        if ($bEverFoundOldOdds || count($aPropsOdds) > 1)
                        {
                            $oCurrentPropOddsIndex = OddsHandler::getCurrentEventPropIndex($oPropOdds->getEventID(), $iX, $oPropOdds->getPropTypeID());
                            if (($iX == 1 ? $oCurrentPropOddsIndex->getPropOdds() : $oCurrentPropOddsIndex->getNegPropOdds()) > ($iX == 1 ? $oBestOdds->getPropOdds() : $oBestOdds->getNegPropOdds()))
                            {
                                $oCurrentPropOddsIndex = $oBestOdds;
                            }
                            echo '<td class="button-cell but-siep" data-li="[' . $oPropOdds->getEventID() . ',' . $iX . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']">
                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                            </td>';
                        }
                        else
                        {
                            echo '<td class="button-cell">
                                <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                            </td>';
                        }

                        //Add empty cell normally used for props
                        echo '<td class="prop-cell"></td>';
                        echo '</tr>';
                    }

                    $iPropCounter++;
            }
        }




       if (!$bSomeOddsFound)
        {
            echo '<tr><td colspan="' . (sizeof($aBookies) + 3) . '" style="padding: 20px 0; ">No betting lines available for this event';
            //Check if event is in the future, if so, add a notifier about alerts, tweets, etc..
            if (strtotime(GENERAL_TIMEZONE . ' hours') < strtotime(date('Y-m-d 23:59:59', strtotime($event->getDate()))))
            {
                echo '<br /><br /><img src="/img/info.gif" class="img-info-box" /> Get notified when odds are posted either via our <a href="/alerts" style="text-decoration: underline">Alerts</a> functionality or by following us on <a href="http://twitter.com/bestfightodds" target="_blank" rel="noopener" style="text-decoration: underline">Twitter</a>';
            }
            echo '</td></tr>';
        }


        echo '</tbody>'
        . '</table></div></div></div></div>'; 

        echo '%table-lastchanged%';

        //BEING ADDITIONS

       echo '<div class="table-outer-wrapper"><div id="event-swing-area"><div id="page-container"><div class="content-header">Line movement <div id="event-swing-picker-menu"><a href="#" class="event-swing-picker picked" data-li="0">Since opening</a> | <a href="#" class="event-swing-picker" data-li="1">Last 24 hours</a> | <a href="#" class="event-swing-picker" data-li="2">Last hour</a></div></div>';

?>

<?php

        $aData = [];
        $aSeriesNames = ['Change since opening', 'Change in the last 24 hours', 'Change in the last hour'];
        for ($x = 0; $x <= 2; $x++)
        {
            $aSwings = StatsHandler::getAllDiffsForEvent($event->getID(), $x);
            $aRowData = [];
            
            foreach ($aSwings as $aSwing)
            {
                if ($aSwing[2]['swing'] < 0.01 && $aSwing[2]['swing'] > 0.00)
                {
                    $aSwing[2]['swing'] = 0.01;
                }
                if (round($aSwing[2]['swing'] * 100) != 0)
                {
                    
                    $aRowData[]  = [$aSwing[0]->getTeamAsString($aSwing[1]), -round($aSwing[2]['swing'] * 100)];
                }
            }
            if (count($aRowData) == 0)
            {
                $aRowData[] = ['No ' . strtolower($aSeriesNames[$x]), null];
            }
            $aData[]  = ["name" => $aSeriesNames[$x], "data" => $aRowData, "visible" => ($x == 0 ? true : false)];

        }

        //Size of chart should be maximum 10 rows initially but if less we need to check that 
        $iMaxRows = 10;
        if (count($aData[0]['data']) < 10)
        {
            $iMaxRows = count($aData[0]['data']);
        }
        echo '<div id="event-swing-container" data-moves="' . htmlentities(json_encode($aData), ENT_QUOTES, 'UTF-8') . '" style="height:' . (50 + $iMaxRows * 18) . 'px;"></div>';

        echo '<div class="event-swing-expandarea ' . (count($aData[0]['data']) < 10 ? ' hidden' : '') . '"><a href="#" class="event-swing-expand"><span>Show all</span><div class="event-swing-expandarrow"></div></a></div></div></div>';

        //END ADDITIONS

        //BEING ADDITIONS

       echo '<div id="event-outcome-area" style=""><div id="page-container"><div class="content-header">Expected outcome</div>';

?>

<?php

        //TODO: This should be refactored to use the generic getExpectedOutcomes instead
        $aOutcomes = StatsHandler::getExpectedOutcomesForEvent($event->getID());
        $aRowData = [];
        foreach ($aOutcomes as $aOutcome)
        {
            $aLabels = [$aOutcome[0]->getTeamAsString(1), $aOutcome[0]->getTeamAsString(2)];
            $iOffset = $aOutcome[1]['team1_dec'] + $aOutcome[1]['team1_itd'] + ($aOutcome[1]['draw'] / 2);

            $aPoints = [$aOutcome[1]['team1_dec'],
                        $aOutcome[1]['team1_itd'],
                        $aOutcome[1]['draw'],
                        $aOutcome[1]['team2_itd'],
                        $aOutcome[1]['team2_dec']];
            $aRowData[] = [$aLabels, $aPoints];

        }
        if (count($aRowData) == 0)
        {
            $aPoints = [0,0,0,0,0];
            $aRowData[] = [['N/A','N/A'], $aPoints];
        }
        $aData  = ["name" => 'Outcomes', "data" => $aRowData];
        echo '<div id="event-outcome-container" data-outcomes="' . htmlentities(json_encode($aData), ENT_QUOTES, 'UTF-8') . '" style="height:' . (67 + count($aRowData) * 20) . 'px;"></div>';

                //echo '<div id="event-outcome-container" style="width: 50%; height: 400px; display: inline-block;"></div>';
        echo '</div></div></div>';

        //END ADDITIONS


        $sBuffer = ob_get_clean();

        CacheControl::cleanPageCacheWC('event-' . $event->getID() . '-*');
        CacheControl::cachePage($sBuffer, 'event-' . $event->getID() . '-' . strtotime($sLastChange) . '.php');
        
        echo '<!--C:MIS-->';

    //Dynamically replace last change placeholder
    $sBufferLastChange = '<div class="table-last-changed">Last change: <span title="' . ($sLastChange == null ? 'n/a' : (date('M jS Y H:i', strtotime($sLastChange)) . ' EST')) . '">' . getTimeDifference(strtotime($sLastChange), strtotime(GENERAL_TIMEZONE . ' hours')) . '</span></div> ';
    $sBuffer = str_replace('%table-lastchanged%', $sBufferLastChange, $sBuffer);

    //Perform dynamic modifications to the content (cached or not)
    echo preg_replace_callback('/changedate-([^\"]*)/', function ($a_aMatches)
    {
        $iHoursDiff = intval(floor((time() - strtotime($a_aMatches[1])) / 3600));
        if ($iHoursDiff >= 72)
        {
            return 'arage-3';
        }
        else if ($iHoursDiff >= 24)
        {
            return 'arage-2"';
        }
        else
        {
            return 'arage-1"';
        }
    }, $sBuffer);

    //Replace last changed placeholder



?>