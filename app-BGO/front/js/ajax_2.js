// ========= MAIN ===========

var storedOdds = new Array();
var usedSearch = false;
var oddsType = 1; //1. Moneyline 2. Decimal. 3. Custom
var parlayMode = false;
var parlay = new Array();

//showHistory
function sH(obj, bookie, fighter, fight)
{
    if (parlayMode)
    {
        return addToParlay(obj);
    }
    else
    {
        var prevHTML = $(obj).html();
        $(obj).html("<img src=\'/img/ajax-loader2.gif\' />");
        
        $.get("/ajax/ajax.Interface.php?function=getLineDetails", {
            m: fight,
            b: bookie,
            p: fighter
        }, function(data) {
            json_data = eval('(' + data + ')');
            
            var openLine = json_data.open.odds;
            if (oddsType == 2)
            {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
            }
            $(obj).html(prevHTML);

            return overlib('<div class=\'popup-container\'><div class=\'popup-header\'>Last change: <span style=\'font-weight: normal;\' id="popupTarget">' + json_data.current.changed + '</span><span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><div class=\'graph-container\'><img src=\'/ajax/getGraph.php?bookieID=' + bookie + '&fighter=' + fighter + '&fightID=' + fight + '&oddsType=' + oddsType + '\' class="graph-image" /></div></div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
        });
        return false;
    }
    return null;
}

//showHistoryProp
function sHp(obj, bookie, posprop, matchup, proptype, teamnum)
{
    if (parlayMode)
    {
        return addToParlay(obj);
    }
    else
    {
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
            if (oddsType == 2)
            {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
                currentLine = parseFloat(singleMLToDecimal(currentLine)).toFixed(2);
            }
            $(obj).html(prevHTML);
            return overlib('<div class=\'popup-container\'><div class=\'popup-header\'>Last change: <span style=\'font-weight: normal;\'>' + json_data.current.changed + '<span><span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><div class=\'graph-container\'><img src=\'/ajax/getPropGraph.php?bookieID=' + bookie + '&posProp=' + posprop + '&matchupID=' + matchup + '&oddsType=' + oddsType + '&propTypeID=' + proptype + '&teamNum=' + teamnum + '\' class="graph-image" /></div></div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
        });
        return false;
    }
    return null;
}

//showIndex
function sI(target, fighter, fight)
{
    if (parlayMode)
    {
        return false;
    }
    else
    {
        var prevHTML = $(target).html();
        $(target).html("<img src=\'/img/ajax-loader2.gif\' />");
        
        $.get("/ajax/ajax.Interface.php?function=getLineDetails", {
            m: fight,
            p: fighter
        }, function(data) {
            
            json_data = eval('(' + data + ')');
            
            var openLine = json_data.open.odds;
            var currentLine = json_data.current.odds;
            if (oddsType == 2)
            {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
                currentLine = parseFloat(singleMLToDecimal(currentLine)).toFixed(2);
            }
            $(target).html(prevHTML);
            return overlib('<div class=\'popup-container\'><div class=\'popup-header\'>Current mean: ' + currentLine + '<span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><div class=\'graph-container\'><img src=\'/ajax/getIndexGraph.php?fighter=' + fighter + '&fightID=' + fight + '&oddsType=' + oddsType + '\' class="graph-image" /></div></div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
        });
        return false;
    }
    
    return null;
}


//showPropIndex
function sIp(target, posprop, matchup, proptype, teamnum)
{
    if (parlayMode)
    {
        return false;
    }
    else
    {
        var prevHTML = $(target).html();
        $(target).html("<img src=\'/img/ajax-loader2.gif\' />");
        
        $.get("/ajax/ajax.Interface.php?function=getPropLineDetails", {
            m: matchup,
            p: posprop,
            pt: proptype,
            tn: teamnum
            
        }, function(data) {
            
            json_data = eval('(' + data + ')');
            
            var openLine = json_data.open.odds;
            var currentLine = json_data.current.odds;
            if (oddsType == 2)
            {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
                currentLine = parseFloat(singleMLToDecimal(currentLine)).toFixed(2);
            }
            $(target).html(prevHTML);
            return overlib('<div class=\'popup-container\'><div class=\'popup-header\'>Current mean: ' + currentLine + '<span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><div class=\'graph-container\'><img src=\'/ajax/getPropIndexGraph.php?posProp=' + posprop + '&matchupID=' + matchup + '&oddsType=' + oddsType + '&propTypeID=' + proptype + '&teamNum=' + teamnum + '\' class="graph-image" /></div></div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
        });
        return false;
    }
}

//showNoHistory
function sNH(obj, bookie, fighter, fight)
{
    if (parlayMode)
    {
        return addToParlay(obj);
    }
    else
    {
        var prevHTML = $(obj).html();
        $(obj).html("<img src=\'/img/ajax-loader2.gif\' />");
        
        $.get("/ajax/ajax.Interface.php?function=getLineDetails", {
            m: fight,
            b: bookie,
            p: fighter
        }, function(data) {
            json_data = eval('(' + data + ')');
            
            var openLine = json_data.open.odds;
            if (oddsType == 2)
            {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
            }
            $(obj).html(prevHTML);
            return overlib('<div class=\'popup-container\' style="height: 65px;"><div class=\'popup-header\'>Last change: <span style=\'font-weight: normal;\'>' + json_data.current.changed + '<span><span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><br />No line movement since opening</div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
        });
        return false;
    }
}

//showNoHistoryProp
function sNHp(obj, bookie, posprop, matchup, proptype, teamnum)
{
    if (parlayMode)
    {
        return addToParlay(obj);
    }
    else
    {
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
            if (oddsType == 2)
            {
                openLine = parseFloat(singleMLToDecimal(openLine)).toFixed(2);
                currentLine = parseFloat(singleMLToDecimal(currentLine)).toFixed(2);
            }
            $(obj).html(prevHTML);
            return overlib('<div class=\'popup-container\' style="height: 65px;"><div class=\'popup-header\'>Last change: <span style=\'font-weight: normal;\'>' + json_data.current.changed + '<span><span style=\'float: right; font-weight: normal;\'>Opened at: ' + openLine + '</span></div><br />No line movement since opening</div>', STICKY, MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
        });
        return false;
    }
}

function iSI()
{

}


//closeOverLib
function cO()
{
    if (!parlayMode)
    {
        return nd();
    }
}

//linkOut
function lO(operator, event)
{
    $.get("/ajax/ajax.LinkOut.php", {
        operator: operator,
        event: event
    });
}


//showSpreadList
function sSL(spreads)
{
    if (!parlayMode)
    {
        return overlib('<div class=\'previous-odds\'>' + spreads + '</div>', WRAP , FGCOLOR, '#eeeeee', BGCOLOR, '#1f2a34', BORDER, 1)
    }
}


function addToParlay(obj)
{
    if (obj != null)
    {
        if (parlay.length >= 25)
        {
            return false;
        }

        tmpArr = new Array();
        tmpArr["ml"] = obj.getElementsByTagName('span')[0].innerHTML;
        if (obj.parentNode.parentNode.getElementsByTagName('th')[0].getElementsByTagName('a').length > 0)
        {
            //Regular row
            tmpArr["name"] = obj.parentNode.parentNode.getElementsByTagName('th')[0].getElementsByTagName('a')[0].innerHTML;        
        }
        else
        {
            //Prop row
            tmpArr["name"] = obj.parentNode.parentNode.getElementsByTagName('th')[0].innerHTML;
        }
                 
        tmpArr["ref"] = obj.getElementsByTagName('span')[0].id.substring(6);

	found = false;
	for (var i = 0; i < parlay.length; i++)
	{
		if (parlay[i]["ref"] == tmpArr["ref"])
		{
			found = true;
		}
	}
	if (!found)
	{	
		parlay.push(tmpArr);
	}

    }
    else
    {
        if (parlay.length == 0)
        {
            return false;
        }
    }

    tmpText = '';
    pvalue = 1;
    for (var i = 0; i < parlay.length; i++)
    {
        dispLine = '';
        if (storedOdds[parlay[i]["ref"]] != null)
        {
            switch (oddsType)
            {
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
        }
        else
        {
            switch (oddsType)
            {
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
    switch (oddsType)
    {
        case 1:
            if (parlay.length == 1)
            {
                dispValue = parlay[0]["ml"];
            }
            else
            {
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

function oddsToMoneyline()
{
    if (oddsType == 1)
    {
        return;
    }

    if (storedOdds.length > 0)
    {
        var obj;
        var id = 0;
        var newOdds;
        while((obj = document.getElementById('oddsID' + id)))
        {
            newOdds = storedOdds[id];
            obj.innerHTML = newOdds;
            id++;
        }
    }

    oddsType = 1;
}

function oddsToDecimal()
{
    if (oddsType == 2)
    {
        return;
    }

    if (oddsType != 1)
    {
        oddsToMoneyline();
    }

    //If odds are not stored, store them
    if (oddsType == 1 && storedOdds.length == 0)
    {
        var obj;
        var id = 0;
        while((obj = document.getElementById('oddsID' + id)))
        {
            storedOdds[id] = obj.innerHTML;
            id++;
        }
    }
    
    obj = null;
    id = 0;
    while((obj = document.getElementById('oddsID' + id)))
    {
        obj.innerHTML = parseFloat(singleMLToDecimal(obj.innerHTML)).toFixed(2);
        id++;
    }
    oddsType = 2;
}

function oddsToAmount(amount)
{
    var value;
    if (amount == null)
    {
        value = document.getElementById('format-amount-box1').value;
    }
    else
    {
        value = amount;
    }
    if (isNaN(value) || value < 0)
    {
        return;
    }

    oddsToDecimal();

    var obj;
    var id = 0;
    while((obj = document.getElementById('oddsID' + id)))
    {
        var odds = parseFloat(obj.innerHTML);
        obj.innerHTML = '$' + (Math.round(((value * odds) - value)));
        id++;
    }
    
    oddsType = 3;
    $.cookie('bfo_odds_type', 3, {
        expires: 999,
        path: '/'
    });
    $.cookie('bfo_risk_amount', value, {
        expires: 999,
        path: '/'
    });

    if (parlayMode)
    {
        addToParlay(null);
    }

}

function singleMLToDecimal(odds)
{
    if (String(odds).substring(0, 1) == "-")
    {
        oddsFloat = parseFloat(String(odds).substring(1, String(odds).length));
        oddsFloat = Math.round(((100 / oddsFloat) + 1) * 100) / 100;
        return oddsFloat.toString();
    }
    else if (String(odds).substring(0, 1) == "+")
    {
        oddsFloat = parseFloat(String(odds).substring(1, String(odds).length));
        oddsFloat = Math.round(((oddsFloat / 100) + 1) * 100) / 100;
        return oddsFloat.toString();
    }
    else
    {
        return 'error';
    }
}

function singleDecimalToML(odds)
{
    if (odds >= 2)
    {
        return '+' + Math.round(100 * (odds - 1));
    }
    else if (odds < 2)
    {
        return '' + Math.round(-100/(odds - 1));
    }
    else
    {
        return 'error';
    }
}

function singleDecimalToAmount(odds, amount)
{
    var value;
    if (amount == null)
    {
        value = document.getElementById('format-amount-box1').value;
    }
    else
    {
        value = amount;
    }
    if (isNaN(value) || value < 0)
    {
        return '';
    }
    return '$' + (Math.round(((value * odds) - value)));
}


function toggleAmountPicker(show)
{
    document.getElementById("format-amount-container1").style.display = (show ? "block" : "none");
}

function setOddsType()
{
    var selectBox = document.getElementById('format-select1');
    switch (selectBox.value)
    {
        case '1':
            toggleAmountPicker(false);
            oddsToMoneyline();
            $.cookie('bfo_odds_type', 1, {
                expires: 999,
                path: '/'
            });
            break;
        case '2':
            toggleAmountPicker(false);

            oddsToDecimal();
            $.cookie('bfo_odds_type', 2, {
                expires: 999,
                path: '/'
            });
            break;
        case '3':
            toggleAmountPicker(true);
            break;
        default:
    }
    if (parlayMode)
    {
        addToParlay(null);
    }
}

function initializeFront() 
{
    document.getElementById('search-box1').style.color="#a8a8a8";
    document.getElementById('search-box1').value = "MMA Event / Fighter";
    if ($.cookie('bfo_odds_type') != null)
    {
        if (!isNaN($.cookie('bfo_odds_type')))
        {
            document.getElementById('format-select1').value = $.cookie('bfo_odds_type');
            if ($.cookie('bfo_odds_type') == 3)
            {
                document.getElementById("format-amount-container1").style.display = 'block';
                document.getElementById("format-amount-box1").value = $.cookie('bfo_risk_amount');
            }
        }
    }
    else
    {
        document.getElementById('format-select1').value = "1";
    }
    
}

function useSearchBox()
{
    if (usedSearch == false)
    {
        $('#search-box1').css('color', '#fff');
        $('#search-box1').val('');
        usedSearch = true;
        $('#search-box1').focus();
    }
}

function toggleParlayMode()
{
    if (parlayMode == true)
    {
        parlay = [];
        parlayMode = false;
        id = 0;
        while((obj = document.getElementById('tablenot' + id)))
        {
            obj.innerHTML = 'Click on odds to view a history chart.';
            id++;
        }
        return nd();
    }
    else
    {
        parlayMode = true;
        id = 0;
        while((obj = document.getElementById('tablenot' + id)))
        {
            obj.innerHTML = 'Click on odds to add it to your parlay.';
            id++;
        }
        return overlib('<div class=\'popup-container\' id=\'parlay-mode-window\' style="width: auto; height: auto"><div class=\'popup-header\'>Parlay</div><p><span>-</span> Click on a betting line to add it<br />to your parlay<br /><br /><span>-</span> You can change the parlay format<br />at any time with the top right box</p></div>', MOUSEOFF, WRAP, CELLPAD, 5 , FULLHTML);
    }
}

//togglePropRow
function tPR(matchup_id, exp_button)
{
    var id = 1;
    var toggled = false;
    while((obj = document.getElementById('prop-' + matchup_id + '-' + id)))
    {
        if (obj.style.display == 'none' || obj.style.display == '')
        {
            if(navigator.appName.indexOf("Microsoft") > -1 && navigator.appVersion.indexOf("MSIE 10.0") == -1)
            {
                obj.style.display = 'block';
            }
            else
            {
                obj.style.display = 'table-row';
            }

            toggled = true;
        }
        else
        {
            obj.style.display = 'none';
            toggled = false;
        }
        id++;
    }
    
    if (toggled)
    {
        document.getElementById(exp_button + '-1').src = '/img/dexp.gif';
        document.getElementById(exp_button + '-2').src = '/img/dexp.gif';
    }
    else
    {
        document.getElementById(exp_button + '-1').src = '/img/exp.gif';
        document.getElementById(exp_button + '-2').src = '/img/exp.gif';
    }

    return false;
}

function getElementsByClassName( strClassName, obj )
{
    var ar = arguments[2] || new Array();
    var re = new RegExp("\\b" + strClassName + "\\b", "g");

    if ( re.test(obj.className) ) {
        ar.push( obj );
    }
    for ( var i = 0; i < obj.childNodes.length; i++ )
        getElementsByClassName( strClassName, obj.childNodes[i], ar );

    return ar;
}

window.onload = function() { 
    document.getElementById('parlay-mode-box').disabled = false;
};

$(document).ready(function()
{
    
    document.getElementById('parlay-mode-box').disabled = true;
    switch ($.cookie('bfo_odds_type'))
    {
        case '1':
            break;
        case '2':
            toggleAmountPicker(false);
            oddsToDecimal();
            break;
        case '3':
            oddsToAmount($.cookie('bfo_risk_amount'));
            break;
        default:
    }

    document.getElementById('parlay-mode-box').checked = false;
});





