#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker/Select.php';


function prefix ($t)
{
    do {
        $stmt = _ns(array('quote_char' => '`', 'name_sep' => '.'));
        $stmt->add_select('*');
        $stmt->add_from('foo');
        $t->is($stmt->as_sql( ), "SELECT *\nFROM `foo`");
    } while (false);

    do {
        $stmt = _ns(array('quote_char' => '`', 'name_sep' => '.'));
        $stmt->prefix('SELECT SQL_CALC_FOUND_ROWS');
        $stmt->add_select('*');
        $stmt->add_from('foo');
        $t->is($stmt->as_sql( ), "SELECT SQL_CALC_FOUND_ROWS *\nFROM `foo`");
    } while (false);
}

function from ($t)
{
    do {
        $stmt = _ns(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $stmt->add_from('foo');
        $t->is($stmt->as_sql( ), "FROM foo");
    } while (false);

    do {
        $stmt = _ns(array('quote_char' => '`', 'name_sep' => '.', 'new_line' => ' '));
        $stmt->add_from('foo');
        $stmt->add_from('bar');
        $t->is($stmt->as_sql( ), "FROM `foo`, `bar`");
    } while (false);

    do {
        $stmt = _ns(array('quote_char' => '`', 'name_sep' => '.', 'new_line' => ' '));
        $stmt->add_from( array('foo' => 'f') );
        $stmt->add_from('bar', 'b');
        $t->is($stmt->as_sql( ), "FROM `foo` `f`, `bar` `b`");
    } while (false);

    do {
        $stmt = _ns(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $stmt->add_from( array('foo', 'f') );
        $stmt->add_from('bar', 'b');
        $t->is($stmt->as_sql( ), "FROM foo f, bar b");
    } while (false);
}

function _ns ($args=array( ))
{
    return new SQLMakerSelect($args);
}

done_testing( );
