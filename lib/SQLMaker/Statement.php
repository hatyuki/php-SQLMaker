<?php

class mark
{
    static function raw ($value, $args=array( ))
    {
        return new SQLMakerStatement($value, $args);
    }
}

class SQLMakerStatement
{
    public    $bind = array( );
    protected $stmt = null;

    function __construct ($stmt, $bind=array( ))
    {
        $this->stmt = $stmt;
        $this->bind = is_array($bind) ? $bind : array($bind);
    }

    function statement( )
    {
        if ( method_exists($this->stmt, 'as_sql') ) {
            return $this->stmt->as_sql( );
        }

        return $this->stmt;
    }

    function bind ( )
    {
        if ( method_exists($this->stmt, 'bind') ) {
            return $this->stmt->bind( );
        }

        return $this->bind;
    }

    function __toString ( )
    {
        return $this->statement( );
    }
}
