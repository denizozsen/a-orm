<?php

namespace AOrm\Db;

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
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function escapeString($string)
    {
        return $this->pdo->quote($string);
    }
}
