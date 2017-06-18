<?php

namespace AOrm;

/**
 * A Condition object is used to describe a data condition that can be used as part of the criteria for a
 * model fetching instruction.
 *
 * @package DenOrm
 */
interface Condition
{
    // Condition types
    const EQUALS = 'equals';
    const NOT_EQUALS = 'not_equals';
    const LESS_THAN = 'less_than';
    const LESS_THAN_OR_EQUALS = 'less_than_or_equals';
    const GREATER_THAN = 'greater_than';
    const GREATER_THAN_OR_EQUALS = 'greater_than_or_equals';
    const BETWEEN = 'between';
    const NOT_BETWEEN = 'not_between';
    const IN = 'in';
    const NOT_IN = 'not_in';
    const LIKE = 'like';
    const NOT_LIKE = 'not_like';
    const IS_NULL = 'is_null';
    const IS_NOT_NULL = 'is_not_null';
    const RAW = 'is_not_null';

    // Operator constants
    const OPERATOR_AND = 'AND';
    const OPERATOR_OR = 'OR';

    /**
     * @param string|null $alias alias to be used as prefix for property names, or null, to not use an alias prefix
     * @return string a string representation of the condition, that can be used for fetching a model or set of models
     * @throws DenOrmException if the type set on this SimpleCondition is not supported by render()
     */
    public function render($alias = null);

    /**
     * @return array associative array, keys being parameter names and values being parameter values
     */
    public function getParameters();

    /**
     * Creates a new Condition instance that represents the combination of this and the given condition(s).
     *
     * @param Condition|Condition[] $condition
     * @param string $operator one of Conditions::OPERATOR_AND and Conditions::OPERATOR_OR
     * @return Condition
     */
    public function combineWith($condition, $operator = self::OPERATOR_AND);
}
