<?php

require_once('lib/bfocore/general/class.OddsHandler.php');
require_once('lib/bfocore/general/class.BookieHandler.php');

echo 'Add new prop template (use &#60;T&#62; to specify team names and &#60;*&#62; for wildcards (must exist) or  &#60;?&#62; for optional wildcards (optional). Use the latter two with caution!)<br /><br />';

echo '<form method="post" action="logic/logic.php?action=addPropTemplate"  name="addPropTemplateForm">';

echo 'Bookie: <select name="bookieID">';
echo '<option value="0" selected>- pick one -</option>';
$aBookies = BookieHandler::getAllBookies();
foreach ($aBookies as $oBookie)
{
    if (isset($_GET['inBookieID']) && $_GET['inBookieID'] == $oBookie->getID())
    {
        echo '<option value="' . $oBookie->getID() . '" selected>' . $oBookie->getName() . '</option>';
    } else
    {
        echo '<option value="' . $oBookie->getID() . '">' . $oBookie->getName() . '</option>';
    }
}
echo '</select><br /><br />';

echo 'Prop Type: <select name="propTypeID">';
echo '<option value="0" selected>- pick one -</option>';
$aPropTypes = OddsHandler::getAllPropTypes();
foreach ($aPropTypes as $oPropType)
{
    echo '<option value="' . $oPropType->getID() . '">' . $oPropType->getPropDesc() . '</option>';
}
echo '</select><br /><br />';

echo 'Fields Type: <select name="fieldsTypeID">';
echo '<option value="0" selected>- pick one -</option>';
echo '<option value="1">lastname vs lastname (koscheck vs miller)</option>';
echo '<option value="2">fullname vs fullname (e.g josh koscheck vs dan miller)</option>';
echo '<option value="3">single lastname (koscheck)</option>';
echo '<option value="4">full name (josh koscheck)</option>';
echo '<option value="5">first letter.lastname (e.g. j.koscheck)</option>';
echo '<option value="6">first letter.lastname vs first letter.lastname (e.g. j.koscheck vs d.miller)</option>';
echo '<option value="7">first letter lastname vs first letter lastname (e.g. j koscheck vs d miller)</option>';
echo '<option value="8">first letter lastname (e.g. j koscheck)</option>';
echo '</select><br /><br />

Template:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type="text" id="templateField" name="template" size="70" value="' . (isset($_GET['inTemplate']) ? $_GET['inTemplate'] : '') . '" /> <a href="#" onclick="switchFields(\'templateField\',\'templateNegField\')">Switch</a><br />
Neg Template: <input type="text" id="templateNegField" name="negTemplate"  size="70" value="' . (isset($_GET['inNegTemplate']) ? $_GET['inNegTemplate'] : '') . '"/><br /><br />';

echo '<input type="submit" value="Add template" onclick="javascript:return confirm(\'Are you sure?\')"/>';
echo '</form><br />';

if (isset($_GET['message']))
{
    echo $_GET['message'];
}
?>
