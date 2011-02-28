#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker.php';
require_once 'SQLMaker/Statement.php';


function insert_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite');
    list($sql, $binds) = $builder->insert('foo', array('bar' => 'baz', 'john' => 'man'));
    $t->is($sql, "INSERT INTO \"foo\"\n(\"bar\", \"john\")\nVALUES (?, ?)");
    $t->is(implode(',', $binds), 'baz,man');
}

function insert_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'new_line' => ' ') );
    list($sql, $binds) = $builder->insert('foo', array('bar' => 'baz', 'john' => 'man'));
    $t->is($sql, 'INSERT INTO foo (bar, john) VALUES (?, ?)');
    $t->is(implode(',', $binds), 'baz,man');
}

function delete_simple_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite');
    list($sql, $binds) = $builder->delete('foo', array('bar' => 'baz', 'john' => 'man'));
    $t->is($sql, 'DELETE FROM "foo" WHERE ("bar" = ?) AND ("john" = ?)');
    $t->is(implode(',', $binds), 'baz,man');
}

function delete_simple_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'new_line' => ' '));
    list($sql, $binds) = $builder->delete('foo', array('bar' => 'baz', 'john' => 'man'));
    $t->is($sql, 'DELETE FROM foo WHERE (bar = ?) AND (john = ?)');
    $t->is(implode(',', $binds), 'baz,man');
}

function delete_all_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite');
    list($sql, $binds) = $builder->delete('foo');
    $t->is($sql, 'DELETE FROM "foo"');
    $t->is(implode(',', $binds), '');
}

function delete_all_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'new_line' => ' '));
    list($sql, $binds) = $builder->delete('foo');
    $t->is($sql, 'DELETE FROM foo');
    $t->is(implode(',', $binds), '');
}

function update_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite');

    do {
        list($sql, $binds) = $builder->update('user', array('name' => 'john', 'email' => 'john@example.com'), array('user_id' => 3));
        $t->is($sql, 'UPDATE "user" SET "name" = ?, "email" = ? WHERE ("user_id" = ?)');
        $t->is(implode(',', $binds), 'john,john@example.com,3');
    } while (false);

    do {
        list($sql, $binds) = $builder->update('foo', array('bar' => 'baz', 'john' => 'man'), array('yo' => 'king'));
        $t->is($sql, 'UPDATE "foo" SET "bar" = ?, "john" = ? WHERE ("yo" = ?)');
        $t->is(implode(',', $binds), 'baz,man,king');
    } while (false);

    do {
        list($sql, $binds) = $builder->update('foo', array('bar' => 'baz', 'john' => 'man'));
        $t->is($sql, 'UPDATE "foo" SET "bar" = ?, "john" = ?');
        $t->is(implode(',', $binds), 'baz,man');
    } while (false);

    do {
        list($sql, $binds) = $builder->update('foo', array('created_at' => mark::raw('NOW( )')));
        $t->is($sql, 'UPDATE "foo" SET "created_at" = NOW( )');
        $t->is(implode(',', $binds), '');
    } while (false);
}

function update_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'new_line' => ' '));

    do {
        list($sql, $binds) = $builder->update('user', array('name' => 'john', 'email' => 'john@example.com'), array('user_id' => 3));
        $t->is($sql, 'UPDATE user SET name = ?, email = ? WHERE (user_id = ?)');
        $t->is(implode(',', $binds), 'john,john@example.com,3');
    } while (false);

    do {
        list($sql, $binds) = $builder->update('foo', array('bar' => 'baz', 'john' => 'man'), array('yo' => 'king'));
        $t->is($sql, 'UPDATE foo SET bar = ?, john = ? WHERE (yo = ?)');
        $t->is(implode(',', $binds), 'baz,man,king');
    } while (false);

    do {
        list($sql, $binds) = $builder->update('foo', array('bar' => 'baz', 'john' => 'man'));
        $t->is($sql, 'UPDATE foo SET bar = ?, john = ?');
        $t->is(implode(',', $binds), 'baz,man');
    } while (false);
}

function select_query_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite');

    $stmt = $builder->select_query('foo', array('foo', 'bar'), array('bar' => 'baz', 'john' => 'man'), array('order_by' => 'yo'));
    $t->is($stmt->as_sql( ), "SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)\nORDER BY \"yo\"");
    $t->is(implode(',', $stmt->bind( )), 'baz,man');
}

function select_query_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'new_line' => ' '));

    $stmt = $builder->select_query('foo', array('foo', 'bar'), array('bar' => 'baz', 'john' => 'man'), array('order_by' => 'yo'));
    $t->is($stmt->as_sql( ), 'SELECT foo, bar FROM foo WHERE (bar = ?) AND (john = ?) ORDER BY yo');
    $t->is(implode(',', $stmt->bind( )), 'baz,man');
}

function new_select_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite', array('quote_char' => '`', 'name_sep' => '.'));
    $select  = $builder->new_select( );
    $t->isa_ok($select, 'SQLMakerSelect');
    $t->is($select->quote_char, '`');
    $t->is($select->name_sep, '.');
    $t->is($select->new_line, "\n");
}

function new_select_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
    $select  = $builder->new_select( );
    $t->isa_ok($select, 'SQLMakerSelect');
    $t->is($select->quote_char, '');
    $t->is($select->name_sep, '.');
    $t->is($select->new_line, " ");
}

function select_driver_sqlite ($t)
{
    $builder = new SQLMaker('sqlite', array('new_line' => ' '));

    do {
        list($sql, $binds) = $builder->select('foo', array('foo', 'bar'), array('bar' => 'baz', 'john' => 'man'), array('order_by' => 'yo'));
        $t->is($sql, 'SELECT "foo", "bar" FROM "foo" WHERE ("bar" = ?) AND ("john" = ?) ORDER BY "yo"');
        $t->is(implode(',', $binds), 'baz,man');
    } while (false);

    do {
        list($sql, $binds) = $builder->select('foo', array('foo', 'bar'), array('bar' => 'baz', 'john' => 'man'), array('order_by' => 'yo', 'limit' => 1, 'offset' => 3));
        $t->is($sql, 'SELECT "foo", "bar" FROM "foo" WHERE ("bar" = ?) AND ("john" = ?) ORDER BY "yo" LIMIT 1 OFFSET 3');
        $t->is(implode(',', $binds), 'baz,man');
    } while (false);

    do {
        list($sql, $binds) = $builder->select('foo', array('foo', 'bar'), array( ), array('prefix' => 'SELECT SQL_CALC_FOUND_ROWS'));
        $t->is($sql, 'SELECT SQL_CALC_FOUND_ROWS "foo", "bar" FROM "foo"');
        $t->is(implode(',', $binds), '');
    } while (false);
}

function order_by_driver_mysql ($t)
{
    $builder = new SQLMaker('mysql', array('quote_char' => '', 'new_line' => ' '));

    do {
        list($sql, $binds) = $builder->select('foo', array('*'), null, array('order_by' => 'yo'));
        $t->is($sql, 'SELECT * FROM foo ORDER BY yo');
        $t->is(implode(',', $binds), '');
    } while (false);

    do {
        list($sql, $binds) = $builder->select('foo', array('*'), array( ), array('order_by' => array('yo', 'ya')));
        $t->is($sql, 'SELECT * FROM foo ORDER BY yo, ya');
        $t->is(implode(',', $binds), '');
    } while (false);

    do {
        list($sql, $binds) = $builder->select('foo', array('*'), array( ), array('order_by' => array('yo' => 'desc', 'ya' => 'asc')));
        $t->is($sql, 'SELECT * FROM foo ORDER BY yo DESC, ya ASC');
        $t->is(implode(',', $binds), '');
    } while (false);
}

done_testing( );
