<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('config/inc.parseConfig.php');

class ScheduleChangeTracker
{
    private static $instance;
    private $aMatchups;
    private $aAuthoritiveRunBookies;

    public function addMatchup($a_aMatchup)
    {
        if (isset($a_aMatchup['bookie_id']) && 
            isset($a_aMatchup['matchup_id']))
        {
            $this->aMatchups[] = $a_aMatchup;
            return true;
        }
        return false;
    }

    //This function is called to declare that the bookie had an Authoritive run. This means that it was able to report all odds exactly as they are right now with any removals included. Use with care
    public function reportAuthoritiveRun($a_iBookieID)
    {
        $this->aAuthoritiveRunBookies[$a_iBookieID] = true;
    }

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        $this->aMatchups = [];
        $this->aAuthoritiveRunBookies = [];
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    public function dumpResult()
    {
        var_dump($this->aMatchupDates);
    }

    public function checkForChanges()
    {
        //Fetch future event date, we need to use it later to exclude it   
        $sFutureEventDate = (new DateTime(EventHandler::getEvent(PARSE_FUTURESEVENT_ID)->getDate()))->format('Y-m-d');

        $aUpcomingMatchups = EventHandler::getAllUpcomingMatchups(true);
        $aProcessedBookieMatchups = [];

        //Routine to move matchup if parser has suggested it
        foreach ($aUpcomingMatchups as $oUpMatch)
        {
            $sFoundNewDate = '';
            $sFoundOwner = '';
            $oEvent = EventHandler::getEvent($oUpMatch->getEventID());
            $oCurDate = new DateTime($oEvent->getDate());
            foreach ($this->aMatchups as $aMatchup)
            {
                if ($aMatchup['matchup_id'] == $oUpMatch->getID() && isset($aMatchup['date']) && $aMatchup['date'] != '')
                {
                    $aProcessedBookieMatchups[$aMatchup['bookie_id']][$aMatchup['matchup_id']] = true;
                    $oNewDate = new DateTime();
                    $oNewDate->setTimestamp($aMatchup['date']);
                    //Subtract 6 hours to adjust for timezones (but not for future events)
                    if ($oNewDate->format('Y-m-d') != $sFutureEventDate)
                    {
                        $oNewDate->sub(new DateInterval('PT6H'));
                    }
                    //Check that new date is other than current and also that it is not in the past 
                    if ($oNewDate->format('Y-m-d') != $oCurDate->format('Y-m-d') &&
                        new DateTime() < $oNewDate)
                    {
                        //We'll favour the earliest date since it is most likely to not be a preliminary date
                        if ($sFoundNewDate == '' || $oNewDate->format('Y-m-d') < $sFoundNewDate)
                        {
                            $sFoundNewDate = $oNewDate->format('Y-m-d');
                            $sFoundOwner = $aMatchup['bookie_id'];
                        }
                    }
                }
            }

            if ($sFoundNewDate != '')
            {
                $iEventID = EventHandler::getGenericEventForDate($sFoundNewDate)->getID();
                if (EventHandler::changeFight($oUpMatch->getID(), $iEventID))
                {
                    Logger::getInstance()->log('Moved matchup ' . $oUpMatch->getID() . ' to ' . $sFoundNewDate . ' as suggested by ' . $sFoundOwner, 0);
                }
                else
                {
                    Logger::getInstance()->log('Tried to move matchup ' . $oUpMatch->getID() . ' to ' . $sFoundNewDate . ' as suggested by ' . $sFoundOwner . ' but failed', -2);
                }
            }
        }

        //Check if bookie has odds for the matchup, ran a Authoritive run but did not report it back to the change tracker. If so, remove any odds associated with it
        foreach ($this->aAuthoritiveRunBookies as $sKey => $sVal)
        {
            Logger::getInstance()->log('Bookie ' . $sKey  . ' reported an authoritive run. Performing cleanups', 0);
            foreach ($aUpcomingMatchups as $oUpMatch)
            {
                if (!array_key_exists($oUpMatch->getID(), $aProcessedBookieMatchups[$sKey]) && EventHandler::getLatestOddsForFightAndBookie($oUpMatch->getID(), $sKey) != null)
                {
                    //Only remove matchups that are > 24 hours away to avoid removing one the day matchups by accident
                    $datetime = new DateTime($oEvent->getDate());
                    $nowdatetime = new Datetime();
                    $nowdatetime->modify('+1 day');
                    if ($datetime > $nowdatetime) 
                    {
                        Logger::getInstance()->log('-Matchup: ' . $oUpMatch->getID() . ' was not found in feed and will be removed <a href="#/" onclick="removeOddsForMatchupAndBookie(\'' . $oUpMatch->getID() . '\',\'' . $sKey . '\')">remove</a>', 0);
                        //TODO: Perform actual removal
                    }   
                    else
                    {
                        Logger::getInstance()->log('-Matchup: ' . $oUpMatch->getID() . ' was not found in feed but is too close in time to remove. Maybe manually remove? <a href="#/" onclick="removeOddsForMatchupAndBookie(\'' . $oUpMatch->getID() . '\',\'' . $sKey . '\')">remove</a>', 0);
                    }             
                }
            }
        }
    }
}

?>