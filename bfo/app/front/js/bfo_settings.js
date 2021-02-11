document.getElementById('bookieHideSelector').addEventListener("click", function(e) {
    showSettingsWindow();
}, false);

function updateBookieColumns() {
    var current_settings = localStorage.getItem("bfo_bookie_order");
    if (current_settings) 
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
                    //Check if this bookie should be disabled as well, if so loop through al 
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

function showSettingsWindow() {
    //Gather all bookie IDs and positions
    var bookies = [];
    var area = document.getElementById('bookie-order-items');

    var current_settings = localStorage.getItem("bfo_bookie_order");
    if (current_settings) 
    {
        current_settings = JSON.parse(current_settings)
    }

    current_settings = JSON.parse(localStorage.getItem("bfo_bookie_order"));

    document.querySelectorAll('.table-scroller')[0].querySelectorAll('th[data-b]').forEach(function (item) {
        bookies[item.cellIndex] = item.dataset.b;
        area.innerHTML = area.innerHTML + '<li data-b="' + item.dataset.b + '">' + item.textContent + ' <input type="checkbox" '+ (current_settings[item.dataset.b].enabled ? 'checked' : '') + '></li>';
    });

    
    document.querySelectorAll('input.bsetting').forEach(function (item) {
        item.addEventListener("click", function () {
            var settings = [];
            document.querySelectorAll('input.bsetting').forEach(function (item) {
                if (item.checked == false) {
                    settings[item.dataset.bookie] = true;
                }
            });
            Cookies.set('bfo_hidebookies', JSON.stringify(settings), {
                'expires': 999
            });
        })
    });

    //Restructure according to previously saved bookie order
    localStorage.getItem("bfo_bookie_order");

    var el = document.getElementById('bookie-order-items');
    var sortable = Sortable.create(el, {
        onUpdate: function (evt) {
                //When an element is dropped we update local storage to store this
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


