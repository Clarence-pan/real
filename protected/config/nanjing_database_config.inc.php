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
        'emulatePrepare' => true,
        'username' => 'buckbeektest',
        'password' => 'tuniu520',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
    ),
    'buckbeek_slave' => array(
        'connectionString' => 'mysql:host=10.10.30.35;dbname=buckbeek;port=3307',
        'emulatePrepare' => true,
        'username' => 'buckbeektest',
        'password' => 'tuniu520',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
    ),
    'bi_slave' => array(
        'connectionString' => 'sqlsrv:server=172.22.0.193;database=BI_EXCHANGE',
        'username' => 'skb',
        'password' => 'skb12#df35r5yy',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'persistent' => false,
    ),
);

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