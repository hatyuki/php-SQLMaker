#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker/Select.php';
require_once 'SQLMaker/SelectSet.php';


$s1 = _ns( )
    ->add_from('table1')
    ->add_select('id')
    ->add_where('foo', 100);

$s2 = _ns( )
    ->add_from('table2')
    ->add_select('id')
    ->add_where('bar', 200);

$s3 = _ns( )
    ->add_from('table3')
    ->add_select('id')
    ->add_where('baz', 300);

function union ($t)
{
    global $s1, $s2, $s3;

    $set = SQLMakerSelectSet::union($s1, $s2);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) UNION SELECT id FROM table2 WHERE (bar = ?)');
    $t->is(implode(',', $set->bind( )), '100,200');

    $set = SQLMakerSelectSet::union($set, $s3);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) UNION SELECT id FROM table2 WHERE (bar = ?) UNION SELECT id FROM table3 WHERE (baz = ?)');
    $t->is(implode(',', $set->bind( )), '100,200,300');

    $set = SQLMakerSelectSet::union($s3, SQLMakerSelectSet::union($s1, $s2));
    $t->is($set->as_sql( ), 'SELECT id FROM table3 WHERE (baz = ?) UNION SELECT id FROM table1 WHERE (foo = ?) UNION SELECT id FROM table2 WHERE (bar = ?)');
    $t->is(implode(',', $set->bind( )), '300,100,200');

    $set = SQLMakerSelectSet::union_all($s1, $s2);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) UNION ALL SELECT id FROM table2 WHERE (bar = ?)');
    $t->is(implode(',', $set->bind( )), '100,200');

    $set->add_order_by('id');
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) UNION ALL SELECT id FROM table2 WHERE (bar = ?) ORDER BY id');
    $t->is(implode(',', $set->bind( )), '100,200');

    $set = SQLMakerSelectSet::union(SQLMakerSelectSet::union($s3, $s1), $s2);
    $t->is($set->as_sql( ), 'SELECT id FROM table3 WHERE (baz = ?) UNION SELECT id FROM table1 WHERE (foo = ?) UNION SELECT id FROM table2 WHERE (bar = ?)');
    $t->is(implode(',', $set->bind( )), '300,100,200');

    $set = SQLMakerSelectSet::union(SQLMakerSelectSet::union($s1, $s2), SQLMakerSelectSet::union($s2, $s3));
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) UNION SELECT id FROM table2 WHERE (bar = ?) UNION SELECT id FROM table2 WHERE (bar = ?) UNION SELECT id FROM table3 WHERE (baz = ?)');
    $t->is(implode(',', $set->bind( )), '100,200,200,300');
}

function multiple ($t)
{
    global $s1, $s2, $s3;

    $set = SQLMakerSelectSet::intersect(SQLMakerSelectSet::except($s1, $s2), $s3);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) EXCEPT SELECT id FROM table2 WHERE (bar = ?) INTERSECT SELECT id FROM table3 WHERE (baz = ?)');
    $t->is(implode(',', $set->bind( )), '100,200,300');

    $set = SQLMakerSelectSet::intersect_all(SQLMakerSelectSet::except($s1, $s2), $s3);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) EXCEPT SELECT id FROM table2 WHERE (bar = ?) INTERSECT ALL SELECT id FROM table3 WHERE (baz = ?)');
    $t->is(implode(',', $set->bind( )), '100,200,300');

    $set = SQLMakerSelectSet::union(SQLMakerSelectSet::except($s1, $s2), $s3);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) EXCEPT SELECT id FROM table2 WHERE (bar = ?) UNION SELECT id FROM table3 WHERE (baz = ?)');
    $t->is(implode(',', $set->bind( )), '100,200,300');

    $set = SQLMakerSelectSet::union(SQLMakerSelectSet::except_all($s1, $s2), $s3);
    $t->is($set->as_sql( ), 'SELECT id FROM table1 WHERE (foo = ?) EXCEPT ALL SELECT id FROM table2 WHERE (bar = ?) UNION SELECT id FROM table3 WHERE (baz = ?)');
    $t->is(implode(',', $set->bind( )), '100,200,300');
}

function _ns ( )
{
    return new SQLMakerSelect(array('quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
}

done_testing( );
