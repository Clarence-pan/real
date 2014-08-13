<?php

/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:03 PM
 * Description: database_config.inc.php
 */
$databaseConfig = array(
    'buckbeek_master' => array(
        'connectionString' => 'mysql:host=10.10.30.35;dbname=buckbeek;port=3307',
        'username' => 'buckbeektest',
        'password' => 'tuniu520',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
        'autoConnect' => false
    ),
    'buckbeek_slave' => array(
        'connectionString' => 'mysql:host=10.10.30.35;dbname=buckbeek;port=3307',
        'username' => 'buckbeektest',
        'password' => 'tuniu520',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
        'autoConnect' => false
    ),

);

//     array(//这组memcache用在bb上有问题，请勿使用
//        'host' => '10.10.0.105',
//        'port' => '11234',
//        'weight' => '60',
//    ),
$cacheConfig = array(
    array(
        'host' => '127.0.0.1',
        'port' => '11211',
        'weight' => '50',
    )
);
