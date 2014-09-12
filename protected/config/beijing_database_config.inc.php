<?php

/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:03 PM
 * Description: database_config.inc.php
 */
if ("debuging"){
    $db = array(
        'connectionString' => 'mysql:host=127.0.0.1;dbname=buckbeek_mirror_beijing;port=3306',
        'username' => 'root',
        'password' => '',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
        'autoConnect' => false
    );
    $databaseConfig = array('hagrid_master' => $db, 'hagrid_slave' => $db);
}else{
    $databaseConfig = array(
        'hagrid_master' => array(
            'connectionString' => 'mysql:host=10.10.30.37;dbname=hagrid;port=3306',
            'emulatePrepare' => true,
            'username' => 'buckbeekdev',
            'password' => 'tuniu520',
            'class' => 'CDbConnection',
            'charset' => 'utf8',
            'enableProfiling' => YII_DEBUG,
            'enableParamLogging' => YII_DEBUG,
        ),
        'hagrid_slave' => array(
            'connectionString' => 'mysql:host=10.10.30.37;dbname=hagrid;port=3306',
            'emulatePrepare' => true,
            'username' => 'buckbeekdev',
            'password' => 'tuniu520',
            'class' => 'CDbConnection',
            'charset' => 'utf8',
            'enableProfiling' => YII_DEBUG,
            'enableParamLogging' => YII_DEBUG,
        ),
    );
}

$cacheConfig = array(
    array(
        'host' => '127.0.0.1',
        'port' => '11211',
        'weight' => '50',
    ),
    array(
        'host' => '127.0.0.1',
        'port' => '11211',
        'weight' => '50',
    )
);