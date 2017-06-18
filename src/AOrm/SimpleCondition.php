<?php

namespace AOrm;

/**
 * A SimpleCondition is used to narrow down the result set of a model fetching instruction. It consists of a property
 * name (the 'key'), a condition type (e.g. EQUALS, GREATER_THAN, IN, etc) and one or more values.
 *
 * @package DenOrm
 */
class SimpleCondition implements Condition
{
    private $type;
    private $key;
    private $value;
    private $value2;
    private $parameters;

    /**
     * SimpleCondition constructor.
     *
     * @param string $type the condition type (one of the constants in the Condition interface, e.g. Condition::EQUALS)
     * @param string $key a property name
     * @param string $value the value, if required by the condition
     * @param string $value2 the second value, if required by the condition
     * @param array $parameters the parameter array, if required by the condition
     */
    public function __construct($type, $key, $value = null, $value2 = null, $parameters = [])
    {
        $this->type = $type;
        $this->key = $key;
        $this->value = $value;
        $this->value2 = $value2;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getValue2()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function render($alias = null)
    {
        $property_prefix = $alias ? "{$alias}." : '';

        switch($this->type) {
            case self::EQUALS:
                return "{$property_prefix}{$this->key} = {$this->generateParameterName()}";
                break;
            case self::NOT_EQUALS:
                return "{$property_prefix}{$this->key} <> {$this->generateParameterName()}";
                break;
            case self::LESS_THAN:
                return "{$property_prefix}{$this->key} < {$this->generateParameterName()}";
                break;
            case self::LESS_THAN_OR_EQUALS:
                return "{$property_prefix}{$this->key} <= {$this->generateParameterName()}";
                break;
            case self::GREATER_THAN:
                return "{$property_prefix}{$this->key} > {$this->generateParameterName()}";
                break;
            case self::GREATER_THAN_OR_EQUALS:
                return "{$property_prefix}{$this->key} >= {$this->generateParameterName()}";
                break;
            case self::BETWEEN:
            case self::NOT_BETWEEN:
                $is_it_not = ($this->type == self::NOT_BETWEEN) ? 'NOT' : '';
                return "{$property_prefix}{$this->key} {$is_it_not} BETWEEN {$this->generateParameterName(1)} AND {$this->generateParameterName(1)}";
                break;
            case self::IN:
            case self::NOT_IN:
                $parameter_names = array_map(function($index) {
                    return $this->generateParameterName($index);
                }, range(1, count($this->value)));
                $is_it_not = ($this->type == self::NOT_IN) ? 'NOT' : '';
                return "{$property_prefix}{$this->key} {$is_it_not} IN (" . implode(', ', $parameter_names) . ')';
                break;
            case self::LIKE:
            case self::NOT_LIKE:
                $is_it_not = ($this->type == self::NOT_LIKE) ? 'NOT' : '';
                return "{$property_prefix}{$this->key} {$is_it_not} LIKE {$this->generateParameterName()}";
                break;
            case self::IS_NULL:
                return "{$property_prefix}{$this->key} IS NULL";
                break;
            case self::IS_NOT_NULL:
                return "{$property_prefix}{$this->key} IS NOT NULL";
                break;
            case self::RAW:
                return $this->key;
                break;
            default:
                throw new DenOrmException("Unsupported condition type: {$this->type}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        if ($this->type == self::RAW) {
            return $this->parameters;
        }
        elseif ( in_array($this->type, [self::IN, self::NOT_IN]) ) {
            $parameters = [];
            $i = 0;
            foreach($this->value as $value) {
                $parameters[$this->generateParameterName(++$i)] = $value;
            }
            return $parameters;
        } elseif (is_null($this->value2)) {
            return [ $this->generateParameterName() => $this->value ];
        } else {
            return [
                $this->generateParameterName(1) => $this->value,
                $this->generateParameterName(2) => $this->value2,
            ];
        }
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
        return CompositeCondition::combine($conditions_to_be_merged, $operator);
    }

    private function generateParameterName($index = null)
    {
        return ':' . $this->key . ( is_null($index) ? '' : ('__' . $index) );
    }
}
