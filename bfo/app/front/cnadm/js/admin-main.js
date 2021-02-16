function maAddEventWithMatchups(ma_id, inputdata)
{
    inputJSON = JSON.parse(inputdata);
    var opts = {
        method: 'POST',      
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            event_name: inputJSON.eventTitle,
            event_date: inputJSON.eventDate,
            event_hidden: false
        })
    };
    fetch('/cnadm/api/events', opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('added event');
            maRecursiveAddMatchup(body.event_id, inputJSON, 0)
            console.log('all done');
            clearManulAction(ma_id);
        }
    });
}

function maRecursiveAddMatchup(indata_eventid, inputJSON, currentindex)
{
    totalindex = inputJSON.matchups.length;
    if (currentindex < totalindex)
    {
        var opts = {
            method: 'POST',      
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify({
                event_id: indata_eventid,
                team1_name: inputJSON.matchups[currentindex][0],
                team2_name: inputJSON.matchups[currentindex][1]
            })
        };
        fetch('/cnadm/api/matchups', opts).then(function (response) {
            return response.json();
        })
        .then(function (body) {
            if (body.error == true) {
                alert(body.msg);
            }
            else {
                console.log('added matchup ' + (currentindex + 1) + ' of ' + totalindex);
            }
        });
        maRecursiveAddMatchup(indata_eventid, inputJSON, currentindex + 1)
    }
    return $.Deferred().resolve();
}

function maRenameEvent(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    var opts = {
        method: 'PUT',      
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            event_id: parseInt(inputJSON.eventID),
            event_name: inputJSON.eventTitle,
        })
    };
    fetch('/cnadm/api/events/' + inputJSON.eventID, opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('renamed event');
            clearManulAction(ma_id);
        }
    });
}

function maAddMatchup(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    var opts = {
        method: 'POST',      
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            event_id: inputJSON.eventID,
            team1_name: inputJSON.matchups[0].team1,
            team2_name: inputJSON.matchups[0].team2
        })
    };
    fetch('/cnadm/api/matchups', opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('added matchup');
            clearManulAction(ma_id);
        }
    });
}


function maDeleteMatchup(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    var opts = {
        method: 'DELETE',
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            matchup_id: inputJSON.matchupID,
        })
    };
    fetch('/cnadm/api/matchups/' + inputJSON.matchupID, opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('deleted matchup');
            clearManulAction(ma_id);
        }
    });
}


function maMoveMatchup(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    var opts = {
        method: 'PUT',      
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            matchup_id: inputJSON.matchupID,
            event_id: inputJSON.eventID,
        })
    };
    fetch('/cnadm/api/matchups/' + inputJSON.matchupID, opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('moved matchup');
            clearManulAction(ma_id);
        }
    });
}

function maDeleteEvent(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    var opts = {
        method: 'DELETE',
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            event_id: inputJSON.eventID,
        })
    };
    fetch('/cnadm/api/events/' + inputJSON.eventID, opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('deleted event');
            clearManulAction(ma_id);
        }
    });
}

function maRedateEvent(ma_id, inputdata)
{

    inputJSON = $.parseJSON(inputdata);
    var opts = {
        method: 'PUT',      
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            event_id: parseInt(inputJSON.eventID),
            event_date: inputJSON.eventDate
        })
    };
    fetch('/cnadm/api/events/' + inputJSON.eventID, opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('redated event');
            clearManulAction(ma_id);
        }
    });
}

function clearManulAction(ma_id)
{
    var opts = {
        method: 'DELETE',
        headers: {
            'Content-type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({
            action_id: ma_id,
        })
    };
    fetch('/cnadm/api/manualactions/' + ma_id, opts).then(function (response) {
        return response.json();
    })
    .then(function (body) {
        if (body.error == true) {
            alert(body.msg);
        }
        else {
            console.log('deleted manual action');
            $("#ma" + ma_id).find('td').css("text-decoration", "line-through");
            $("#ma" + ma_id).find('input[type=submit]').attr("disabled", "disabled");
        }
    });
}

$(document).ready(function(){
  $("#fighter1").autocomplete("../ajax/ajax.Interface.php?function=searchFighter", { delay: 100, minChars: 3 });
  $("#fighter2").autocomplete("../ajax/ajax.Interface.php?function=searchFighter", { delay: 100, minChars: 3 });
});

