<?php

namespace DenOrm\Db;

/**
 * The database connection wrapper that DenOrm uses internally.
 *
 * @package DenOrm\Db
 */
abstract class Connection
{
    /**
     * Executes the given SQL, as a prepared statement, and returns the result set (if any), as an array of
     * associative arrays.
     *
     * @param string $sql the SQL statement
     * @param array $parameters an optional associative array of query parameters, as name/value pairs
     * @return array|null the result set, as an array of associative arrays, or null, if none
     */
    public abstract function query($sql, $parameters = []);

    /**
     * @return mixed the row ID of the last row that was inserted into the database
     */
    public abstract function lastInsertId();

    /**
     * Escapes the given string that is to be used in a SQL query, to guard against SQL injection attacks.
     *
     * @param string $string the string to be escaped
     * @return string the escaped string
     */
    public abstract function escapeString($string);

    /**
     * Performs an INSERT ... ON DUPLICATE KEY UPDATE statement.
     *
     * @param string $table_name the name of the table into which to insert/update
     * @param array $insert_array an associative array with the keys/values to insert/update
     * @return int the insert_id returned by MySQL
     */
    public function insertOnDuplicateKeyUpdate($table_name, $insert_array)
    {
        // Sanitize all values in $insert_array and convert booleans to integers
        $insert_array = array_map(function ($value) {
            return (is_bool($value) || is_integer($value))
                ? (int)$value
                : ("'" . $this->escapeString($value) . "'");
        }, $insert_array);

        // Generate strings column and value list
        $column_value_array = $insert_array;
        $columns = implode(",", array_keys($column_value_array));
        $values = implode(",", array_values($column_value_array));

        // Generate string for on-duplicate-key-update assignment list
        $on_duplicate_assignments_array = array_map(function($column) {
            return "{$column} = VALUES({$column})";
        }, array_keys($insert_array));
        $on_duplicate_assignments = implode(',', $on_duplicate_assignments_array);

        // Perform insert/update query
        $this->query("
            INSERT INTO {$table_name} (
                {$columns}
            )
            VALUES (
                {$values}
            )
            ON DUPLICATE KEY UPDATE
                {$on_duplicate_assignments}
        ");

        // Return last insert id
        return $this->lastInsertId();
    }

    /**
     * Returns the list of column names for the given table.
     *
     * @param string $table_name a table name
     * @return array the list of column names for the given table
     */
    public function getTableColumns($table_name)
    {
        static $cache = [];
        if (!isset($cache[$table_name])) {
            $rows = $this->query("SHOW COLUMNS FROM " . $table_name);
            $table_columns = array_column($rows, 'Field');
            $cache[$table_name] = $table_columns;
        }
        return $cache[$table_name];
    }
    /**
     * TODO - document
     *
     * @param array $associative_array
     * @param string $table_name
     * @return array
     */
    public function getSubsetArrayForInsert($associative_array, $table_name)
    {
        $table_columns = self::getTableColumns($table_name);
        $subset_array = array_intersect_key($associative_array, array_flip($table_columns));
        return $subset_array;
    }
}