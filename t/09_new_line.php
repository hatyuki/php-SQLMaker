#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'SQLMaker.php';


function empty_string ($t)
{
    $builder = new SQLMaker('mysql', array('new_line' => ''));
    $t->is($builder->new_line, '');
}


done_testing( );
