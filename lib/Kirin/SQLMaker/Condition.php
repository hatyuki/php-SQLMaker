<?php
require_once 'Kirin/SQLMaker/Util.php';


class KirinSQLMakerCondition
{
	public    $bind       = array( );
	protected $sql        = array( );
	protected $quote_char = '';
	protected $name_sep   = '.';

    protected function quote ($label)
    {
        return KirinSQLMakerUtil::quote_identifier(
            $label, $this->quote_char, $this->name_sep
        );
    }

	function __construct ($args=array( ))
	{
        foreach (get_object_vars($this) as $property => $v) {
            if ( isset($args[$property]) ) {
                $this->$property = $args[$property];
            }
        }
	}

	protected function make_term ($col, $val)
	{
        $ref_val = KirinSQLMakerUtil::ref($val);

        if ($ref_val == 'ARRAY') {
            # make_term( array('foo', array(1, 2, 3)) ) => foo IN (1, 2, 3)
            $term = $this->quote($col) . " IN (" . implode(', ', array_fill(0, sizeof($val), '?')) . ')';
            return array($term, $val);
        }
        else if ($ref_val == 'HASH') {
            list($op, $v) = each($val);
            $op = strtoupper($op);
            $ref_v = KirinSQLMakerUtil::ref($v);

            if ( ($op == 'IN' || $op == 'NOT IN') && $ref_v == 'ARRAY') {
                if (sizeof($v) == 0) {
                    if ($op == 'IN') {
                        # make_term('foo', array('in' => array( ))) => 0=1
                        return array('0=1', array( ));
                    }
                    else {
                        # make_term('foo', array('not in' => array( ))) => 1=1
                        return array('1=1', array( ));
                    }
                }
                else {
                    # make_term('foo', array('in' => array(1, 2, 3))) => foo IN (1, 2, 3)
                    $term = "{$this->quote($col)} {$op} (" . implode(', ', array_fill(0, sizeof($v), '?')) . ')';
                    return array($term, $v);
                }
            }
            # make_term('foo', array('and' => array(1, 2, 3))) => (foo = 1) AND (foo = 2) AND (foo = 3)
            else if ( ($op == 'OR' || $op == 'AND') && $ref_v == 'ARRAY') {
                $bind  = array( );
                $terms = array( );

                foreach ($v as $vv) {
                    list($t, $b) = $this->make_term($col, $vv);
                    $terms[ ] = "({$t})";
                    $bind = array_merge($bind, $b);
                }
                $term = implode(" {$op} ", $terms);
                return array($term, $bind);
            }
            # make_term('foo', array('in' => array('SELECT foo FROM bar WHERE baz = ?' => array(3))))
            #  => foo IN (SELECT foo FROM bar WHERE baz = ?)
            else if ( ($op == 'IN' || $op == 'NOT IN') && $ref_v == 'HASH') {
                list($t, $b) = each($v);
                $term = "{$this->quote($col)} {$op} ({$t})";
                return array($term, $b);
            }
            # make_term('foo', array('between' => array(1, 2))) => foo BETWEEN 1 AND 2
            else if ( ($op == 'BETWEEN' || $op == 'NOT BETWEEN') && $ref_v == 'ARRAY') {
                if (sizeof($v) != 2) {
                    trigger_error("USAGE: make_term('foo', array('between' => array(\$a, \$b)))", E_USER_ERROR);
                }
                $term = "{$this->quote($col)} {$op} ? AND ?";
                return array($term, $v);
            }
            else if ($op == 'INJECT') {
                # make_term('foo', array('inject' => array('!= ?' => array(3)))) => foo != 3
                if ($ref_v == 'HASH') {
                    list($t, $b) = each($v);
                    $term = "{$this->quote($col)} {$t}";
                    return array($term, $b);
                }
                # make_term('foo', array('inject' => "ILIKE 'toku%'")) => foo ILIKE 'toku%'
                else {
                    $term = "{$this->quote($col)} {$v}";
                    return array($term, array( ));
                }
            }
            # make_term('foo', array('>' => 3)) => foo BETWEEN 1 AND 2
            else {
                $term = "{$this->quote($col)} {$op} ?";
                return array($term, array($v));
            }
        }
        else {
            if ( isset($val) ) {
                # make_term('foo', true) => foo IS TRUE
                if ( is_bool($val) ) {
                    $term = "{$this->quote($col)} IS " . ($val ? 'TRUE' : 'FALSE');
                    return array($term, array( ));
                }
                # make_term('foo', 3) => foo = 3;
                else {
                    $term = "{$this->quote($col)} = ?";
                    return array($term, array($val));
                }
            }
            # make_term('foo', null) => foo IS NULL
            else {
                $term = "{$this->quote($col)} IS NULL";
                return array($term, array( ));
            }
        }
	}

    function add ($value, $option=array( ))
    {
        if ( !is_array($value) ) {
            $value = array($value => $option);
        }

        foreach ($value as $col => $val) {
            list($term, $bind) = $this->make_term($col, $val);
            $this->sql[ ] = "({$term})";
            $this->bind   = array_merge($this->bind, $bind);
        }

        return $this;
    }

    function compose_and ($other)
    {
        return new KirinSQLMakerCondition( array(
            'sql'  => array("({$this->as_sql( )}) AND ({$other->as_sql( )})"),
            'bind' => array_merge($this->bind, $other->bind),
        ) );
    }

    function compose_or ($other)
    {
        return new KirinSQLMakerCondition( array(
            'sql'  => array("({$this->as_sql( )}) OR ({$other->as_sql( )})"),
            'bind' => array_merge($this->bind, $other->bind),
        ) );
    }

    function as_sql     ( ) { return implode(' AND ', $this->sql); }
    function __toString ( ) { return $this->as_sql( ); }
}
