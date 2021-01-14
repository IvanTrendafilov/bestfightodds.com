function setHiddenBookieColumn(index, hidden) {


    if (Cookies.get('bfo_hidebookies') !== null) {
        //No cookie set, create one
        var arr = [];
        Cookies.set('bfo_hidebookies', JSON.stringify(arr), {
            'expires': 999
        });
    }

    return;
}

function hideBookieColumns() {
    hbcookie = Cookies.get('bfo_hidebookies');
    if (hiddenBookies !== null) {
        hiddenBookies = JSON.parse(hbcookie);
        hiddenBookies.forEach(function(value) {
            //value[0] = bookie_id, value[1] = hidden_state (0 = visible, 1 = hidden)
            value[0] 


            console.log(self);
        });

    }
}