<div class="table-outer-wrapper">
    <div class="table-header"><a href="/events/<?= $event->getEventAsLinkString() ?>">
            <?php if (strtoupper($event->getName()) != 'FUTURE EVENTS') : //Add date for all events except for FUTURE EVENTS ?> 
            
                <?= date('F jS', strtotime($event->getDate())) ?>
            <?php else : ?>
                <?=$event->getName()?>
            <?php endif ?>
        </a>

        <div class="share-area">
            <div class="share-button"></div>
        </div>
        <div class="share-window">
            <div data-href="https://twitter.com/intent/tweet?text=<?= urlencode($event->getName() . ' betting lines') ?>&amp;url=<?= urlencode('https://www.proboxingodds.com/events/' . $event->getEventAsLinkString()) ?>" class="share-item share-twitter"></div>
            <div data-href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://www.proboxingodds.com/events/' . $event->getEventAsLinkString()) ?>" class="share-item share-facebook"></div>
            <div data-href="whatsapp://send?text=<?= urlencode($event->getName() . ' betting lines') ?> <?= urlencode('https://www.proboxingodds.com/events/' . $event->getEventAsLinkString()) ?>" data-action="share/whatsapp/share" class="share-item share-whatsapp item-mobile-only"></div>
        </div>

    </div>
    <div class="table-div" id="event<?= $event->getID() ?>">


        <?php if (count($matchups) > 0) : ?>

            <table class="odds-table odds-table-responsive-header">
                <thead>
                    <tr>
                        <th scope="col" class="date-head"></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>

                    <?php //============== Add matchups ====================== 
                    ?>
                    <?php foreach (array_reverse($matchups) as $matchup_key => $matchup) : ?>
                        

                        <?php for ($i = 1; $i <= 2; $i++) : ?>
                            <tr <?= $i == 1 ? 'id="mu-' . $matchup->getID() . '"' : '' ?> <?= (($i == 2 && $matchup_key == array_key_last($matchups)) ? ' style="border-bottom: 0;" ' : '') ?>>
                                <td class="date-cell" data-time="<?=date('H:i', intval($matchup->getMetadata('max_gametime')))?>"><?= ($i == 1 ? date('H:i', intval($matchup->getMetadata('max_gametime'))) : (date('H:i', intval($matchup->getMetadata('max_gametime'))) != '' ? 'UTC' : '')) ?></td>
                                <th scope="row"><a href="/fighters/<?= $matchup->getFighterAsLinkString($i) ?>"><span class="t-b-fcc"><?= $matchup->getFighterAsString($i) ?></span></a></th>
                            </tr>
                        <?php endfor ?>
                        <?php //============== Add props ======================= 
                        ?>
                        <?php if (isset($matchup_prop_count[$matchup->getID()]) && $matchup_prop_count[$matchup->getID()] > 0) : ?>
                            <?php foreach ($prop_odds[$event->getID()][$matchup->getID()] as $proptype_id => $team_num_row) : ?>
                                <?php foreach ($team_num_row as $team_num => $prop) : ?>
                                    <?php for ($i = 1; $i <= 2; $i++) : ?>
                                        <tr class="pr">
                                            <th scope="row"  colspan="2"><?= $i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName() ?></th>
                                        </tr>
                                    <?php endfor ?>
                                <?php endforeach ?>
                            <?php endforeach ?>
                        <?php endif ?>
                    <?php endforeach ?>

                    <?php //============== Add event props ======================= 
                    ?>

                    <?php if ($event_prop_count > 0) : ?>
                        <tr class="eventprop" id="mu-e<?= $event->getID() ?>">
                            <th scope="row" style="font-weight: 400"><a href="#" data-mu="<?= $event->getID() ?>">Event props</a></th>
                        </tr>
                        <tr style="display: none;"></tr>
                        <?php foreach ($event_prop_odds[$event->getID()] as $proptype_id => $prop) : ?>
                            <?php for ($i = 1; $i <= 2; $i++) : ?>
                                <tr class="pr">
                                    <th scope="row"  colspan="2"><?= $i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName() ?></th>
                                </tr>
                            <?php endfor ?>
                        <?php endforeach ?>
                    <?php endif ?>

                </tbody>
            </table>

            <div class="table-inner-wrapper">
                <div class="table-inner-shadow-left"></div>
                <div class="table-inner-shadow-right"></div>
                <div class="table-scroller">
                    <table class="odds-table">
                        <thead>
                            <tr>
                                <th scope="col" class="date-head"></th>
                                <th scope="col"></th>
                                <?php foreach ($bookies as $bookie) : ?>
                                    <th scope="col" data-b="<?= $bookie->getID() ?>"><a href="/out/<?= $bookie->getID() ?>" onclick="lO(<?= $bookie->getID() ?>,<?= $event->getID() ?>);"><?= str_replace(' ', '&nbsp;', (strlen($bookie->getName()) > 10 ? (substr($bookie->getName(), 0, 9) . '.') : $bookie->getName())) ?></a></th>
                                <?php endforeach ?>
                                <th scope="col" colspan="3" class="table-prop-header">Props</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php //============== Add matchups ====================== 
                            ?>

                            <?php foreach (array_reverse($matchups) as $matchup_key => $matchup) : ?>

                                <?php for ($i = 1; $i <= 2; $i++) : ?>

                                    <tr <?= (($i == 2 && $matchup_key == array_key_last($matchups)) ? ' style="border-bottom: 0;" ' : '') ?>>
                                        <td class="date-cell" data-time="<?=date('H:i', intval($matchup->getMetadata('max_gametime')))?>"><?= ($i == 1 ? date('H:i', intval($matchup->getMetadata('max_gametime'))) : (date('H:i', intval($matchup->getMetadata('max_gametime'))) != '' ? 'UTC' : '')) ?></td>
                                        <th scope="row"><a href="/fighters/<?= $matchup->getFighterAsLinkString($i) ?>"><span class="t-b-fcc"><?= $matchup->getFighterAsString($i) ?></span></a></th>

                                        <?php foreach ($bookies as $bookie) : ?>

                                            <?php $odds = @$matchup_odds[$event->getID()][$matchup->getID()][$bookie->getID()]; 
                                            ?>

                                            <?php if (isset($odds['odds_obj'])) : ?>

                                                <td class="but-sg" data-li="[<?= $odds['odds_obj']->getBookieID() ?>,<?= $i ?>,<?= $odds['odds_obj']->getFightID() ?>]">
                                                    <span id="oID<?= ('1' . sprintf("%06d", $odds['odds_obj']->getFightID()) . sprintf("%02d", $odds['odds_obj']->getBookieID()) . $i) ?>" <?= isset($odds['is_best_team' . $i]) ? ' class="bestbet"' : '' ?>><?= $odds['odds_obj']->getFighterOddsAsString($i) ?></span>
                                                    <?php if (isset($odds['previous_team' . $i . '_odds'])) : ?>
                                                        <?php if ($odds['odds_obj']->getOdds($i) > $odds['previous_team' . $i . '_odds']) : ?>
                                                            <span class="aru changedate-<?= $odds['odds_obj']->getDate() ?>">▲</span>
                                                        <?php elseif ($odds['odds_obj']->getOdds($i) < $odds['previous_team' . $i . '_odds']) : ?>
                                                            <span class="ard changedate-<?= $odds['odds_obj']->getDate() ?>">▼</span>
                                                        <?php endif ?>
                                                    <?php endif ?>
                                                </td>

                                            <?php else : ?>

                                                <td></td>

                                            <?php endif ?>

                                        <?php endforeach ?>


                                        <?php if (isset($alerts_enabled) && $alerts_enabled == true) : //Add alert cell only if enabled for this type of page
                                        ?>
                                            <td class="button-cell but-al" data-li="[<?= $matchup->getID() ?>,<?= $i ?>]">
                                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                    <g>
                                                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"></path>
                                                    </g>
                                                </svg>
                                            </td>
                                        <?php endif ?>

                                        <?php if (count($matchup_odds[$event->getID()][$matchup->getID()]) >= 1) : ?>
                                            <td class="button-cell but-si" data-li="[<?= $i ?>,<?= $matchup->getID() ?>]">
                                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                    <g>
                                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path>
                                                    </g>
                                                </svg>
                                            </td>
                                        <?php else : ?>
                                            <td class="button-cell but-si">
                                                <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                    <g>
                                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path>
                                                    </g>
                                                </svg>
                                            </td>
                                        <?php endif ?>

                                        <td class="prop-cell prop-cell-exp" data-mu="<?= $matchup->getID() ?>">
                                            <?php if (isset($matchup_prop_count[$matchup->getID()])) : ?>
                                                <?= $matchup_prop_count[$matchup->getID()] ?>&nbsp;<span class="exp-ard"></span>
                                            <?php else : ?>
                                                &nbsp;
                                            <?php endif ?>
                                        </td>

                                    </tr>

                                <?php endfor ?>

                                <?php //============== Add props ======================= 
                                ?>

                                <?php if (isset($matchup_prop_count[$matchup->getID()]) && $matchup_prop_count[$matchup->getID()] > 0) : ?>
                                    <?php foreach ($prop_odds[$event->getID()][$matchup->getID()] as $proptype_id => $team_num_row) : ?>

                                        <?php foreach ($team_num_row as $team_num => $prop) : ?>

                                            <?php for ($i = 1; $i <= 2; $i++) : ?>

                                                <tr class="pr">
                                                    <th scope="row" colspan="2"><?= $i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName() ?></th>

                                                    <?php foreach ($bookies as $bookie) : ?>

                                                        <?php $odds = @$prop[$bookie->getID()]; 
                                                        ?>
                                                        <?php if (isset($odds['odds_obj'])) {
                                                            $odds_val = ($i == 1 ? $odds['odds_obj']->getPropOdds() : $odds['odds_obj']->getNegPropOdds());
                                                        } 
                                                        ?>
                                                        <?php $previous_odds_val = $i == 1 ?  @$prop[$bookie->getID()]['previous_prop_odds'] : @$prop[$bookie->getID()]['previous_negprop_odds']; 
                                                        ?>

                                                        <?php if (isset($odds['odds_obj'])) : ?>

                                                            <?php if (($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()) != '-99999') : ?>

                                                                <td class="but-sgp" data-li="[<?= $bookie->getID() ?>,<?= $i ?>,<?= $matchup->getID() ?>,<?= $proptype_id ?>,<?= $team_num ?>]"><span id="oID<?= ('2' . sprintf("%06d", $matchup->getID()) . sprintf("%02d", $bookie->getID()) . $i . sprintf("%03d", $proptype_id) . $team_num) ?>" <?= $i == 1 ? (isset($odds['is_best_pos']) ? ' class="bestbet"' : '') : (isset($odds['is_best_neg']) ? ' class="bestbet"' : '') ?>><?= $i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString() ?></span>
                                                                    <?php if (isset($previous_odds_val)) : ?>
                                                                        <?php if ($odds_val > $previous_odds_val) : ?>
                                                                            <span class="aru changedate-<?= $odds['odds_obj']->getDate() ?>">▲</span>
                                                                        <?php elseif ($odds_val < $previous_odds_val) : ?>
                                                                            <span class="ard changedate-<?= $odds['odds_obj']->getDate() ?>">▼</span>
                                                                        <?php endif ?>
                                                                    <?php endif ?>

                                                                </td>

                                                            <?php else : ?>
                                                                <td><span class="na">n/a</span></td>
                                                            <?php endif ?>

                                                        <?php else : ?>

                                                            <td></td>

                                                        <?php endif ?>

                                                    <?php endforeach ?>

                                                    <?php if (isset($alerts_enabled) && $alerts_enabled == true) : //Add alert cell only if enabled for this type of page
                                                    ?>
                                                        <td class="button-cell">
                                                            <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                                <g>
                                                                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"></path>
                                                                </g>
                                                            </svg>
                                                        </td>
                                                    <?php endif ?>

                                                    <td class="button-cell but-sip" data-li="[<?= $i ?>,<?= $matchup->getID() ?>,<?= $proptype_id ?>,<?= $team_num ?>]">
                                                        <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                            <g>
                                                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path>
                                                            </g>
                                                        </svg>
                                                    </td>

                                                    <td class="prop-cell"></td>
                                                </tr>

                                            <?php endfor ?>

                                        <?php endforeach ?>

                                    <?php endforeach ?>
                                <?php endif ?>

                            <?php endforeach ?>

                            <?php //============== Add event props ======================= 
                            ?>

                            <?php if ($event_prop_count > 1) : ?>

                                <tr class="eventprop" id="mu-e<?= $event->getID() ?>">
                                    <th scope="row" style="font-weight: 400"><a href="#" data-mu="<?= $event->getID() ?>">Event props</a></th>

                                    <?php foreach ($bookies as $bookie) : ?>
                                        <td></td>
                                    <?php endforeach ?>
                                    <?php if (isset($alerts_enabled) && $alerts_enabled == true) : //Add alert cell only if enabled for this type of page
                                    ?>
                                        <td class="button-cell"></td>
                                    <?php endif ?>
                                    <td class="button-cell"></td>
                                    <td class="prop-cell prop-cell-exp" data-mu="e<?= $event->getID() ?>">
                                        <?= $event_prop_count ?>&nbsp;<span class="exp-ard"></span>
                                    </td>

                                </tr>
                                <tr style="display: none;">
                                    <th scope="row"></th>
                                    <?php foreach ($bookies as $bookie) : ?>
                                        <td></td>
                                    <?php endforeach ?>
                                    <td class="button-cell"></td>
                                    <td class="prop-cell prop-cell-exp">
                                </tr>

                                <?php foreach ($event_prop_odds[$event->getID()] as $proptype_id => $prop) : ?>

                                    <?php for ($i = 1; $i <= 2; $i++) : ?>

                                        <tr class="pr">
                                            <th scope="row"><?= $i == 1 ? $prop[array_key_first($prop)]['odds_obj']->getPropName() : $prop[array_key_first($prop)]['odds_obj']->getNegPropName() ?></th>

                                            <?php foreach ($bookies as $bookie) : ?>

                                                <?php $odds = @$prop[$bookie->getID()]; 
                                                ?>
                                                <?php if (isset($odds['odds_obj'])) {
                                                    $odds_val = ($i == 1 ? $odds['odds_obj']->getPropOdds() : $odds['odds_obj']->getNegPropOdds());
                                                } 
                                                ?>
                                                <?php $previous_odds_val = $i == 1 ?  @$prop[$bookie->getID()]['previous_prop_odds'] : @$prop[$bookie->getID()]['previous_negprop_odds']; 
                                                ?>

                                                <?php if (isset($odds['odds_obj'])) : ?>

                                                    <?php if (($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()) != '-99999') : ?>

                                                        <td class="but-sgep" data-li="[<?= $event->getID() ?>,<?= $bookie->getID() ?>,<?= $i ?>,<?= $proptype_id ?>,0]"><span id="oID<?= ('2' . sprintf("%06d", $odds['odds_obj']->getMatchupID()) . sprintf("%02d", $bookie->getID()) . $i . sprintf("%03d", $proptype_id) . 0) ?>" <?= $i == 1 ? (isset($odds['is_best_pos']) ? ' class="bestbet"' : '') : (isset($odds['is_best_neg']) ? ' class="bestbet"' : '') ?>><?= ($i == 1 ? $odds['odds_obj']->getPropOddsAsString() : $odds['odds_obj']->getNegPropOddsAsString()) ?></span>
                                                            <?php if (isset($previous_odds_val)) : ?>
                                                                <?php if ($odds_val > $previous_odds_val) : ?>
                                                                    <span class="aru changedate-<?= $odds['odds_obj']->getDate() ?>">▲</span>
                                                                <?php elseif ($odds_val < $previous_odds_val) : ?>
                                                                    <span class="ard changedate-<?= $odds['odds_obj']->getDate() ?>">▼</span>
                                                                <?php endif ?>
                                                            <?php endif ?>

                                                        </td>

                                                    <?php else : ?>
                                                        <td><span class="na">n/a</span></td>
                                                    <?php endif ?>

                                                <?php else : ?>

                                                    <td></td>

                                                <?php endif ?>

                                            <?php endforeach ?>

                                            <?php if (isset($alerts_enabled) && $alerts_enabled == true) : //Add alert cell only if enabled for this type of page
                                            ?>
                                                <td class="button-cell">
                                                    <svg class="svg-i-disabled" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                        <g>
                                                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"></path>
                                                        </g>
                                                    </svg>
                                                </td>
                                            <?php endif ?>

                                            <td class="button-cell but-siep" data-li="[<?= $event->getID() ?>,<?= $i ?>,<?= $proptype_id ?>, 0]">
                                                <svg class="svg-i" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                    <g>
                                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path>
                                                    </g>
                                                </svg>
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
        <?php else : ?>

            <div class="no-odds">There are currently no odds available for this event<br><br>
                Get notified when odds are available using our <a href="/alerts" style="text-decoration: underline">Alerts</a> feature or by following us on <a href="http://twitter.com/proboxingodds" target="_blank" rel="noopener" style="text-decoration: underline">Twitter</a>
            </div>

        <?php endif ?>
    </div>
</div>
<div class="table-last-changed">Last change: <span title="%<?= $event->getID() ?>_last_change_date%">%<?= $event->getID() ?>_last_change_diff%</span></div>