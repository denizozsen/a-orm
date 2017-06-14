<?php

namespace DenOrm;

/**
 * Factory for creating Condition instances.
 *
 * @package DenOrm
 */
class ConditionFactory
{
    /**
     * Creates an equality condition: property = value
     *
     * @param string $key the property name
     * @param string $value the value against which to check equality with the property
     * @return SimpleCondition
     */
    public function equals($key, $value)
    {
        return new SimpleCondition(Condition::EQUALS, $key, $value);
    }

    /**
     * Creates a non-equality condition: property <> value
     *
     * @param string $key the property name
     * @param string $value the value against which to check non-equality with the property
     * @return SimpleCondition
     */
    public function notEquals($key, $value)
    {
        return new SimpleCondition(Condition::NOT_EQUALS, $key, $value);
    }

    /**
     * Creates a less-than inequality condition: property < value
     *
     * @param string $key the property name
     * @param string $value the value against which to check the inequality
     * @return SimpleCondition
     */
    public function lessThan($key, $value)
    {
        return new SimpleCondition(Condition::LESS_THAN, $key, $value);
    }

    /**
     * Creates a less-than-or-equals inequality condition: i.e. property <= value
     *
     * @param string $key the property name
     * @param string $value the value against which to check the inequality
     * @return SimpleCondition
     */
    public function lessThanOrEquals($key, $value)
    {
        return new SimpleCondition(Condition::LESS_THAN_OR_EQUALS, $key, $value);
    }

    /**
     * Creates a greater-than inequality condition: property > value
     *
     * @param string $key the property name
     * @param string $value the value against which to check the inequality, i.e.
     * @return SimpleCondition
     */
    public function greaterThan($key, $value)
    {
        return new SimpleCondition(Condition::GREATER_THAN, $key, $value);
    }

    /**
     * Creates a greater-than-or-equals inequality condition: property >= value
     *
     * @param string $key the property name
     * @param string $value the value against which to check the inequality
     * @return SimpleCondition
     */
    public function greaterThanOrEquals($key, $value)
    {
        return new SimpleCondition(Condition::GREATER_THAN_OR_EQUALS, $key, $value);
    }

    /**
     * Creates a between-range condition: property BETWEEN value1 AND value2.
     *
     * @param string $key the property name
     * @param string $value1 the start of the range
     * @param string $value2 the end of the range
     * @return SimpleCondition
     */
    public function between($key, $value1, $value2)
    {
        return new SimpleCondition(Condition::BETWEEN, $key, $value1, $value2);
    }

    /**
     * Creates a not-between-range condition: property NOT BETWEEN value1 AND value2.
     *
     * @param string $key the property name
     * @param string $value1 the start of the range
     * @param string $value2 the end of the range
     * @return SimpleCondition
     */
    public function notBetween($key, $value1, $value2)
    {
        return new SimpleCondition(Condition::NOT_BETWEEN, $key, $value1, $value2);
    }

    /**
     * Creates an IN condition: property IN (value1, value2, value3, ...)
     *
     * @param string $key the property name
     * @param string[] $values the set of values for the IN condition
     * @return SimpleCondition
     */
    public function in($key, array $values)
    {
        return new SimpleCondition(Condition::IN, $key, $values);
    }

    /**
     * Creates a NOT IN condition: property NOT IN (value1, value2, value3, ...)
     *
     * @param string $key the property name
     * @param array $values the set of (string) values for the NOT IN condition
     * @return SimpleCondition
     */
    public function notIn($key, array $values)
    {
        return new SimpleCondition(Condition::NOT_IN, $key, $values);
    }

    /**
     * Creates a LIKE wildcard condition: property LIKE value
     *
     * @param string $key the property name
     * @param string $value the value for the LIKE condition
     * @return SimpleCondition
     */
    public function like($key, $value)
    {
        return new SimpleCondition(Condition::LIKE, $key, $value);
    }

    /**
     * Creates a NOT LIKE wildcard condition: property NOT LIKE value
     *
     * @param string $key the property name
     * @param string $value the value for the NOT LIKE condition
     * @return SimpleCondition
     */
    public function notLike($key, $value)
    {
        return new SimpleCondition(Condition::NOT_LIKE, $key, $value);
    }

    /**
     * Creates a null-check condition: property IS NULL
     *
     * @param string $key the property name
     * @return SimpleCondition
     */
    public function isNull($key)
    {
        return new SimpleCondition(Condition::IS_NULL, $key);
    }

    /**
     * Creates a not-null-check condition: property IS NOT NULL
     *
     * @param string $key the property name
     * @return SimpleCondition
     */
    public function isNotNull($key)
    {
        return new SimpleCondition(Condition::IS_NOT_NULL, $key);
    }

    /**
     * Creates a condition that is given by an arbitrary custom expression and, optionally, a set of parameters
     *
     * @param string $expression the expression, e.g. "my_prop IS NULL OR my_prop = '' OR my_prop = :magic_value"
     * @param array $parameters optional set of parameter key/values, e.g. [ :magic_value => '---' ]
     * @return SimpleCondition
     */
    public function raw($expression, $parameters = [])
    {
        return new SimpleCondition(Condition::RAW, $expression, null, null, $parameters);
    }
}
