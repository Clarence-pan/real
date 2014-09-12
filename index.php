<?php
header("Access-Control-Allow-Origin: *");
// change the following paths if necessary
$yii = dirname(__FILE__).'/../framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main_config.inc.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
require_once($yii);
require_once('/../test/plib/dump/dump.php');
Yii::createWebApplication($config)->run();
