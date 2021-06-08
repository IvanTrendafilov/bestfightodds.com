<div id="page-wrapper" style="max-width: 800px;">
    <div id="page-container">
    <div class="content-header team-stats-header" style="display: flex"><h1 id="team-name"><?=$event->getName()?></h1></a>
        <?php if (strtoupper($event->getName()) != 'FUTURE EVENTS'): //Add date for all events except for FUTURE EVENTS?>
            <span class="table-header-date"><?=date('F jS', strtotime($event->getDate()))?></span>
        <?php endif ?></div>
        <div id="page-inner-wrapper">
            <div id="page-content">
                <div id="team-stats-container" style="display: inline-block">
                    <table class="team-stats-table" cellspacing="0" summary="Odds for <?=$event->getName()?>">
                        <thead>
                            <tr>
                                <?php //<th>Result</th>?>
                                <th>Matchup</th>
                                <th style="text-align: right; padding-right: 4px;">Opening line</th>
                                <th style="text-align: center;" colspan="3">Closing range</th>
                                <th class="header-movement" colspan="2">Movement</th>
                            </tr>
                        </thead>
                        <tbody>
                        
                        <?php $odds_counter = 0; ?>

                        <?php foreach ($matchups as $matchup): ?>

                            <?php if ($matchup['team1_low'] != null): ?>

                                <tr class="main-row">
                                    <th class="oppcell"><a href="/fighters/<?=$matchup['matchup_obj']->getFighterAsLinkString(1)?>"><?=$matchup['matchup_obj']->getFighterAsString(1)?></a></td>
                                    <td class="moneyline" style="padding-right: 4px;"><span id="oID<?=$odds_counter++?>"><?=$matchup['odds_opening']->getFighterOddsAsString(1)?></span></td>
                                    <td class="moneyline"><span id="oID<?=$odds_counter++?>"><?=$matchup['team1_low']->getFighterOddsAsString(1)?></span></td>
                                    <td class="dash-cell">...</td>
                                    <td class="moneyline" style="text-align: left; padding-left: 0; padding-right: 7px;">
                                        <span id="oID<?=$odds_counter++?>"><?=$matchup['team1_high']->getFighterOddsAsString(1)?></span>
                                    </td>
                                    
                                <?php if (strpos($matchup['graph_data_team1'], ',') !== false): //Disable sparkline if only one line of odds available ?> 
                                            <td class="chart-cell" data-sparkline="<?=$matchup['graph_data_team1']?>" data-li="[<?=$matchup['matchup_obj']->getID()?>,1]"></td>
                                            <td class="change-cell">
                                                <span class="teamPercChange" data-li="[<?=$matchup['matchup_obj']->getID()?>,1]">
                                                    <?=$matchup['percentage_change_team1'] > 0 ? '+' : ''?><?=$matchup['percentage_change_team1']?>%<span style="color: <?=$matchup['percentage_change_team1'] > 0 ? '#4BCA02' : ($matchup['percentage_change_team1'] < 0 ? '#E93524' : '') ?>;position:relative; margin-left: 0"><?=$matchup['percentage_change_team1'] > 0 ? '▲' : ($matchup['percentage_change_team1'] < 0 ? '▼' : '') ?></span>
                                                </span>
                                            </td>
                                <?php else: ?>
                                            <td class="chart-cell"></td>
                                            <td class="change-cell"></td>
                                <?php endif ?>
                                            
                                        </tr>

                                        <tr>
                                    <th class="oppcell"><a href="/fighters/<?=$matchup['matchup_obj']->getFighterAsLinkString(2)?>"><?=$matchup['matchup_obj']->getFighterAsString(2)?></a></td>
                                    <td class="moneyline" style="padding-right: 4px;"><span id="oID<?=$odds_counter++?>"><?=$matchup['odds_opening']->getFighterOddsAsString(2)?></span></td>
                                    <td class="moneyline"><span id="oID<?=$odds_counter++?>"><?=$matchup['team1_low']->getFighterOddsAsString(2)?></span></td>
                                    <td class="dash-cell">...</td>
                                    <td class="moneyline" style="text-align: left; padding-left: 0; padding-right: 7px;">
                                        <span id="oID<?=$odds_counter++?>"><?=$matchup['team1_high']->getFighterOddsAsString(2)?></span>
                                    </td>
                                    
                                <?php if (strpos($matchup['graph_data_team2'], ',') !== false): //Disable sparkline if only one line of odds available ?> 
                                            <td class="chart-cell" data-sparkline="<?=$matchup['graph_data_team2']?>" data-li="[<?=$matchup['matchup_obj']->getID()?>,2]"></td>
                                            <td class="change-cell">
                                                <span class="teamPercChange" data-li="[<?=$matchup['matchup_obj']->getID()?>,2]">
                                                    <?=$matchup['percentage_change_team2'] > 0 ? '+' : ''?><?=$matchup['percentage_change_team2']?>%<span style="color: <?=$matchup['percentage_change_team2'] > 0 ? '#4BCA02' : ($matchup['percentage_change_team2'] < 0 ? '#E93524' : '') ?>;position:relative; margin-left: 0"><?=$matchup['percentage_change_team2'] > 0 ? '▲' : ($matchup['percentage_change_team2'] < 0 ? '▼' : '') ?></span>
                                                </span>
                                            </td>
                                <?php else: ?>
                                            <td class="chart-cell"></td>
                                            <td class="change-cell"></td>
                                <?php endif ?>
                                            
                                        </tr>

                            <?php else: ?>

                                <tr class="main-row">
                                    <?php /* <td class="resultcell <?=$sResultClass?>">
                                        <div class="result"><?=$sResult?></div>
                                    </td> */?>
                                    <th class="oppcell"><?='<a href="/fighters/' . $matchup['matchup_obj']->getFighterAsLinkString(1) . '">' . $matchup['matchup_obj']->getFighterAsString(1) . '</a>'?></td>
                                    <td class="moneyline"></td>
                                    <td class="moneyline">n/a</td>
                                    <td></td>
                                    <td class="moneyline"></td>
                                    <td></td>
                                    <td></td>
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