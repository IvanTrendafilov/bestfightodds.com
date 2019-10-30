function maAddEventWithMatchups(ma_id, inputdata)
{
	inputJSON = $.parseJSON(inputdata);
	$.post("logic/api.php", {
        apiFunction: 'addEvent',
        eventName: inputJSON.eventTitle,
        eventDate: inputJSON.eventDate
    }, function(data) {
    	console.log('added event');
        result = $.parseJSON(data);

        $.when(maRecursiveAddMatchup(result, inputJSON, 0)          
        ).done(function() {
        	console.log('all done');
            clearManulAction(ma_id);
        })
    });
    //TODO: Add check that things actually went good
}

function maRecursiveAddMatchup(indata, inputJSON, currentindex)
{
    totalindex = inputJSON.matchups.length;
    if (currentindex < totalindex)
    {
        $.post("logic/api.php", {
                apiFunction: 'addMatchup',
                eventID: indata.result.meta_data.eventID,
                team1Name: inputJSON.matchups[currentindex][0],
                team2Name: inputJSON.matchups[currentindex][1]
            }, function(data) {
                    console.log('added matchup ' + (currentindex + 1) + ' of ' + totalindex);
                    maRecursiveAddMatchup(indata, inputJSON, currentindex + 1)
        });
    }
    return $.Deferred().resolve();
}

function maRenameEvent(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    $.post("logic/api.php", {
        apiFunction: 'renameEvent',
        eventID: inputJSON.eventID,
        eventTitle: inputJSON.eventTitle
    }, function(data) {
        console.log('renamed event');
        result = $.parseJSON(data);
        clearManulAction(ma_id);
    });
    //TODO: Add check that things actually went good
}

function maAddMatchup(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    $.post("logic/api.php", {
        apiFunction: 'addMatchup',
        eventID: inputJSON.eventID,
        team1Name: inputJSON.matchups[0].team1,
        team2Name: inputJSON.matchups[0].team2
    }, function(data) {
        console.log('added matchup');
        result = $.parseJSON(data);
        clearManulAction(ma_id);
    });
    //TODO: Add check that things actually went good
}


function maDeleteMatchup(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    $.post("logic/api.php", {
        apiFunction: 'deleteMatchup',
        matchupID: inputJSON.matchupID
    }, function(data) {
        console.log('deleted matchup');
        result = $.parseJSON(data);
        clearManulAction(ma_id);
    });
    //TODO: Add check that things actually went good
}


function maMoveMatchup(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    $.post("logic/api.php", {
        apiFunction: 'moveMatchup',
        matchupID: inputJSON.matchupID,
        eventID: inputJSON.eventID
    }, function(data) {
        console.log('moved matchup');
        result = $.parseJSON(data);
        clearManulAction(ma_id);
    });
    //TODO: Add check that things actually went good
}

function maDeleteEvent(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    $.post("logic/api.php", {
        apiFunction: 'deleteEvent',
        eventID: inputJSON.eventID
    }, function(data) {
        console.log('deleted event');
        result = $.parseJSON(data);
        clearManulAction(ma_id);
    });
    //TODO: Add check that things actually went good
}

function maRedateEvent(ma_id, inputdata)
{
    inputJSON = $.parseJSON(inputdata);
    $.post("logic/api.php", {
        apiFunction: 'redateEvent',
        eventID: inputJSON.eventID,
        eventDate: inputJSON.eventDate
    }, function(data) {
        console.log('redated event');
        result = $.parseJSON(data);
        clearManulAction(ma_id);
    });
    //TODO: Add check that things actually went good
}

function clearManulAction(ma_id)
{
    $.post("logic/api.php", {
        apiFunction: 'clearManualAction',
        actionID: ma_id
    }, function(data) {
        result = $.parseJSON(data);
        console.log('cleared manual action');
        $("#ma" + ma_id).find('td').css("text-decoration", "line-through");
        $("#ma" + ma_id).find('input[type=submit]').attr("disabled", "disabled");
    });
    //TODO: Add check that things actually went good
}

function removeOddsForMatchupAndBookie(matchup_id, bookie_id)
{
    $.post("logic/api.php", {
        apiFunction: 'removeOddsForMatchupAndBookie',
        matchupID: matchup_id,
        bookieID: bookie_id
    }, function(data) {
        result = $.parseJSON(data);
        console.log('removed odds');
    });
    //TODO: Add check that things actually went good
}

function switchFields(field1, field2)
{
    storeVal = document.getElementById(field1).value;
    document.getElementById(field1).value = document.getElementById(field2).value;
    document.getElementById(field2).value = storeVal;
}

$(document).ready(function(){
  $("#fighter1").autocomplete("../ajax/ajax.Interface.php?function=searchFighter", { delay: 100, minChars: 3 });
  $("#fighter2").autocomplete("../ajax/ajax.Interface.php?function=searchFighter", { delay: 100, minChars: 3 });
});

