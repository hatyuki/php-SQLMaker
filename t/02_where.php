#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker/Condition.php';

$w1 = new SQLMakerCondition( );
$w1->add( array('x' => 1) );
$w1->add( array('y' => 2) );

$w2 = new SQLMakerCondition( );
$w2->add('a', 3);
$w2->add('b', 4);

function compose_and ($t)
{
    global $w1, $w2;

    $and = $w1->compose_and($w2);
    $t->is($and->as_sql( ), '((x = ?) AND (y = ?)) AND ((a = ?) AND (b = ?))');
    $t->is(implode(',', $and->bind), '1,2,3,4');

    $and->add('z', 99);
    $t->is($and->as_sql( ), '((x = ?) AND (y = ?)) AND ((a = ?) AND (b = ?)) AND (z = ?)');
    $t->is(implode(',', $and->bind), '1,2,3,4,99');
}

function compose_or ($t)
{
    global $w1, $w2;

    $and = $w1->compose_or($w2);
    $t->is($and->as_sql( ), '((x = ?) AND (y = ?)) OR ((a = ?) AND (b = ?))');
    $t->is(implode(',', $and->bind), '1,2,3,4');

    $and->add('z', 99);
    $t->is($and->as_sql( ), '((x = ?) AND (y = ?)) OR ((a = ?) AND (b = ?)) AND (z = ?)');
    $t->is(implode(',', $and->bind), '1,2,3,4,99');
}

function to_string ($t)
{
    global $w1;

    $t->is("{$w1}", "(x = ?) AND (y = ?)");
}

done_testing( );
