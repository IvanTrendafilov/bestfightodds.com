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
            useUTC: true
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
                
                //Format and adjust date according to user timezone
                var now = new Date();
                var da = new Date(this.x - (now.getTimezoneOffset() * 60000));

                var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                var monthNames = [
                    "Jan", "Feb", "Mar",
                    "Apr", "May", "Jun", "Jul",
                    "Aug", "Sep", "Oct",
                    "Nov", "Dec"
                  ];
                var formatted_date = days[da.getDay()] + ', ' + monthNames[da.getMonth()] + ' ' + da.getDate() + ', ' + (da.getHours() < 10? '0' : '') + da.getHours() + ':' + (da.getMinutes() < 10? '0' : '') + da.getMinutes();
                //Old method: Highcharts.dateFormat('%a %d. %b %H:%M', this.x)

                return '<span style="color: #666; font-weight: bold; font-size: 11px">' +  formatted_date + '</span><br/>' + this.series.name + ': <b>' + ttVal + '</b>' + carr + '<br/>';

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

