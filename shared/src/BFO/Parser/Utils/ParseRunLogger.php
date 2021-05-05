<?php

namespace BFO\Parser\Utils;

/*
 * The purpose of this class is to provide logging of any parsing runs that have been made. Logged runs are simply stored in the database for later analysis and monitoring
 */

use BFO\Utils\DB\PDOTools;

class ParseRunLogger
{
    /*
     * Status codes to code as part of metadata:
         -1 Failed to open URL
         -2 URL content empty
         1 OK
     */
    public function logRun($parser_id, $metadata)
    {
        $query = "INSERT INTO logs_parseruns(parser_id, bookie_id, parsed_matchups, parsed_props, matched_matchups, matched_props, status, url, authoritative_run, mockfeed_used, mockfeed_file) VALUES (?,?,?,?,?,?,?,?,?,?,?)";

        $params = [
            $parser_id,
            isset($metadata['bookie_id']) ? $metadata['bookie_id'] : '',
            isset($metadata['parsed_matchups']) ? $metadata['parsed_matchups'] : -1,
            isset($metadata['parsed_props']) ? $metadata['parsed_props'] : -1,
            isset($metadata['matched_matchups']) ? $metadata['matched_matchups'] : -1,
            isset($metadata['matched_props']) ? $metadata['matched_props'] : -1,
            isset($metadata['status']) ? $metadata['status'] : '',
            isset($metadata['url']) ? $metadata['url'] : '',
            isset($metadata['authoritative_run']) ? $metadata['authoritative_run'] : '',
            isset($metadata['mockfeed_used']) ? $metadata['mockfeed_used'] : '',
            isset($metadata['mockfeed_file']) ? $metadata['mockfeed_file'] : ''
        ];

        $id = null;
        try {
            $id = PDOTools::insert($query, $params);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new \Exception("Duplicate entry", 10);
            }
        }
        return $id;
    }

    //Clears old runs from the database, defaults to 14 days
    public function clearOldRuns($age = 14)
    {
        $query = 'DELETE FROM logs_parseruns WHERE date < NOW() - INTERVAL ? DAY';
        $rows = 0;
        try {
            $rows = PDOTools::delete($query, [$age]);
        } catch (\PDOException $e) {
            throw new \Exception("Unable to delete old entries", 10);
        }
        return $rows;
    }
}
