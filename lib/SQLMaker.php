<?php
require_once 'SQLMaker/Select.php';
require_once 'SQLMaker/Condition.php';
require_once 'SQLMaker/Util.php';


class SQLMaker
{
    public $quote_char   = null;
    public $name_sep     = '.';
    public $new_line     = "\n";
    public $driver       = null;
    public $select_class = null;

    function __construct ($driver, $args=array( ))
    {
        if ( !isset($driver) ) {
            trigger_error("'driver' is required", E_USER_ERROR);
        }
        $this->driver = $driver;

        if ( !isset($args['quote_char']) ) {
            $args['quote_char'] = $driver == 'mysql' ? '`' : '"';
        }

        $args['select_class'] = 'SQLMakerSelect';

        foreach (get_object_vars($this) as $property => $v) {
            if ( isset($args[$property]) ) {
                $this->$property = $args[$property];
            }
        }
    }

    function new_condition ($args=array( ))
    {
        return new SQLMakerCondition( array_merge( array(
            'quote_char' => $this->quote_char,
            'name_sep'   => $this->name_sep,
        ), $args) );
    }

    function new_select ($args=array( ))
    {
        return new $this->select_class( array_merge( array(
            'name_sep'   => $this->name_sep,
            'quote_char' => $this->quote_char,
            'new_line'   => $this->new_line,
        ), $args) );
    }

    function insert ($table, $values, $opt=array( ))
    {
        $prefix = isset($opt['prefix']) ? $opt['prefix'] : 'INSERT';

        $quoted_table = $this->quote($table);

        $columns        = array( );
        $bind_columns   = array( );
        $quoted_columns = array( );
        foreach ($values as $col => $val) {
            $quoted_columns[ ] = $this->quote($col);

            if (SQLMakerUtil::ref($val) == 'HASH' && isset($val['inject'])) {
                # $builder->insert('foo', array('created_on' => array('inject' => 'NOW( )')));
                $columns[ ] = $val['inject'];
            }
            else {
                $columns[ ] = '?';
                $bind_columns[ ] = $val;
            }
        }

        $sql  = "{$prefix} INTO {$quoted_table}" . $this->new_line;
        $sql .= '(' . implode(', ', $quoted_columns) . ')' . $this->new_line;
        $sql .= 'VALUES (' . implode(', ', $columns) . ')';

        return array($sql, $bind_columns);
    }

    protected function quote ($label)
    {
        return SQLMakerUtil::quote_identifier(
            $label, $this->quote_char, $this->name_sep
        );
    }

    function delete ($table, $where=array( ))
    {
        $w = $this->make_where_clause($where);
        $quoted_table = $this->quote($table);
        $sql = "DELETE FROM {$quoted_table}{$w[0]}";

        return array($sql, $w[1]);
    }

    function update ($table, $args=array( ), $where=array( ))
    {
        $columns      = array( );
        $bind_columns = array( );

        foreach ($args as $col => $val) {
            $quoted_col = $this->quote($col);

            if (SQLMakerUtil::ref($val) == 'HASH' && isset($val['inject'])) {
                # $builder->insert('foo', array('created_on' => array('inject' => 'NOW( )')));
                $columns[ ] = "{$quoted_col} = {$val['inject']}";
            }
            else {
                $columns[ ] = "{$quoted_col} = ?";
                $bind_columns[ ] = $val;
            }
        }

        $w = $this->make_where_clause($where);
        $bind_columns = array_merge($bind_columns, $w[1]);

        $quoted_table = $this->quote($table);
        $sql = "UPDATE {$quoted_table} SET " . implode(', ', $columns) . $w[0];

        return array($sql, $bind_columns);
    }

    protected function make_where_clause ($where)
    {
        $w = new SQLMakerCondition( array(
            'quote_char' => $this->quote_char,
            'name_sep'   => $this->name_sep,
        ) );

        $w->add($where);

        $sql = $w->as_sql( );

        return array($sql ? " WHERE {$sql}" : '', $w->bind);
    }

    function select ($table, $fields, $where=array( ), $opt=array( ))
    {
        $stmt = $this->select_query($table, $fields, $where, $opt);
        return array($stmt->as_sql( ), $stmt->bind( ));
    }

    function select_query ($table, $fields, $where=array( ), $opt=array( ))
    {
        if (SQLMakerUtil::ref($fields) != 'ARRAY') {
            trigger_error("SQLMaker::select_query: \$fields should be ARRAY", E_USER_ERROR);
        }

        $stmt = $this->new_select( array('select' => $fields) );
        $stmt->add_from($table);

        if ( isset($opt['prefix']) ) {
            $stmt->prefix($opt['prefix']);
        }

        if ( !empty($where) ) {
            $stmt->add_where($where);
        }

        if ( isset($opt['order_by']) ) {
            $o = $opt['order_by'];

            if (SQLMakerUtil::ref($o) == 'ARRAY') {
                foreach ($o as $order) {
                    $stmt->add_order_by($order);
                }
            }
            else {
                $stmt->add_order_by($o);
            }
        }

        if ( isset($opt['limit']) ) {
            $stmt->limit($opt['limit']);
        }

        if ( isset($opt['offset']) ) {
            $stmt->offset($opt['offset']);
        }

        if ( isset($opt['having']) ) {
            $stmt->add_having($opt['having']);
        }
        
        if ( isset($opt['for_update']) && $opt['for_update'] ) {
            $stmt->for_update(true);
        }

        return $stmt;
    }
}
