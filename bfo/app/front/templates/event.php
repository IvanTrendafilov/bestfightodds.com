<?php $this->layout('template', ['title' => $team_title, 'meta_desc' => $meta_desc, 'meta_keywords' => $meta_keywords, 'current_page' => 'event']) ?>

<?php
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
?>
       

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

        <?php /*$sLastChange = EventHandler::getLatestChangeDate($event->getID());*/?>

        <div class="table-inner-wrapper"><div class="table-inner-shadow-left"></div><div class="table-inner-shadow-right"></div>
            <div class="table-scroller">
                <table class="odds-table">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <?php foreach ($bookies as $bookie): ?>
                            <th scope="col" data-b="<?=$bookie->getID()?>">
                            <a href="/out/<?=$bookie->getID()?>" onclick="lO(<?=$bookie->getID()?>,<?=$event->getID()?>);">
                                <?=str_replace(' ', '&nbsp;', (strlen($bookie->getName()) > 10 ? (substr($bookie->getName(), 0, 9) . '.') : $bookie->getName()))?>
                            </a></th>
                        <?php endforeach ?>
                        <th scope="col" colspan="3" class="table-prop-header">Props</th>
                    </tr>
                </thead>
                <tbody>

                    <?php //------------- Add matchups ------------------------- ?>

                    <?php foreach ($matchups as $matchup_key => $matchup): ?>

                        <?php for ($i = 1; $i <= 2; $i++): ?>

                            <tr <?=(($i == 2 && $matchup_key == array_key_last($matchups)) ? ' style="border-bottom: 0;" ' : '')?>>
                            <th scope="row"><a href="/fighters/<?=$matchup->getFighterAsLinkString($i)?>"><span class="t-b-fcc"><?=$matchup->getFighterAsString($i)?></span></a></th>

                            <?php foreach ($bookies as $bookie): ?>

                                <?php $odds = @$matchup_odds[$event->getID()][$matchup->getID()][$bookie->getID()]; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>

                                <?php if (isset($odds['odds_obj'])): ?>

                                    <td class="but-sg" data-li="[<?=$odds['odds_obj']->getBookieID()?>,<?=$i?>,<?=$odds['odds_obj']->getFightID()?>]">
                                        <span id="oID<?=('1' . sprintf("%06d", $odds['odds_obj']->getFightID()) . sprintf("%02d", $odds['odds_obj']->getBookieID()) . $i)?>"<?=$odds['is_best_team' . $i] ? ' class="bestbet"' : ''?>><?=$odds['odds_obj']->getFighterOddsAsString($i)?></span>
                                        <?php if (isset($odds['previous_team' . $i . '_odds'])): ?>
                                            <?php if ($odds['odds_obj']->getFighterOdds($i) > $odds['previous_team' . $i . '_odds']): ?>
                                                <span class="aru changedate-<?=$odds['odds_obj']->getDate()?>">▲</span>
                                            <?php elseif ($odds['odds_obj']->getFighterOdds($i) < $odds['previous_team' . $i . '_odds']): ?>
                                                <span class="ard changedate-<?=$odds['odds_obj']->getDate()?>">▼</span>
                                            <?php endif ?>
                                        <?php endif ?>
                                    </td>

                                <?php else: ?>    

                                    <td></td>

                                <?php endif ?>

                            <?php endforeach ?>

                            <td class="button-cell"><a href="#" class="but-al" data-li="[<?=$matchup->getID()?>,<?=$i?>]"><div class="but-img i-a" title="Add alert"></div></a></td>

                            <?php if (count($matchup_odds[$event->getID()][$matchup->getID()]) >= 1): //TODO: Needs check here to check if old odds was found?>
                                <td class="button-cell but-si" data-li="[<?=$i?>,<?=$matchup->getID()?>]">
                                    <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                                </td>
                            <?php else: ?>
                                <td class="button-cell but-si">
                                    <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                                </td>
                            <?php endif ?>

                            <td class="prop-cell prop-cell-exp" data-mu="<?=$matchup->getID()?>">

                            <?php if (isset($matchup_prop_count[$matchup->getID()])): ?> 
                                <?=$matchup_prop_count[$matchup->getID()]?>&nbsp;<span class="exp-ard"></span>
                            <?php else: ?>
                                &nbsp;
                            <?php endif ?>
                            </td>

                            </tr>

                        <?php endfor ?>

                        <?php foreach ($prop_odds[$event->getID()][$matchup->getID()] as $proptype_id => $team_num_row): ?>

                            <?php //------------- Add props ------------------------- ?>
                            
                            <?php foreach ($team_num_row as $team_num => $prop): ?>

                                <?php for ($i = 1; $i <= 2; $i++): ?>

                                    <?php 
    /*
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
                                    }*/
                                    ?>


                                    <tr class="pr"<?=(($i == 2 && $team_num == array_key_last($team_num_row)) ? ' style="border-bottom: 2px solid #f8f8f8;"' : (($i == 1) ? ' style="border-top: 1px solid #C6C6C6;"' : ''))?>>
                                    <th scope="row"><?=$i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName()?></th>
                                
                                    <?php foreach ($bookies as $bookie): ?>

                                        <?php $odds = @$prop[$bookie->getID()]; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>
                                        <?php if (isset($odds['odds_obj'])) { $odds_val = ($i == 1 ? $odds['odds_obj']->getPropOdds() : $odds['odds_obj']->getNegPropOdds()); } //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>
                                        <?php $previous_odds_val = $i == 1 ?  @$prop[$bookie->getID()]['previous_prop_odds'] : @$prop[$bookie->getID()]['previous_negprop_odds']; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>

                                        <?php if (isset($odds['odds_obj'])): ?>

                                            <?php if (($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()) != '-99999'): ?>

                                                <td class="but-sgp" data-li="[<?=$bookie->getID()?>,<?=$i?>,<?=$matchup->getID()?>,<?=$proptype_id?>,<?=$team_num?>]"><span id="oID<?=('2' . sprintf("%06d", $matchup->getID()) . sprintf("%02d", $bookie->getID()) . $i . sprintf("%03d", $proptype_id) . $team_num)?>"<?=$i == 1 ? ($odds['is_best_pos'] ? ' class="bestbet"' : '') : ($odds['is_best_neg'] ? ' class="bestbet"': '') ?>><?=$i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()?></span>
                                                <?php if (isset($previous_odds_val)): ?>
                                                    <?php if ($odds_val > $previous_odds_val): ?>
                                                                <span class="aru changedate-<?=$odds['odds_obj']->getDate()?>">▲</span>
                                                    <?php elseif ($odds_val < $previous_odds_val): ?>
                                                                <span class="ard changedate-<?=$odds['odds_obj']->getDate()?>">▼</span>
                                                    <?php endif ?>
                                                <?php endif ?>

                                                </td>

                                            <?php else: ?>
                                                <td><span class="na">n/a</span></td>
                                            <?php endif ?>
                                            
                                        <?php else: ?>

                                            <td></td>

                                        <?php endif ?>

                                    <?php endforeach ?>



                                    <td class="button-cell"><div class="but-img i-na"></div></td>


                                    <?php if (count($prop) > 1): //TODO: Add $bEverFoundOldOddscheck ?>
                                            <?php /*TODO:
                                            $oCurrentPropOddsIndex = OddsHandler::getCurrentPropIndex($oPropOdds->getMatchupID(), $iX, $oPropOdds->getPropTypeID(), $oPropOdds->getTeamNumber());
                                            if (($iX == 1 ? $oCurrentPropOddsIndex->getPropOdds() : $oCurrentPropOddsIndex->getNegPropOdds()) > ($iX == 1 ? $oBestOdds->getPropOdds() : $oBestOdds->getNegPropOdds()))
                                            {
                                                $oCurrentPropOddsIndex = $oBestOdds;
                                            } */?>
                                            <td class="button-cell but-sip" data-li="[' . $iX . ',' . $oPropOdds->getMatchupID() . ',' . $oPropOdds->getPropTypeID() . ',' . $oPropOdds->getTeamNumber() . ']">
                                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                                            </td>
                                    <?php else: ?>
                                        <td class="button-cell">
                                            <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                                        </td>
                                    <?php endif ?>


                                    <td class="prop-cell"></td>
                                    </tr>

                                <?php endfor ?>

                            <?php endforeach ?>


                            <?php endforeach ?>

                    <?php endforeach ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

        %table-lastchanged%

<div class="table-outer-wrapper">

    <div id="event-swing-area">
        <div id="page-container">
            <div class="content-header">Line movement <div id="event-swing-picker-menu"><a href="#" class="event-swing-picker picked" data-li="0">Since opening</a> | <a href="#" class="event-swing-picker" data-li="1">Last 24 hours</a> | <a href="#" class="event-swing-picker" data-li="2">Last hour</a></div></div>
                <div id="event-swing-container" data-moves="<?=htmlentities(json_encode($swing_chart_data), ENT_QUOTES, 'UTF-8')?>" style="height: <?=(50 + (count($swing_chart_data[0]['data']) < 10 ? count($swing_chart_data[0]['data']) : 10) * 18)?>px;"></div>
                <div class="event-swing-expandarea <?=count($swing_chart_data[0]['data']) < 10 ? ' hidden' : ''?>"><a href="#" class="event-swing-expand"><span>Show all</span><div class="event-swing-expandarrow"></div></a></div>
            </div>
        </div>

    <div id="event-outcome-area" style="">
        <div id="page-container">
            <div class="content-header">Expected outcome</div>
            <div id="event-outcome-container" data-outcomes="<?=htmlentities(json_encode($expected_outcome_data), ENT_QUOTES, 'UTF-8')?>" style="height: <?=(67 + count($expected_outcome_data['data']) * 20)?>px;"></div>

        </div>
    </div>
</div>

