<?php $this->insert('partials/event', $this->data) ?>

<div class="table-outer-wrapper">
    <div id="event-swing-area">
        <div class="chart-container">
            <div class="content-header">Line movement <div id="event-swing-picker-menu"><a href="#" class="event-swing-picker picked" data-li="0">Since opening</a> | <a href="#" class="event-swing-picker" data-li="1">Last 24 hours</a> | <a href="#" class="event-swing-picker" data-li="2">Last hour</a></div></div>
                <div id="event-swing-container" data-moves="<?=htmlentities(json_encode($swing_chart_data), ENT_QUOTES, 'UTF-8')?>" style="height: <?=(50 + (count($swing_chart_data[0]['data']) < 10 ? count($swing_chart_data[0]['data']) : 10) * 18)?>px;"></div>
                <div class="event-swing-expandarea <?=count($swing_chart_data[0]['data']) < 10 ? ' hidden' : ''?>"><a href="#" class="event-swing-expand"><span>Show all</span><div class="event-swing-expandarrow"></div></a></div>
            </div>
        </div>
    <div id="event-outcome-area">
        <div class="chart-container">
            <div class="content-header">Expected outcome</div>
            <div id="event-outcome-container" data-outcomes="<?=htmlentities(json_encode($expected_outcome_data), ENT_QUOTES, 'UTF-8')?>" style="height: <?=(67 + count($expected_outcome_data['data']) * 20)?>px;"></div>
        </div>
    </div>
</div>