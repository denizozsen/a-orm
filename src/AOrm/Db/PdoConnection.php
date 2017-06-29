<?php

namespace AOrm\Db;

/**
 * A database connection implemented by the PDO client library.
 *
 * @package AOrm\Db
 */
class PdoConnection extends Connection
{
    private $pdo;

    /**
     * {@inheritdoc}
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, $parameters = [])
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sql, $parameters = [])
    {
        $statement = $this->pdo->prepare($sql);
        $statement->nextRowset();
        $success = $statement->execute($parameters);
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function escapeString($string)
    {
        return trim($this->pdo->quote($string), "'");
    }
}
