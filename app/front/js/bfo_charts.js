function createMChart(b, p, m) {
    $.get('/api?f=ggd&b=' + b + '&m=' + m + '&p=' + p, function(indata) {
        createChart($.parseJSON(notIn(indata)));
    });
}

function createMIChart(m, p) {
    $.get('/api?f=ggd&m=' + m + '&p=' + p, function(indata) {
        createChart($.parseJSON(notIn(indata)));
    });
}

function createPChart(b, m, p, pt, tn) {
    $.get('/api?f=ggd&b=' + b + '&m=' + m + '&p=' + p + '&pt=' + pt + '&tn=' + tn, function(indata) {
        createChart($.parseJSON(notIn(indata)));
    });
}

function createPIChart(m, p, pt, tn) {
    $.get('/api?f=ggd&m=' + m + '&p=' + p + '&pt=' + pt + '&tn=' + tn, function(indata) {
        createChart($.parseJSON(notIn(indata)));
    });
}

function createEPChart(e, b, p, pt) {
    $.get('/api?f=ggd&b=' + b + '&e=' + e + '&p=' + p + '&pt=' + pt, function(indata) {
        createChart($.parseJSON(notIn(indata)));
    });
}

function createEPIChart(e, p, pt) {
    $.get('/api?f=ggd&e=' + e + '&p=' + p + '&pt=' + pt, function(indata) {
        createChart($.parseJSON(notIn(indata)));
    });
}

function createChart(indata) {
    Highcharts.setOptions({
        global: {
            useUTC: false
        }
    });

    $('#chart-area').highcharts({
        chart: {
            type: 'line',
            style: {
                fontFamily: "'Roboto', Arial, sans-serif"
            },
            marginTop: 18
        },
        credits: {
            enabled: false
        },
        legend: {
            enabled: false
        },
        colors: ['#393B42'],
        title: {
            text: '',
            style: {
                color: '#272727',
                fontSize: '12px',
                fontWeight: 'bold'
            }
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: { // don't display the dummy year
                month: '%e. %b',
                year: '%b'
            },
            title: {
                text: ''
            },
            tickPixelInterval: 50

        },
        yAxis: {
            title: {
                text: ''
            },
            labels: {
                formatter: function() {
                    if (this.value > 1) {
                        if (oddsType == 2) {
                            return Highcharts.numberFormat(this.value, 2);
                        } else if (oddsType == 4) {
                            //Rounds down to closest 5 factor
                            odds = 5 * Math.round(oneDecToML(this.value)/5);
                            return singleMLToFractional(odds);
                        } else {
                            return oneDecToML(this.value);
                        }

                    }
                    return '';
                }
            },
            tickPixelInterval: 50

        },
        tooltip: {
            formatter: function() {

                var index = this.series.xData.indexOf(this.x);
                var nextY = this.series.yData[index - 1];
                var carr = '';
                if (typeof this.series.yData[index - 1] !== 'undefined') {
                    if (this.series.yData[index - 1] < this.y) {
                        carr = '<span style="color: #4BCA02">▲</span>';
                    } else if (this.series.yData[index - 1] > this.y) {
                        carr = '<span style="color: #E93524">▼</span>';
                    }
                }

                var ttVal;
                if (oddsType == 2) { 
                    ttVal = Highcharts.numberFormat(this.y, 2);
                } else if (oddsType == 4) {
                    //Rounds down to closest 5 factor
                    odds = 5 * Math.round(oneDecToML(this.y)/5);
                    ttVal = singleMLToFractional(odds);
                } else {
                    ttVal = oneDecToML(this.y);
                }
                return '<span style="color: #666; font-weight: bold; font-size: 11px">' + Highcharts.dateFormat('%a %d. %b %H:%M', this.x) + '</span><br/>' +

                    this.series.name + ': <b>' + ttVal + '</b>' + carr + '<br/>';

            },
            style: {
                fontSize: '12px'
            }
        },

        plotOptions: {
            series: {
                step: 'left',
                animation: false
            },
            line: {
                turboThreshold: 1500,
                lineWidth: 1.75,
                marker: {
                    enabled: false,
                    fillColor: '#666',
                    radius: 2
                },
                states: {
                    hover: {
                        lineWidth: 1.75
                    }
                },
                dataLabels: {
                    shape: 'callout',
                    defer: false,
                    enabled: true,
                    formatter: function() {
                        //if ((this.y == this.series.chart.yAxis[0].getExtremes().dataMax || this.y == this.series.chart.yAxis[0].getExtremes().dataMin) && this.series.yData.indexOf(this.y) == this.point.index || this.series.chart.series[0].points.length -1 == this.point.index) {
                        if (this.point.index === 0 || this.series.chart.series[0].points.length - 1 == this.point.index) {
                            //return '<span style="font-size: 1.3em">oneDecToML(this.y) '</span>';
                            if (oddsType == 2) {
                                return '<span style="margin-left: 4px; margin-right: 4px;">' + Highcharts.numberFormat(this.y, 2) + '</span>';
                            } else if (oddsType == 4) {
                                //Rounds down to closest 5 factor
                                odds = 5 * Math.round(oneDecToML(this.y)/5);
                                return '<span style="margin-left: 4px; margin-right: 4px;">' + singleMLToFractional(odds) + '</span>';
                            } else {
                                return '<span style="margin-left: 4px; margin-right: 4px;">' + oneDecToML(this.y) + '</span>';
                            }
                        } else {
                            return null;
                        }
                    },


                    backgroundColor: 'rgba(69, 69, 69, 0.8)',
                    padding: 3,
                    style: {
                        textShadow: 0,
                        fontSize: '11px',
                        color: '#fff',
                        fontWeight: 'normal'

                    },
                    y: -8,

                    overflow: 'none',
                    crop: false,
                    useHTML: true
                }
            }

        },

        series: indata
    });
}

$(function() {
    if (document.getElementById("event-swing-container"))
    {
        function createSwingChart(in_data) {

            var xdata = in_data[0]['data'];
            var cats = [];
            for (var j = 0; j < xdata.length; j++) {
                cats.push(xdata[j][0]);
            }

            //Set max to 9 (or less depending on dynamic data)
            var maxten = 10
            if (xdata.length < 10)
            {
                maxten = xdata.length;
            }


            $('#event-swing-container').highcharts({
                title:{
                    text:''
                },

                legend: {
                    enabled: false
                },
                chart: { 
                   animation: false,
                    type: 'bar',
                    style: {
                        fontFamily: "'Roboto', Arial, sans-serif",
                        color: '#1a1a1a',
                        fontSize: '10px',
                        fontWeight: '500'
                    },
                },
                xAxis: {
                    categories: cats,
                    labels: {
                        overflow: 'justify',
                        style: {
                            fontSize: '10px',
                            fontWeight: '500',
                            align: 'left'
                        }
                    },
                    max: maxten - 1
                },
                yAxis: {
                    title: {
                        text: 'Line change (%)',
                        align: 'high'
                    },
                    //minTickInterval: 10,

                },
                tooltip: {
                    valueSuffix: ' %'
                },
                plotOptions: {
                    bar: {
                        dataLabels: {
                            formatter: function() {
                                if (this.y > 0)
                                {
                                    return '+' + this.y + '%';
                                }
                                else
                                {
                                    return this.y + '%';
                                }
                                
                            },
                            style: {
                                fontFamily: "'Roboto', Arial, sans-serif",
                                color: '#4d4d4d',
                                fontSize: '11px',
                                fontWeight: '500'
                            },
                            enabled: true,
                            crop: false,
                            overflow: 'none',
                            allowOverlap: true
                        },
                    },
                    series: {
                       animation: {
                        duration: 200,

                    },

                    }
                },
                credits: {
                    enabled: false
                },
                series: in_data,
            }); 
        }
        $('#event-swing-container').data('expanded', false);
        $('#event-swing-container').data('series', 0);
        var move_data = $.parseJSON($('#event-swing-container').attr('data-moves'));
        createSwingChart(move_data);
    }
});

$(function() {
    if (document.getElementById("event-outcome-container"))
    {
        function createOutcomeChart() {
            // http://jsfiddle.net/nLxL4Ljy/2/

            //var xdata = in_data[0]['data'];
            //var cats = [];
            //for (var j = 0; j < xdata.length; j++) {
            //    cats.push(xdata[j][0]);
            //}

            $('#event-outcome-container').highcharts({
                            chart: {
                        type: 'bar',
                    },

                    title: {
                        text: ''
                    },
                    xAxis: [{
                            categories: ['Jon Jones', 'Anderson Silva', 'Matt Hammill', 'Pete Sell', 'Matt Serra'],
                            reversed: false,
                            labels: {
                                step: 1
                            }
                        }, { // mirror axis on right side
                            opposite: true,
                            reversed: false,
                            categories: ['Royce Gracie', 'Mike Van Arsdale', 'CM Punk', 'Brock Lesnar', 'Demetrious Johson'],
                            linkedTo: 0,
                            labels: {
                                step: 1
                            }
                        }],
                    
                    yAxis: {
                        min: 0,
                        title: {
                            text: ''
                        }
                    },
                    tooltip: {
                        pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
                        shared: true
                    },
                    plotOptions: {
                        bar: {
                            stacking: 'percent',
                                            dataLabels: {
                                            formatter: function() {
                                        
                                                return this.series.name;
                                            },
                                enabled: true,
                                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                style: {
                                    fontSize: '10px',
                                    textShadow: '0 0 2px black'
                                }
                            },
                                        animation: false
                        }
                    },
                    series: [{
                        name: 'Decision',
                        data: [7, 3, 4, 7, 9],
                        color: '#404040'
                    }, {
                        name: 'TKO',
                        data: [4, 2, 3, 4, 1],
                        color: '#909090'
                    }, {
                        name: 'Submission',
                        data: [6, 4, 4, 6, 5],
                        color: '#c0c0c0'
                    },
                            {
                        name: 'Draw',
                        data: [1, 1, 1, 1, 1],
                        color: '#a10000'
                    },
                    {
                        name: 'Submission',
                        data: [5, 3, 4, 7, 2],
                        color: '#c0c0c0'
                    }, {
                        name: 'TKO',
                        data: [2, 2, 3, 2, 1],
                        color: '#909090'
                    }, {
                        name: 'Decision',
                        data: [3, 4, 4, 2, 5],
                         color: '#404040'
                    }]

            });
        }
        //var move_data = $.parseJSON($('#event-swing-container').attr('data-moves'));
        createOutcomeChart();
    }
});

$(function () {
    $('.event-swing-picker').click(function () {
        var opts = $(this).attr('data-li');
        var container = $('#event-swing-container');
        var chart = container.highcharts();

        //Ignore if switching to same category
        if (opts == container.data('series'))
        {
            return false;
        }

        container.data('series', opts);
        //If max value is < 10 then set the toggled option automatically. Otherwise we always dexpand it
        if (chart.series[opts].data.length < 10)
        {
            container.data('expanded', !container.data('expanded'));
        }
        else
        {
            container.data('expanded', false);
        }

        var series = chart.series;
        var i = series.length;
        var otherSeries;
        while (i--) {
            otherSeries = series[i];
            if (i != opts) {
                otherSeries.hide();
            }
        }
        series[opts].show();
        cats = getCategoriesFromSeries(chart.series[opts].data);
        chart.xAxis[0].setCategories(cats);
        setSwingMaxValue(container);

        resizeSwingChart(chart.series[opts].data);
        return false;
    });

    $('.event-swing-expand').click(function () {
        
        var container = $('#event-swing-container');
        var chart = container.highcharts();
      
        container.data('expanded', !container.data('expanded'));

        cats = getCategoriesFromSeries(chart.series[container.data('series')].data); 
        chart.xAxis[0].setCategories(cats);
        setSwingMaxValue($('#event-swing-container'));
        resizeSwingChart(chart.series[container.data('series')].data);

        return false;
    });


    function getCategoriesFromSeries(data)
    { 
        var cats = [];
        for (var j = 0; j < data.length; j++) {
            cats.push(data[j].name);
        }
        return cats;
    }

    function setSwingMaxValue(container)
    {
        var chart = container.highcharts();
        if (container.data('expanded') == false)
        {       
            var maxten = 10;
            if (chart.series[container.data('series')].data.length < 10)
            {
                maxten = chart.series[container.data('series')].data.length;
            }
            chart.xAxis[0].setExtremes(null, maxten - 1);
        }
        else
        {
            chart.xAxis[0].setExtremes(null, chart.series[container.data('series')].data.length - 1);
        }        
    }

    function resizeSwingChart(data)
    {
        var chart = $('#event-swing-container').highcharts();
        $('#event-swing-container').css("height", 60 + (chart.xAxis[0].getExtremes().max + 1) * 18);
        chart.setSize(chart.chartWidth, 60 + (chart.xAxis[0].getExtremes().max + 1) * 18);
        chart.redraw();
    }
});


$(function() {
    Highcharts.SparkLine = function(options, callback) {
        var defaultOptions = {
            chart: {
                renderTo: (options.chart && options.chart.renderTo) || this,
                backgroundColor: null,
                borderWidth: 0,
                type: 'area',
                margin: [3, 0, 5, 2],
                width: 50,
                height: 32,
                className: 'chart-spark',
                events: {
                    click: function(event) {
                        var opts = $.parseJSON($('#' + this.container.id).closest('td').attr('data-li'));
                        var versus = $('#' + this.container.id).closest('tr').next('tr').find("th.oppcell").text();
                        var title = $("#team-name").text() + " <span style=\"font-weight: normal;\">(vs. " + versus +  ") &#150; Mean odds";
                        chartCC();
                        createMIChart(opts[0], opts[1]);
                        chartSC(title, event.clientX, event.clientY);
                    }
                },
                style: {
                    overflow: 'visible'
                },
                skipClone: true
            },
            colors: ['#000'],
            title: {
                text: ''
            },
            credits: {
                enabled: false
            },
            xAxis: {
                labels: {
                    enabled: false
                },
                title: {
                    text: null
                },
                startOnTick: false,
                endOnTick: false,
                tickPositions: []
            },
            yAxis: {
                endOnTick: false,
                startOnTick: false,
                labels: {
                    enabled: false
                },
                title: {
                    text: null
                },
                tickPositions: [0]
            },
            legend: {
                enabled: false
            },
            tooltip: {
                enabled: false

            },
            plotOptions: {
                series: {
                    threshold: null,
                    animation: false,
                    lineWidth: 0.65,
                    states: {
                        hover: {
                            enabled: false
                        }
                    },
                    shadow: false,
                    events: {
                        click: function(event) {
                            var opts = $.parseJSON($('#' + this.chart.container.id).closest('td').attr('data-li'));
                            var versus = $('#' + this.chart.container.id).closest('tr').next('tr').find("th.oppcell").text();
                            var title = $("#team-name").text() + " <span style=\"font-weight: normal;\">(vs. " + versus +  ") &#150; Mean odds";
                            chartCC();
                            createMIChart(opts[0], opts[1]);
                            chartSC(title, event.clientX, event.clientY);
                        }
                    },

                    marker: {
                        enabled: false
                    },
                    fillColor: '#e9ebed'


                }
            }
        };
        options = Highcharts.merge(defaultOptions, options);

        return new Highcharts.Chart(options, callback);
    };

    var start = +new Date(),
        $tds = $("td[data-sparkline]"),
        fullLen = $tds.length,
        n = 0;

    // Creating 153 sparkline charts is quite fast in modern browsers, but IE8 and mobile
    // can take some seconds, so we split the input into chunks and apply them in timeouts
    // in order avoid locking up the browser process and allow interaction.
    function doChunk() {
        var time = +new Date(),
            i,
            len = $tds.length,
            $td,
            stringdata,
            arr,
            data,
            chart;

        for (i = 0; i < len; i += 1) {
            $td = $($tds[i]);
            stringdata = $td.data('sparkline');
            arr = stringdata.split('; ');
            data = $.map(arr[0].split(', '), parseFloat);
            chart = {};

            if (arr[1]) {
                chart.type = arr[1];
            }
            $td.highcharts('SparkLine', {
                series: [{
                    data: data,
                    pointStart: 1
                }],
                chart: chart
            });

            n += 1;

            // If the process takes too much time, run a timeout to allow interaction with the browser
            if (new Date() - time > 500) {
                $tds.splice(0, i + 1);
                setTimeout(doChunk, 0);
                break;
            }
        }
    }
    doChunk();

});

