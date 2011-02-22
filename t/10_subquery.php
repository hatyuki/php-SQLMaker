#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker.php';


function select_subquery ($t)
{
    $builder = new SQLMaker('sqlite', array('quote_char' => '', 'new_line' => ' '));

    do {
        $stmt1 = $builder->select_query(
            'sakura',
            array('hoge', 'fuga'),
            array(
                'fuga' => 'piyo',
                'zun'  => 'doko',
            )
        );

        $t->is($stmt1->as_sql( ), 'SELECT hoge, fuga FROM sakura WHERE (fuga = ?) AND (zun = ?)');
        $t->is(implode(',', $stmt1->bind( )), 'piyo,doko');
    } while (false);

    do {
        $stmt2 = $builder->select_query(
            array($stmt1, 'stmt1'),
            array('foo', 'bar'),
            array(
                'bar'  => 'baz',
                'john' => 'man',
            )
        );

        $t->is($stmt2->as_sql( ), 'SELECT foo, bar FROM (SELECT hoge, fuga FROM sakura WHERE (fuga = ?) AND (zun = ?)) stmt1 WHERE (bar = ?) AND (john = ?)');
        $t->is(implode(',', $stmt2->bind( )), 'piyo,doko,baz,man');
    } while (false);

    do {
        $stmt3 = $builder->select_query(
            array($stmt2, 'stmt2'),
            array('baz'),
            array('baz' => 'bar'),
            array('order_by' => 'yo')
        );
        $t->is($stmt3->as_sql( ), 'SELECT baz FROM (SELECT foo, bar FROM (SELECT hoge, fuga FROM sakura WHERE (fuga = ?) AND (zun = ?)) stmt1 WHERE (bar = ?) AND (john = ?)) stmt2 WHERE (baz = ?) ORDER BY yo');
        $t->is(implode(',', $stmt3->bind( )), 'piyo,doko,baz,man,bar');
    } while (false);

    do {
        $stmt = $builder->new_select( );
        $stmt->add_select('id');
        $stmt->add_where('foo', 'bar');
        $stmt->add_from($stmt, 'itself');

        $t->is($stmt->as_sql( ), 'SELECT id FROM (SELECT id FROM  WHERE (foo = ?)) itself WHERE (foo = ?)');
        $t->is(implode(',', $stmt->bind( )), 'bar,bar');
    } while (false);
}

function subquery_and_join ($t)
{
    $subquery = new SQLMakerSelect(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
    $subquery->add_select('*');
    $subquery->add_from('foo');
    $subquery->add_where('hoge', 'fuga');

    $stmt = new SQLMakerSelect(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
    $stmt->add_join(array($subquery, 'bar'), array(
        'type'      => 'inner',
        'table'     => 'baz',
        'alias'     => 'b1',
        'condition' => 'bar.baz_id = b1.baz_id',
    ) );

    $t->is($stmt->as_sql( ), 'FROM (SELECT * FROM foo WHERE (hoge = ?)) bar INNER JOIN baz b1 ON bar.baz_id = b1.baz_id');
    $t->is(implode(',', $stmt->bind( )), 'fuga');
}

function complex ($t)
{
    $s1 = new SQLMakerSelect(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
    $s1->add_select('*');
    $s1->add_from('foo');
    $s1->add_where('hoge', 'fuga');

    $s2 = new SQLMakerSelect(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
    $s2->add_select('*');
    $s2->add_from( array($s1, 'f') );
    $s2->add_where(array('piyo' => 'puyo'));

    $stmt = new SQLMakerSelect(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
    $stmt->add_join( array($s2, 'bar'), array(
        'type'      => 'inner',
        'table'     => 'baz',
        'alias'     => 'b1',
        'condition' => 'bar.baz_id = b1.baz_id',
    ) );

    $t->is($stmt->as_sql( ), 'FROM (SELECT * FROM (SELECT * FROM foo WHERE (hoge = ?)) f WHERE (piyo = ?)) bar INNER JOIN baz b1 ON bar.baz_id = b1.baz_id');
    $t->is(implode(',', $stmt->bind( )), 'fuga,puyo');
}

done_testing( );
