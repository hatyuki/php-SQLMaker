#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker/Select.php';


function synopsis ($t)
{
    $sql = _ns( )
        ->add_select('foo')
        ->add_select('bar')
        ->add_select('baz')
        ->add_from('table_name')
        ->as_sql( );

    $t->is($sql, 'SELECT foo, bar, baz FROM table_name');
}

function add_join ($t)
{
    $sql = _ns( )
        ->add_join('user', array(
            'type'      => 'inner',
            'table'     => 'config',
            'condition' => 'user.user_id = config.user_id',
        ) )->as_sql( );

    $t->is($sql, 'FROM user INNER JOIN config ON user.user_id = config.user_id');
}

function add_join_using ($t)
{
    $sql = _ns( )
        ->add_select('name')
        ->add_join('user', array(
            'type'      => 'inner',
            'table'     => 'config',
            'condition' => array('user_id'),
        ) )->as_sql( );

    $t->is($sql, 'SELECT name FROM user INNER JOIN config USING (user_id)');
}

function subquery ($t)
{
    $subquery = _ns( )
        ->add_select('*')
        ->add_from('foo')
        ->add_where( array('hoge' => 'fuga') );

    $sql = _ns( )
        ->add_join(
            array($subquery, 'bar'),
            array(
                'type'      => 'inner',
                'table'     => 'baz',
                'alias'     => 'b1',
                'condition' => 'bar.baz_id = b1.baz_id'
            )
        )->as_sql( );

    $t->is($sql, 'FROM (SELECT * FROM foo WHERE (hoge = ?)) bar INNER JOIN baz b1 ON bar.baz_id = b1.baz_id');
}

function index_hint ($t)
{
    $sql = _ns( )
        ->add_select('name')
        ->add_from('user')
        ->add_index_hint('user', array('type' => 'USE', 'list' => array('index_hint')))
        ->as_sql( );

    $t->is($sql, 'SELECT name FROM user USE INDEX (index_hint)');
}

function add_where ($t)
{
    $sql = _ns( )
        ->add_select('c')
        ->add_from('foo')
        ->add_where( array(
            'name' => 'john',
            'type' => array(1, 2, 3),
        ) )->as_sql( );

    $t->is($sql, 'SELECT c FROM foo WHERE (name = ?) AND (type IN (?, ?, ?))');
}

function set_where ($t)
{
    $cond1 = new SQLMakerCondition( );
    $cond1->add( array('name' => 'john') );
    $cond2 = new SQLMakerCondition( );
    $cond2->add( array('type' => array('in' => array(1, 2, 3))) );
    $sql = _ns( )
        ->add_select('c')
        ->add_from('foo')
        ->set_where( $cond1->compose_and($cond2) )
        ->as_sql( );

    $t->is($sql, 'SELECT c FROM foo WHERE ((name = ?)) AND ((type IN (?, ?, ?)))');
}

function add_order_by ($t)
{
    $sql = _ns( )
        ->add_select('c')
        ->add_from('foo')
        ->add_order_by( array('name' => 'DESC') )
        ->add_order_by('id')
        ->as_sql( );

    $t->is($sql, 'SELECT c FROM foo ORDER BY name DESC, id');
}

function add_group_by ($t)
{
    $sql = _ns( )
        ->add_select('c')
        ->add_from('foo')
        ->add_group_by('id')
        ->as_sql( );

    $t->is($sql, 'SELECT c FROM foo GROUP BY id');
}

function add_group_by_with_order ($t)
{
    $sql = _ns( )
        ->add_select('c')
        ->add_from('foo')
        ->add_group_by( array('id' => 'DESC') )
        ->as_sql( );

    $t->is($sql, 'SELECT c FROM foo GROUP BY id DESC');
}

function add_having ($t)
{
    $sql = _ns( )
        ->add_from('foo')
        ->add_select( array('COUNT(*)' => 'cnt') )
        ->add_having( array('cnt' => 2) )
        ->as_sql( );

    $t->is($sql, 'SELECT COUNT(*) AS cnt FROM foo HAVING (COUNT(*) = ?)');
}

function _ns ( )
{
    return new SQLMakerSelect( array(
        'new_line' => ' ',
    ) );
}

done_testing( );
