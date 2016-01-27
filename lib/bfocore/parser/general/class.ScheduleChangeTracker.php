<?php

require_once('lib/bfocore/general/class.EventHandler.php');

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
        $aUpcomingMatchups = EventHandler::getAllUpcomingMatchups(true);
        foreach ($aUpcomingMatchups as $oUpMatch)
        {
            $oEvent = EventHandler::getEvent($oUpMatch->getEventID());
            foreach ($this->aMatchups as $aMatchup)
            {
                if ($aMatchup['matchup_id'] == $oUpMatch->getID())
                {
                    $firstDateTimeObj = new DateTime();
                    $firstDateTimeObj->setTimestamp($aMatchup['date']);
                    //Subtract 6 hours to adjust for timezones (but not for future events)
                    $firstDateTimeObj->sub(new DateInterval('PT6H'));

                    $secondDateTimeObj = new DateTime($oEvent->getDate());
                    $firstDate = $firstDateTimeObj->format('Y-m-d');
                    $secondDate = $secondDateTimeObj->format('Y-m-d');//Check if date matches


                    echo $aMatchup['matchup_id'] . " " . $firstDate .  " " . $secondDate . " 
                    ";
                    if ($firstDate != $secondDate)
                    {
                        echo 'yes ';
                    }
                    else
                    {
                        echo 'no';
                    }

                }
            }

        }


    }
}

?>