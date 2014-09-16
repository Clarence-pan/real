<?php

/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:22 PM
 * Description: main_config.inc.php
 */

//TNSRV_INTERFACE_LOCATION  = nanjing | beijing
//$TNSRV_INTERFACE_LOCATION = getenv("TNSRV_INTERFACE_LOCATION");
//$TNSRV_INTERFACE_LOCATION  = "beijing";
//if ($TNSRV_INTERFACE_LOCATION == "beijing") {
    require_once(dirname(__FILE__) . '/beijing_app_config.inc.php');
    require_once(dirname(__FILE__) . '/beijing_database_config.inc.php');
//}else{
//    require_once(dirname(__FILE__) . '/nanjing_app_config.inc.php');
//    require_once(dirname(__FILE__) . '/nanjing_database_config.inc.php');
//}

//if(defined('T_API') && T_API){
//    require_once(dirname(__FILE__) . '/url_manager_tapi.inc.php');
//}else{
//    require_once(dirname(__FILE__) . '/url_manager.inc.php');
//}
/**
 * 测试环境属于内网，使用tapi的url路由配置
 * 备注：
 * “url_manager_tapi.inc.php”涵盖了所有可能的外部访问路径规则
 */
require_once(dirname(__FILE__) . '/url_manager_tapi.inc.php');

$urlManager = array(
    'urlManager' => array(
        'urlFormat' => 'path',
        'rules' => $urlRules,
        'caseSensitive' => false,
    ),
);

$memcache = array(
    'memcache' => array(
        'class' => 'system.caching.CMemCache',
        'servers' => $cacheConfig,
    ),
);

$logComponent = array(
    'log' => array(
        'class' => 'CLogRouter',
        'routes' => array(
            array(//Mail-Log
                'class' => 'ext.phpmailer.TEmailLogRoute',
                'levels' => 'error',
                'subject' => '[招客宝错误报警]SERVER_ADDR:' . $_SERVER['SERVER_ADDR'].'|'.$_SERVER['REQUEST_METHOD'].'| REMOTE_ADDR:'.$_SERVER['REMOTE_ADDR'],
                'sentFrom' => 'wuhuanhong@tuniu.com',
                'emails' => array(
                    //'wuhuanhong@tuniu.com',
                ),
                'smtpAuth' => true,
                'host' => 'mail.tuniu.com',
                'port' => 25,
                'username' => 'wuhuanhong',
                'password' => 'tuniu520',
            ),
        ),
    ),
);

// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'My Web Application',
    //'defaultController' => 'site',
    // preloading 'log' component
    'preload' => array('log'),
    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.dal.dao.*',
        'application.components.*',
        'application.extensions.*',
        'application.modules.bidmanage.dal.dao.const.*',
        'ext.restfullyii.components.*',
        'ext.restfullyii.const.*',
        'ext.restfullyii.dictionary.*',
        'ext.restfullyii.tools.*',
    ),
    'modules' => array(
        'bidmanage' => array(),
    ),
    // application components
    'components' => array_merge($urlManager, $databaseConfig, $memcache),
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => $appConfig,
);