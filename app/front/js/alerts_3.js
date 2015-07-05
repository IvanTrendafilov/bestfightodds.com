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