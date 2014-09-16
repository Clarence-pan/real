<?php

/**
 * 内部接口
 */
define('T_API', '1');
//header("Access-Control-Allow-Origin: *");
// change the following paths if necessary
$yii = dirname(__FILE__).'/../../../framework/yii.php';
//$yii='D:\G\workspace\YX\Src\OLV\lumos\yii/yii.php';
$config=dirname(__FILE__).'/../protected/config/main_config.inc.php';

// remove the following lines when in production mode
//defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
//defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);
Yii::createWebApplication($config)->run();
