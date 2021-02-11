

document.getElementById('bookieHideSelector').addEventListener("click", function(e) {
    showSettingsWindow();
}, false);

function hideBookieColumns() {
    if (typeof Cookies.get('bfo_hidebookies') !== 'undefined' && Cookies.get('bfo_hidebookies') !== null) {
        hbcookie = Cookies.get('bfo_hidebookies');
        if (hbcookie !== null) {
            hiddenBookies = JSON.parse(hbcookie);
            for (const [key, value] of Object.entries(hiddenBookies)) {
                if (value == true)
                {
                    //Hide bookie column
                    var bookiecolumn = document.querySelector('th[data-b="' + key + '"]');
                    if (bookiecolumn !== null)
                    {
                        console.log(bookiecolumn.cellIndex);
                        addCSSRule(document.styleSheets[1], "table.odds-table th:nth-child(" + (bookiecolumn.cellIndex + 1) + "), td:nth-child(" + (bookiecolumn.cellIndex + 1) + ")", "display: none");
                    }
                }
            }
        }
    }
}

function appendColumnCopy(source) {
    $("tr > :last-child").each(function () {
        var $this = $(source);
        $this.clone().appendTo($this.parent());
      });
}

function updateBookieColumns() {
    var current_settings = localStorage.getItem("bfo_bookie_order");
    if (current_settings) 
    {
        current_settings = JSON.parse(current_settings)
        //Get the current index for each bookie
        var bookies = [];
        document.querySelectorAll('.table-scroller')[0].querySelectorAll('th[data-b]').forEach(function (item) {
            bookies[item.cellIndex] = item.dataset.b;
            //item.cellIndex = current index
            //current_settings[item.dataset.b] == index from settings
            if (item.cellIndex != current_settings[item.dataset.b].order)
            {

            }


        });


        document.querySelectorAll('.table-scroller').querySelectorAll('th[data-b]').forEach(function (item) {
            if (current_settings[item.dataset.b] && current_settings[item.dataset.b].enabled == false)
            {
                //Bookie disabled
                addCSSRule(document.styleSheets[1], "table.odds-table th:nth-child(" + (item.cellIndex + 1) + "), td:nth-child(" + (item.cellIndex + 1) + ")", "display: none");
            }
            else
            {
                //Bookie enabled
                addCSSRule(document.styleSheets[1], "table.odds-table th:nth-child(" + (item.cellIndex + 1) + "), td:nth-child(" + (item.cellIndex + 1) + ")", "display: table-cell");
            }
    
            
    
        });
        
    }

    //Hide columns if disabled
    /*document.querySelectorAll('.table-scroller')[0].querySelectorAll('th[data-b]').forEach(function (item) {
        if (current_settings[item.dataset.b] && current_settings[item.dataset.b].enabled == false)
        {
            //Bookie disabled
            addCSSRule(document.styleSheets[1], "table.odds-table th:nth-child(" + (item.cellIndex + 1) + "), td:nth-child(" + (item.cellIndex + 1) + ")", "display: none");
        }
        else
        {
            //Bookie enabled
            addCSSRule(document.styleSheets[1], "table.odds-table th:nth-child(" + (item.cellIndex + 1) + "), td:nth-child(" + (item.cellIndex + 1) + ")", "display: table-cell");
        }

        

    });
    */
}


function addCSSRule(sheet, selector, rules, index) {
	if("insertRule" in sheet) {
		sheet.insertRule(selector + "{" + rules + "}", index);
	}
	else if("addRule" in sheet) {
		sheet.addRule(selector, rules, index);
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

    //Bind the enable/disable button for each bookie - TODO
    /*document.querySelectorAll('input.bsetting').forEach(function (item) {
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
*/
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


