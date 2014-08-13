<?php

/**
 * 内部访问
 */
define('T_API', '1');
header("Access-Control-Allow-Origin: *");
// change the following paths if necessary
$yii='/opt/tuniu/www/html/framework/yii.php';
//$yii = 'D:\G\workspace\YX\Src\PLA\development\php-framework\framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main_config.inc.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
//defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
require_once($yii);

xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);   //XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY

Yii::createWebApplication($config)->run();

$xhprof_data = xhprof_disable();   //返回运行数据

include_once "/opt/tuniu/www/xhprof/xhprof_lib/utils/xhprof_lib.php";  
include_once "/opt/tuniu/www/xhprof/xhprof_lib/utils/xhprof_runs.php";  
 
$xhprof_runs = new XHProfRuns_Default();  
 
$run_id = $xhprof_runs->save_run($xhprof_data, 'vnd.bb.dev');   //第一个参数是 xhprof_disable()函数返回的运行信息，第二个参数是自定义的命名空间字符串（任意字符串），返回运行ID。

file_put_contents('assets/bb_xhprof_log.txt',date('Y-m-d H:i:s')." >>>\nhttp://xhprof.bb.tuniu.org/xhprof_html/?run=$run_id&source=vnd.bb.dev&all=1&sort=excl_wt\n",FILE_APPEND);