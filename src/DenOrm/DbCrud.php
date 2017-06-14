<?php

namespace DenOrm;

/**
 * A default Crud implementation that manipulates model data in the database.
 *
 * @package DenOrm
 */
class DbCrud implements Crud
{
    private $model_class;
    private $primary_key;
    private $main_table;

    /**
     * DbCrud constructor.
     *
     * @param string $model_class the name of the model class (hint, use the class static member, like this: ClassName::class)
     * @param string $main_table the main database table containing the data for the model
     * @param string|array $primary_key the primary key field name, or array of field names, if it's a composite PK
     */
    public function __construct($model_class, $main_table, $primary_key)
    {
        $this->model_class = $model_class;
        $this->primary_key = $primary_key;
        $this->main_table = $main_table;
    }

    /**
     * @return string the name of the main table that this Crud manipulates
     */
    public function getMainTable()
    {
        return $this->main_table;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelClass()
    {
        return $this->model_class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne(Criteria $criteria = null)
    {
        $result = $this->fetchAll($criteria);
        return $result ? reset($result) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(Criteria $criteria = null)
    {
        if (!$criteria) {
            $criteria = Criteria::null();
        }

        $main_table = $this->getMainTable();

        $query = "
            SELECT
                *
            FROM {$main_table}
        ";

        $parameters = array();
        if ($criteria->getCondition()) {
            $query .= 'WHERE ' . $criteria->getCondition()->render();
            $parameters = $criteria->getCondition()->getParameters();
        }

        return Registry::getDbConnection()->query($query, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedJoinFragment($relation)
    {
        throw new DenOrmException("getRelatedJoinFragment() is not implemented!");
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $record)
    {
        $table = $this->getMainTable();
        $db_connection = Registry::getDbConnection();
        $save_data = $db_connection->getSubsetArrayForInsert($record, $table);
        $pk_value = $db_connection->insertOnDuplicateKeyUpdate($table, $save_data);
        if (!$pk_value) {
            $primary_key = $this->getPrimaryKey();
            $pk_value = is_array($primary_key)
                ? array_intersect_key($record, array_flip($primary_key))
                : $record[$primary_key];
        }
        return $pk_value;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($pk_value)
    {
        $pk_value = is_array($pk_value) ? $pk_value : [ $this->getPrimaryKey() => $pk_value ];
        $table = $this->getMainTable();
        $conditions_sql = $this->renderConditions($pk_value);
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $conditions_sql);
        Registry::getDbConnection()->query($sql, $this->conditionsToParameters($pk_value));
    }

    /**
     * Renders the equality conditions given by key/value pairs (associative array) and ANDs them together.
     *
     * @param array $conditions associative array of key/value pairs for equality conditions
     * @return string the rendered condition
     */
    protected function renderConditions(array $conditions)
    {
        $conditions_str = '';
        foreach (array_keys($conditions) as $column) {
            if ($conditions_str) {
                $conditions_str .= " AND ";
            }
            $parameter = ':' . $column;
            $conditions_str .= "{$column} = {$parameter}";
        }
        return $conditions_str;
    }

    /**
     * Creates a parameter array from the give key/value pairs representing equality conditions.
     *
     * @param array $conditions key/value pairs representing equality conditions
     * @return array the parameter array
     */
    protected function conditionsToParameters(array $conditions)
    {
        $parameters = [];
        foreach ($conditions as $key => $value) {
            $parameters[':' . $key] = $value;
        }
        return $parameters;
    }

    /**
     * Renders the given criteria assoc array as a one-line string
     *
     * @param array $criteria associative array representing criteria
     * @return string the string representation of the given criteria assoc array
     */
    protected function criteriaToString(array $criteria)
    {
        return str_replace("\n", '', var_export($criteria, true));
    }
}
