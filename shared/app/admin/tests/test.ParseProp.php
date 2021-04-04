<html>
    <head>
    </head>
    <body style="font-size: 12px; font-family: Verdana">
        <form method="get" action ="/cnadm/tests/test.ParseProp.php">
            Prop text to match: <input type="text" name="prop" value="<?php echo (isset($_GET['prop']) ? $_GET['prop'] : ''); ?>" style="width: 400px;"/>
            <input type="submit" value="Execute" />
        </form>
        <br />

        <div style="font-size: 10px; font-family: Verdana">
            <?php
            require_once('lib/bfocore/parser/general/class.PropParser.php');
            require_once('lib/bfocore/general/class.BookieHandler.php');
            require_once('lib/bfocore/parser/general/class.ParsedProp.php');
            require_once('lib/bfocore/parser/utils/class.ParseTools.php');


            if (isset($_GET['prop']))
            {



                //Fetch all bookies
                $aBookies = BookieHandler::getAllBookies();
                $oPropParser = new PropParser();

                //For each bookie, check if it can match the prop
                foreach ($aBookies as $oBookie)
                {
                    echo 'Checking ' . $oBookie->getName() . '<br />';

                    $oParsedProp = new ParsedProp($_GET['prop'], '', '-115', '-115');
                    $oTemplate = $oPropParser->matchParsedPropToTemplate($oBookie->getID(), $oParsedProp);

                    if ($oTemplate == null)
                    {
                        echo '&nbsp;&nbsp;&nbsp;Found no template' . '<br />';
                    }
                    else
                    {


                        echo '&nbsp;&nbsp;&nbsp;Found template: ' . $oTemplate->toString() . '<br />';
                        $aMatchup = $oPropParser->matchParsedPropToMatchup($oParsedProp, $oTemplate);


                        if ($aMatchup['matchup'] == null)
                        {

                            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Found no matchup' . '<br />';
                        }
                        else
                        {
                            $oMatchup = EventHandler::getFightByID($aMatchup['matchup']);


                            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Found matchup: ' . $oMatchup->getFighterAsString(1) . ' vs ' . $oMatchup->getFighterAsString(2) . '<br />';
                        }
                    }
                }
            }
            ?>

        </div>


    </body>
</html>
