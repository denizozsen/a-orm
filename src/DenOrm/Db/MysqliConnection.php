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
        // Convert PDO-style named parameters to question mark placeholders,
        // because mysqli does not support named parameters
        $this->convertNamedParameters($sql, $parameters);

        $statement = $this->mysqli->prepare($sql);
        foreach ($parameters as $i => $value) {
            $type = 's';
            if (is_integer($value)) {
                $type = 'i';
            } elseif (is_double($value)) {
                $type = 'd';
            }
            $statement->bind_param($type, $parameters[$i]);
        }
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        return $rows;
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

    /**
     * Converts the PDO-style named parametrers in the given SQL and parameter array
     * to the question mark placeholders, accepted by mysqli prepared statements. Both
     * the given SQL string and paramerter array arguments are replaced by reference.
     * Note that the $parameters array must be associative, but will be replaced with
     * a simple indexed array of values.
     *
     * @param string $sql SQL string, passed by reference
     * @param array $parameters associative array of named parameters, passed by reference
     */
    public function convertNamedParameters(&$sql, &$parameters)
    {
        $converted_parameters = [];
        $callback = function(array $matches) use($parameters, &$converted_parameters) {
            $converted_parameters[] = $parameters[$matches[0]];
            return '?';
        };

        $sql = preg_replace_callback('/:[^\s]+/', $callback, $sql);
        $parameters = $converted_parameters;
    }
}

