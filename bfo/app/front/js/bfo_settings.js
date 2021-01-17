/**
 * Binds a function to all input checkboxes on the settings page. 
 * 
 * If a checkbox is unchecked it will mark that bookie in a cookie bfo_hidebookies as hidden by setting
 * its value to true. A bookie that is visible is not present in the cookie at all
 */
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

function hideBookieColumns() {
    if (Cookies.get('bfo_hidebookies') !== null) {
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


function addCSSRule(sheet, selector, rules, index) {
	if("insertRule" in sheet) {
		sheet.insertRule(selector + "{" + rules + "}", index);
	}
	else if("addRule" in sheet) {
		sheet.addRule(selector, rules, index);
	}
}