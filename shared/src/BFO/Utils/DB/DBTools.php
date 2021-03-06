<?php

namespace BFO\Utils\DB;

/**
 * Legacy DB connection class. Use PDOTools instead
 */
class DBTools
{
    public static $aCachedResults = array();
    public static $rDBConnection;

    /**
     * Gets the current database connection or creates a new one if no one exists
     *
     * @return \mysqli Database connection
     */
    private static function getConnection()
    {
        if (!isset(self::$rDBConnection)) {
            self::$rDBConnection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_SCHEME);
        }

        return self::$rDBConnection;
    }

    /**
     * Executes a query without any parameters
     *
     * @param string $a_sQuery Query to execute
     * @return \mysqli_result Query results
     */
    public static function doQuery($a_sQuery)
    {
        $rResult = mysqli_query(self::getConnection(), $a_sQuery) or die('MySQL error: ' . mysqli_error(self::getConnection()));
        return $rResult;
    }

    /**
     * Executes a query with parameters
     *
     * In the query string, parameters are indicated with a ?. The parameters to be included
     * in these places are passed as an array.
     *
     * @param string $a_sQuery Query to execute
     * @param array $aParams Parameters to include
     * @return \mysqli_result Query results
     */
    public static function doParamQuery($a_sQuery, $a_aParams)
    {
        $iParamCount = 0;
        while ($iPos = strpos($a_sQuery, '?')) {
            if (sizeof($a_aParams) < ($iParamCount + 1)) {
                return false;
            }
            $a_sQuery = substr_replace($a_sQuery, "'" . DBTools::makeParamSafe($a_aParams[$iParamCount]) . "'", $iPos, 1);
            $iParamCount++;
        }

        if (($iParamCount + 1) < sizeof($a_aParams)) {
            return false;
        }

        return DBTools::doQuery($a_sQuery);
    }

    /**
     * Makes a parameter safe from SQL injections
     *
     * Wrapper for mysqli_real_escape_string
     *
     * @param string $a_sParam Parameter to make safe
     * @return string Safe parameter
     */
    public static function makeParamSafe($a_sParam)
    {
        return mysqli_real_escape_string(self::getConnection(), $a_sParam);
    }

    /**
     * Gets the number of affected rows in the previously executed query
     *
     * Wrapper for mysqli_affected_rows
     *
     * @return int The number of affected rows
     */
    public static function getAffectedRows()
    {
        return mysqli_affected_rows(self::getConnection());
    }

    /**
     * Caches query results for later retrieval using getCachedQuery
     *
     * @param string $a_sQuery Query to cache
     * @param Resource $a_rResults Results of the query to cache
     * @return boolean True if result was cached and false if it was not
     *
     */
    public static function cacheQueryResults($a_sQuery, $a_rResults)
    {
        if ($a_sQuery != '' && $a_rResults != null) {
            DBTools::$aCachedResults[$a_sQuery] = $a_rResults;
            return true;
        }
        return false;
    }

    /**
     * Retrieves a previously cached query
     *
     * @param string $a_sQuery Query to find cached
     * @return \mysqli_result The cached query or false if it could not be found
     */
    public static function getCachedQuery($a_sQuery)
    {
        if (array_key_exists($a_sQuery, DBTools::$aCachedResults)) {
            if (mysqli_num_rows(DBTools::$aCachedResults[$a_sQuery]) > 0) {
                mysqli_data_seek(DBTools::$aCachedResults[$a_sQuery], 0);
                return DBTools::$aCachedResults[$a_sQuery];
            }
        }
        return false;
    }

    /**
     * Clears the cache
     *
     * @return If successful or not
     */
    public static function invalidateCache()
    {
        self::$aCachedResults = array();
        return true;
    }


    /**
     * Gets the latest ID updated in the database
     *
     * Wrapper for mysqli_insert_id
     *
     * @return int Latest ID updated by auto-increment
     */
    public static function getLatestID()
    {
        return mysqli_insert_id(self::getConnection());
    }

    /**
     * Get single value from a query
     *
     * If a query only returns one single value this function
     * can be used to fetch that value.
     */
    public static function getSingleValue($a_rResults)
    {
        if ($a_rResults != null) {
            $aRow = mysqli_fetch_row($a_rResults);
            if ($aRow != null && isset($aRow[0])) {
                return $aRow[0];
            }
        }
        return null;
    }
}
