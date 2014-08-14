<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:56 PM
 * Description: url_manager.inc.php
 */
$urlRules = array(


    'hg/refund/<id:\w+>' => array('manage/views/refund/restView', 'verb' => 'GET,HEAD'),
    'hg/user/<id:\w+>' => array('manage/views/user/restView', 'verb' => 'GET,HEAD'),
    'hg/reconciliation/<id:\w+>' => array('manage/views/reconciliation/restView', 'verb' => 'GET,HEAD'),
    'hg/statement/<id:\w+>' => array('manage/views/statement/restView', 'verb' => 'GET,HEAD'),
    'hg/product/<id:\w+>' => array('manage/views/productdata/restView', 'verb' => 'GET,HEAD'),
    'hg/packagedate/<id:\w+>' => array('manage/views/packagedate/restView', 'verb' => 'GET,HEAD'),
    'hg/packageplan/<id:\w+>' => array('manage/views/packageplan/restView', 'verb' => 'GET,HEAD'),
    'hg/channel/<id:\w+>' => array('manage/views/channel/restView', 'verb' => 'GET,HEAD'),
    'hg/clsrecommend/<id:\w+>' => array('manage/views/clsrecommend/restView', 'verb' => 'GET,HEAD'),
    'hg/config/<id:\w+>' => array('manage/views/config/restView', 'verb' => 'GET,HEAD'),

    array('manage/views/user/restCreate', 'pattern' => 'hg/user/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/refund/restCreate', 'pattern' => 'hg/refund/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/reconciliation/restCreate', 'pattern' => 'hg/reconciliation/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/packagedate/restCreate', 'pattern' => 'hg/packagedate/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/productdata/restCreate', 'pattern' => 'hg/product/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/packageplan/restCreate', 'pattern' => 'hg/packageplan/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/channel/restCreate', 'pattern' => 'hg/channel/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/clsrecommend/restCreate', 'pattern' => 'hg/clsrecommend/update/<func:\w+>', 'verb' => 'POST'),
    array('manage/views/config/restCreate', 'pattern' => 'hg/config/update/<func:\w+>', 'verb' => 'POST'),
    
    'hg/public/user/<id:\w+>' => array('manage/interface/user/restView', 'verb' => 'GET,HEAD'),
    'hg/public/refund/<id:\w+>' => array('manage/interface/refund/restView', 'verb' => 'GET,HEAD'),
    'hg/public/product/<id:\w+>' => array('manage/interface/productdata/restView', 'verb' => 'GET,HEAD'),
    
    array('manage/interface/user/restCreate', 'pattern' => 'hg/public/user/create/<func:\w+>', 'verb' => 'POST'),
    array('manage/interface/refund/restCreate', 'pattern' => 'hg/public/refund/create/<func:\w+>', 'verb' => 'POST'),
    
    array('manage/interface/user/restUpdate', 'pattern' => 'hg/public/user/update/<func:\w+>', 'verb' => 'PUT'),
    array('manage/interface/refund/restUpdate', 'pattern' => 'hg/public/refund/update/<func:\w+>', 'verb' => 'PUT'),
    
    array('manage/interface/user/restDelete', 'pattern' => 'hg/public/user/delete/<func:\w+>', 'verb' => 'DELETE'),
    array('manage/interface/refund/restDelete', 'pattern' => 'hg/public/refund/delete/<func:\w+>', 'verb' => 'DELETE'),
    
    // apilib
    array('apilib/restCreate', 'pattern' => 'hg/apilib/<func:\w+>', 'verb' => 'GET,POST'),
    

    
    /* 以下为常规 url rules */
    'hg/<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
    'hg/<modules:\w+>/<controller:\w+>/<action:\w+>'=>'<modules>/<controller>/<action>',
    'hg/<modules:\w+>/form/<controller:\w+>/<action:\w+>'=>'<modules>/form/<controller>/<action>',

    
);