<?php
/**
 * pdo singleton class. Override connection to db
 * because PDO constructor throw RuntimeException
 * in case of error with backtrace (include user & password)
 * 
 * Usage:
 *  
 * $db = PDOConnector::connect($dsn, $user, $password);
 * 
 * $db->query($sql);
 *
 * @author michael
 */
class PDOConnector {
    /**
     * The singleton instance
     * @var PDO $_db
     */
    protected static $_db;

    protected function __construct() {}
    protected function __clone() {}
    
    /**
     * Creates a PDO instance representing a connection to a database and makes the instance available as a singleton
     *
     * @param string $dsn The full DSN, eg: mysql:host=localhost;dbname=testdb
     * @param string $username The user name for the DSN string. This parameter is optional for some PDO drivers.
     * @param string $password The password for the DSN string. This parameter is optional for some PDO drivers.
     * @param array $driverOptions A key=>value array of driver-specific connection options
     *
     * @return PDO | false
     */
    public static function connect($dsn, $username = '', $password = '', $driverOptions = array()) {
        if(!self::$_db) {
            try {
                self::$_db = new PDO($dsn, $username, $password, $driverOptions);
            } catch (PDOException $e) {
                return false;
            }
        }
        return self::$_db;
    }
}
?>