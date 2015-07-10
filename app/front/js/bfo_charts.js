function createMChart(b, m, p) {
    $.getJSON('/ajax/ajax.Interface.php?function=getGraphData&b=' + b + '&m=' + m + '&p=' + p, function(indata) {
        createChart(indata);
    });
};

function createMIChart(m, p) {
    $.getJSON('/ajax/ajax.Interface.php?function=getGraphData&m=' + m + '&p=' + p, function(indata) {
        createChart(indata);
    });
};

function createPChart(b, m, p, pt, tn) {
    $.getJSON('/ajax/ajax.Interface.php?function=getGraphData&b=' + b + '&m=' + m + '&p=' + p + '&pt=' + pt + '&tn=' + tn, function(indata) {
        createChart(indata);
    });
};

function createPIChart(m, p, pt, tn) {
    $.getJSON('/ajax/ajax.Interface.php?function=getGraphData&m=' + m + '&p=' + p + '&pt=' + pt + '&tn=' + tn, function(indata) {
        createChart(indata);
    });
};

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
                        } else {
                            return singleDecimalToML(this.value);
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
                } else {
                    ttVal = singleDecimalToML(this.y);
                }
                return '<span style="color: #666; font-weight: bold; font-size: 11px">' + Highcharts.dateFormat('%a %d. %b %H:%M', this.x) + '</span><br/>' +

                    this.series.name + ': <b>' + ttVal + '</b>' + carr + '<br/>'

            },
            style: {
                fontSize: '12px'
            }
        },
        /*tooltip: {
                headerFormat: '<b>{series.name}</b><br>',
                pointFormat: '{point.x:%e. %b}: {point.y:.2f} m'
            },*/



        plotOptions: {

            series: {
                step: 'left',
                animation: false
                /*{duration: 500
                    }*/
            },
            line: {
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
                        if (this.point.index == 0 || this.series.chart.series[0].points.length - 1 == this.point.index) {
                            //return '<span style="font-size: 1.3em">singleDecimalToML(this.y) '</span>';
                            if (oddsType == 2) {
                                return '<span style="margin-left: 4px; margin-right: 4px;">' + Highcharts.numberFormat(this.y, 2); + '</span>';
                            } else {
                                return '<span style="margin-left: 4px; margin-right: 4px;">' + singleDecimalToML(this.y) + '</span>';
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




$(function () {
    /**
     * Create a constructor for sparklines that takes some sensible defaults and merges in the individual
     * chart options. This function is also available from the jQuery plugin as $(element).highcharts('SparkLine').
     */
    Highcharts.SparkLine = function (options, callback) {
        var defaultOptions = {
            chart: {
                renderTo: (options.chart && options.chart.renderTo) || this,
                backgroundColor: null,
                borderWidth: 0,
                type: 'area',
                margin: [2, 0, 2, 0],
                width: 120,
                height: 20,
                style: {
                    overflow: 'visible'
                },
                skipClone: true
            },
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
                backgroundColor: null,
                borderWidth: 0,
                shadow: false,
                useHTML: true,
                hideDelay: 0,
                shared: true,
                padding: 0,
                positioner: function (w, h, point) {
                    return { x: point.plotX - w / 2, y: point.plotY - h};
                }
            },
            plotOptions: {
                series: {
                    animation: false,
                    lineWidth: 1,
                    shadow: false,
                    states: {
                        hover: {
                            lineWidth: 1
                        }
                    },
                    marker: {
                        radius: 1,
                        states: {
                            hover: {
                                radius: 2
                            }
                        }
                    },
                    fillOpacity: 0.25
                },
                column: {
                    negativeColor: '#910000',
                    borderColor: 'silver'
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
                tooltip: {
                    headerFormat: '<span style="font-size: 10px">' + $td.parent().find('th').html() + ', Q{point.x}:</span><br/>',
                    pointFormat: '<b>{point.y}.000</b> USD'
                },
                chart: chart
            });

            n += 1;

            // If the process takes too much time, run a timeout to allow interaction with the browser
            if (new Date() - time > 500) {
                $tds.splice(0, i + 1);
                setTimeout(doChunk, 0);
                break;
            }

            // Print a feedback on the performance
            if (n === fullLen) {
                $('#result').html('Generated ' + fullLen + ' sparklines in ' + (new Date() - start) + ' ms');
            }
        }
    }
    doChunk();

});