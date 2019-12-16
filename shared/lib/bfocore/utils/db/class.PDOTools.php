<?php

require_once('config/inc.config.php');

class PDOTools
{
    private static $cached_queries = [];
    private static $db;

    /**
     * Gets the current database connection or creates a new one if no one exists
     *
     * @return Resource Database connection
     */
    private static function getConnection()
    {
        if (!isset(self::$db))
        {
            try 
            {
                self::$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_SCHEME . ';charset=utf8', DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                //self::$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_SCHEME . ';charset=utf8', DB_USER, DB_PASSWORD, [\PDO::MYSQL_ATTR_INIT_COMMAND =>"SET time_zone = '-6:00';", PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
               
            } 
            catch (PDOException $e) 
            {
                throw new PDOException("Error  : " . $e->getMessage());
            }
        }
        return self::$db;
    }

    public static function insert($query, array $data)
    {   
        self::getConnection()->prepare($query)->execute($data);
        return self::getConnection()->lastInsertId();
    }

    public static function update($query, array $data) 
    {
        $stmt = self::executeQuery($query,$data);
        return $stmt->rowCount();       
    }

    public static function delete($query, array $data) 
    {
        $stmt = self::executeQuery($query,$data);
        return $stmt->rowCount();       
    }

    public static function findOne($query, array $data = null)
    {        
        $stmt = self::executeQuery($query,$data);          
        return $stmt->fetchObject();
    }

    public static function findMany($query, array $data = null)
    {
        $stmt = self::executeQuery($query,$data);
        return($stmt->fetchAll());
    }

    public static function executeQuery($query, $data = null)
    {
        try 
        {
            $stmt = self::getConnection()->prepare($query);
            $stmt->execute($data);
            return $stmt;
        }
        catch (PDOException $e)
        {
            //TODO: Catch all generic SQL errors but pass on others
            throw $e;
        }
    }


    /**
     * Caches query results for later retrieval using getCachedQuery
     *
     * @param string $a_sQuery Query to cache
     * @param Resource $a_rResults Results of the query to cache
     * @return boolean True if result was cached and false if it was not
     *
     */
    public static function cacheQueryResults($query, $results)
    {
        if (!empty($query) && !empty($results))
        {
            self::$cached_queries[$query] = $results;
            return true;
        }
        return false;
    }

    /**
     * Retrieves a previously cached query
     *
     * @param string $a_sQuery Query to find cached
     * @return Resource The cached query or false if it could not be found
     */
    public static function getCachedQuery($query)
    {
        if (array_key_exists($query, self::$cached_queries))
        {
            if (self::$cached_queries[$query]->fetchColumn() > 0)
            {
                return self::$cached_queries[$query];
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
        self::$cached_queries = [];
        return true;
    }
}

?>