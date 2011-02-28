<?php
require_once 'SQLMaker/Util.php';


class SQLMakerSelectSet
{
    public    $quote_char = '';
    public    $name_sep   = '.';
    public    $new_line   = "\n";
    protected $statements = array( );
    protected $order_by   = array( );
    protected $operator   = null;


    static function union ( )
    {
        return self::select_set('UNION', func_get_args( ));
    }

    static function union_all ( )
    {
        return self::select_set('UNION ALL', func_get_args( ));
    }

    static function intersect ( )
    {
        return self::select_set('INTERSECT', func_get_args( ));
    }

    static function intersect_all ( )
    {
        return self::select_set('INTERSECT ALL', func_get_args( ));
    }

    static function except ( )
    {
        return self::select_set('EXCEPT', func_get_args( ));
    }

    static function except_all ( )
    {
        return self::select_set('EXCEPT ALL', func_get_args( ));
    }

    static protected function select_set ($operator, $args)
    {
        $options = array( );

        if ( is_a($args[0], 'SQLMakerSelect') || is_a($args[0], 'SQLMakerSelectSet')) {
            $options['quote_char'] = $args[0]->quote_char;
            $options['name_sep']   = $args[0]->name_sep;
            $options['new_line']   = $args[0]->new_line;
        }

        $stmt = new SQLMakerSelectSet($operator, $options);
        foreach ($args as $arg) {
            $stmt->add_statement($arg);
        }

        return $stmt;
    }

    function __construct ($operator, $args=array( ))
    {
        $args['operator'] = $operator;

        foreach (get_object_vars($this) as $property => $v) {
            if ( isset($args[$property]) ) {
                $this->$property = $args[$property];
            }
        }
    }

    function add_statement ($statement)
    {
        if ( !is_object($statement) || !method_exists($statement, 'as_sql') ) {
            trigger_error("'\$statement' does not have 'as_sql' method.");
        }

        $this->statements[ ] = $statement;
        return $this;
    }

    function as_sql_order_by ( )
    {
        $attrs = $this->order_by;

        if ( empty($attrs) ) return '';

        $order_by = array( );
        foreach ($attrs as $attr) {
            list($col, $type) = $attr;
            $order_by[ ] = $type ? $this->quote($col) . " $type" : $this->quote($col);
        }

        return 'ORDER BY ' . implode(', ', $order_by);
    }

    function as_sql ( )
    {
        $new_line = $this->new_line;
        $operator = $this->operator;

        $stmt = array( );
        foreach ($this->statements as $statement) {
            $stmt [ ] = $statement->as_sql( );
        }

        $sql = implode($new_line . $operator . $new_line, $stmt);

        if ( !empty($this->order_by) ) {
            $sql .= ' ' . $this->as_sql_order_by( );
        }

        return $sql;
    }

    function bind ( )
    {
        $binds = array( );

        foreach ($this->statements as $select) {
            $binds = array_merge($binds, $select->bind( ));
        }

        return $binds;
    }

    function add_order_by ($col, $type=null)
    {
        $this->order_by[ ] = array($col, $type);
        return $this;
    }

    protected function quote ($label)
    {
        if ( is_a($label, 'SQLMakerRawString') ) {
            return $label->raw_string( );
        }

        return SQLMakerUtil::quote_identifier(
            $label, $this->quote_char, $this->name_sep
        );
    }
}
