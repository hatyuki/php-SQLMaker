#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';

$t->include_ok('SQLMaker.php');
$t->ok(new SQLMaker( array('driver' => 'dummy') ));
