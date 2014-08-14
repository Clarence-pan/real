<?php

/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:22 PM
 * Description: main_config.inc.php
 */

//TNSRV_INTERFACE_LOCATION  = nanjing | beijing
$TNSRV_INTERFACE_LOCATION = getenv("TNSRV_INTERFACE_LOCATION");
if ($TNSRV_INTERFACE_LOCATION == "beijing") {
    require_once(dirname(__FILE__) . '/beijing_app_config.inc.php');
    require_once(dirname(__FILE__) . '/beijing_database_config.inc.php');
}else{
    require_once(dirname(__FILE__) . '/nanjing_app_config.inc.php');
    require_once(dirname(__FILE__) . '/nanjing_database_config.inc.php');
}

require_once(dirname(__FILE__) . '/url_manager.inc.php');

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

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');
// This is the main Web application configuration. Any writable
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
        'application.components.*',
        'application.extensions.*',
        'ext.restfullyii.components.*'
    ),
    'modules' => array(
        'manage' => array(),
    // uncomment the following to enable the Gii tool
//         'gii' => array(
//             'class' => 'system.gii.GiiModule',
//             'password' => 'demo',
//             // If removed, Gii defaults to localhost only. Edit carefully to taste.
//             'ipFilters' => array('127.0.0.1','::1'),
//         ), 
    ),
    // application components
    'components' => array_merge($urlManager, $databaseConfig, $memcache),
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array_merge($appConfig, array('extended_db' => $extendedDatabaseConfig)),
);