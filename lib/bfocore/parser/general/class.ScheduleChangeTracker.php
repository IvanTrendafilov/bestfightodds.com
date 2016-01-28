<?php

require_once('lib/bfocore/general/class.EventHandler.php');
require_once('config/inc.parseConfig.php');

class ScheduleChangeTracker
{
    private static $instance;
    private $aMatchups;

    public function addMatchup($a_aMatchup)
    {
        if (isset($a_aMatchup['bookie_id']) && 
            isset($a_aMatchup['matchup_id']) &&
            isset($a_aMatchup['date']))
        {
            $this->aMatchups[] = $a_aMatchup;
            return true;
        }
        return false;
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
        foreach ($aUpcomingMatchups as $oUpMatch)
        {
            $sFoundNewDate = '';
            $sFoundOwner = '';
            $oEvent = EventHandler::getEvent($oUpMatch->getEventID());
            $oCurDate = new DateTime($oEvent->getDate());
            foreach ($this->aMatchups as $aMatchup)
            {
                if ($aMatchup['matchup_id'] == $oUpMatch->getID())
                {
                    $oNewDate = new DateTime();
                    $oNewDate->setTimestamp($aMatchup['date']);
                    //Subtract 6 hours to adjust for timezones (but not for future events)
                    if ($oNewDate->format('Y-m-d') != $sFutureEventDate)
                    {
                        $oNewDate->sub(new DateInterval('PT6H'));
                    }
                    if ($oNewDate->format('Y-m-d') != $oCurDate->format('Y-m-d'))
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
    }
}

?>