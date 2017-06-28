<?php

namespace AOrm;

use AOrm\Db\MysqliConnection;
use AOrm\Db\PdoConnection;

/**
 * The Registry provides a way to register the service instances required by AOrm, such as the database connection.
 *
 * @package AOrm
 */
class Registry
{
    private static $db_connection = null;

    /**
     * @return \AOrm\Db\Connection
     * @throws AOrmException
     */
    public static function getDbConnection()
    {
        if (is_null(self::$db_connection)) {
            throw new AOrmException('No db connection was registered');
        }

        return self::$db_connection;
    }

    /**
     * @param \PDO $pdo
     */
    public static function registerPdoConnection(\PDO $pdo)
    {
        self::$db_connection = new PdoConnection($pdo);
    }

    /**
     * @param \mysqli $mysqli
     */
    public static function registerMysqliConnection(\mysqli $mysqli)
    {
        self::$db_connection = new MysqliConnection($mysqli);
    }
}
