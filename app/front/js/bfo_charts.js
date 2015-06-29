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

            marginTop: 18,
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
            tickPixelInterval: 50,

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
            tickPixelInterval: 50,

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
                animation: false,
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
                        fontWeight: 'normal',

                    },
                    y: -8,

                    overflow: 'none',
                    crop: false,
                    useHTML: true,
                },
            }

        },

        series: indata
    });
}


function getTeamSpreadChart(t) {
        $.getJSON('/ajax/ajax.Interface.php?function=getTeamSpreadData&t=' + t, function(indata) {
            createTeamSpreadChart(indata);
        });
}

function createTeamSpreadChart(indata) {
  
    $('#teamChartArea').highcharts({

        chart: {
            type: 'columnrange',
                      inverted: true  
        },

        title: {
            text: 'Temperature variation by month'
        },

        subtitle: {
            text: 'Observed in Vik i Sogn, Norway'
        },

        xAxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        },

        yAxis: {
            title: {
                text: 'Temperature ( °C )'
            }
        },

        tooltip: {
            valueSuffix: '°C'
        },

        plotOptions: {
            columnrange: {
                dataLabels: {
                    enabled: true,
                    formatter: function () {
                        return this.y + '°C';
                    }
                }
            }
        },

        legend: {
            enabled: false
        },

        series: [{
            name: 'Temperatures',
            data: indata
        }]

    });

}