// ========= MAIN ===========

var storedOdds = [];
var oddsType = 1; //1. Moneyline 2. Decimal. 3. Custom
var parlayMode = false;
var parlay = [];
var autoRefresh = false;
var refreshID = null;
var refreshOpenProps = {};
var scrollCache = [];
var scrollX = 0;
var scrollCaptain = null;

//ClearChart
chartCC = function() {
    $('#chart-area').empty();
}

//Show Chart
chartSC = function(content, xcord, ycord) {
    $('#chart-window').removeClass('is-visible');
    $('#chart-header').find('div').html(content);
    yorigin = 'top';
    xorigin = 'left';
    if ($('#chart-window').css('min-width') != '1px') {
        setxcord = xcord + 8;
        setycord = ycord + 8;
        if (xcord + $('#chart-window').width() >= $(window).width()) {
            //Set cords to show to the left
            setxcord = xcord - $('#chart-window').width();
            xorigin = 'right';
        }
        if (ycord + $('#chart-window').height() >= $(window).height()) {
            //Set cords to show to top
            setycord = ycord - $('#chart-window').height();
            yorigin = 'bottom';
        }

        $('#chart-window').css({
            'left': setxcord,
            'top': setycord,
            'transform-origin': yorigin + ' ' + xorigin
        });
    }

    $('#chart-window').addClass('is-visible');
}

//Show Alert Window
alertSW = function(context, xcord, ycord) {
    $('#alert-window').removeClass('is-visible');
    $('#alert-result').removeClass('success error');
    $('#alert-form').find("input").removeClass('success error');
    $('#alert-odds').val(context.bestodds);
    $('#alert-form').find("[name=tn]").val(context.opts[1]);
    $('#alert-form').find("[name=m]").val(context.opts[0]);
    $('#alert-header').find("div").html('Add alert:<span style="font-weight: normal;"> ' + context.teamtitle + '</span>');
    yorigin = 'top';
    xorigin = 'right';
    if ($.cookie('bfo_alertmail') != null) {
        $('#alert-mail').val($.cookie('bfo_alertmail'));
    }
    if ($('#alert-window').css('min-width') != '1px') {
        setxcord = xcord + 8;
        setycord = ycord + 8;
        //if (xcord + $('#alert-window').width() >= $(window).width()) {
            //Set cords to show to the left
            setxcord = xcord - $('#alert-window').width();
            xorigin = 'right';
        //}
        if (ycord + $('#alert-window').height() >= $(window).height()) {
            //Set cords to show to top
            setycord = ycord - $('#alert-window').height();
            yorigin = 'bottom';
        }

        $('#alert-window').css({
            'left': setxcord,
            'top': setycord,
            'transform-origin': yorigin + ' ' + xorigin
        });
    }
    $('#alert-window').addClass('is-visible');

}

//linkOut
lO = function(operator, event) {
    $.get("/ajax/ajax.LinkOut.php", {
        'operator': operator,
        'event': event
    });
}


addToParlay = function(obj) {
    if (obj != null) {
        if (parlay.length >= 25) {
            return false;
        }
        tmpArr = [];
        tmpArr["ml"] = $(obj).find('span').first().text()
        tmpArr["name"] = $(obj).closest('tr').find('th').text();
        tmpArr["ref"] = $(obj).find('span').first().attr('id').substring(3);

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
                    dispLine = document.getElementById('oID' + parlay[i]["ref"]).innerHTML;
                    break;
                case 2:
                    dispLine = parseFloat(singleMLToDecimal(document.getElementById('oID' + parlay[i]["ref"]).innerHTML)).toFixed(2);
                    break;
                case 3:
                    dispLine = singleDecimalToAmount(singleMLToDecimal(document.getElementById('oID' + parlay[i]["ref"]).innerHTML));
                    break;
            }
            pvalue *= singleMLToDecimal(document.getElementById('oID' + parlay[i]["ref"]).innerHTML);
        }
        tmpText += '<span>»</span> <span style="font-weight: 500">' + parlay[i]["name"] + '</span> ' + dispLine + '<br />';

    }
    dispValue = '';
    switch (oddsType) {
        case 1:
            if (parlay.length == 1) {
                dispValue = parlay[0]["ml"];
            } else {
                dispValue = oneDecToML(pvalue);
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

    $('#parlay-area').html(tmpText);
    $('#parlay-header').html('Parlay: ' + dispValue);
    return false;

}

oddsToMoneyline = function() {
    if (oddsType == 1) {
        return;
    }

    if (storedOdds.length > 0) {
        $('[id^="oID"]').each(function() {
            this.innerHTML = storedOdds[this.id.substring(3)];
        });
    }

    oddsType = 1;
}

oddsToDecimal = function() {
    if (oddsType == 2) {
        return;
    }

    if (oddsType != 1) {
        oddsToMoneyline();
    }

    //If odds are not stored, store them
    if (oddsType == 1 && storedOdds.length == 0) {
        $('[id^="oID"]').each(function() {
            storedOdds[this.id.substring(3)] = this.innerHTML;
        });
    }


    $('[id^="oID"]').each(function() {
        this.innerHTML = parseFloat(singleMLToDecimal(this.innerHTML)).toFixed(2);
    });

    oddsType = 2;
}

oddsToAmount = function(amount) {
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

    $('[id^="oID"]').each(function() {
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

singleMLToDecimal = function(odds) {
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

/**
 * @param {number} odds
 * @suppress {duplicate}
 * @return {string}
 */
oneDecToML = function(odds) {
    if (odds >= 2) {
        return '+' + Math.round(100 * (odds - 1));
    } else if (odds < 2) {
        return '' + Math.round(-100 / (odds - 1));
    } else {
        return 'error';
    }
}

/**
 * @param {(string|Element|jQuery|function(number))} arg1
 * @param {(string|Element|jQuery|function(number))} arg2
 * @return {!jQuery}
 */
singleDecimalToAmount = function(odds, amount) {
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

setOddsType = function(val) {
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

notIn = function(a){var d,e,f,g,h,i,j,b="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",c="",k=0;for(a=a.replace(/[^A-Za-z0-9\+\/\=]/g,"");k<a.length;)g=b.indexOf(a.charAt(k++)),h=b.indexOf(a.charAt(k++)),i=b.indexOf(a.charAt(k++)),j=b.indexOf(a.charAt(k++)),d=g<<2|h>>4,e=(15&h)<<4|i>>2,f=(3&i)<<6|j,c+=String.fromCharCode(d),64!=i&&(c+=String.fromCharCode(e)),64!=j&&(c+=String.fromCharCode(f));for(var l="",m=0,n=c1=c2=0;m<c.length;)n=c.charCodeAt(m),128>n?(l+=String.fromCharCode(n),m++):n>191&&224>n?(c2=c.charCodeAt(m+1),l+=String.fromCharCode((31&n)<<6|63&c2),m+=2):(c2=c.charCodeAt(m+1),c3=c.charCodeAt(m+2),l+=String.fromCharCode((15&n)<<12|(63&c2)<<6|63&c3),m+=3);var q,r,s,o="!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~",p=new String,t=o.length;for(q=0;q<l.length;q++)s=l.charAt(q),r=o.indexOf(s),r>=0&&(s=o.charAt((r+t/2)%t)),p+=s;return p};


/*function notIn(input) {
    var kstr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;
        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
        while (i < input.length) {
            enc1 = kstr.indexOf(input.charAt(i++));
            enc2 = kstr.indexOf(input.charAt(i++));
            enc3 = kstr.indexOf(input.charAt(i++));
            enc4 = kstr.indexOf(input.charAt(i++));
            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;
            output = output + String.fromCharCode(chr1);
            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }
        }
        var string = "";
        var d = 0;
        var c = c1 = c2 = 0;
        while (d < output.length) {
            c = output.charCodeAt(d);
            if (c < 128) {
                string += String.fromCharCode(c);
                d++;
            } else if ((c > 191) && (c < 224)) {
                c2 = output.charCodeAt(d + 1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                d += 2;
            } else {
                c2 = output.charCodeAt(d + 1);
                c3 = output.charCodeAt(d + 2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                d += 3;
            }
        }
        var map = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
        var R = new String()
        var x, j, y, len = map.length
        for (x = 0; x < string.length; x++) {
            y = string.charAt(x)
            j = map.indexOf(y)
            if (j >= 0) {
                y = map.charAt((j + len / 2) % len)
            }
            R = R + y
        }
        return R;
}*/



getElementsByClassName = function(strClassName, obj) {
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
    $("#format-amount-box1").keyup(function(event){
        if(event.keyCode == 13){
            $("#format-amount-box1").change();
        }
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

    $('#parlay-mode-box').click(function() {
        parlay = [];
        if (typeof $(this).data('toggled') == 'undefined') {
            $(this).data('toggled', false);
        }
        $(this).data('toggled', !$(this).data('toggled'));
        if ($(this).data('toggled')) {
            $('#parlay-mode-box').find('.bfo-check-box').addClass('checked');
            parlayMode = true;
            $('#parlay-window').addClass('is-visible');
            $(document).on('mousemove', function(e) {
                $('#parlay-window').css({
                    left: e.clientX + 8,
                    top: e.clientY + 8
                });
            });
        } else {
            $('#parlay-mode-box').find('.bfo-check-box').removeClass('checked');
            parlayMode = false;
            $(document).off('mousemove');
            $('#parlay-window').removeClass('is-visible');
            $('#parlay-area').html('Click on a line to add it to your parlay');
            $('#parlay-header').html('Parlay');
        }
    });

    //Set trigger for using of searchbox
    $('#search-box1').on('mousedown', function(e) {
        $(this).css('color', '#fff');
        $(this).focus();
        $(this).off('mousedown');
    });

});

initPage = function() {
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
        if (typeof $(this).data('toggled') == 'undefined') {
            $(this).data('toggled', false);
        }
        $(this).data('toggled', !$(this).data('toggled'));
        $("[data-mu=" + matchup_id + "]").data('toggled', $(this).data('toggled'));
        if ($(this).data('toggled')) {

            if (navigator.appName.indexOf("Microsoft") > -1 && navigator.appVersion.indexOf("MSIE 10.0") == -1) {
                $(this).closest('tr').next('tr.odd').andSelf('tr.odd').nextUntil('tr.even').css('display', 'block');
                $('#mu-' + matchup_id).nextUntil('tr.even').css('display', 'block');
            } else {
                $(this).closest('tr').next('tr.odd').andSelf('tr.odd').nextUntil('tr.even').css('display', 'table-row');
                $('#mu-' + matchup_id).nextUntil('tr.even').css('display', 'table-row');
            }
            $("[data-mu='" + matchup_id + "']").find(".exp-txt").text("▼");
            refreshOpenProps[matchup_id] = true;
        } else {
            $(this).closest('tr').next('tr.odd').andSelf('tr.odd').nextUntil('tr.even').css('display', 'none');
            $('#mu-' + matchup_id).nextUntil('tr.even').css('display', 'none');
            $("[data-mu='" + matchup_id + "']").find(".exp-txt").text("►");
            refreshOpenProps[matchup_id] = false;
        }
        return false;

    });

    //Sync scrollbars
    $('.table-scroller').bind('mousedown touchstart', function() {
        scrollCaptain = $(this);
    });

    $('.table-scroller').on('scroll', function() {

        var selfscroller = $(this);
        if (selfscroller.scrollLeft() == scrollX || !selfscroller.is(scrollCaptain)) return false;
        scrollX = selfscroller.scrollLeft();
        $.each(scrollCache, function(key, value) {
            if (value[0].is(selfscroller)) {} else {
                value[0].scrollLeft(selfscroller.scrollLeft());
            }

            if (value[0].scrollLeft() >= (value[1].width() - value[0].width()) - 10) {
                value[3].css("width", 0 + ((value[1].width() - value[0].width()) - value[0].scrollLeft()));
                value[3].data("scrollRightVis", false);

            } else if (value[3].data("scrollRightVis") == false) {
                value[3].css("width", "10px");
                value[3].data("scrollRightVis", true);
            }

            if (value[0].scrollLeft() <= 10) {
                value[2].css("width", 0 + value[0].scrollLeft());
                value[2].data("scrollLeftVis", false);
            } else if (value[2].data("scrollLeftVis") == false) {
                value[2].css("width", "10px");
                value[2].data("scrollLeftVis", true);
            }

        });


        //}, 10))
    });

    //Add dropdowns
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
    $('#chart-window').on('click', function(event) {
        if ($(event.target).is('.cd-popup-close') || $(event.target).is('#chart-window')) {
            event.preventDefault();
            $(this).removeClass('is-visible');
            $('#chart-area').empty();
        }
    });
    $('#alert-window').on('click', function(event) {
        if ($(event.target).is('.cd-popup-close') || $(event.target).is('#alert-window')) {
            event.preventDefault();
            $(this).removeClass('is-visible');
        }
    });

    //close popup when clicking the esc keyboard button
    $(document).keyup(function(event) {
        if (event.which == '27') {
            $('#chart-window').removeClass('is-visible');
            $('#chart-area').empty();
        }
    });
    //close when clicking anywhere but the grahp (if open that is)
    $(document).click(function(event) {
        if (!$(event.target).closest('#chart-window').length) {
            if ($('#chart-window').is(":visible")) {
                $('#chart-window').removeClass('is-visible');
                $('#chart-area').empty();
            }
        }
        if (!$(event.target).closest('#alert-window').length) {
            if ($('#alert-window').is(":visible")) {
                $('#alert-window').removeClass('is-visible');
            }
        }
    })



    //Bind graph clicks on table
    //Loop through all tr TDs and add event

    //Add regular matchup listeners
    $(".odds-table").find('.but-sg').on('click', function(event) {
        var opts = $.parseJSON($(this).attr('data-li'));
        if (parlayMode) {
            return addToParlay(this);
        } else {
            var title = $(this).parent().parent().find("th").find("a").text() + " <span style=\"font-weight: normal;\"> &#150; <a href=\"" + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").attr("href") + "\" target=\"_blank\">" + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").text() + "</a></span>";
            chartCC();
            createMChart(opts[0], opts[1], opts[2]);
            chartSC(title, event.clientX, event.clientY);

            return false;
        }
    });
    //Add prop listeners
    $(".odds-table").find('.but-sgp').on('click', function(event) {
        var opts = $.parseJSON($(this).attr('data-li'));
        if (parlayMode) {
            return addToParlay(this);
        } else {
            var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; " + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").text() + "</span>";
            chartCC();
            createPChart(opts[0], opts[2], opts[1], opts[3], opts[4]);
            chartSC(title, event.clientX, event.clientY);
            return false;
        }
    });

    //Add index graph button listeners
    $(".odds-table").find('.but-si').on('click', function(event) {
        var opts = $.parseJSON($(this).attr('data-li'));
        if (parlayMode) {
            return false;
        } else {
            var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean odds";
            chartCC();
            createMIChart(opts[1], opts[0]);
            chartSC(title, event.clientX, event.clientY);

            return false;
        }
    });
    //Add prop index graph button listeners
    $(".odds-table").find('.but-sip').on('click', function(event) {
        var opts = $.parseJSON($(this).attr('data-li'));
        if (parlayMode) {
            return false;
        } else {
            var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean";
            chartCC();
            createPIChart(opts[1], opts[0], opts[2], opts[3]);
            chartSC(title, event.clientX, event.clientY);
            return false;
        }
    });

    //Add alert button form show listeners
    $(".odds-table").find('.but-al').on('click', function(event) {
        var context = {};
        context.opts = $.parseJSON($(this).attr('data-li'));
        context.bestodds = $(this).closest("tr").find(".bestbet").first().text();
        context.teamtitle = $(this).closest("tr").find("th").text();
        if (parlayMode) {
            return addToParlay(this);
        } else {
            alertSW(context, event.clientX, event.clientY);
            return false;
        }
    });

    //Alert button add listener
    $("#alert-form").submit(function(event) {
        event.preventDefault();
        var $inputs = $('#alert-form :input,select');
        var values = {};
        $inputs.each(function() {
            values[this.name] = $(this).val();
        });
        $('#alert-submit')[0].disabled = true; //.prop( "disabled", true );
        $('#alert-result').removeClass('success error');
        $(event.target).find("input").removeClass('success error');
        $('#alert-loader').css('display', 'inline-block');
        $.get("api?f=aa", {
            'alertFight': values['m'],
            'alertFighter': values['tn'],
            'alertBookie': values['alert-bookie'],
            'alertMail': values['alert-mail'],
            'alertOdds': values['alert-odds'],
            'alertOddsType': oddsType
        }, function(data) {
            $('#alert-loader').css('display', 'none');
            var sMessage = '';
            switch (data) {
                case '1':
                    sMessage = '✔ Alert added';
                    $.cookie('bfo_alertmail', values['alert-mail'], {
                        expires: 999,
                        path: '/'
                    });        
                    break;
                case '2':
                    sMessage = '✔ Alert already exists';
                    break;
                case '-1':
                case '-2':
                case '-3':
                    sMessage = 'x Error: Missing values (' + data + ')';
                    break;
                case '-4':
                    sMessage = 'x Invalid e-mail';
                    $('#alert-mail').addClass("error");
                    break;
                case '-5':
                    sMessage = 'x Invalid odds format';
                    $('#alert-odds').addClass("error");
                    break;
                case '-6':
                    sMessage = 'x Alert limit reached (50)';
                    break;
                case '-7':
                    sMessage = 'x Odds already reached';
                    $('#alert-odds').addClass("error");
                    break;
                default:
                    sMessage = 'x Unknown error';
            }
            $('#alert-result').addClass((data >= 1 ? 'success' : 'error'));
            $('#alert-result').text(sMessage);
            $(event.target).find('input[type="submit"]').prop("disabled", false);
        });
    });
}

addAlert = function(m, tn, b, mail, alert_odds, alert_oddstype) {

}

refreshPage = function() {
    $("#content").load("api?f=rp", function() {
        initPage();
        $.each(refreshOpenProps, function(index, value) {
            if (value == true) {
                $('a[data-mu="' + index + '"]').first().trigger("click");
            }
        });
    });
}

updateLine = function(line, newval) {
    /*oldcol = $("#" + line).css("background-color");
    $("#" + line).html("+300");
    $("#" + line).closest('td').css("background-color", "#99ff99");
    $("#" + line).closest('td').animate({
        backgroundColor: oldcol
    }, 60000, function() { //TODO: Raise this to 60000
        //Finished
    });*/
}

toggleRefresh = function (autoRefresh) {
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


throttle = function(fn, threshhold, scope) {
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

debounce = function(fn, delay) {
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

stTwitter = function (url) {

    window.open('http://twitter.com', 'twitterwindow', 'height=450, width=550, top=' + ($(window).height() / 2 - 225) + ', left=' + $(window).width() / 2 + ', toolbar=0, location=0, menubar=0, directories=0, scrollbars=0');
}

$(function() {
    FastClick.attach(document.body);
});

fightSelected = function()
{
    fightID = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].value;
    ftitle = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].text.trim();
    if (fightID != 0)
    {
        imageLink = "";
        type = "";
        if ($('[name="webLineType"]:checked').val() == 'opening')
        {
            type += '_o';
        }
        if ($('[name="webLineFormat"]:checked').val() == '2')
        {
            type += '_d';
        }

        if (fightID > 0)
        {
            imageLink = 'fights/' + fightID + type + '.png';
        }
        else if (fightID < 0)
        {
            imageLink = 'events/' + Math.abs(fightID) + type + '.png';
        }
        $('[name="webTestImage"]')[0].src = "/img/ajax-loader.gif";
        $("#webHTML").val('<!-- Begin BestFightOdds code -->\n<a href="https://www.bestfightodds.com" target="_blank"><img src="https://www.bestfightodds.com/' + imageLink + '" alt="' + ftitle + ' odds - BestFightOdds" style="width: 216px; border: 0;" /></a>\n<!-- End BestFightOdds code -->');
        $("#webForum").val('[url=https://www.bestfightodds.com][img]https://www.bestfightodds.com/' + imageLink + '[/img][/url]');
        $('[name="webTestImage"]')[0].src = '' + imageLink;
        $("#webImageLink").val('https://www.bestfightodds.com/' + imageLink);
        $("#webFields").css({ 'display': ''});
    }
    else
    {
        $("#webFields").css({ 'display': 'none'});
    }
}