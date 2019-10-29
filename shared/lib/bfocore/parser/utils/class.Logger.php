<?php

require_once('config/inc.parseConfig.php');

/**
 * Singleton logging class used when parsing data.
 *
 * Used like this:
 * 1. Get the singleton instance with the getInstance method
 * 2. Call the start() method to start logging
 * 3. Log something with the log(message, severity) method
 * 4. Seperate log entries by calling the seperate() method (not required but makes it look nice)
 * 5. Call the end(filename) method to finish the logging and save log to filename.
 * 
 */
class Logger
{

    private static $instance;
    private $rFileHandle;

    /**
     * Constructor
     */
    private function __construct()
    {

    }

    /**
     * Returns the singleton instance
     * 
     * @return Logger instance
     */
    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    /**
     * Logs an entry
     *
     * Severity is defined like this: 0 is neutral, a postive number is good and the higher the better
     * a negative number is bad and the lower the worse. 2 is perfect, -2 is as worse as it gets
     *
     * @param string $a_sMessage The message to log
     * @param int $a_iSeverity Severity of the entry
     */
    public function log($a_sMessage, $a_iSeverity = 0)
    {
        if ($a_iSeverity <= PARSE_LOG_LEVEL)
        {
            $sClass = '';
            switch ($a_iSeverity)
            {
                case 1: $sClass = 'sev1';
                    break;
                case 2: $sClass = 'sev2';
                    break;
                case -1: $sClass = 'sev-1';
                    break;
                case -2: $sClass = 'sev-2';
                    break;
                default:
            }

            fwrite($this->rFileHandle, "<tr><td class=\"" . $sClass . "\">[" . date("H:i:s") . "] " . $a_sMessage . "</td></tr>");
        }
    }

    /**
     * Seperates two log entries with nice formatting
     */
    public function seperate()
    {
        fwrite($this->rFileHandle, "<tr><td>&nbsp;</td></tr>");
    }

    /**
     * Starts logging
     */
    public function start($a_sFilename)
    {
        $this->rFileHandle = fopen($a_sFilename, 'a');
        fwrite($this->rFileHandle, "<table class=\"logTable\">");
    }

    /**
     * Ends logging and saves the log to the specified filename
     *
     * @param string $a_sFileName Filename for the log file to be saved
     */
    public function end($a_sFileName)
    {
        fclose($this->rFileHandle);
    }

}

?>