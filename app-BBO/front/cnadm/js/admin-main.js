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
