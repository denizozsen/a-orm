<?php

namespace DenOrm;

/**
 * A CompositeCondition is a condition that combines two or more other conditions via an operator (either AND or OR).
 *
 * @package DenOrm
 */
class CompositeCondition implements Condition
{
    private $children;
    private $operator;

    /**
     * CompositeCondition constructor.
     *
     * @param Condition[] $children the child conditions that make up this composite condition
     * @param string $operator the operator linking the children, one of Condition::OPERATOR_AND or Condition::OPERATOR_OR
     */
    public function __construct(array $children, $operator)
    {
        $this->children = $children;
        $this->operator = $operator;
    }

    /**
     * Creates a Condition instance that represents the combination of the given conditions.
     *
     * @param Condition[] $conditions the conditions to be combined
     * @param string $operator the operator linking the children, one of Condition::OPERATOR_AND or Condition::OPERATOR_OR
     * @return CompositeCondition the Condition instance that represents the combination of the given conditions
     */
    public static function combine(array $conditions, $operator = self::OPERATOR_AND)
    {
        // Simplify the given condition list, by unwrapping the children of composites, as much as possible.
        // This is only possible for composites that use the same operator as the given one.
        $simplified_conditions = [];
        foreach($conditions as $condition) {
            if ($condition instanceof CompositeCondition && $condition->getOperator() == $operator) {
                $simplified_conditions = array_merge($simplified_conditions, $condition->getChildren());
                continue;
            }
            $simplified_conditions[] = $condition;
        }

        return new CompositeCondition($simplified_conditions, $operator);
    }

    /**
     * @return Condition[] the child condition objects making up this composite condition
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return string the operator linking the children, one of Condition::OPERATOR_AND or Condition::OPERATOR_OR
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    public function render($alias = null)
    {
        $rendered_conditions = array_map(function(Condition $condition) use($alias) {
            return $condition->render($alias);
        }, $this->children);
        return '(' . implode(') ' . $this->getOperator() . ' (', $rendered_conditions) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $parameters = [];
        foreach($this->children as $child_condition) {
            $parameters = array_merge($parameters, $child_condition->getParameters());
        }
        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function combineWith($condition, $operator = self::OPERATOR_AND)
    {
        if (!is_array($condition)) {
            $condition = [$condition];
        }
        $conditions_to_be_merged = array_merge([$this], $condition);
        return self::combine($conditions_to_be_merged, $operator);
    }
}
