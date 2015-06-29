<html>
    <head><title>BFO.com</title></head>
    <body>
        <?php

        require_once('lib/bfocore/general/class.EventHandler.php');
        require_once('lib/bfocore/general/class.BookieHandler.php');
        require_once('lib/bfocore/general/inc.GlobalTypes.php');

        $aBookies = BookieHandler::getAllBookies();
        if (sizeof($aBookies) == 0)
        {
            echo 'No bookies found';
            exit();
        }
        $aEvent = EventHandler::getAllUpcomingEvents();


        $iCellCounter = 0;
        $bAdAdded = false;

        //List all events
        foreach ($aEvent as $oEvent)
        {
            $aFights = EventHandler::getAllFightsForEvent($oEvent->getID(), true);
            if (sizeof($aFights) > 0)
            {
                echo '<b>' . $oEvent->getName() . '</b><br /><br />';
                //List all bookies, save a reference list for later use in table
                $aBookieRefList = array();
                foreach ($aBookies as $oBookie)
                {
                    $aBookieRefList[] = $oBookie->getID();
                }

                foreach ($aFights as $oFight)
                {
                    //List all odds for the fight
                    $aFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID());
                    $aOldFightOdds = EventHandler::getAllLatestOddsForFight($oFight->getID(), 1);
                    $oBestOdds = EventHandler::getBestOddsForFight($oFight->getID());

                    $iProcessed = 0;
                    $iCurrentOperatorColumn = 0;
                    for ($iX = 1; $iX <= 2; $iX++)
                    {
                        echo $oFight->getFighterAsString($iX) . ' ';

                        $iProcessed = 0;
                        $bEverFoundOldOdds = false;
                        $bFound = false;
                        foreach ($aFightOdds as $oFightOdds)
                        {
                            $iCurrentOperatorColumn = $iProcessed;
                            while (isset($aBookieRefList[$iCurrentOperatorColumn]) && $aBookieRefList[$iCurrentOperatorColumn] != $oFightOdds->getBookieID())
                            {
                                $iCurrentOperatorColumn++;
                                $iProcessed++;
                            }

                            $sClassName = 'normalbet';
                            if ($oFightOdds->getFighterOdds($iX) == $oBestOdds->getFighterOdds($iX) && $bFound == false)
                            {
                                $sClassName = 'bestbet';


                                //Loop through the previous odds and check if odds is higher or lower or non-existant (kinda ugly, needs a fix)
                                $iCurrentOperatorID = $oFightOdds->getBookieID();
                                $bFoundOldOdds = false;

                                foreach ($aOldFightOdds as $oOldFightOdds)
                                {
                                    if ($oOldFightOdds->getBookieID() == $iCurrentOperatorID)
                                    {
                                        $bFound = true;
                                        echo $oFightOdds->getFighterOddsAsString($iX) . "<br />";
                                        $bFoundOldOdds = true;
                                        $bEverFoundOldOdds = true;
                                    }
                                }

                                if (!$bFoundOldOdds)
                                {
                                    $bFound = true;
                                    echo $oFightOdds->getFighterOddsAsString($iX) . '<br />';
                                }
                            }
                            $iProcessed++;
                        }
                    }
                    echo '<br />';
                }
                echo '<hr />';
            }
        }
        ?>
    </body>
</html>