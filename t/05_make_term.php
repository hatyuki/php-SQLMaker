#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker/Condition.php';
require_once 'SQLMaker/Statement.php';


function test ($t)
{
    $test_case = array(
        array(  # 1
            'in'    => array('foo' => 'bar'),
            'query' => '`foo` = ?',
            'bind'  => array('bar'),
        ),
        array(  # 3
            'in'    => array('foo' => array('bar', 'baz')),
            'query' => '`foo` IN (?, ?)',
            'bind'  => array('bar', 'baz'),
        ),
        array(  # 5
            'in'    => array('foo' => array('in' => array('bar', 'baz'))),
            'query' => '`foo` IN (?, ?)',
            'bind'  => array('bar', 'baz'),
        ),
        array(  # 7
            'in'    => array('foo' => array('not in' => array('bar', 'baz'))),
            'query' => '`foo` NOT IN (?, ?)',
            'bind'  => array('bar', 'baz'),
        ),
        array(  # 9
            'in'    => array('foo' => array('!=' => 'bar')),
            'query' => '`foo` != ?',
            'bind'  => array('bar'),
        ),
        array(  # 11
            'in'    => array('foo' => mark::raw('IS NOT NULL')),
            'query' => '`foo` IS NOT NULL',
            'bind'  => array( ),
        ),
        array(  # 13
            'in'    => array('foo' => array('between' => array(1, 2))),
            'query' => '`foo` BETWEEN ? AND ?',
            'bind'  => array(1, 2),
        ),
        array(  # 15
            'in'    => array('foo' => array('like' => 'neko%')),
            'query' => '`foo` LIKE ?',
            'bind'  => array('neko%'),
        ),
        array(  # 17
            'in'    => array('foo' => array('or' => array( array('>' => 'bar'), array('<' => 'baz') ))),
            'query' => '(`foo` > ?) OR (`foo` < ?)',
            'bind'  => array('bar', 'baz'),
        ),
        array(  # 19
            'in'    => array('foo' => array('and' => array('foo', 'bar', 'baz'))),
            'query' => '(`foo` = ?) AND (`foo` = ?) AND (`foo` = ?)',
            'bind'  => array('foo', 'bar', 'baz'),
        ),
        array(  # 21
            'in'    => array('foo_id' => mark::raw('IN (SELECT foo_id FROM bar WHERE t = ?)', array(44))),
            'query' => '`foo_id` IN (SELECT foo_id FROM bar WHERE t = ?)',
            'bind'  => array(44),
        ),
        array(  # 23
            'in'    => array('foo_id' => array('in' => array('SELECT foo_id FROM bar WHERE t = ?' => array(44)))),
            'query' => '`foo_id` IN (SELECT foo_id FROM bar WHERE t = ?)',
            'bind'  => array(44),
        ),
        array(  # 25
            'in'    => array('foo_id' => mark::raw('MATCH (col1, col2) AGAINST (?)', array('apples'))),
            'query' => '`foo_id` MATCH (col1, col2) AGAINST (?)',
            'bind'  => array('apples'),
        ),
        array(  # 27
            'in'    => array('foo_id' => null),
            'query' => '`foo_id` IS NULL',
            'bind'  => array( ),
        ),
        array(  # 29
            'in'    => array('foo_id' => array('in' => array( ))),
            'query' => '0=1',
            'bind'  => array( ),
        ),
        array(  # 31
            'in'    => array('foo_id' => array('not in' => array( ))),
            'query' => '1=1',
            'bind'  => array( ),
        ),
        ## EX
        array(  # 33
            'in'    => array('foo' => true),
            'query' => '`foo` IS TRUE',
            'bind'  => array( ),
        ),
        array(  # 35
            'in'    => array('foo' => array('not between' => array(1, 2))),
            'query' => '`foo` NOT BETWEEN ? AND ?',
            'bind'  => array(1, 2),
        ),
        array(  # 37
            'in'    => array('foo_id' => mark::raw('IN (SELECT foo_id FROM bar WHERE t = ? AND q = ?)', array(1, 2))),
            'query' => '`foo_id` IN (SELECT foo_id FROM bar WHERE t = ? AND q = ?)',
            'bind'  => array(1, 2),
        ),
        array(  # 39
            'in'    => array('foo_id' => array('in' => mark::raw('SELECT foo_id FROM bar WHERE t = ?', array(44)))),
            'query' => '`foo_id` IN (SELECT foo_id FROM bar WHERE t = ?)',
            'bind'  => array(44),
        ),
        //array(  # 41
            //'in'    => array('' => ''),
            //'query' => '',
            //'bind'  => array(),
        //),
    );

    foreach ($test_case as $test) {
        $condition = new SQLMakerCondition( array('name_sep' => '.', 'quote_char' => '`') );
        $condition->add( $test['in'] );
        $t->is("{$condition}", "({$test['query']})");
        $t->is_deeply($condition->bind, $test['bind']);
    }
}

done_testing( );
