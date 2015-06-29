<?php

/**
 * Logging class used for generic purposes
 *
 * Used like this:
 * 1. Instantiate
 * 2. Call the start() method to start logging
 * 3. Log something with the log(message, severity) method
 * 4. Call the end(filename) method to finish the logging and save log to filename.
 * 
 */
class Logger
{

    private $sFilename;
    private $rFileHandle;

    /**
     * Constructor
     */
    private function __construct($a_sFilename)
    {
        $this->sFilename = $a_sFilename;
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
    public function log($a_sMessage)
    {
        fwrite($this->rFileHandle, $a_sMessage);
    }

    /**
     * Starts logging
     */
    public function start($a_sFilename)
    {
        $this->rFileHandle = fopen($a_sFilename, 'a');
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