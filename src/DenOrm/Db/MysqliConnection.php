<?php

namespace DenOrm\Db;

class MysqliConnection extends Connection
{
    private $mysqli;

    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql, $parameters = [])
    {
        $statement = $this->mysqli->prepare($sql);
        foreach ($parameters as $name => $value) {
            $type = 's';
            if (is_integer($value)) {
                $type = 'i';
            } elseif (is_double($value)) {
                $type = 'd';
            }
            $statement->bind_param($type, $parameters[$name]);
        }
        $result = $statement->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId()
    {
        return $this->mysqli->insert_id;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeString($string)
    {
        return $this->mysqli->real_escape_string($string);
    }
}
