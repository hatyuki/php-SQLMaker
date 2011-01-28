#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';

$t->include_ok('Kirin/SQLMaker.php');
$t->ok(new KirinSQLMaker( array('driver' => 'dummy') ));
