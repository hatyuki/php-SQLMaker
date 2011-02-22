<?php
require_once 'SQLMaker/Util.php';
require_once 'SQLMaker/Condition.php';


class SQLMakerSelect
{
    public    $quote_char         = '';
    public    $name_sep           = '.';
    public    $new_line           = "\n";
    protected $distinct           = false;
    protected $for_update         = false;
    protected $prefix             = 'SELECT';
    protected $select             = array( );
    protected $select_map         = array( );
    protected $select_map_reverse = array( );
    protected $from               = array( );
    protected $joins              = array( );
    protected $index_hint         = array( );
    protected $group_by           = array( );
    protected $order_by           = array( );
    protected $offset             = null;
    protected $limit              = null;
    protected $subqueries         = array( );
    protected $where              = null;
    protected $having             = null;

    function distinct ($value=false)
    {
        if ( isset($value) ) {
            $this->distinct = $value;
            return $this;
        }
        else {
            return $this->distinct;
        }
    }

    function for_update ($value=false)
    {
        if ( isset($value) ) {
            $this->for_update = $value;
            return $this;
        }
        else {
            return $this->for_update;
        }
    }

    function prefix ($value=false)
    {
        if ( isset($value) ) {
            $this->prefix = $value;
            return $this;
        }
        else {
            return $this->prefix;
        }
    }

    function offset ($value=null)
    {
        if ( isset($value) ) {
            $this->offset = $value;
            return $this;
        }
        else {
            return $this->offset;
        }
    }

    function limit ($value=null)
    {
        if ( isset($value) ) {
            $this->limit = $value;
            return $this;
        }
        else {
            return $this->limit;
        }
    }

    function __construct ($args=array( ))
    {
        foreach (get_object_vars($this) as $property => $v) {
            if ( isset($args[$property]) ) {
                $this->$property = $args[$property];
            }
        }
    }

    function new_condition ( )
    {
        return new SQLMakerCondition( array(
            'quote_char' => $this->quote_char,
            'name_sep'   => $this->name_sep,
        ) );
    }

    function bind ( )
    {
        $bind = array( );
        $bind = empty($this->subqueries) ? $bind : array_merge($bind, $this->subqueries);
        $bind = !isset($this->where)     ? $bind : array_merge($bind, $this->where->bind);
        $bind = !isset($this->having)    ? $bind : array_merge($bind, $this->having->bind);

        return $bind;
    }

    function add_select ($term, $col=null)
    {
        if (SQLMakerUtil::ref($term) == 'SCALAR') {
            $term = array($term => $col);
        }

        foreach ($term as $t => $c) {
            if ( is_int($t) ) {
                list($t, $c) = array($c, $c);
            }
            else if ( !isset($c) ) {
                $c = $t;
            }

            $this->select[ ] = $t;
            $this->select_map[$t] = $c;
            $this->select_map_reverse[$c] = $t;
        }

        return $this;
    }

    function add_from ($table, $alias=null)
    {
        $ref = SQLMakerUtil::ref($table);

        if ($ref == 'ARRAY') {
            list($table, $alias) = $table;
        }
        else if ($ref == 'HASH') {
            list($table, $alias) = each($table);
        }

        if ( is_object($table) && method_exists($table, 'as_sql') ) {
            $this->subqueries = array_merge($this->subqueries, $table->bind( ));
            $this->from[ ] = array("({$table->as_sql( )})", $alias);
        }
        else {
            $this->from[ ] = array($table, $alias);
        }

        return $this;
    }

    function add_join ($table, $joins)
    {
        $alias = null;
        if (SQLMakerUtil::ref($table) == 'ARRAY') {
            list($table, $alias) = $table;
        }

        if ( is_object($table) && method_exists($table, 'as_sql') ) {
            $this->subqueries = array_merge($this->subqueries, $table->bind( ));
            $table = "({$table->as_sql( )})";
        }

        $this->joins[ ] = array(
            'table' => array($table, $alias),
            'joins' => $joins,
        );

        return $this;
    }

    function add_index_hint ($table, $hint)
    {
        $this->index_hint[$table] = array(
            'type' => isset($hint['type']) ? $hint['type'] : 'USE',
            'list' => $hint['list'],
        );

        return $this;
    }

    protected function quote ($label)
    {
        return SQLMakerUtil::quote_identifier(
            $label, $this->quote_char, $this->name_sep
        );
    }

    protected function ref ($value)
    {
        return SQLMakerUtil::ref($value);
    }

    function as_sql ( )
    {
        $sql = '';
        $new_line = $this->new_line;

        if ( !empty($this->select) ) {
            $sql .= "{$this->prefix} ";

            // DISTINCT
            if ($this->distinct) {
                $sql .= 'DISTINCT ';
            }

            // SELECT columns
            $select = array( );
            foreach ($this->select as &$s) {
                if ( !isset($this->select_map[$s]) ) {
                    $select[ ] = $this->quote($s);
                }
                else {
                    $alias = $this->select_map[$s];
                    if ($alias && preg_match("/(:?^|\.)\Q$alias\E$/", $s)) {
                        $select[ ] = $this->quote($s);
                    }
                    else {
                        $select[ ] = "{$this->quote($s)} AS {$this->quote($alias)}";
                    }
                }
            }
            $sql .= implode(', ', $select) . $new_line;
        }

        $sql .= 'FROM ';

        if ( !empty($this->joins) ) {
            $initial_table_written = false;
            foreach ($this->joins as $j) {
                $table = $this->_add_index_hint($j['table']);
                $join  = $j['joins'];

                if ( !$initial_table_written ) {
                    $sql .= $table;
                    $initial_table_written = true;
                }

                $sql .= ' ' . strtoupper($join['type']) . " JOIN {$this->quote($join['table'])}";
                $sql .= isset($join['alias']) ? " {$this->quote($join['alias'])}" : '';

                if (SQLMakerUtil::ref($join['condition']) == 'ARRAY') {
                    $cond = array( );
                    foreach ($join['condition'] as &$c) {
                        $cond[ ] = $this->quote($c);
                    }
                    $sql .= ' USING (' . implode(', ', $cond) . ')';
                }
                else {
                    $sql .= " ON {$join['condition']}";
                }
            }

            $sql .= empty($this->from) ? '' : ', ';
        }

        if ( !empty($this->from) ) {
            $from = array( );
            foreach ($this->from as &$f) {
                $from[ ] = $this->_add_index_hint($f[0], $f[1]);
            }
            $sql .= implode(', ', $from);
        }

        $sql .= $new_line;

        $sql .= !isset($this->where)   ? '' : $this->as_sql_where( );
        $sql .= empty($this->group_by) ? '' : $this->as_sql_group_by( );
        $sql .= !isset($this->having)  ? '' : $this->as_sql_having( );
        $sql .= empty($this->order_by) ? '' : $this->as_sql_order_by( );
        $sql .= !isset($this->limit)   ? '' : $this->as_sql_limit( );
        $sql .= $this->as_sql_for_update( );

        $sql = preg_replace("/$new_line+$/", '', $sql);

        return $sql;
    }

    function as_sql_limit ( )
    {
        if ( !$this->limit ) {
            return '';
        }
        else if ( !is_numeric($this->limit) ) {
            trigger_error("Non-numerics in limit clause ({$this->limit})", E_USER_ERROR);
        }

        return sprintf(
            "LIMIT %d%s{$this->new_line}",
            $this->limit, ($this->offset ? ' OFFSET ' . intval($this->offset) : '')
        );
    }

    function add_order_by ($col, $type=null)
    {
        if ( !is_array($col) ) {
            $this->order_by[ ] = array($col, strtoupper($type));
        }
        else {
            foreach ($col as $c => $t) {
                $this->order_by[ ] = array($c, strtoupper($t));
            }
        }

        return $this;
    }

    function as_sql_order_by ( )
    {
        $attrs = $this->order_by;

        if ( empty($attrs) ) {
            return '';
        }

        $order = array( );
        foreach ($attrs as &$o) {
            list($col, $type) = $o;
            $order[ ] = $type ? "{$this->quote($col)} {$type}" : $this->quote($col);
        }

        return 'ORDER BY ' . implode(', ', $order) . $this->new_line;
    }

    function add_group_by ($group, $order=null)
    {
        $order = null;
        if (SQLMakerUtil::ref($group) == 'HASH') {
            list($group, $order) = each($group);
        }

        $this->group_by[ ] = $order
                           ? $this->quote($group) . ' ' . strtoupper($order)
                           : $this->quote($group);
        return $this;
    }

    function as_sql_group_by ( )
    {
        $elems = $this->group_by;

        if ( empty($elems) ) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', $elems) . $this->new_line;
    }

    function set_where ($where)
    {
        $this->where = $where;
        return $this;
    }

    function add_where ($col, $val=null)
    {
        if ( !isset($this->where) ) {
            $this->where = $this->new_condition( );
        }
        $this->where->add($col, $val);

        return $this;
    }

    function as_sql_where ( )
    {
        $where = $this->where->as_sql( );
        return $where ? "WHERE {$where}{$this->new_line}" : '';
    }

    function as_sql_having ( )
    {
        return isset($this->having) ? "HAVING {$this->having->as_sql( )}{$this->new_line}" : '';
    }

    function add_having ($col, $val=null)
    {
        if (SQLMakerUtil::ref($col) == 'HASH') {
            list($col, $val) = each($col);
        }

        if ( isset($this->select_map_reverse[$col]) ) {
            $col = $this->select_map_reverse[$col];
        }

        if ( !isset($this->having) ) {
            $this->having = $this->new_condition( );
        }

        $this->having->add($col, $val);

        return $this;
    }

    function as_sql_for_update ( )
    {
        return $this->for_update ? ' FOR UPDATE' : '';
    }

    protected function _add_index_hint ($table, $alias=null)
    {
        if (SQLMakerUtil::ref($table) == 'ARRAY') {
            list($table, $alias) = $table;
        }

        $quoted = empty($alias) ? $this->quote($table): "{$this->quote($table)} {$this->quote($alias)}";

        if ( !isset($this->index_hint[$table]) ) {
            return $quoted;
        }

        $hint = $this->index_hint[$table];
        if (SQLMakerUtil::ref($hint['list']) == 'ARRAY' && !empty($hint['list'])) {
            $list = array( );
            foreach ($hint['list'] as &$l) {
                $list[ ] = $l;
            }

            return $quoted . ' ' . (isset($hint['type']) ? strtoupper($hint['type']) : 'USE') .
                ' INDEX (' .  implode(', ', $list) . ')';
        }

        return $quoted;
    }
}
