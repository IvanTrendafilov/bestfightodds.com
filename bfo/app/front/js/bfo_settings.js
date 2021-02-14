
$(document).ready(function () {
document.getElementById('bookieHideSelector').addEventListener("click", function(e) {
    showSettingsWindow();
}, false);

document.getElementById('bookieResetDefault').addEventListener("click", function(e) {
    localStorage.removeItem("bfo_bookie_order");
    location.reload();
    //Reset to defaults
}, false);
});
function updateBookieColumns() {
    var current_settings = localStorage.getItem("bfo_bookie_order");
    if (current_settings !== null) 
    {
        current_settings = JSON.parse(current_settings)
        //Re-order columns        
        remapped = [];
        Object.entries(current_settings).forEach(function (item) {
            remapped[item[1].order] = item[0];
        });
        remapped.reverse();
        remapped.forEach(function (item) {
            var cur_index = document.querySelectorAll('.table-scroller')[0].querySelector('th[data-b="' + item + '"]').cellIndex;
            
            document.querySelectorAll('.table-scroller table').forEach(function (table) {
                for (var i = 0, row; row = table.rows[i]; i++) {
                    row.cells[0].insertAdjacentElement('afterend', row.cells[cur_index]);
                    //Check if this bookie should be disabled as well
                    if (current_settings[item].enabled == false) {
                        row.cells[1].style.display = "none";
                    }
                    else {
                        row.cells[1].setAttribute("style", "");
                    }
                }
            });
        });
    }
}

function saveBookieSettings(el) {
    var bookie_orders = {};
    var i = 0;
    el.childNodes.forEach(function (item) {
        bookie_orders[item.dataset.b] = {};
        bookie_orders[item.dataset.b].enabled = item.querySelector('input').checked;
        bookie_orders[item.dataset.b].order = i;
        i++;
    });
    localStorage.setItem("bfo_bookie_order", JSON.stringify(bookie_orders));
    updateBookieColumns();
}

function showSettingsWindow() {

    //Gather all bookie IDs and positions
    var bookies = [];
    var el = document.getElementById('bookie-order-items');
    el.innerHTML = '';

    var current_settings = localStorage.getItem("bfo_bookie_order");
    if (current_settings) 
    {
        current_settings = JSON.parse(current_settings)
    }
    
    document.querySelectorAll('.table-scroller')[0].querySelectorAll('th[data-b]').forEach(function (item) {
        bookies[item.cellIndex] = item.dataset.b;
        var checked = 'checked';
        if (current_settings !== null && item.dataset.b in current_settings && current_settings[item.dataset.b].enabled == false) {
            checked = '';
        }
        el.innerHTML = el.innerHTML + '<li data-b="' + item.dataset.b + '">' + item.textContent + '<input class="inp-checkbox" type="checkbox" ' + checked + '></li>';
    });

    el.querySelectorAll('.inp-checkbox').forEach(function (item) {
        item.addEventListener("change", function(e) {
            saveBookieSettings(document.querySelector('#bookie-order-items'));
        }, false);
    });


    //Restructure according to previously saved bookie order
    var sortable = new Sortable(el, {
        onUpdate: function(item) {saveBookieSettings(el)},
        filter: ".inp-checkbox"
        }
        
    );

    var settingswindow = document.getElementById('bookie-settings-window');

    settingswindow.classList.remove('is-visible');
    settingswindow.classList.add('no-transition');

    //Flush CSS cache. Not sure if this actually has any effect..*/
    getComputedStyle(settingswindow).display;
    settingswindow.offsetHeight;

    settingswindow.classList.remove('no-transition');
    settingswindow.classList.add('is-visible');

}


