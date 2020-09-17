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
var shareVisible = false;

function setHeight(el, val) {
    if (typeof val === "function") val = val();
    if (typeof val === "string") el.style.height = val;
    else el.style.height = val + "px";
}

function index(el) {
    if (!el) return -1;
    var i = 0;
    do {
      i++;
    } while (el = el.previousElementSibling);
    return i;
  }

//ClearChart - JQUERY REMOVED
chartCC = function () {
    el = document.getElementById('chart-area');
    while(el.firstChild)
        el.removeChild(el.firstChild);
    document.getElementById('chart-link').style.display = 'none';
    setHeight(document.getElementById('chart-window'), 228);
};

//Show Chart - JQUERY REMOVED
chartSC = function (content, xcord, ycord) {
    var chartwindow = document.getElementById('chart-window');
    var chartlink = document.getElementById('chart-link');
    var chartdisc = document.getElementById('chart-disc');

    //Pick out bookie name and link from content
    if ((url = content.match('href="([^"]*)"')) != null && (bookie = content.match('>([^<]*)</a>')) != null) {
        chartdisc.style.display = 'none';
        chartlink.innerHTML = 'Bet this line at ' + bookie[1];
        chartlink.style.display = '';
        chartlink.parentNode.setAttribute('href', 'https://www.bestfightodds.com' + url[1]);
        setHeight(chartwindow, 260);
        
        //Fix for Bet365 to discourage US players:
        if (bookie[1] == 'Bet365') {
            setHeight(chartwindow, 300);
            chartdisc.style.display = 'block';
        }
    }

    chartwindow.classList.remove('is-visible');
    chartwindow.classList.add('no-transition');

    //Flush CSS cache
    getComputedStyle(chartwindow).display;
    chartwindow.offsetHeight;

    document.getElementById('chart-header').querySelector('div').innerHTML = content;
    yorigin = 'top';
    xorigin = 'left';
    if (getComputedStyle(chartwindow)['min-width'] != '1px') {
        setxcord = xcord + 8;
        setycord = ycord + 8;
        if (xcord + parseFloat(getComputedStyle(chartwindow, null).width.replace("px", "")) >= window.innerWidth) {
            //Set cords to show to the left
            setxcord = xcord - parseFloat(getComputedStyle(chartwindow, null).width.replace("px", ""));
            xorigin = 'right';
        }
        if (ycord + parseFloat(getComputedStyle(chartwindow, null).height.replace("px", "")) >= window.innerHeight) {
            //Set cords to show to top
            setycord = ycord - parseFloat(getComputedStyle(chartwindow, null).height.replace("px", ""));
            yorigin = 'bottom';
        }

        chartwindow.style.left = setxcord  + 'px';
        chartwindow.style.top = setycord  + 'px';
        chartwindow.style.transformOrigin = yorigin + ' ' + xorigin;
       
    }
    //Flush CSS cache. Not sure if this actually has any effect..
    getComputedStyle(chartwindow).display;
    chartwindow.offsetHeight;

    chartwindow.classList.remove('no-transition');
    chartwindow.classList.add('is-visible');
};

//Show Alert Window
alertSW = function (context, xcord, ycord) {
    $('#alert-window').removeClass('is-visible');
    $('#alert-window').addClass('no-transition');
    //Flush CSS
    getComputedStyle($('#alert-window')[0]).display;
    $('#alert-window')[0].offsetHeight;

    $('#alert-result').removeClass('success error');
    $('#alert-form').find("input").removeClass('success error');
    $('#alert-odds').val(context.bestodds);
    $('.alert-result').removeClass('success error');
    $('.alert-result').text('');
    $('#alert-form').find("[name=tn]").val(context.opts[1]);
    $('#alert-form').find("[name=m]").val(context.opts[0]);
    $('#alert-header').find("div").html('Add alert:<span style="font-weight: normal;"> ' + context.teamtitle + '</span>');
    yorigin = 'top';
    xorigin = 'right';
    if (Cookies.get('bfo_alertmail') !== null) {
        $('#alert-mail').val(Cookies.get('bfo_alertmail'));
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
    //Flush CSS
    getComputedStyle($('#alert-window')[0]).display;
    $('#alert-window')[0].offsetHeight;

    $('#alert-window').removeClass('no-transition');
    $('#alert-window').addClass('is-visible');
};

//linkOut
lO = function (operator, event) {
    ga('send', 'event', 'outbound', 'click', 'bookie ' + operator);
};


addToParlay = function (obj) {
    if (obj !== null) {
        if (parlay.length >= 25) {
            return false;
        }
        tmpArr = [];
        tmpArr["ml"] = $(obj).find('span').find('span').first().text();
        tmpArr["name"] = $(obj).closest('tr').find('th').text();
        tmpArr["ref"] = $(obj).find('span').find('span').first().attr('id').substring(3);

        found = false;
        foundEl = null;
        for (var i = 0; i < parlay.length; i++) {
            if (parlay[i]["ref"] == tmpArr["ref"]) {
                foundEl = i;
                found = true;
            }
        }
        if (!found) {
            parlay.push(tmpArr);
        }
        else {
            //Remove it from parlay
            parlay.splice(foundEl, 1);
        }

    } else {
        if (parlay.length === 0) {
            return false;
        }
    }

    if (parlay.length === 0) {
        $('#parlay-area').html('Click on a line to add it to your parlay');
        $('#parlay-header').html('Parlay');
        return false;
    }

    tmpText = '';
    pvalue = 1;
    for (var i = 0; i < parlay.length; i++) {
        var dispLine = '';
        if (storedOdds[parlay[i]["ref"]] != null) {
            switch (oddsType) {
                case 1:
                    dispLine = storedOdds[parlay[i]["ref"]];
                    break;
                case 2:
                    dispLine = parseFloat(singleMLToDecimal(storedOdds[parlay[i]["ref"]])).toFixed(2);
                    break;
                case 3:
                    dispLine = singleDecimalToAmount(singleMLToDecimal(storedOdds[parlay[i]["ref"]]), $('#format-amount-box1').val());
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
                    dispLine = singleDecimalToAmount(singleMLToDecimal(document.getElementById('oID' + parlay[i]["ref"]).innerHTML), $('#format-amount-box1').val());
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
            dispValue = singleDecimalToAmount(pvalue, $('#format-amount-box1').val());
            break;
        default:
            break;
    }

    $('#parlay-area').html(tmpText);
    $('#parlay-header').html('Parlay: ' + dispValue);
    return false;
};

oddsToMoneyline = function () {
    if (oddsType == 1) {
        return;
    }

    if (storedOdds.length > 0) {
        $('[id^="oID"]').each(function () {
            this.innerHTML = storedOdds[this.id.substring(3)];
        });
    }

    oddsType = 1;
};

oddsToDecimal = function () {
    if (oddsType == 2) {
        return;
    }

    if (oddsType != 1) {
        oddsToMoneyline();
    }

    //If odds are not stored, store them
    if (oddsType == 1 && storedOdds.length === 0) {
        $('[id^="oID"]').each(function () {
            storedOdds[this.id.substring(3)] = this.innerHTML;
        });
    }


    $('[id^="oID"]').each(function () {
        this.innerHTML = parseFloat(singleMLToDecimal(this.innerHTML)).toFixed(2);
    });

    oddsType = 2;
};

oddsToAmount = function (amount) {
    var value;
    if (amount === null) {
        //value = document.getElementById('format-amount-box1').value;
        value = $('#format-amount-box1').val();
    } else {
        value = amount;
    }
    if (isNaN(value) || value < 0) {
        return;
    }

    oddsToDecimal();

    $('[id^="oID"]').each(function () {
        var odds = parseFloat(this.innerHTML);
        this.innerHTML = '$' + (Math.round(((value * odds) - value)));
    });

    oddsType = 3;
    Cookies.set('bfo_odds_type', 3, {
        'expires': 999
    });
    Cookies.set('bfo_risk_amount', value, {
        'expires': 999
    });

    $("#format-toggle-text").find('span').first().next().html("$" + value + "  &#9660;");
    $('#format-amount-box1').val(value);

    if (parlayMode) {
        addToParlay(null);
    }
};

oddsToFraction = function () {
    if (oddsType == 4) {
        return;
    }

    if (oddsType != 1) {
        oddsToMoneyline();
    }

    //If odds are not stored, store them
    if (oddsType == 1 && storedOdds.length === 0) {
        $('[id^="oID"]').each(function () {
            storedOdds[this.id.substring(3)] = this.innerHTML;
        });
    }

    $('[id^="oID"]').each(function () {
        this.innerHTML = singleMLToFractional(this.innerHTML);
    });

    oddsType = 4;
};

singleMLToDecimal = function (odds) {
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
};

singleMLToFractional = function (odds) {
    //Rounds down to closest 5 factor
    odds = 5 * Math.round(odds / 5);
    function getCalcDig(num1, num2) {
        var a; var b;
        if (num1 < num2) { a = num2; b = num1; }
        else if (num1 > num2) { a = num1; b = num2; }
        else if (num1 == num2) { return num1; }
        while (1) {
            if (b == 0) { return a; }
            else {
                var temp = b;
                b = a % b;
                a = temp;
            }
        }
    }

    function reduceNum(a, b) {
        var n = new Array(2);
        var f = getCalcDig(a, b);
        n[0] = a / f;
        n[1] = b / f;
        return n;
    }

    var mn = parseFloat(odds);
    var dec;
    var num;
    var dom;

    if (mn < 0) {
        dom = (-1) * (mn);
        num = 100;
    }
    else if (mn > 0) {
        dom = 100;
        num = mn;
    }
    var a = reduceNum(num, dom)
    num = a[0];
    dom = a[1];

    dec = (num / dom) + 1;
    return "" + num + "/" + dom;
};

/**
 * @param {number} odds
 * @suppress {duplicate}
 * @return {string}
 */
oneDecToML = function (odds) {
    if (odds >= 2) {
        return '+' + Math.round(100 * (odds - 1));
    } else if (odds < 2) {
        return '' + Math.round(-100 / (odds - 1));
    } else {
        return 'error';
    }
};

/**
 * @param {(string|Element|jQuery|function(number))} arg1
 * @param {(string|Element|jQuery|function(number))} arg2
 * @return {!jQuery}
 */
singleDecimalToAmount = function (odds, amount) {
    var value;
    if (amount === null) {
        value = document.getElementById('format-amount-box1').value;
    } else {
        value = amount;
    }
    if (isNaN(value) || value < 0) {
        return '';
    }
    var val = new String((Math.round((value * odds) - value)));
    //var val = new String((Math.round(((value * odds) - value) * 100)));
    //val = val.slice(0, val.length - 2) + '.' + val.slice(-2);
    return '$' + val;
};

setOddsType = function (val) {
    switch (val) {
        case 1:
            oddsToMoneyline();
            Cookies.set('bfo_odds_type', 1, {
                'expires': 999
            });
            $("#format-toggle-text").find('span').first().next().html("Moneyline &#9660;");
            break;
        case 2:
            oddsToDecimal();
            Cookies.set('bfo_odds_type', 2, {
                'expires': 999
            });
            $("#format-toggle-text").find('span').first().next().html("Decimal &#9660;");
            break;
        case 3:
            $("#format-toggle-text").find('span').first().next().html("Amount &#9660;" + $("#format-amount-box1").html());
            break;
        case 4:
            oddsToFraction();
            Cookies.set('bfo_odds_type', 4, {
                'expires': 999
            });
            $("#format-toggle-text").find('span').first().next().html("Fractional &#9660;");
            break;
        default:
    }
    if (parlayMode) {
        addToParlay(null);
    }
};

notIn = function (a) { var d, e, f, g, h, i, j, b = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=", c = "", k = 0; for (a = a.replace(/[^A-Za-z0-9\+\/\=]/g, ""); k < a.length;)g = b.indexOf(a.charAt(k++)), h = b.indexOf(a.charAt(k++)), i = b.indexOf(a.charAt(k++)), j = b.indexOf(a.charAt(k++)), d = g << 2 | h >> 4, e = (15 & h) << 4 | i >> 2, f = (3 & i) << 6 | j, c += String.fromCharCode(d), 64 != i && (c += String.fromCharCode(e)), 64 != j && (c += String.fromCharCode(f)); for (var l = "", m = 0, n = c1 = c2 = 0; m < c.length;)n = c.charCodeAt(m), 128 > n ? (l += String.fromCharCode(n), m++) : n > 191 && 224 > n ? (c2 = c.charCodeAt(m + 1), l += String.fromCharCode((31 & n) << 6 | 63 & c2), m += 2) : (c2 = c.charCodeAt(m + 1), c3 = c.charCodeAt(m + 2), l += String.fromCharCode((15 & n) << 12 | (63 & c2) << 6 | 63 & c3), m += 3); var q, r, s, o = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~", p = new String, t = o.length; for (q = 0; q < l.length; q++)s = l.charAt(q), r = o.indexOf(s), r >= 0 && (s = o.charAt((r + t / 2) % t)), p += s; return p };


getElementsByClassName = function (strClassName, obj) {
    var ar = arguments[2] || new Array();
    var re = new RegExp("\\b" + strClassName + "\\b", "g");

    if (re.test(obj.className)) {
        ar.push(obj);
    }
    for (var i = 0; i < obj.childNodes.length; i++)
        getElementsByClassName(strClassName, obj.childNodes[i], ar);

    return ar;
};

$(document).ready(function () {

    initPage();

    //TODO: Auto refresh disabled
    /*if ($('#auto-refresh-container').css('display') != 'none') {
        if (Cookies.get('bfo_autorefresh') !== null && !isNaN(Cookies.get('bfo_autorefresh')) && Cookies.get('bfo_autorefresh') == '0') {
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
    }*/

    //Bind dropdowns
    $('#formatSelector1').click(function () {
        $('[id^="formatSelector"]').each(function () {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        setOddsType(1);
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#formatSelector2').click(function () {
        $('[id^="formatSelector"]').each(function () {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        setOddsType(2);
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#formatSelector3').click(function () {
        $('[id^="formatSelector"]').each(function () {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        oddsToAmount($('#format-amount-box1').val());
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#formatSelector4').click(function () {
        $('[id^="formatSelector"]').each(function () {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        setOddsType(4);
        $(this).addClass("list-checked");
        $('span', this).css('display', 'inline-block');
    });
    $('#format-amount-box1').change(function () {
        $('[id^="formatSelector"]').each(function () {
            $(this).removeClass("list-checked");
            $('span', this).css('display', 'none');
        });
        oddsToAmount($('#format-amount-box1').val());
        $('#formatSelector3').addClass("list-checked");
        $('span', $('#formatSelector3')).css('display', 'inline-block');
    });
    $("#format-amount-box1").keyup(function (event) {
        if (event.keyCode == 13) {
            $("#format-amount-box1").change();
        }
    });

    //Darkmode/normal selectors

    $('#normalModeSelector').click(function () {
        $('#darkModeSelector').removeClass("list-checked");
        $('span', $('#darkModeSelector')).css('display', 'none');
        setDarkMode(false);
        $('#normalModeSelector').addClass("list-checked");
        $('span', $('#normalModeSelector')).css('display', 'inline-block');

    });
    $('#darkModeSelector').click(function () {
        $('#normalModeSelector').removeClass("list-checked");
        $('span', $('#normalModeSelector')).css('display', 'none');
        setDarkMode(true);
        $('#darkModeSelector').addClass("list-checked");
        $('span', $('#darkModeSelector')).css('display', 'inline-block');
    });

    //Autorefresh selectors - Disabled
    /*$('#afSelectorOn').click(function() {
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
    });*/

    $('#parlay-mode-box').click(function () {
        parlay = [];
        if (typeof $(this).data('toggled') == 'undefined') {
            $(this).data('toggled', false);
        }
        $(this).data('toggled', !$(this).data('toggled'));
        if ($(this).data('toggled')) {
            $('#parlay-mode-box').find('.bfo-check-box').addClass('checked');
            parlayMode = true;
            $('#parlay-window').addClass('is-visible');

            var isTouch = (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));
            if (!isTouch) {
                $(document).on('mousemove', function (e) {
                    $('#parlay-window').css({
                        left: e.clientX + 8,
                        top: e.clientY + 8
                    });
                });
            }
            else {
                //Position parlay mode somehow
                $('#parlay-window').css({
                    left: 5,
                    top: 5
                });
            }
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
    $('#search-box1').on('mousedown', function (e) {
        $(this).css('color', '#fff');
        $(this).focus();
        $(this).off('mousedown');
    });


    //Alert button add listener
    $("#alert-form").submit(function (event) {
        event.preventDefault();
        var $inputs = $('#alert-form :input,select');
        var values = {};
        $inputs.each(function () {
            values[this.name] = $(this).val();
        });
        $('#alert-submit')[0].disabled = true; //.prop( "disabled", true );
        $('.alert-result').removeClass('success error');
        $(event.target).find("input").removeClass('success error');
        $('.alert-loader').css('display', 'inline-block');
        $.get("api?f=aa", {
            'alertFight': values['m'],
            'alertFighter': values['tn'],
            'alertBookie': values['alert-bookie'],
            'alertMail': values['alert-mail'],
            'alertOdds': values['alert-odds'],
            'alertOddsType': oddsType
        }, function (data) {
            $('.alert-loader').css('display', 'none');
            var sMessage = '';
            switch (data) {
                case '1':
                    sMessage = '✓ Alert added';
                    Cookies.set('bfo_alertmail', values['alert-mail'], {
                        'expires': 999
                    });
                    break;
                case '2':
                    sMessage = '✓ Alert already exists';
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
            $('.alert-result').addClass((data >= 1 ? 'success' : 'error'));
            $('.alert-result').text(sMessage);
            $(event.target).find('input[type="submit"]').prop("disabled", false);
        });
    });

    //Alert button (inline) add listener
    //$("#alert-form-il").submit(function(event) {
    $("#alert-form-il .button").on('click', function (event) {
        event.preventDefault();
        var $inputs = $('#alert-form-il :input,select');
        var values = {};
        $inputs.each(function () {
            values[this.name] = $(this).val();
        });
        var curbut = $(this);
        curbut[0].disabled = true; //.prop( "disabled", true );
        curbut.prevAll('.alert-result').removeClass('success error');
        $('#alert-mail-il').removeClass('success error');
        curbut.prevAll('.alert-result-il').text('');
        curbut.prevAll('.alert-loader').css('display', 'inline-block');
        $.get("/api?f=aa", {
            'alertFight': $(this).data("mu"),
            'alertFighter': '1',
            'alertBookie': values['alert-bookie-il'],
            'alertMail': values['alert-mail-il'],
            'alertOdds': '-9999',
            'alertOddsType': oddsType
        }, function (data) {
            curbut.prevAll('.alert-loader').css('display', 'none');
            var sMessage = '';
            switch (data) {
                case '1':
                    sMessage = '✓ Alert added';
                    Cookies.set('bfo_alertmail', values['alert-mail-il'], {
                        'expires': 999
                    });
                    break;
                case '2':
                    sMessage = '✓ Alert already exists';
                    break;
                case '-1':
                case '-2':
                case '-3':
                    sMessage = 'x Error: Missing values (' + data + ')';
                    break;
                case '-4':
                    sMessage = 'x Invalid e-mail';
                    $('#alert-mail-il').addClass("error");
                    break;
                case '-5':
                    sMessage = 'x Invalid odds format';
                    break;
                case '-6':
                    sMessage = 'x Alert limit reached (50)';
                    break;
                case '-7':
                    sMessage = 'x Odds already reached';
                    break;
                default:
                    sMessage = 'x Unknown error';
            }

            curbut.prevAll('.alert-result-il').addClass((data >= 1 ? 'success' : 'error'));
            curbut.prevAll('.alert-result-il').text(sMessage);

            if (data <= 0) {
                curbut[0].disabled = false; //.prop( "disabled", true );    
            }
        });
    });

    //Team page: Mean graph text button listener
    $("span.teamPercChange").on('click', function (event) {
        var versus = $(this).closest('tr').next('tr').find("th.oppcell").text();
        var title = $("#team-name").text() + " <span style=\"font-weight: normal;\">(vs. " + versus + ") &#150; Mean odds";
        chartCC();
        createMIChart(opts[0], opts[1]);
        chartSC(title, event.clientX, event.clientY);
        return false;
    });

});

initPage = function () {
    oddsType = 1;
    storedOdds = [];
    if (Cookies.get('bfo_odds_type') !== null) {
        if (!isNaN(Cookies.get('bfo_odds_type'))) {
            cOddsType = parseInt(Cookies.get('bfo_odds_type'));
            switch (cOddsType) {
                case 1:
                    $("#format-toggle-text").find('span').first().next().html("Moneyline &#9660;");
                    break;
                case 2:
                    $("#format-toggle-text").find('span').first().next().html("Decimal &#9660;");
                    oddsToDecimal();
                    break;
                case 3:
                    $("#format-toggle-text").find('span').first().next().html(" $" + Cookies.get('bfo_risk_amount') + " &#9660;");
                    $("#format-amount-box1").find('span').first().next().html(Cookies.get('bfo_risk_amount'));
                    oddsToAmount(Cookies.get('bfo_risk_amount'));
                    break;
                case 4:
                    $("#format-toggle-text").find('span').first().next().html("Fractional &#9660;");
                    oddsToFraction();
                    break;
                default:
            }
            $('[id^="formatSelector"]').each(function () {
                $(this).removeClass("list-checked");
                $('span', this).css('display', 'none');
            });
            $("#formatSelector" + cOddsType).addClass("list-checked");
            $('span', $("#formatSelector" + cOddsType)).css('display', 'inline-block');
        }
    }

    //Enable dark mode if stored in cookie bfo_darkmode
    if (Cookies.get('bfo_darkmode') !== null) {
        if (parseInt(Cookies.get('bfo_darkmode')) == 1) {
            $('#normalModeSelector').removeClass("list-checked");
            $('span', $('#normalModeSelector')).css('display', 'none');
            $('#darkModeSelector').addClass("list-checked");
            $('span', $('#darkModeSelector')).css('display', 'inline-block');
        }
    }

    //Cache tables for scrolling purpose
    $('div.table-scroller').each(function () {
        scrollCache.push([$(this), $('table', $(this)), $(this).prev().prev('.table-inner-shadow-left'), $(this).prev('.table-inner-shadow-right')]);
        $.each(scrollCache, function (key, value) {
            value[2].data("scrollLeftVis", false);
            value[3].data("scrollRightVis", true);
        });
    });

    //Add prop row togglers

    document.querySelectorAll('.prop-cell-exp').forEach(function (item) {
        item.addEventListener("click", function (event) {
        matchup_id = $(this).attr('data-mu');
        if (typeof $(this).data('toggled') == 'undefined') {
            $(this).data('toggled', false);
        }
        $(this).data('toggled', !$(this).data('toggled'));
        $("[data-mu=" + matchup_id + "]").data('toggled', $(this).data('toggled'));
        if ($(this).data('toggled')) {
            if (navigator.appName.indexOf("Microsoft") > -1 && navigator.appVersion.indexOf("MSIE 10.0") == -1) {
                $(this).closest('tr').next('tr.odd').addBack('tr.odd').nextUntil('tr.even').css('display', 'block');
                $('#mu-' + matchup_id).nextUntil('tr.even').css('display', 'block');
            } else {
                $(this).closest('tr').next('tr.odd').addBack('tr.odd').nextUntil('tr.even').css('display', 'table-row');
                $('#mu-' + matchup_id).nextUntil('tr.even').css('display', 'table-row');
            }
            $("[data-mu='" + matchup_id + "']").find(".exp-ard").addClass("exp-aru").removeClass("exp-ard");
            refreshOpenProps[matchup_id] = true;
        } else {
            $(this).closest('tr').next('tr.odd').addBack('tr.odd').nextUntil('tr.even').css('display', 'none');
            $('#mu-' + matchup_id).nextUntil('tr.even').css('display', 'none');
            $("[data-mu='" + matchup_id + "']").find(".exp-aru").addClass("exp-ard").removeClass("exp-aru");
            refreshOpenProps[matchup_id] = false;
        }
        return false;
    })
});

    $('tr.eventprop th').find('a').on('click', function (event) {
        event.preventDefault();
        event_id = $(this).attr('data-mu');
        $('.prop-cell').find("[data-mu=" + event_id + "]").trigger('click');
    });


    //Sync scrollbars
    /*$('div.table-scroller').bind('mousedown touchstart', function () {
        scrollCaptain = $(this);
    });*/
    document.querySelectorAll('.table-scroller').forEach(function (item) {
        item.addEventListener("mousedown", function (event) {
            scrollCaptain = this;
        }, {passive: true});
        item.addEventListener("touchstart", function (event) {
            scrollCaptain = this;
        }, {passive: true});
    });

    $('div.table-scroller').on('scroll', function () {

        var selfscroller = $(this);
        if (selfscroller.scrollLeft() == scrollX || !selfscroller.is(scrollCaptain)) return false;
        scrollX = selfscroller.scrollLeft();
        $.each(scrollCache, function (key, value) {
            if (value[0].is(selfscroller)) { } else {
                value[0].scrollLeft(selfscroller.scrollLeft());
            }

            if (value[0].scrollLeft() >= (value[1].width() - value[0].width()) - 5) {
                value[3].css("width", 0 + ((value[1].width() - value[0].width()) - value[0].scrollLeft()));
                value[3].data("scrollRightVis", false);

            } else if (value[3].data("scrollRightVis") === false) {
                value[3].css("width", "5px");
                value[3].data("scrollRightVis", true);
            }

            if (value[0].scrollLeft() <= 5) {
                value[2].css("width", 0 + value[0].scrollLeft());
                value[2].data("scrollLeftVis", false);
            } else if (value[2].data("scrollLeftVis") === false) {
                value[2].css("width", "5px");
                value[2].data("scrollLeftVis", true);
            }

        });


        //}, 10))
    });

    //Add dropdowns
    $(function () {
        //enable hover
        $("ul.dropdown li").hover(function () {
            $(this).addClass("hover");
            $('ul:first', this).css('visibility', 'visible');
        }, function () {

            $(this).removeClass("hover");
            $('ul:first', this).css('visibility', 'hidden');
        });

        //click for mobile
        $("ul.dropdown li").click(function (e) {
            if (!$(e.toElement).is("#format-amount-box1")) {
                if ($(this).hasClass("hover")) {
                    $(this).removeClass("hover");
                    $('ul:first', this).css('visibility', 'hidden');
                }
                else {
                    $(this).addClass("hover");
                    $('ul:first', this).css('visibility', 'visible');
                }
            }

        });

        //close on click (doesnt really work)
        $("ul.dropdown li ul li").click(function () {

        });
    });

    //Add share popup
    $('.share-area').click(function (event) {
        var shw = $(this).parent().find('.share-window');
        $(shw).addClass('show');
        //Add event handlers for each share window
        $(shw).find('.share-item').on('click', function (event) {
            if ($(this).data('href').substring(0, 11) == 'whatsapp://') {
                window.location.href = $(this).data('href');
            }
            else {
                window.open('' + $(this).data('href'), '_blank');
            }
        });
        shareVisible = true;
    });

    //Add graph popup controls
    //close popup
    $('#chart-window').on('click', function (event) {
        if ($(event.target).is('.cd-popup-close') || $(event.target).is('#chart-window')) {
            event.preventDefault();
            $(this).removeClass('is-visible');
            $('#chart-area').empty();
        }
    });
    $('#alert-window').on('click', function (event) {
        if ($(event.target).is('.cd-popup-close') || $(event.target).is('#alert-window')) {
            event.preventDefault();
            $(this).removeClass('is-visible');
        }
    });

    //close popup when clicking the esc keyboard button
    $(document).keyup(function (event) {
        if (event.which == '27') {
            $('#chart-window').removeClass('is-visible');
            $('#chart-area').empty();
        }
    });
    //close when clicking anywhere but the graph (if open that is)
    //$(document).click(function (event) {
    document.addEventListener("click", function (event) {
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

        if (shareVisible === true) {
            if (!$(event.target).closest('.share-button').length) {
                if (!$(event.target).closest('.share-item').length) {
                    $('div.share-window.show').removeClass('show');
                    shareVisible = false;
                    $('.share-item').off("click");
                }
            }
        }
    });



    //Bind graph clicks on table
    //Loop through all tr TDs and add event

    //Add regular matchup listeners
    document.querySelectorAll('.but-sg').forEach(function (item) {
        item.addEventListener("click", function (event) {
            event.stopPropagation();
            var opts = JSON.parse(this.getAttribute('data-li'));
            if (parlayMode) {
                return addToParlay(this);
            } else {
                var title = this.parentNode.parentNode.querySelector("th").querySelector("a").textContent + " <span style=\"font-weight: normal;\"> &#150; <a href=\"" + 
                this.closest('table').querySelectorAll('th')[index(this.parentNode) - 1].querySelector("a").getAttribute("href") + "\" target=\"_blank\">" + 
                this.closest('table').querySelectorAll('th')[index(this.parentNode) - 1].querySelector("a").textContent 
                + "</a></span>";

                chartCC();
                createMChart(opts[0], opts[1], opts[2]);
                chartSC(title, event.clientX, event.clientY);

                return false;
            }
        })
    });

    //Add prop listeners
    document.querySelectorAll('.but-sgp').forEach(function (item) {
        item.addEventListener("click", function () {
            event.stopPropagation();
            if (parlayMode) {
                return addToParlay(this);
            } else {
                var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; <a href=\"" + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").attr("href") + "\" target=\"_blank\">" + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").text() + "</a></span>";
                chartCC();
                createPChart(opts[0], opts[2], opts[1], opts[3], opts[4]);
                chartSC(title, event.clientX, event.clientY);
                return false;
            }
        })
    });
    //Add event prop listeners
    document.querySelectorAll('.but-sgep').forEach(function (item) {
        item.addEventListener("click", function () {
            event.stopPropagation();
            if (parlayMode) {
                return addToParlay(this);
            } else {
                var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; <a href=\"" + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").attr("href") + "\" target=\"_blank\">" + $(this).closest('table').find('th').eq($(this).parent().index()).find("a").text() + "</a></span>";
                chartCC();
                createEPChart(opts[0], opts[1], opts[2], opts[3]);
                chartSC(title, event.clientX, event.clientY);
                return false;
            }
        })
    });


    //Add index graph button listeners
    document.querySelectorAll('.but-si').forEach(function (item) {
        item.addEventListener("click", function () {
            event.stopPropagation();
            if (parlayMode) {
                return false;
            } else {
                var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean odds";
                chartCC();
                createMIChart(opts[1], opts[0]);
                chartSC(title, event.clientX, event.clientY);

                return false;
            }
        })
    });
    //Add prop index graph button listeners
    document.querySelectorAll('.but-sip').forEach(function (item) {
        item.addEventListener("click", function () {
            event.stopPropagation();
            if (parlayMode) {
                return false;
            } else {
                var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean odds";
                chartCC();
                createPIChart(opts[1], opts[0], opts[2], opts[3]);
                chartSC(title, event.clientX, event.clientY);
                return false;
            }
        })
    });

    //Add event prop index graph button listeners
    document.querySelectorAll('.but-siep').forEach(function (item) {
        item.addEventListener("click", function () {
            event.stopPropagation();
            if (parlayMode) {
                return false;
            } else {
                var title = $(this).parent().parent().find("th").text() + " <span style=\"font-weight: normal;\"> &#150; Mean odds";
                chartCC();
                createEPIChart(opts[0], opts[1], opts[2]);
                chartSC(title, event.clientX, event.clientY);
                return false;
            }
        })
    });

    //Add alert button form show listeners
    document.querySelectorAll('.but-al').forEach(function (item) {
        item.addEventListener("click", function () {
            event.stopPropagation();
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
        })
    });
};


//TODO: Why is this empty??
addAlert = function (m, tn, b, mail, alert_odds, alert_oddstype) {
};

refreshPage = function () {
    $("#content").load("api?f=rp", function () {
        initPage();
        $.each(refreshOpenProps, function (index, value) {
            if (value === true) {
                $('a[data-mu="' + index + '"]').first().trigger("click");
            }
        });
    });
};

toggleRefresh = function (autoRefresh) {
    if (autoRefresh === true) {
        refreshId = setInterval(function () {
            refreshPage();
        }, 120000);
        $("#autoRefresh").addClass("refresh-ind-spin");
        Cookies.set('bfo_autorefresh', 1, {
            'expires': 999
        });
    } else {
        $("#autoRefresh").removeClass("refresh-ind-spin");
        Cookies.set('bfo_autorefresh', 0, {
            'expires': 999
        });
        if (typeof refreshId !== 'undefined') {
            clearInterval(refreshId);
        }
    }
};

setDarkMode = function (darkmode) {
    if (darkmode == true) {
        if (typeof (document.getElementById("darkmodecss")) == 'undefined' || document.getElementById("darkmodecss") == null) {
            var e = document.createElement('link');
            e.href = '/css/bfo.darkmode.css';
            e.type = 'text/css';
            e.rel = 'stylesheet';
            e.media = 'screen';
            e.id = 'darkmodecss';
            document.getElementsByTagName('head')[0].appendChild(e);
        }
        Cookies.set('bfo_darkmode', 1, {
            'expires': 999
        });
    }
    else {
        var darkmodecss = document.getElementById("darkmodecss");
        if (typeof (darkmodecss) != 'undefined' && darkmodecss != null) {
            darkmodecss.outerHTML = "";
        }
        Cookies.set('bfo_darkmode', 0, {
            'expires': 999
        });
    }
}


throttle = function (fn, threshhold, scope) {
    threshhold || (threshhold = 250);
    var last,
        deferTimer;
    return function () {
        var context = scope || this;

        var now = +new Date,
            args = arguments;
        if (last && now < last + threshhold) {
            // hold on to it
            clearTimeout(deferTimer);
            deferTimer = setTimeout(function () {
                last = now;
                fn.apply(context, args);
            }, threshhold);
        } else {
            last = now;
            fn.apply(context, args);
        }
    };
};

debounce = function (fn, delay) {
    var timer = null;
    return function () {
        var context = this,
            args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
};


$(function () {
    FastClick.attach(document.body);
});

fightSelected = function () {
    fightID = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].value;
    ftitle = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].text.trim();
    if (fightID !== 0) {
        imageLink = "";
        type = "";
        if ($('[name="webLineType"]:checked').val() == 'opening') {
            type += '_o';
        }
        if ($('[name="webLineFormat"]:checked').val() == '2') {
            type += '_d';
        }

        if (fightID > 0) {
            imageLink = 'fights/' + fightID + type + '.png';
        }
        else if (fightID < 0) {
            imageLink = 'events/' + Math.abs(fightID) + type + '.png';
        }
        $('[name="webTestImage"]')[0].src = "/img/loading.gif";
        $("#webHTML").val('<!-- Begin BestFightOdds code -->\n<a href="https://www.bestfightodds.com" target="_blank"><img src="https://www.bestfightodds.com/' + imageLink + '" alt="' + ftitle + ' odds - BestFightOdds" style="width: 216px; border: 0;" /></a>\n<!-- End BestFightOdds code -->');
        $("#webForum").val('[url=https://www.bestfightodds.com][img]https://www.bestfightodds.com/' + imageLink + '[/img][/url]');
        $('[name="webTestImage"]')[0].src = '' + imageLink;
        $("#webImageLink").val('https://www.bestfightodds.com/' + imageLink);
        $("#webFields").css({ 'display': '' });
    }
    else {
        $("#webFields").css({ 'display': 'none' });
    }
};

function hideArrows() {
    $('.ard').css("display", "none");
    $('.aru').css("display", "none");
    window.setTimeout(function () {
        $('.ard').css("display", "inline-block");
        $('.aru').css("display", "inline-block");
    }, 0);
}
document.oncopy = hideArrows;
