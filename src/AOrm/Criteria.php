<?php

namespace AOrm;

/**
 * A Criteria object is used to describe what data should be retrieved via a call to either fetchOne() or fetchAll()
 * to a Model sub-class.
 *
 * @package DenOrm
 */
class Criteria
{
    /** @var Condition */
    private $condition = null;
    private $related_map = [];
    private $immutable = false;

    /**
     * Creates a new empty Criteria instance. This is handy, when using a fluent style, e.g.:
     *
     *     $criteria = Criteria::create()
     *         ->addCondition($condition1)
     *         ->addCondition($condition2, Condition::OPERATOR_OR)
     *         ->addRelated('my_related_model')
     *         ->order('created_on DESC')
     *         ->limit($page_size, ($page-1)*page_size)
     *         ->immutable();
     *
     * @param Condition|null $condition
     * @return Criteria
     */
    public static function create(Condition $condition = null)
    {
        $instance = new self();

        if ($condition) {
            $instance->addCondition($condition);
        }

        return $instance;
    }

    /**
     * Returns a special Criteria instance that return the default (or empty) value for each criteria component. This
     * is useful for situations where a default Criteria object is needed, when none was given.
     *
     * @return NullCriteria the null criteria instance
     */
    public static function null()
    {
        static $null_criteria = null;
        if (is_null($null_criteria)) {
            $null_criteria = new NullCriteria();
        }
        return $null_criteria;
    }

    /**
     * Adds the given condition to the criteria's set of conditions.
     *
     * @param Condition $condition the condition to add
     * @param string $operator the linking operator, one of Condition::OPERATOR_AND or Condition::OPERATOR_OR
     * @return $this this instance, to enable fluent style call chaining
     */
    public function addCondition(Condition $condition, $operator = 'AND')
    {
        // Set given condition, or, if one is already set, combine the existing condition with the given one
        $this->condition = is_null($this->condition)
            ? $condition
            : $this->condition->combineWith([$condition], $operator);

        return $this;
    }

    /**
     * Adds a related model to fetch together with the main model being fetched.
     *
     * @param string $relation_name the name of the model relation to add
     * @return $this this instance, to enable fluent style call chaining
     */
    public function addRelated($relation_name)
    {
        $this->addToRelatedMap($this->related_map, $relation_name);
        return $this;
    }

    /**
     * Makes the fetched model immutable.
     *
     * @return $this this instance, to enable fluent style call chaining
     */
    public function immutable()
    {
        $this->immutable = true;
        return $this;
    }

    /**
     * Adds all parts of the the given criteria object to this criteria object.
     *
     * @param Criteria $criteria the criteria object whose parts to add
     */
    public function addCriteria(Criteria $criteria)
    {
        // Conditions
        if ($criteria->getCondition()) {
            $this->addCondition($criteria->getCondition());
        }

        // Related
        if ($criteria->getRelatedMap()) {
            $this->addRelated($criteria->getRelatedMap());
        }

        // Immutable
        if ($criteria->isImmutable()) {
            $this->immutable();
        }
    }

    /**
     * @return Condition associative array of fetching conditions
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return array the map of names of related models to fetch at the same time as the main model
     */
    public function getRelatedMap()
    {
        return $this->related_map;
    }

    /**
     * @return boolean true if fetched model(s) should be immutable, false otherwise
     */
    public function isImmutable()
    {
        return $this->immutable;
    }

    /**
     * Adds the given relation name to the given map of relation names.
     *
     * @param array $related_map the map to which to add the given relation name
     * @param $new_relation_name the relation name to add
     */
    private function addToRelatedMap(&$related_map, $new_relation_name)
    {
        if ($new_relation_name === 1) {
            return;
        }

        if ($related_map == 1) {
            $related_map = [];
        }

        if (is_string($new_relation_name)) {
            if (!isset($related_map[$new_relation_name])) {
                $related_map[$new_relation_name] = 1;
            }
        } elseif (is_array($new_relation_name)) {
            foreach ($new_relation_name as $name => $children) {
                if ( !isset($related_map[$name]) || !is_array($related_map[$name]) ) {
                    $related_map[$name] = 1;
                }
                $this->addToRelatedMap($related_map[$name], $children);
            }
        }
    }
}

/**
 * A criteria object that returns the default (or empty) value for each of its components.
 *
 * @package DenOrm
 */
class NullCriteria extends Criteria
{
    /**
     * {@inheritdoc}
     */
    public function addCondition(Condition $condition, $operator = 'AND')
    {
        $this->preventModification();
    }

    /**
     * {@inheritdoc}
     */
    public function addRelated($relation_name)
    {
        $this->preventModification();
    }

    /**
     * {@inheritdoc}
     */
    public function immutable()
    {
        $this->preventModification();
    }

    /**
     * {@inheritdoc}
     */
    public function addCriteria(Criteria $criteria)
    {
        $this->preventModification();
    }

    /**
     * @throws DenOrmException unconditionally
     */
    private function preventModification()
    {
        throw new DenOrmException('NullCriteria cannot be modified');
    }
}
