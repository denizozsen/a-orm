<?php

namespace DenOrm;

/**
 * A repository is an object that provides a collection-like interface for retrieving/saving/deleting Model instances.
 *
 * @package DenOrm
 */
class Repository
{
    private $model_class;

    /**
     * Repository constructor.
     *
     * @param string $model_class the name of the Model sub-class that the repository will work with
     */
    public function __construct($model_class)
    {
        $this->ensureModelSubclass($model_class);
        $this->model_class = $model_class;
    }

    /**
     * Retrieves the record corresponding to the given primary key.
     *
     * @param mixed $primary_key_value the primary key value
     * @param Criteria $extra_criteria object with fetching criteria (e.g. related models, immutable, etc)
     * @return Model the model instance representing the fetched record
     * @throws DenOrmException
     */
    public function getByPrimaryKey($primary_key_value, Criteria $extra_criteria = null)
    {
        return call_user_func([$this->model_class, 'fetchByPrimaryKey'], $primary_key_value, $extra_criteria);
    }

    /**
     * Retrieves the first record matching the given criteria.
     *
     * @param Criteria|Condition|array|null $criteria the fetching criteria, or null, to fetch the first one of all
     * @return Model|null the model instance representing the fetched record, or null, if no such record was found
     * @throws DenOrmException
     */
    public function getOne($criteria = null)
    {
        return call_user_func([$this->model_class, 'fetchOne'], $criteria);
    }

    /**
     * Retrieves the list of all records matching the given criteria.
     *
     * @param Criteria|Condition|array|null $criteria the fetching criteria, or null, to fetch all
     * @return Model[] array of model instances representing the fetched records
     * @throws DenOrmException
     */
    public function getAll($criteria = null)
    {
        return call_user_func([$this->model_class, 'fetchAll'], $criteria);
    }

    /**
     * Saves the the given model instance.
     *
     * @param Model $model the model instance to save
     * @throws DenOrmException
     */
    public function save(Model $model)
    {
        $model->save();
    }

    /**
     * Deletes given model instance.
     *
     * @param Model $model the model instance to delete
     * @throws DenOrmException
     */
    public function delete(Model $model)
    {
        $model->delete();
    }

    /**
     * Throws an exception, if the given class name is not the name of a sub-class of Model
     *
     * @param string $class a class name
     * @throws DenOrmException
     */
    private function ensureModelSubclass($class)
    {
        if ( !is_string($class) || !is_a($class, Model::class, true) ) {
            throw new DenOrmException(
                "model_class must be the name of a sub-class of " . Model::class . ", but was: {$class}");
        }
    }
}