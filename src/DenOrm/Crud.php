<?php

namespace DenOrm;

/**
 * An object implementing the Crud interface knows how to fetch, save and delete the data for a particular model type.
 *
 * @package DenOrm
 */
interface Crud
{
    /**
     * @return string the name of the model class that owns this Crud
     */
    public function getModelClass();

    /**
     * @return string|array the primary key field name, or array of composite primary key field names
     */
    public function getPrimaryKey();

    /**
     * Fetches the first record matching the given criteria.
     *
     * @param Criteria|null $criteria object describing the fetching criteria, or null, to fetch the first of all
     * @return array|null associative array representing the fetched record, or null, if the record is not found
     * @throws DenOrmException
     */
    public function fetchOne(Criteria $criteria = null);

    /**
     * Fetches the list of all records matching the given criteria.
     *
     * @param Criteria|null $criteria object describing the fetching criteria, or null, to fetch all
     * @return array array of associative arrays representing the fetched records
     * @throws DenOrmException
     */
    public function fetchAll(Criteria $criteria = null);

    /**
     * Returns a resource that is used to join the model to which this Crud belongs, to another model.
     *
     * @param array $relation an associative array that describes the relation for which a join is to be constructed
     * @return mixed the join fragment resource
     */
    public function getRelatedJoinFragment($relation);

    /**
     * Saves the given record.
     *
     * @param array $record associative array representing the record to be saved
     * @return mixed the primary key value of the saved record, either a single value or an associative array, keyed on column names
     * @throws DenOrmException
     */
    public function save(array $record);

    /**
     * Deletes the record with the given primary key value.
     *
     * @param mixed $pk_value the primary key value of the record to be deleted, either a single value or an associative array, keyed on column names
     * @throws DenOrmException
     */
    public function delete($pk_value);
}
