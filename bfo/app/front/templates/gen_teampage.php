<div id="page-wrapper" style="max-width: 800px;">
    <div id="page-container">
    <div class="content-header team-stats-header"><h1 id="team-name"><?=$team->getNameAsString()?></h1></div>
        <div id="page-inner-wrapper">
            <div id="page-content">
                <div id="team-stats-container" style="display: inline-block">
                    <table class="team-stats-table" cellspacing="0" summary="Odds history for <?=$team->getNameAsString()?>">
                        <thead>
                            <tr>
                                <?php //<th>Result</th>?>
                                <th>Matchup</th>
                                <th style="text-align: right; padding-right: 4px;">Open</th>
                                <th style="text-align: center; width: 110px;" colspan="3">Closing range</th>
                                <th class="header-movement">Movement</th>
                                <th></th>
                                <th class="item-non-mobile" style="padding-left: 20px">Event</th>
                            </tr>
                        </thead>
                        <tbody>
                        
                        <?php $odds_counter = 0; ?>

                        <?php foreach ($matchups as $matchup): ?>

                            <tr class="event-header item-mobile-only-row">
                                <td colspan="8" scope="row"><a href="/events/<?=$matchup['event']->getEventAsLinkString()?>"><?=$matchup['event']->getName()?></a> <?=$matchup['event_date'] ?></td>
                            </tr>                            
                            <?php if ($matchup['team1_low'] != null && $matchup['event'] != null): ?>

                                <tr class="main-row">
                                    <?php /*<td class="resultcell <?=$sResultClass?>">
                                        <div class="result"><?=$sResult?></div>
                                    </td> */?>
                                    <th class="oppcell"><a href="/fighters/<?=$matchup['matchup_obj']->getFighterAsLinkString($matchup['team_pos'])?>"><?=$matchup['matchup_obj']->getFighterAsString($matchup['team_pos'])?></a></td>
                                    <td class="moneyline" style="padding-right: 4px;"><span id="oID<?=$odds_counter++?>"><?=$matchup['odds_opening']->getFighterOddsAsString($matchup['team_pos'])?></span></td>
                                    <td class="moneyline"><span id="oID<?=$odds_counter++?>"><?=($matchup['team_pos'] == 1 ? $matchup['team1_low']->getFighterOddsAsString(1) : $matchup['team2_low']->getFighterOddsAsString(2))?></span></td>
                                    <td class="dash-cell">...</td>
                                    <td class="moneyline" style="text-align: left; padding-left: 0; padding-right: 7px;">
                                        <span id="oID<?=$odds_counter++?>"><?=($matchup['team_pos'] == 1 ? $matchup['team1_high']->getFighterOddsAsString(1) : $matchup['team2_high']->getFighterOddsAsString(2))?></span>
                                    </td>
                                    
                                <?php if (strpos($matchup['graph_data'], ',') !== false): //Disable sparkline if only one line of odds available ?> 
                                            <td class="chart-cell" data-sparkline="<?=$matchup['graph_data']?>" data-li="[<?=$matchup['matchup_obj']->getID()?>,<?=$matchup['team_pos']?>]" rowspan="2"></td>
                                            <td rowspan="2" class="change-cell">
                                                <span class="teamPercChange" data-li="[<?=$matchup['matchup_obj']->getID()?>,<?=$matchup['team_pos']?>]">
                                                    <?=$matchup['percentage_change'] > 0 ? '+' : ''?><?=$matchup['percentage_change']?>%<span style="color: <?=$matchup['percentage_change'] > 0 ? '#4BCA02' : ($matchup['percentage_change'] < 0 ? '#E93524' : '') ?>;position:relative; margin-left: 0"><?=$matchup['percentage_change'] > 0 ? '▲' : ($matchup['percentage_change'] < 0 ? '▼' : '') ?></span>
                                                </span>
                                            </td>
                                <?php else: ?>
                                            <td class="chart-cell" rowspan="2"></td>
                                            <td rowspan="2" class="change-cell"></td>
                                <?php endif ?>
                                            
                                            <td class="item-non-mobile" scope="row" style="padding-left: 20px"><a href="/events/<?=$matchup['event']->getEventAsLinkString()?>" ><?=$matchup['event']->getName()?></a></td>
                                        </tr>
                                        <tr>
                                            <?php /* <td class="resultcell"><div class="method"><?=(isset($aResults['winner']) && $aResults['winner'] != '-1') ? '' . $sMethod . '' : ''?></div></td> */?>
                                            <th class="oppcell"><?='<a href="/fighters/' . $matchup['matchup_obj']->getFighterAsLinkString($matchup['other_pos']) . '">' . $matchup['matchup_obj']->getFighterAsString($matchup['other_pos']) . '</a>'?></td>
                                            <td class="moneyline" style="padding-right: 4px;"><span id="oID<?=$odds_counter++?>"><?=$matchup['odds_opening']->getFighterOddsAsString($matchup['other_pos'])?></span></td>
                                            <td class="moneyline"><span id="oID<?=$odds_counter++?>"><?=($matchup['team_pos'] == 1 ? $matchup['team2_low']->getFighterOddsAsString(2) : $matchup['team1_low']->getFighterOddsAsString(1))?></span></td>
                                            <td class="dash-cell">...</td>
                                            <td class="moneyline" style="text-align: left; padding-left: 0">
                                                <span id="oID<?=$odds_counter++?>"><?=($matchup['team_pos'] == 1 ? $matchup['team2_high']->getFighterOddsAsString(2) : $matchup['team1_high']->getFighterOddsAsString(1))?></span>
                                            </td>

                                    <td class="item-non-mobile" style="padding-left: 20px; color: #767676"><?=$matchup['event_date'] ?></td>
                                </tr>

                            <?php else: ?>

                                <tr class="main-row">
                                    <?php /* <td class="resultcell <?=$sResultClass?>">
                                        <div class="result"><?=$sResult?></div>
                                    </td> */?>
                                    <th class="oppcell"><?='<a href="/fighters/' . $matchup['matchup_obj']->getFighterAsLinkString($matchup['team_pos']) . '">' . $matchup['matchup_obj']->getFighterAsString($matchup['team_pos']) . '</a>'?></td>
                                    <td class="moneyline"></td>
                                    <td class="moneyline">n/a</td>
                                    <td></td>
                                    <td class="moneyline"></td>
                                    <td></td>
                                    <td></td>
                                    <td class="item-non-mobile" scope="row" style="padding-left: 20px"><a href="/events/<?=$matchup['event']->getEventAsLinkString()?>" ><?=$matchup['event']->getName()?></a></th>
                                </tr>
                                <tr>
                                    <?php /*    <td class="resultcell"><div class="method"><?=$aResults['winner'] != '-1' ? '' . $sMethod . '' : ''?></div></td> */?>
                                    <th class="oppcell"><?='<a href="/fighters/' . $matchup['matchup_obj']->getFighterAsLinkString($matchup['other_pos']) . '">' . $matchup['matchup_obj']->getFighterAsString($matchup['other_pos']) . '</a>'?></td>
                                    <td class="moneyline"></td>
                                    <td class="moneyline">n/a</td>
                                    <td></td>
                                    <td class="moneyline"></td>
                                    <td></td>
                                    <td></td>
                                    <td class="item-non-mobile" style="padding-left: 20px; color: #767676"><?=$matchup['event_date'] ?></td>
                                </tr>

                            <?php endif ?>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="page-bottom"></div>