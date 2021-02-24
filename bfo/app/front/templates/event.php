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

        <table class="odds-table odds-table-responsive-header">
            <thead>
                <tr>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>

            <?php //============== Add matchups ====================== ?>
                <?php foreach ($matchups as $matchup_key => $matchup): ?>
                    <?php for ($i = 1; $i <= 2; $i++): ?>
                        <tr <?=(($i == 2 && $matchup_key == array_key_last($matchups)) ? ' style="border-bottom: 0;" ' : '')?>>
                            <th scope="row"><a href="/fighters/<?=$matchup->getFighterAsLinkString($i)?>"><span class="t-b-fcc"><?=$matchup->getFighterAsString($i)?></span></a></th>
                        </tr>
                    <?php endfor ?>
                    <?php //============== Add props ======================= ?>
                    <?php foreach ($prop_odds[$event->getID()][$matchup->getID()] as $proptype_id => $team_num_row): ?>
                        <?php foreach ($team_num_row as $team_num => $prop): ?>
                            <?php for ($i = 1; $i <= 2; $i++): ?>
                                <tr class="pr">
                                    <th scope="row"><?=$i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName()?></th>
                                </tr>
                            <?php endfor ?>
                        <?php endforeach ?>
                    <?php endforeach ?>
                <?php endforeach ?>

                <?php //============== Add event props ======================= ?>

                <?php if (count($event_prop_odds[$event->getID()]) > 1): ?>
                    <tr class="eventprop" id="mu-<?=$event->getID()?>">
                            <th scope="row" style="font-weight: 400"><a href="#" data-mu="<?=$event->getID()?>">Event props</a></th>
                            <?php foreach ($bookies as $bookie): ?>
                                <td></td>
                            <?php endforeach ?>
                            <td class="button-cell"></td>
                            <td class="prop-cell prop-cell-exp" data-mu="e<?=$event->getID()?>">
                                <?=$event_prop_count?>&nbsp;<span class="exp-ard"></span>
                            </td>
                    </tr>
                    <tr style="display: none;"></tr>
                    <?php foreach ($event_prop_odds[$event->getID()] as $proptype_id => $prop): ?>
                            <?php for ($i = 1; $i <= 2; $i++): ?>
                                <tr class="pr">
                                    <th scope="row"><?=$i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName()?></th>
                                </tr>
                        <?php endfor ?>
                    <?php endforeach ?>
                <?php endif ?>

            </tbody>
        </table>

        <div class="table-inner-wrapper"><div class="table-inner-shadow-left"></div><div class="table-inner-shadow-right"></div>
            <div class="table-scroller">
                <table class="odds-table">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <?php foreach ($bookies as $bookie): ?>
                            <th scope="col" data-b="<?=$bookie->getID()?>"><a href="/out/<?=$bookie->getID()?>" onclick="lO(<?=$bookie->getID()?>,<?=$event->getID()?>);"><?=str_replace(' ', '&nbsp;', (strlen($bookie->getName()) > 10 ? (substr($bookie->getName(), 0, 9) . '.') : $bookie->getName()))?></a></th>
                        <?php endforeach ?>
                        <th scope="col" colspan="3" class="table-prop-header">Props</th>
                    </tr>
                </thead>
                <tbody>

                    <?php //============== Add matchups ====================== ?>

                    <?php foreach ($matchups as $matchup_key => $matchup): ?>

                        <?php for ($i = 1; $i <= 2; $i++): ?>

                            <tr <?=(($i == 2 && $matchup_key == array_key_last($matchups)) ? ' style="border-bottom: 0;" ' : '')?>>
                            <th scope="row"><a href="/fighters/<?=$matchup->getFighterAsLinkString($i)?>"><span class="t-b-fcc"><?=$matchup->getFighterAsString($i)?></span></a></th>

                            <?php foreach ($bookies as $bookie): ?>

                                <?php $odds = @$matchup_odds[$event->getID()][$matchup->getID()][$bookie->getID()]; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>

                                <?php if (isset($odds['odds_obj'])): ?>

                                    <td class="but-sg" data-li="[<?=$odds['odds_obj']->getBookieID()?>,<?=$i?>,<?=$odds['odds_obj']->getFightID()?>]">
                                        <span id="oID<?=('1' . sprintf("%06d", $odds['odds_obj']->getFightID()) . sprintf("%02d", $odds['odds_obj']->getBookieID()) . $i)?>"<?=isset($odds['is_best_team' . $i]) ? ' class="bestbet"' : ''?>><?=$odds['odds_obj']->getFighterOddsAsString($i)?></span>
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

                        <?php //============== Add props ======================= ?>

                        <?php foreach ($prop_odds[$event->getID()][$matchup->getID()] as $proptype_id => $team_num_row): ?>

                            <?php foreach ($team_num_row as $team_num => $prop): ?>

                                <?php for ($i = 1; $i <= 2; $i++): ?>

                                    <tr class="pr">
                                    <th scope="row"><?=$i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName()?></th>
                                
                                    <?php foreach ($bookies as $bookie): ?>

                                        <?php $odds = @$prop[$bookie->getID()]; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>
                                        <?php if (isset($odds['odds_obj'])) { $odds_val = ($i == 1 ? $odds['odds_obj']->getPropOdds() : $odds['odds_obj']->getNegPropOdds()); } //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>
                                        <?php $previous_odds_val = $i == 1 ?  @$prop[$bookie->getID()]['previous_prop_odds'] : @$prop[$bookie->getID()]['previous_negprop_odds']; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>

                                        <?php if (isset($odds['odds_obj'])): ?>

                                            <?php if (($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()) != '-99999'): ?>

                                                <td class="but-sgp" data-li="[<?=$bookie->getID()?>,<?=$i?>,<?=$matchup->getID()?>,<?=$proptype_id?>,<?=$team_num?>]"><span id="oID<?=('2' . sprintf("%06d", $matchup->getID()) . sprintf("%02d", $bookie->getID()) . $i . sprintf("%03d", $proptype_id) . $team_num)?>"<?=$i == 1 ? (isset($odds['is_best_pos']) ? ' class="bestbet"' : '') : (isset($odds['is_best_neg']) ? ' class="bestbet"': '') ?>><?=$i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()?></span>
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

                                    <td class="button-cell but-sip" data-li="[<?=$i?>,<?=$matchup->getID()?>,<?=$proptype_id?>,<?=$team_num?>]">
                                        <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                                    </td>

                                    <td class="prop-cell"></td>
                                    </tr>

                                <?php endfor ?>

                            <?php endforeach ?>

                        <?php endforeach ?>

                    <?php endforeach ?>

                    <?php //============== Add event props ======================= ?>

                    <?php if (count($event_prop_odds[$event->getID()]) > 1): ?>

                        <tr class="eventprop" id="mu-<?=$event->getID()?>">
                                <th scope="row" style="font-weight: 400"><a href="#" data-mu="<?=$event->getID()?>">Event props</a></th>
    
                                <?php foreach ($bookies as $bookie): ?>
                                    <td></td>
                                <?php endforeach ?>

                                <td class="button-cell"></td>
                                <td class="prop-cell prop-cell-exp" data-mu="e<?=$event->getID()?>">
                                    <?=$event_prop_count?>&nbsp;<span class="exp-ard"></span>
                                </td>

                        </tr>
                        <tr style="display: none;"></tr>

                        <?php foreach ($event_prop_odds[$event->getID()] as $proptype_id => $prop): ?>

                                <?php for ($i = 1; $i <= 2; $i++): ?>

                                    <tr class="pr">
                                    <th scope="row"><?=$i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName()?></th>

                                    <?php foreach ($bookies as $bookie): ?>

                                        <?php $odds = @$prop[$bookie->getID()]; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>
                                        <?php if (isset($odds['odds_obj'])) { $odds_val = ($i == 1 ? $odds['odds_obj']->getPropOdds() : $odds['odds_obj']->getNegPropOdds()); } //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>
                                        <?php $previous_odds_val = $i == 1 ?  @$prop[$bookie->getID()]['previous_prop_odds'] : @$prop[$bookie->getID()]['previous_negprop_odds']; //TODO: Not recommended by plates but simplifies access to this object. Any alternative way to handle this? ?>

                                        <?php if (isset($odds['odds_obj'])): ?>

                                            <?php if (($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()) != '-99999'): ?>

                                                <td class="but-sgep" data-li="[<?=$event->getID()?>,<?=$bookie->getID()?>,<?=$i?>,<?=$proptype_id?>,0]"><span id="oID<?=('2' . sprintf("%06d", $odds['odds_obj']->getMatchupID()) . sprintf("%02d", $bookie->getID()) . $i . sprintf("%03d", $proptype_id) . 0)?>"<?=$i == 1 ? (isset($odds['is_best_pos']) ? ' class="bestbet"' : '') : (isset($odds['is_best_neg']) ? ' class="bestbet"': '') ?>><?=($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString())?></span>
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

                                    <td class="button-cell but-siep" data-li="[<?=$event->getID()?>,<?=$i?>,<?=$proptype_id?>, 0]">
                                        <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"><g><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></g></svg>
                                    </td>

                                <td class="prop-cell"></td>

                                </tr>
                            <?php endfor ?>

                        <?php endforeach ?>

                    <?php endif ?>

                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="table-last-changed">Last change: <span title="%last_change_date">%last_change_diff%</span></div>

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

