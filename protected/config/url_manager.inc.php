<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:56 PM
 * Description: url_manager.inc.php 供公网访问的api
 */
$urlRules = array(

    'bb/user/<id:\w+>' => array('bidmanage/views/user/restView', 'verb' => 'GET,HEAD'),
    'bb/bid/<id:\w+>' => array('bidmanage/views/bid/restView', 'verb' => 'GET,HEAD'),
    'bb/fmis/<id:\w+>' => array('bidmanage/views/fmis/restView', 'verb' => 'GET,HEAD'),
    'bb/product/<id:\w+>' => array('bidmanage/views/product/restView', 'verb' => 'GET,HEAD'),
    'bb/common/<id:\w+>' => array('bidmanage/views/common/restView', 'verb' => 'GET,HEAD'),
    'bb/packageplan/<id:\w+>' => array('bidmanage/views/packageplan/restView', 'verb' => 'GET,HEAD'),
    'bb/channel/<id:\w+>' => array('bidmanage/views/channel/restView', 'verb' => 'GET,HEAD'),
    'bb/clsrecommend/<id:\w+>' => array('bidmanage/views/clsrecommend/restView', 'verb' => 'GET,HEAD'),
    'bb/config/<id:\w+>' => array('bidmanage/views/config/restView', 'verb' => 'GET,HEAD'),
    'bb/cps/<id:\w+>' => array('bidmanage/views/cps/restView', 'verb' => 'GET,HEAD'),
    
    array('bidmanage/views/user/restCreate', 'pattern' => 'bb/user/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/bid/restCreate', 'pattern' => 'bb/bid/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/fmis/restCreate', 'pattern' => 'bb/fmis/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/product/restCreate', 'pattern' => 'bb/product/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/packageplan/restCreate', 'pattern' => 'bb/packageplan/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/channel/restCreate', 'pattern' => 'bb/channel/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/clsrecommend/restCreate', 'pattern' => 'bb/clsrecommend/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/config/restCreate', 'pattern' => 'bb/config/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/cps/restCreate', 'pattern' => 'bb/cps/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    
    'bb/public/user/<id:\w+>' => array('bidmanage/interface/user/restView', 'verb' => 'GET,HEAD'),
    /* 以下为常规 url rules */
    'bb/<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
    'bb/user/<controller:\w+>/<action:\w+>'=>'bidmanage/forms/<controller>/<action>',
    'bb/<modules:\w+>/<controller:\w+>/<action:\w+>'=>'<modules>/forms/<controller>/<action>',
    'bb/<modules:\w+>/test/<controller:\w+>/<action:\w+>'=>'<modules>/test/<controller>/<action>',
    // apilib
    array('apilib/restCreate', 'pattern' => 'bb/apilib/<func:\w+>', 'verb' => 'GET,POST'),
);