// ========= ALERTS ===========

var storedAlertOdds = new Array('','');
var usePopUp = true;

/**
 * Sets whether the alert is added through a popup or not
 */
function setPopUp(cond)
{
    usePopUp = cond;
}

function showAlertForm(fightID, fighter, currentOdds) {
    if (parlayMode)
    {
        return false;
    }
    //Convert to decimal if that is the current preference
    if (oddsType == 2)
    {
        currentOdds = singleMLToDecimal(currentOdds);
    }
    var bookie_list;
    bookie_list += "<option value=\'1\'>5Dimes</option>";
    bookie_list += "<option value=\'13\'>BetDSI</option>";
    bookie_list += "<option value=\'3\'>BookMaker</option>";
    bookie_list += "<option value=\'5\'>Bovada</option>";
    bookie_list += "<option value=\'2\'>SportBet</option>";
    bookie_list += "<option value=\'4\'>Sportsbook</option>";
    bookie_list += "<option value=\'7\'>BetUS</option>";
    bookie_list += "<option value=\'9\'>Pinnacle</option>";
    bookie_list += "<option value=\'8\'>SportsInteraction</option>";
    bookie_list += "<option value=\'10\'>SBG</option>";
    bookie_list += "<option value=\'11\'>TheGreek</option>";
    bookie_list += "<option value=\'12\'>BetOnline</option>";
    return overlib('<div class=\'popup-container\'><div class=\'popup-header\'>Add Alert<a href=\'#\' onclick=\'return parent.cClick();\' class=\'alert-popup-close-button\'>X</a></div><div id=\'alert-popup-form-container\'><form name=\'popupAlertForm\' id=\'alert-popup-form1\'><input type=\'hidden\' name=\'alertFight\' value=\'' + fightID + '\' /><input type=\'hidden\' name=\'alertOddsType\' value=\'' + oddsType + '\' /><input type=\'hidden\' name=\'alertFighter\' value=\'' + fighter + '\' />Alert me at e-mail <input type=\'text\' name=\'alertMail\' value=\'\' style=\'width: 150px;\' /><br />when the odds reaches <input type=\'text\' name=\'alertOdds\' value=\'' + currentOdds + '\' style=\'width: 45px; margin-top: 3px;\' /> or better <br />at <select name=\'alertBookie\'><option value=\'-1\'>any bookie</option>' + bookie_list + '</select><br /><div style=\'text-align: center\'><input type="submit" class="abutton"/ value="Add alert" onclick=\'alertFormAddAlert(); return false;\'>  &nbsp; <input type="submit" class="abutton"/ value="Cancel" onclick="return parent.cClick();" style="width: 60px;"></div></form></div><div id="alert-popup-loading-container"><img src=\'img/ajax-loader.gif\' style=\'margin-bottom: -4px;\' />&nbsp; Adding alert. Please wait..</div><div id="alert-popup-result-container"></div></div>', STICKY, LEFT, CLOSECLICK, WRAP, CELLPAD, 5, EXCLUSIVE, FULLHTML);
}

function alertFormReset() {
    document.getElementById('alert-popup-result-container').style.display = 'none';
    document.getElementById('alert-popup-form-container').style.display = 'block';
    return false;
}

function alertFormAddAlert() {
    document.getElementById('alert-popup-form-container').style.display = 'none';
    document.getElementById('alert-popup-loading-container').style.display = 'block';

    $.get("/ajax/ajax.Interface.php?function=addAlert", {
        alertFight: document.forms['alert-popup-form1'].elements['alertFight'].value,
        alertFighter: document.forms['alert-popup-form1'].elements['alertFighter'].value,
        alertBookie: document.forms['alert-popup-form1'].elements['alertBookie'].value,
        alertMail: document.forms['alert-popup-form1'].elements['alertMail'].value,
        alertOdds: document.forms['alert-popup-form1'].elements['alertOdds'].value,
        alertOddsType: document.forms['alert-popup-form1'].elements['alertOddsType'].value
    }, function(data) {
        alertFormShowResult(data);
    });

    return false;
}

function alertFormShowResult(result) {
    document.getElementById('alert-popup-loading-container').style.display = 'none';
    elem = document.getElementById('alert-popup-result-container');

    var sResult = (result >= 1 ? 'success' : 'error');
    var sMessage = '';
    switch (result) {
        case '1':
            sMessage = 'Alert added';
            break;
        case '2':
            sMessage = 'You already have an alert for this selection';
            break;
        case '-1':
        case '-2':
            sMessage = 'No fight selected';
            break;
        case '-3':
            sMessage = 'No fighter selected';
            break;
        case '-4':
            sMessage = 'The e-mail you entered was in the wrong format';
            break;
        case '-5':
            sMessage = 'The odds you entered was in a wrong format';
            break;
        case '-6':
            sMessage = 'Alert limit reached (20)';
            break;
        case '-7':
            sMessage = 'The odds you entered have already been reached';
            break;
        default:
            sMessage = 'Unknown error';
    }
	
    if (usePopUp == true)
    {
        elem.innerHTML = '<img src=\'/img/' + sResult + '.png\' style=\'margin-bottom: -4px;\' />&nbsp; ' + sMessage + '<br />' + (result >= 1 ? '<input type="submit" class="abutton" value="Close"  onclick="return parent.cClick();" style="margin-top: 26px; margin-left: auto; margin-right: auto;">' : '<input type="submit" class="abutton"/ value="<&nbsp;&nbsp;Back" onclick="return alertFormReset();" style="margin-top: 14px; margin-left: auto; margin-right: auto;">' );
        elem.style.display = 'block';
        return false;
    }
    else
    {
        elem.innerHTML = '<img src=\'/img/' + sResult + '.png\' style=\'margin-bottom: -4px;\' />&nbsp; ' + sMessage + '<br /><br /><br />' + (result >= 1 ? '<a href=\'#\' onclick="return alertFormReset();" style="margin-left: -15px;">Add another alert</a>' : '<a href=\'#\' onclick="return alertFormReset();" style="margin-left: -15px;">Back</a>' );
        elem.style.display = 'block';
        return false;
    }
}


function addAlertInline(matchup)
{
    res = document.getElementById('p-alerts-add-' + matchup);
    res.innerHTML = '<img src="/img/ajax-loader.gif"> <input type="submit" class="alerts-add-button" value="Add alert" onclick="return addAlertInline(' + matchup + ')" disabled="disabled">';

    $.get("/ajax/ajax.Interface.php?function=addAlert", {
        alertFight: matchup,
        alertFighter: '1',
        alertBookie: document.forms['alert_form'].elements['alert_bookie'].value,
        alertMail: document.forms['alert_form'].elements['alert_mail'].value,
        alertOdds: '-9999',
        alertOddsType: oddsType
    }, function(data) {
        var msg = '';
        switch (data) {
            case '1':
                msg = 'Alert added';
                break;
            case '2':
                msg = 'You already have an alert for this selection';
                break;
            case '-1':
            case '-2':
                msg = 'No fight selected';
                break;
            case '-3':
                msg = 'No fighter selected';
                break;
            case '-4':
                msg = 'The e-mail you entered was in the wrong format';
                break;
            case '-5':
                msg = 'The odds you entered was in a wrong format';
                break;
            case '-6':
                msg = 'Alert limit reached (20)';
                break;
            case '-7':
                msg = 'The odds you entered have already been reached';
                break;
            default:
                msg = 'Unknown error';
        }

        res.innerHTML = '<img src=\'/img/' + (data >= 1 ? 'success' : 'error') + '.png\' /> ' + msg + ' <input type="submit" class="alerts-add-button" value="Add alert" onclick="return addAlertInline(' + matchup + ')" ' + (data >= 1 ? 'disabled="disabled"' : '') + ' >';
    });

    return false;
}