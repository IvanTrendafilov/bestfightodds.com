// ========= MAIN ===========

var storedOdds = [];
var usedSearch = false;
var oddsType = 1; //1. Moneyline 2. Decimal. 3. Custom
var parlayMode = false;
var parlay = [];
var autoRefresh = false;
var refreshID = null;
var refreshOpenProps = {};
var scrollCache = [];
var scrollX = 0;
var scrollCaptain = null;

//showHistory
function sH(obj, bookie, fighter, fight) {

console.log($(obj));
    if (parlayMode) {
        return addToParlay(obj);
    } else {
        var title = $(obj).parent().parent().find("th").find("a").text() + " <span style=\"font-weight: normal;\"> &#150; " + $(obj).closest('table').find('th').eq($(obj).parent().index()).find("a").text() + "</span>";
        clearChart();
        showChart(title, obj);
        createMChart(bookie, fight, fighter);
        return false;
    }
    return null;
}

//showHistoryProp
function sHp(obj, bookie, posprop, matchup, proptype, teamnum) {
    if (parlayMode) {
        return addToParlay(obj);
    } else {
        var title = $(obj).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; " + $(obj).closest('table').find('th').eq($(obj).parent().index()).find("a").text() + "</span>";
        clearChart();
        showChart(title, obj);
        createPChart(bookie, matchup, posprop, proptype, teamnum);
        return false;
    }
    return null;
}

//showIndex
function sI(target, fighter, fight) {
    if (parlayMode) {
        return false;
    } else {
        var title = $(target).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean odds";
        clearChart();
        showChart(title, target);
        createMIChart(fight, fighter);
        return false;
    }

    return null;
}


//showPropIndex
function sIp(target, posprop, matchup, proptype, teamnum) {
    if (parlayMode) {
        return false;
    } else {
        var title = $(target).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean";
        clearChart();
        showChart(title, target);
        createPIChart(matchup, posprop, proptype, teamnum);
        return false;
    }
}

//showNoHistory
function sNH(obj, bookie, fighter, fight) {
    if (parlayMode) {
        return addToParlay(obj);
    } else {
        var prevHTML = $(obj).html();
        $(obj).html("<img src=\'/img/ajax-loader2.gif\' />");

        $.get("/ajax/ajax.Interface.php?function=getLineDetails", {
            m: fight,
            b: bookie,
            p: fighter
        }, function(data) {
            json_data = eval('(' + data + ')');

            var openLine = json_data.open.odds;
            if (oddsType == 2) {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
            }
            $(obj).html(prevHTML);
            return overlib('<div class=\'popup-container\' style="height: 65px;"><div class=\'popup-header\'>Last change: <span style=\'font-weight: normal;\'>' + json_data.current.changed + '<span><span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><br />No line movement since opening</div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5, FULLHTML);
        });
        return false;
    }
}

//showNoHistoryProp
function sNHp(obj, bookie, posprop, matchup, proptype, teamnum) {
    if (parlayMode) {
        return addToParlay(obj);
    } else {
        var prevHTML = $(obj).html();
        $(obj).html("<img src=\'/img/ajax-loader2.gif\' />");

        $.get("/ajax/ajax.Interface.php?function=getPropLineDetails", {
            m: matchup,
            p: posprop,
            pt: proptype,
            tn: teamnum,
            b: bookie

        }, function(data) {

            json_data = eval('(' + data + ')');

            var openLine = json_data.open.odds;
            var currentLine = json_data.current.odds;
            if (oddsType == 2) {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
                currentLine = parseFloat(singleMLToDecimal(currentLine)).toFixed(2);
            }
            $(obj).html(prevHTML);
            return overlib('<div class=\'popup-container\' style="height: 65px;"><div class=\'popup-header\'>Last change: <span style=\'font-weight: normal;\'>' + json_data.current.changed + '<span><span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><br />No line movement since opening</div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5, FULLHTML);
        });
        return false;
    }
}

function clearChart() {
    $('#chart-area').empty();
}

function showChart(content, relelement) {
    var position = $(relelement).find('div').offset();
    $('#chart-header').find('div').html(content);
    //TODO: Make sure the following positioning does not affect mobile
    if ($('#chart-window').css('min-width') != '1px')
    {
        //TODO: Add a small check if exceeding screen, then show towards left. Requires mouse coordinates first
        $('#chart-window').css({'left': position.left + ($(relelement).find('div').outerWidth()), 'top': position.top + $(relelement).find('div').outerHeight()});
    }
    $('#chart-window').addClass('is-visible');
}


function iSI() {

}


//closeOverLib
function cO() {
    if (!parlayMode) {
        return nd();
    }
}

//linkOut
function lO(operator, event) {
    $.get("/ajax/ajax.LinkOut.php", {
        operator: operator,
        event: event
    });
}


//showSpreadList
function sSL(spreads) {
    if (!parlayMode) {
        return overlib('<div class=\'previous-odds\'>' + spreads + '</div>', WRAP, FGCOLOR, '#eeeeee', BGCOLOR, '#1f2a34', BORDER, 1)
    }
}


function addToParlay(obj) {
    if (obj != null) {
        if (parlay.length >= 25) {
            return false;
        }

        tmpArr = new Array();
        tmpArr["ml"] = obj.getElementsByTagName('span')[0].innerHTML;
        if (obj.parentNode.parentNode.getElementsByTagName('th')[0].getElementsByTagName('a').length > 0) {
            //Regular row
            tmpArr["name"] = obj.parentNode.parentNode.getElementsByTagName('th')[0].getElementsByTagName('a')[0].innerHTML;
        } else {
            //Prop row
            tmpArr["name"] = obj.parentNode.parentNode.getElementsByTagName('th')[0].innerHTML;
        }

        tmpArr["ref"] = obj.getElementsByTagName('span')[0].id.substring(6);

        found = false;
        for (var i = 0; i < parlay.length; i++) {
            if (parlay[i]["ref"] == tmpArr["ref"]) {
                found = true;
            }
        }
        if (!found) {
            parlay.push(tmpArr);
        }

    } else {
        if (parlay.length == 0) {
            return false;
        }
    }

    tmpText = '';
    pvalue = 1;
    for (var i = 0; i < parlay.length; i++) {
        dispLine = '';
        if (storedOdds[parlay[i]["ref"]] != null) {
            switch (oddsType) {
                case 1:
                    dispLine = storedOdds[parlay[i]["ref"]];
                    break;
                case 2:
                    dispLine = parseFloat(singleMLToDecimal(storedOdds[parlay[i]["ref"]])).toFixed(2);
                    break;
                case 3:
                    dispLine = singleDecimalToAmount(singleMLToDecimal(storedOdds[parlay[i]["ref"]]));
                    break;
            }
            pvalue *= singleMLToDecimal(storedOdds[parlay[i]["ref"]]);
        } else {
            switch (oddsType) {
                case 1:
                    dispLine = document.getElementById('oddsID' + parlay[i]["ref"]).innerHTML;
                    break;
                case 2:
                    dispLine = parseFloat(singleMLToDecimal(document.getElementById('oddsID' + parlay[i]["ref"]).innerHTML)).toFixed(2);
                    break;
                case 3:
                    dispLine = singleDecimalToAmount(singleMLToDecimal(document.getElementById('oddsID' + parlay[i]["ref"]).innerHTML));
                    break;
            }
            pvalue *= singleMLToDecimal(document.getElementById('oddsID' + parlay[i]["ref"]).innerHTML);
        }
        tmpText += '<span>»</span> <b>' + parlay[i]["name"] + '</b> ' + dispLine + '<br />';

    }

    dispValue = '';
    switch (oddsType) {
        case 1:
            if (parlay.length == 1) {
                dispValue = parlay[0]["ml"];
            } else {
                dispValue = singleDecimalToML(pvalue);
            }
            break;
        case 2:
            dispValue = Math.round(pvalue * 100) / 100;
            break;
        case 3:
            dispValue = singleDecimalToAmount(pvalue);
            break;
        default:
            break;
    }

    document.getElementById('parlay-mode-window').innerHTML = '<div class=\'popup-header\'>Parlay</div><p>' + tmpText + '<br /><span>Total:</span> ' + dispValue + '</p>';
    return false;

}

function oddsToMoneyline() {
    if (oddsType == 1) {
        return;
    }

    if (storedOdds.length > 0) {
        $('[id^="oddsID"]').each(function() {
            this.innerHTML = storedOdds[this.id.substring(6)];
        });
    }

    oddsType = 1;
}

function oddsToDecimal() {
    if (oddsType == 2) {
        return;
    }

    if (oddsType != 1) {
        oddsToMoneyline();
    }

    //If odds are not stored, store them
    if (oddsType == 1 && storedOdds.length == 0) {
        $('[id^="oddsID"]').each(function() {
            storedOdds[this.id.substring(6)] = this.innerHTML;
        });
    }


    $('[id^="oddsID"]').each(function() {
        this.innerHTML = parseFloat(singleMLToDecimal(this.innerHTML)).toFixed(2);
    });

    oddsType = 2;
}

function oddsToAmount(amount) {
    var value;
    if (amount == null) {
        //value = document.getElementById('format-amount-box1').value;
        value = $('#format-amount-box1').val();
    } else {
        value = amount;
    }
    if (isNaN(value) || value < 0) {
        return;
    }

    oddsToDecimal();

    $('[id^="oddsID"]').each(function() {
        var odds = parseFloat(this.innerHTML);
        this.innerHTML = '$' + (Math.round(((value * odds) - value)));
    });

    oddsType = 3;
    $.cookie('bfo_odds_type', 3, {
        expires: 999,
        path: '/'
    });
    $.cookie('bfo_risk_amount', value, {
        expires: 999,
        path: '/'
    });

    $("#format-toggle-text").find('span').first().next().html("Return on $" + value + "  &#9660;");
    $('#format-amount-box1').val(value);

    if (parlayMode) {
        addToParlay(null);
    }

}

function singleMLToDecimal(odds) {
    if (String(odds).substring(0, 1) == "-") {
        oddsFloat = parseFloat(String(odds).substring(1, String(odds).length));
        oddsFloat = Math.round(((100 / oddsFloat) + 1) * 100000) / 100000;
        return oddsFloat.toString();
    } else if (String(odds).substring(0, 1) == "+") {
        oddsFloat = parseFloat(String(odds).substring(1, String(odds).length));
        oddsFloat = Math.round(((oddsFloat / 100) + 1) * 100000) / 100000;
        return oddsFloat.toString();
    } else {
        return 'error';
    }
}

function singleDecimalToML(odds) {
    if (odds >= 2) {
        return '+' + Math.round(100 * (odds - 1));
    } else if (odds < 2) {
        return '' + Math.round(-100 / (odds - 1));
    } else {
        return 'error';
    }
}

function singleDecimalToAmount(odds, amount) {
    var value;
    if (amount == null) {
        value = document.getElementById('format-amount-box1').value;
    } else {
        value = amount;
    }
    if (isNaN(value) || value < 0) {
        return '';
    }
    var val = new String((Math.round(((value * odds) - value) * 100)));
    val = val.slice(0, val.length - 2) + '.' + val.slice(-2);
    return '$' + val;
}

function setOddsType(val) {
    switch (val) {
        case 1:
            oddsToMoneyline();
            $.cookie('bfo_odds_type', 1, {
                expires: 999,
                path: '/'
            });
            $("#format-toggle-text").find('span').first().next().html("Moneyline &#9660;");
            break;
        case 2:
            oddsToDecimal();
            $.cookie('bfo_odds_type', 2, {
                expires: 999,
                path: '/'
            });
            $("#format-toggle-text").find('span').first().next().html("Decimal &#9660;");
            break;
        case 3:
            $("#format-toggle-text").find('span').first().next().html("Amount &#9660;" + $("#format-amount-box1").html());
            break;
        default:
    }
    if (parlayMode) {
        addToParlay(null);
    }
}

function useSearchBox() {
    if (usedSearch == false) {
        $('#search-box1').css('color', '#fff');
        $('#search-box1').val('');
        usedSearch = true;
        $('#search-box1').focus();
    }
}

function toggleParlayMode() {
    if (parlayMode == true) {
        parlay = [];
        parlayMode = false;
        id = 0;
        while ((obj = document.getElementById('tablenot' + id))) {
            obj.innerHTML = 'Click on odds to view a history chart.';
            id++;
        }
        return nd();
    } else {
        parlayMode = true;
        id = 0;
        while ((obj = document.getElementById('tablenot' + id))) {
            obj.innerHTML = 'Click on odds to add it to your parlay.';
            id++;
        }
        return overlib('<div class=\'popup-container\' id=\'parlay-mode-window\' style="width: auto; height: auto"><div class=\'popup-header\'>Parlay</div><p><span>-</span> Click on a betting line to add it<br />to your parlay<br /><br /><span>-</span> You can change the parlay format<br />at any time with the top right box</p></div>', MOUSEOFF, WRAP, CELLPAD, 5, FULLHTML);
    }
}

function getElementsByClassName(strClassName, obj) {
    var ar = arguments[2] || new Array();
    var re = new RegExp("\\b" + strClassName + "\\b", "g");

    if (re.test(obj.className)) {
        ar.push(obj);
    }
    for (var i = 0; i < obj.childNodes.length; i++)
        getElementsByClassName(strClassName, obj.childNodes[i], ar);

    return ar;
}

$(document).ready(function() {

    $("#parlay-mode-box").prop("disabled", false);
    $("#parlay-mode-box").prop("checked", false);

    initPage();

    if ($('#auto-refresh-container').css('display') != 'none') {
        if ($.cookie('bfo_autorefresh') != null && !isNaN($.cookie('bfo_autorefresh')) && $.cookie('bfo_autorefresh') == 0) {
            //Disable auto refresh
            $('#afSelectorOn').removeClass("list-checked");
            $('span', $('#afSelectorOn')).css('display', 'none');
            toggleRefresh(false);
            $('#afSelectorOff').addClass("list-checked");
            $('span', $('#afSelectorOff')).css('display', 'inline-block');
        } else {
            $('#afSelectorOff').removeClass("list-checked");
            $('span', $('#afSelectorOff')).css('display', 'none');
            toggleRefresh(true);
            $('#afSelectorOn').addClass("list-checked");
            $('span', $('#afSelectorOn')).css('display', 'inline-block');
        }
    }

    //Bind dropdowns
    $('#formatSelector1').click(function() {
        $('[id^="formatSelector"]').each(function() {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        setOddsType(1);
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#formatSelector2').click(function() {
        $('[id^="formatSelector"]').each(function() {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        setOddsType(2);
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#formatSelector3').click(function() {
        $('[id^="formatSelector"]').each(function() {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        oddsToAmount();
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#format-amount-box1').change(function() {
        $('[id^="formatSelector"]').each(function() {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        oddsToAmount();
        $('#formatSelector3').addClass("list-checked");
        $('span', $('#formatSelector3')).css('display', 'inline-block');
    });


    $('#afSelectorOn').click(function() {
        $('#afSelectorOff').removeClass("list-checked");
        $('span', $('#afSelectorOff')).css('display', 'none');
        toggleRefresh(true);
        $('#afSelectorOn').addClass("list-checked");
        $('span', $('#afSelectorOn')).css('display', 'inline-block');
    });
    $('#afSelectorOff').click(function() {
        $('#afSelectorOn').removeClass("list-checked");
        $('span', $('#afSelectorOn')).css('display', 'none');
        toggleRefresh(false);
        $('#afSelectorOff').addClass("list-checked");
        $('span', $('#afSelectorOff')).css('display', 'inline-block');
    });




});

function initPage() {

    oddsType = 1;
    storedOdds = [];
    if ($.cookie('bfo_odds_type') != null) {
        if (!isNaN($.cookie('bfo_odds_type'))) {
            cOddsType = parseInt($.cookie('bfo_odds_type'));
            switch (cOddsType) {
                case 1:
                    $("#format-toggle-text").find('span').first().next().html("Moneyline &#9660;");
                    break;
                case 2:
                    $("#format-toggle-text").find('span').first().next().html("Decimal &#9660;");
                    oddsToDecimal();
                    break;
                case 3:
                    $("#format-toggle-text").find('span').first().next().html(" $" + $.cookie('bfo_risk_amount') + " &#9660;");
                    $("#format-amount-box1").find('span').first().next().html($.cookie('bfo_risk_amount'));
                    oddsToAmount($.cookie('bfo_risk_amount'));
                    break;
                default:
            }
            $('[id^="formatSelector"]').each(function() {
                $(this).removeClass("list-checked");
                $('span', this).css('display', 'none');
            });
            $("#formatSelector" + cOddsType).addClass("list-checked");
            $('span', $("#formatSelector" + cOddsType)).css('display', 'inline-block');
        }
    }




    //Cache tables for scrolling purpose
    $('.table-scroller').each(function() {
        scrollCache.push([$(this), $('table', $(this)), $(this).prev().prev('.table-inner-shadow-left'), $(this).prev('.table-inner-shadow-right')]);
        $.each(scrollCache, function(key, value) {
            value[2].data("scrollLeftVis", false);
            value[3].data("scrollRightVis", true);
        });
    });


    //Add prop row togglers
    $('.prop-cell a').on('click', function() {
        matchup_id = $(this).attr('data-mu');
        $(this).data('toggled', !$(this).data('toggled'));
        if ($(this).data('toggled')) {
            if (navigator.appName.indexOf("Microsoft") > -1 && navigator.appVersion.indexOf("MSIE 10.0") == -1) {
                $('[id^="prop-' + matchup_id + '"]').each(function() {
                    $(this).css('display', 'block')
                });
            } else {
                $('[id^="prop-' + matchup_id + '"]').each(function() {
                    $(this).css('display', 'table-row')
                });
            }
            $("[data-mu='" + matchup_id + "'] div img").attr("src","/img/dexp.gif");
            refreshOpenProps[matchup_id] = true;
        } else {
            $('[id^="prop-' + matchup_id + '"]').each(function() {
                $(this).css('display', 'none')
            });
            $("[data-mu='" + matchup_id + "'] div img").attr("src", "/img/exp.gif");
            refreshOpenProps[matchup_id] = false;
        }
        return false;

    });


    //    $('.table-scroller').on('scroll', throttle(function (event) {
    /*    $('.table-scroller').on('scroll', function() {
        // do the Ajax request
        var selfscroller = $(this);
        $.each(scrollCache, function(key, value) {
            if (value[0] != selfscroller) {
                value[0].scrollLeft(selfscroller.scrollLeft());
            }
        });


        if (selfscroller.scrollLeft() >= ($('table', selfscroller).width() - selfscroller.width()) - 6) {
            $('.table-inner-shadow-right').each(function() {
                //         $(this).css("width", 0 + (($('table', selfscroller).width() - selfscroller.width()) - selfscroller.scrollLeft()));
            });
        } else {
            $('.table-inner-shadow-right').each(function() {
                //           $(this).css("width", "6px");
            });
        }

        if (selfscroller.scrollLeft() <= 6) {
            $('.table-inner-shadow-left').each(function() {
                //      $(this).css("width", 0 + selfscroller.scrollLeft());
            });
        } else {
            $('.table-inner-shadow-left').each(function() {
                //    $(this).css("width", "6px");
            });
        }
        //}, 10))
    });*/


    //    $('.table-scroller').on('scroll', throttle(function (event) {

    //Sync scrollbars
        $('.table-scroller').bind('mousedown touchstart', function() {
                scrollCaptain = $(this);
});

    $('.table-scroller').on('scroll', function() {

        var selfscroller = $(this);
    //console.log(selfscroller.parent().parent().attr('id') + " / " + scrollCaptain.parent().parent().attr('id'));
        if (selfscroller.scrollLeft() == scrollX || !selfscroller.is(scrollCaptain)) return false;
        scrollX = selfscroller.scrollLeft();
        $.each(scrollCache, function(key, value) {
            if (value[0].is(selfscroller)) {} else {
                value[0].scrollLeft(selfscroller.scrollLeft());
            }

            if (value[0].scrollLeft() >= (value[1].width() - value[0].width()) - 6) {
                value[3].css("width", 0 + ((value[1].width() - value[0].width()) - value[0].scrollLeft()));
                value[3].data("scrollRightVis", false);

            } else if (value[3].data("scrollRightVis") == false) {
                value[3].css("width", "6px");
                value[3].data("scrollRightVis", true);
            }

            if (value[0].scrollLeft() <= 6) {
                value[2].css("width", 0 + value[0].scrollLeft());
                value[2].data("scrollLeftVis", false);
            } else if (value[2].data("scrollLeftVis") == false) {
                value[2].css("width", "6px");
                value[2].data("scrollLeftVis", true);
            }

        });


        //}, 10))
    });


    $(function() {

        //enable hover
        $("ul.dropdown li").hover(function() {
            $(this).addClass("hover");
            $('ul:first', this).css('visibility', 'visible');
        }, function() {

            $(this).removeClass("hover");
            $('ul:first', this).css('visibility', 'hidden');
        });

        //click for mobile
        $("ul.dropdown li").click(function() {
            $(this).addClass("hover");
            $('ul:first', this).css('visibility', 'visible');

        });

        //close on click (doesnt really work)
        $("ul.dropdown li ul li").click(function() {

            $(this).removeClass("hover");
            $('ul:first', this).css('visibility', 'hidden');
        });
    });

    //Add graph popup controls
    //close popup
    $('#chart-window').on('click', function(event){
        if( $(event.target).is('.cd-popup-close') || $(event.target).is('#chart-window') ) {
            event.preventDefault();
            $(this).removeClass('is-visible');
            $('#chart-area').empty();
        }
    });
    //close popup when clicking the esc keyboard button
    $(document).keyup(function(event){
        if(event.which=='27'){
            $('#chart-window').removeClass('is-visible');
            $('#chart-area').empty();
        }
    });

}

function refreshPage() {
    $.get("/ajax/ajax.Interface.php?function=refreshPage", {}, function(data) {
        $("#content").html(data);

        initPage();

        $.each(refreshOpenProps, function(index, value) {
            if (value == true) {
                $('a[data-mu="' + index + '"]').trigger("click");
            }
        });

        return true;
    });
    return false;
}

function updateLine(line, newval) {
    /*oldcol = $("#" + line).css("background-color");
    $("#" + line).html("+300");
    $("#" + line).closest('td').css("background-color", "#99ff99");
    $("#" + line).closest('td').animate({
        backgroundColor: oldcol
    }, 60000, function() { //TODO: Raise this to 60000
        //Finished
    });*/
}

function toggleRefresh(autoRefresh) {
    if (autoRefresh == true) {
        refreshId = setInterval(function() {
            refreshPage();
        }, 60000);
        $("#autoRefresh").addClass("refresh-ind-spin");
        $.cookie('bfo_autorefresh', 1, {
            expires: 999,
            path: '/'
        });

    } else {
        $("#autoRefresh").removeClass("refresh-ind-spin");
        $.cookie('bfo_autorefresh', 0, {
            expires: 999,
            path: '/'
        });
        if (typeof refreshId !== 'undefined') {
            clearInterval(refreshId);
        }
    }
}


function throttle(fn, threshhold, scope) {
    threshhold || (threshhold = 250);
    var last,
        deferTimer;
    return function() {
        var context = scope || this;

        var now = +new Date,
            args = arguments;
        if (last && now < last + threshhold) {
            // hold on to it
            clearTimeout(deferTimer);
            deferTimer = setTimeout(function() {
                last = now;
                fn.apply(context, args);
            }, threshhold);
        } else {
            last = now;
            fn.apply(context, args);
        }
    };
}

function debounce(fn, delay) {
    var timer = null;
    return function() {
        var context = this,
            args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function() {
            fn.apply(context, args);
        }, delay);
    };
}

function stTwitter(url) {

    window.open('http://twitter.com', 'twitterwindow', 'height=450, width=550, top=' + ($(window).height() / 2 - 225) + ', left=' + $(window).width() / 2 + ', toolbar=0, location=0, menubar=0, directories=0, scrollbars=0');
}