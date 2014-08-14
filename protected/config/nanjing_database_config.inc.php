<?php

/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:03 PM
 * Description: database_config.inc.php
 */
$databaseConfig = array(
    'hagrid_master' => array(
        'connectionString' => 'mysql:host=10.10.30.35;dbname=hagrid;port=3307',
        'username' => 'hagridtest',
        'password' => 'tuniu520',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
        'autoConnect' => false
    ),
    'hagrid_slave' => array(
        'connectionString' => 'mysql:host=10.10.30.35;dbname=hagrid;port=3307',
        'username' => 'hagridtest',
        'password' => 'tuniu520',
        'class' => 'CDbConnection',
        'charset' => 'utf8',
        'enableProfiling' => YII_DEBUG,
        'enableParamLogging' => YII_DEBUG,
        'autoConnect' => false
    ),
);

$extendedDatabaseConfig = array(
    'bi_slave' => array(
        'host' => '172.22.0.193', //PRODUCT::nj-bi-slave.db.tuniu.org
        'port' => '',
        'dbname' => 'BI_EXCHANGE',
        'usename' => 'skb',
        'password' => 'skb12#df35r5yy',
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