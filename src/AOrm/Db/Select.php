<?php

namespace AOrm\Db;

/**
 * A Select object represents a SQL SELECT statement. The different parts of the SELECT statement can be manipulated
 * via the magic getter properties listed below. Once all the parts are ready, the SELECT statement can be rendered
 * via the <code>render()</code> method.
 *
 * @property string$columns
 * @property string $from
 * @property string[] $joins
 * @property string[] $left_joins
 * @property string $condition
 * @property string $order
 * @property string $limit
 */
class Select
{
    /** A list of the allowed part names */
    const PART_NAMES = array(
        'columns',
        'from',
        'joins',
        'left_joins',
        'condition',
        'order',
        'limit'
    );

    /** @var array associative array with parts (allowed keys listed in PART_NAMES constant) */
    private $parts = array();

    /**
     * Creates a new Select instance, optionally with the parts initialised to values given in the $parts associative
     * array.
     * @param array $parts
     * @throws \ErrorException
     */
    public function __construct($parts = array())
    {
        foreach($parts as $name => $value) {
            $this->ensurePartNameValid($name);
        }
        $this->parts = $parts;
    }

    /**
     * Magic getter for accessing the parts of the Select
     * @param $name string the name of the part to retrieve
     * @return mixed the requested part
     * @throws \ErrorException if $name specifies an invalid part name
     */
    public function __get($name)
    {
        $this->ensurePartNameValid($name);
        return isset($this->parts[$name]) ? $this->parts[$name] : false;
    }

    /**
     * Magic setter for setting parts of the Select
     * @param $name string the name of the part to set
     * @param $value mixed the value to set
     * @throws \ErrorException if $name specifies an invalid part name
     */
    public function __set($name, $value)
    {
        $this->ensurePartNameValid($name);
        $this->parts[$name] = $value;
    }

    /**
     * Renders the SQL query string corresponding to this Select, optionally limited to only the given query parts.
     *
     * @param array|null $what_parts_to_render optionally a list of the query parts to be rendered
     * @return string the constructed SQL query string
     */
    public function render(array $what_parts_to_render = null)
    {
        $sql = '';

        if ( (!$what_parts_to_render || in_array('columns', $what_parts_to_render)) && !empty($this->parts['columns']) ) {
            $sql .= 'SELECT ' . $this->parts['columns'] . PHP_EOL;
        }

        if ( (!$what_parts_to_render || in_array('from', $what_parts_to_render)) && !empty($this->parts['from']) ) {
            $sql .= 'FROM ' . $this->parts['from'] . PHP_EOL;
        }

        if ( (!$what_parts_to_render || in_array('joins', $what_parts_to_render)) && !empty($this->parts['joins']) ) {
            $sql .= 'JOIN ' . implode(' JOIN ', $this->parts['joins']) . PHP_EOL;
        }

        if ( (!$what_parts_to_render || in_array('left_joins', $what_parts_to_render)) && !empty($this->parts['left_joins']) ) {
            $sql .= 'LEFT JOIN ' . implode(' LEFT JOIN ', $this->parts['left_joins']) . PHP_EOL;
        }

        if ( (!$what_parts_to_render || in_array('condition', $what_parts_to_render)) && !empty($this->parts['condition']) ) {
            $sql .= 'WHERE ' . $this->parts['condition'] . PHP_EOL;
        }

        if ( (!$what_parts_to_render || in_array('order', $what_parts_to_render)) && !empty($this->parts['order']) ) {
            $sql .= 'ORDER BY ' . $this->parts['order'] . PHP_EOL;
        }

        if ( (!$what_parts_to_render || in_array('limit', $what_parts_to_render)) && !empty($this->parts['limit']) ) {
            $sql .= 'LIMIT ' . $this->parts['limit'] . PHP_EOL;
        }

        return $sql;
    }

    /**
     * Merges this Select with the given other Select.
     * @param $another_select Select the Select to merge with
     * @return Select this Select instance
     * @throws \ErrorException if merging the two Selects doesn't make sense, e.g. both have a FROM part
     */
    public function merge(Select $another_select)
    {
        if (!$another_select) {
            return $this;
        }

        $merged_parts = array();

        $columns1 = isset($this->parts['columns']) ? $this->parts['columns'] : '';
        $columns2 = isset($another_select->parts['columns']) ? $another_select->parts['columns'] : '';
        if ($columns1) {
            $merged_parts['columns'] = $columns1;
        }
        if ($columns2) {
            $merged_parts['columns'] = $columns1 . ($columns1 ? ', ' : '') . $columns2;
        }

        if (isset($this->parts['from']) && isset($another_select->parts['from'])) {
            throw new \ErrorException("Both Selects specify a 'from' element. I don't know which one to choose...");
        }
        if (isset($this->parts['from'])) {
            $merged_parts['from'] = $this->parts['from'];
        } elseif (isset($this->parts['from'])) {
            $merged_parts['from'] = $another_select->parts['from'];
        }

        if (isset($this->parts['joins'])) {
            $merged_parts['joins'] = $this->parts['joins'];
        }
        if (isset($another_select->parts['joins'])) {
            $merged_parts['joins'] = array_merge(isset($this->parts['joins']) ? $this->parts['joins'] : array(), $another_select->parts['joins']);
        }

        if (isset($this->parts['left_joins'])) {
            $merged_parts['left_joins'] = $this->parts['left_joins'];
        }
        if (isset($another_select->parts['left_joins'])) {
            $merged_parts['left_joins'] = array_merge(isset($this->parts['left_joins']) ? $this->parts['left_joins'] : array(), $another_select->parts['left_joins']);
        }

        $condition1 = isset($this->parts['condition']) ? $this->parts['condition'] : '';
        $condition2 = isset($another_select->parts['condition']) ? $another_select->parts['condition'] : '';
        if ($condition1) {
            $merged_parts['condition'] = $condition1;
        }
        if ($condition2) {
            $merged_parts['condition'] = $condition1 ? ('(' . $condition1 . ') AND (' . $condition2 . ')') : $condition2;
        }

        $order1 = isset($this->parts['order']) ? $this->parts['order'] : '';
        $order2 = isset($another_select->parts['order']) ? $another_select->parts['order'] : '';
        if ($order1) {
            $merged_parts['order'] = $order1;
        }
        if ($order2) {
            $merged_parts['order'] = $order1 . ($order1 ? ', ' : '') . $order2;
        }

        $this->parts = $merged_parts;

        return $this;
    }

    /**
     * @return string the rendered SELECT statement
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Throws an exception if the given part name is invalid.
     * @param $part_name string
     * @throws \ErrorException if the given part name is invalid
     */
    private function ensurePartNameValid($part_name)
    {
        if (!in_array($part_name, self::PART_NAMES))
            throw new \ErrorException("Invalid part name: {$part_name}");
    }
}
