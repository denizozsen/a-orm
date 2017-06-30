<?php

namespace AOrm;

/**
 * This can be used as a base class for data models. It encapsulates a data array for holding the key-value pairs
 * that make up the data model's state.
 *
 * It provides the following magic methods:
 *
 * - __get: implements property getters
 * - __set: implements property setters
 *
 * TODO - document relations
 * TODO - document the important methods that can be overridden
 *
 * @package AOrm
 */
abstract class Model
{
    private $data;
    private $related_models;
    private $is_new;
    private $immutable;

    protected static $cruds = [];

    protected static $crud_override_class = null;
    protected static $crud_overrides = [];

    protected static $relations = [];

    /**
     * Model constructor that takes the model's data array as parameter.
     *
     * @param mixed $data either associative array or stdClass containing key-value pairs
     * @param bool $is_new set tp true (default) to indicate that the record does not exist in the db, false otherwise
     */
    public final function __construct($data = [], $is_new = true)
    {
        $this->data = (array)$data;
        $this->related_models = [];
        $this->is_new = $is_new;
        $this->immutable = false;
    }

    /**
     * Returns the model data instance.
     *
     * @return Crud
     * @throws AOrmException if the model data is not correctly configured
     */
    public static final function getCrud()
    {
        $specific_model_class = get_called_class();

        if (!is_null(self::$crud_override_class)) {
            if (!isset(self::$crud_overrides[$specific_model_class])) {
                self::$crud_overrides[$specific_model_class] = new self::$crud_override_class($specific_model_class);
            }
            return self::$crud_overrides[$specific_model_class];
        }

        if (!isset(self::$cruds[$specific_model_class])) {
            $crud_instance = static::createCrudInstance();
            self::checkCrud($crud_instance);
            self::$cruds[$specific_model_class] = $crud_instance;
        }
        return self::$cruds[$specific_model_class];
    }

    /**
     * Provides a way to globally override all Crud instances with instances of the given class, which is useful for
     * test code.
     * The constructor of the given class must take a single argument, which will receive the model class name.
     * Pass null to cancel any existing override.
     *
     * @param string|null $crud_override_class the name of a Crud sub-class, or null, to cancel any previous override
     */
    public static final function setCrudOverrideClass($crud_override_class)
    {
        if (!is_null($crud_override_class)) {
            self::checkCrud($crud_override_class);
        }

        self::$crud_override_class = $crud_override_class;
        self::$crud_overrides = [];
    }

    /**
     * @return ConditionFactory the factory for creating Condition instances for the specific model class
     */
    public static function condition()
    {
        static $cached = null;
        if (is_null($cached)) {
            $cached = new ConditionFactory();
        }
        return $cached;
    }

    /**
     * Fetches the record corresponding to the given primary key.
     *
     * @param mixed $primary_key_value the primary key value
     * @param Criteria $extra_criteria object with fetching criteria (e.g. related models, immutable, etc)
     * @return static the model instance representing the fetched record
     * @throws AOrmException
     */
    public static function fetchByPrimaryKey($primary_key_value, Criteria $extra_criteria = null)
    {
        if (!is_array($primary_key_value)) {
            $primary_key = static::getCrud()->getPrimaryKey();
            $primary_key_value = [$primary_key => $primary_key_value];
        }

        $criteria = Criteria::create();
        foreach($primary_key_value as $key => $value) {
            $criteria->addCondition(static::condition()->equals($key, $value));
        }
        if ($extra_criteria) {
            $criteria->addCriteria($extra_criteria);
        }

        $model_instance = self::fetchOne($criteria);
        if (!$model_instance) {
            throw new AOrmException(
                "Unable to find " . get_called_class() . " with primary key: "
                    . str_replace("\n", '', var_export($primary_key_value, true))
            );
        }

        return $model_instance;
    }

    /**
     * Fetches the first record matching the given criteria.
     *
     * @param Criteria|Condition|array|null $criteria the fetching criteria, or null, to fetch the first one of all
     * @return static|null the model instance representing the fetched record, or null, if no such record was found
     * @throws AOrmException
     */
    public static function fetchOne($criteria = null)
    {
        $criteria = self::processCriteria($criteria);
        $record = self::getCrud()->fetchOne($criteria);
        return $record ? self::createModelInstance($record, $criteria) : null;
    }

    /**
     * Fetches the list of all records matching the given criteria.
     *
     * @param Criteria|Condition|array|null $criteria the fetching criteria, or null, to fetch all
     * @return static[] array of model instances representing the fetched records
     * @throws AOrmException
     */
    public static function fetchAll($criteria = null)
    {
        $criteria = self::processCriteria($criteria);
        $records = self::getCrud()->fetchAll($criteria);
        $instances = array_map(function($record) use($criteria) {
            return self::createModelInstance($record, $criteria);
        }, $records);
        return $instances;
    }

    /**
     * Saves the the record represented by this model instance.
     *
     * @throws AOrmException
     */
    public final function save()
    {
        // Call beforeSave event
        $this->beforeSave();

        // Save to persistent storage via the model's Crud
        $primary_key_value = static::getCrud()->save($this->getData());

        // Set primary value(s) in data array
        if (!is_array($primary_key_value)) {
            $primary_key_value = [ self::getCrud()->getPrimaryKey() => $primary_key_value ];
        }
        foreach ($primary_key_value as $key => $value) {
            $this->data[$key] = $value;
        }

        // Clear new flag
        $this->is_new = false;

        // Call afterSave event
        $this->afterSave();
    }

    /**
     * Inserts the the record represented by this model instance.
     * Unlike save(), this method will not update an existing record, but will throw an
     * AOrmException instead, e.g. if a unique key constraint is violated by the insert.
     *
     * @throws AOrmException
     */
    public final function insert()
    {
        // Call beforeSave event
        $this->beforeSave();

        // Insert into persistent storage via the model's Crud
        $primary_key_value = static::getCrud()->insert($this->getData());

        // Set primary value(s) in data array
        if (!is_array($primary_key_value)) {
            $primary_key_value = [ self::getCrud()->getPrimaryKey() => $primary_key_value ];
        }
        foreach ($primary_key_value as $key => $value) {
            $this->data[$key] = $value;
        }

        // Clear new flag
        $this->is_new = false;

        // Call afterSave event
        $this->afterSave();
    }

    /**
     * Deletes the record represented by thsui model instance.
     *
     * @throws AOrmException
     */
    public final function delete()
    {
        if ($this->isNew()) {
            throw new AOrmException("New model cannot be deleted");
        }

        // Call beforeDelete event
        $this->beforeDelete();

        $primary_key = self::getCrud()->getPrimaryKey();
        $pk_value = is_array($primary_key)
            ? array_intersect_key($this->data, array_flip($primary_key))
            : $this->data[$primary_key];
        static::getCrud()->delete($pk_value);

        // Call afterDelete event
        $this->afterDelete();
    }

    /**
     *
     * @param bool $relations Whether to return data from relations also
     *
     * @return array the data array
     */
    public final function getData($relations = false)
    {
        $data = $this->data;

        if ($relations) {
            foreach ($this->related_models as $related_name => $related){
                if (is_array($related)) {
                    $data[$related_name] = array_map(function(Model $related_model) {
                        return $related_model->getData();
                    }, $related);
                } else {
                    $data[$related_name] = $related->getData();
                }
            }
        }

        return $data;
    }

    /**
     * Sets the given key-value pairs in the model's data array, but does not remove any existing elements that are
     * not specified in the given array of key-value pairs.
     *
     * @param array $data array of key-value pairs to set in the model's data array
     */
    public final function setData($data)
    {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return bool true if the model is new (has no corresponding record in the db yet), false otherwise
     */
    public final function isNew()
    {
        return $this->is_new;
    }

    /**
     * Returns the model, or list of models, related to this model via the given relation name.
     *
     * @param string $relation_name a relation name
     * @return mixed the model, or list of models, related to this model via the given relation name
     * @throws AOrmException if the given relation name is not configured in this model
     */
    public final function getRelated($relation_name = null)
    {
        if (
            ($relation_name === null && !count($this->related_models)) ||
            !isset($this->related_models[$relation_name])
        ) {
            $relations = self::getCachedRelations();

            if ($relation_name !== null && !isset($relations[$relation_name])) {
                throw new AOrmException("Unknown relation name: {$relation_name}");
            }

            $relation_names = $relation_name ? (array)$relation_name : array_keys($relations);
            foreach ($relation_names as $relation_name) {
                /** @var Model $related_model */
                $related_model = $relations[$relation_name]($this);
                if ($this->immutable) {
                    $related_model->makeImmutable();
                }
                $this->related_models[$relation_name] = $related_model;
            }
        }

        if ($relation_name === null){
            return $this->related_models;
        }

        return $this->related_models[$relation_name];
    }

    /**
     * Makes the model immutable, i.e. exceptions are thrown, if changes to data elements are attempted.
     *
     * @return $this the model instance, to allow fluid calls
     */
    public final function makeImmutable()
    {
        $this->immutable = true;
        return $this;
    }

    /**
     * @return bool true, if the model is immutable (exceptions are thrown, if changes to data elements are attempted), false otherwise
     */
    public final function isImmutable()
    {
        return $this->immutable;
    }

    /**
     * Allows a data element or related model/model-list to be retrieved by key in property-style.
     *
     * Example:
     *     $my_value = $model->my_key
     *
     * @param string $key the key
     * @return mixed the value corresponding to the given key
     * @throws AOrmException if the requested key is not found
     */
    public final function __get($key)
    {
        $relations = self::getCachedRelations();
        if (isset($relations[$key])) {
            return $this->getRelated($key);
        }

        if (!array_key_exists($key, $this->data)) {
            throw new AOrmException("no such key: {$key}");
        }

        return $this->data[$key];
    }

    /**
     * Allows a data element to be added/changed in property-style.
     *
     * Example:
     *     $model->my_key = 'my value'
     *
     * @param string $key the key
     * @param mixed $value the value
     * @throws AOrmException
     */
    public final function __set($key, $value)
    {
        if ($this->immutable) {
            throw new AOrmException("cannot change immutable object");
        }

        $this->data[$key] = $value;
    }

    /**
     * Allows checking if a particular key is set and/or empty, via isset() / empty().
     *
     * @param string $name the key
     * @return bool true, if the key is set, false otherwise
     */
    public final function __isset($name)
    {
        return
            isset($this->data[$name])
            || isset($this->related_models[$name])
            || isset(self::getCachedRelations()[$name]);
    }

    /**
     * Unsets the attribute with the given name.
     *
     * @param string $name the name of the attribute to unset.
     */
    public final function __unset($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
        if (isset($this->related_models[$name])) {
            unset($this->related_models[$name]);
        }
    }

    /**
     * Creates and returns the Crud instance for this model.
     *
     * @return Crud the Crud instance for this model
     */
    public static abstract function createCrudInstance();

    /**
     * Returns the set of relations for this model: each key being a relation name and each value being a function,
     * taking this model as parameter and returning a new instance (or array of instances) of the related model.
     *
     * Example: relations for a Product model:
     *
     * [
     *     'category' => function(Product $product) {
     *         return Category::fetch()->one(['category_id' => $product->category_id]);
     *     },
     *     'accessory_choices' => function(Product $product) {
     *         return Product::fetch()->all(['is_accessory' => 1]);
     *     }
     * ]
     *
     * @return array array of associative arrays, describing the set of relations
     */
    public static function getRelations()
    {
        return [];
    }

    /**
     * May be overridden by specific model classes. Called just before the model is saved to the db.
     * The base implementation does nothing.
     */
    protected function beforeSave()
    {}

    /**
     * May be overridden by specific model classes. Called just after the model is saved to the db.
     * The base implementation does nothing.
     */
    protected function afterSave()
    {}

    /**
     * May be overridden by specific model classes. Called just before the model is deleted from the db.
     * The base implementation does nothing.
     */
    protected function beforeDelete()
    {}

    /**
     * May be overridden by specific model classes. Called just after the model is deleted from the db.
     * The base implementation does nothing.
     */
    protected function afterDelete()
    {}

    /**
     * Creates a model instance, with related models, possibly immutable, as per the given criteria.
     *
     * @param array $record
     * @param Criteria|null $criteria
     * @return static
     */
    public static function createModelInstance(array $record, Criteria $criteria = null)
    {
        $model = new static($record, false);
        if ($criteria && $criteria->isImmutable()) {
            $model->makeImmutable();
        }
        $related_map = $criteria ? $criteria->getRelatedMap() : [];
        self::addRelatedModels($model, $related_map);
        return $model;
    }

    /**
     * Produces a Criteria object corresponding to the given value, or null, if the given value is null.
     *
     * These are the rules, based on the type of the given value:
     *   - Criteria object: the same object is returned
     *   - Condition object: a new Criteria object is returned with the given Condition set
     *   - array: a new Criteria object is returned with an EQUALS condition for each key/value pair in the given array
     *   - null: null is returned
     *   - any other type: a AOrmException is thrown
     *
     * @param Criteria|Condition|array|null $criteria the criteria
     * @return Criteria the result Criteria object
     * @throws AOrmException if the given criteria value is a type other than Criteria, Condition, array or null
     */
    protected static function processCriteria($criteria)
    {
        if (is_null($criteria)) {
            return null;
        } elseif (is_array($criteria)) {
            $criteria_object = Criteria::create();
            foreach ($criteria as $key => $value) {
                if (is_int($key)) {
                    throw new AOrmException('criteria array must be associative');
                }
                $criteria_object->addCondition(static::condition()->equals($key, $value));
            }
            return $criteria_object;
        } elseif ($criteria instanceof Criteria) {
            return $criteria;
        } elseif ($criteria instanceof Condition) {
            return Criteria::create($criteria);
        }

        $type = (gettype($criteria) == 'object') ? get_class($criteria) : gettype($criteria);
        throw new AOrmException("Unhandled criteria type: {$type}");
    }

    /**
     * Adds all requested related models to the given model.
     *
     * @param Model $model the model to which to add requested related models
     * @param array $related_map the map of names of related models
     * @throws AOrmException if an invalid relation name is encountered
     */
    private static function addRelatedModels(Model $model, array $related_map)
    {
        foreach($related_map as $relation_name => $child_relations) {
            $related_models = (array)$model->getRelated($relation_name);
            if (is_array($child_relations)) {
                foreach($related_models as $related_model) {
                    self::addRelatedModels($related_model, $child_relations);
                }
            }
        }
    }

    private static function getCachedRelations()
    {
        $derived_class = get_called_class();
        if (!isset(self::$relations[$derived_class])) {
            self::$relations[$derived_class] = static::getRelations();
        }
        return self::$relations[$derived_class];
    }

    private static function checkCrud($crud)
    {
        // Ensure that the configured crud class implements the Crud interface
        if (!is_a($crud, Crud::class, true)) {
            $crud_class_name = is_object($crud) ? get_class($crud) : $crud;
            throw new AOrmException("Configured crud class {$crud_class_name} must implement " . Crud::class);
        }
    }
}
