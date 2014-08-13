<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:56 PM
 * Description: url_manager_tapi.inc.php
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
    
    array('bidmanage/views/user/restCreate', 'pattern' => 'bb/user/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/bid/restCreate', 'pattern' => 'bb/bid/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/fmis/restCreate', 'pattern' => 'bb/fmis/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/product/restCreate', 'pattern' => 'bb/product/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/packageplan/restCreate', 'pattern' => 'bb/packageplan/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/channel/restCreate', 'pattern' => 'bb/channel/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/views/clsrecommend/restCreate', 'pattern' => 'bb/clsrecommend/(update-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    
    'bb/public/user/<id:\w+>' => array('bidmanage/interface/user/restView', 'verb' => 'GET,HEAD'),
    'bb/public/bid/<id:\w+>' => array('bidmanage/interface/bid/restView', 'verb' => 'GET,HEAD'),
    'bb/public/fmis/<id:\w+>' => array('bidmanage/interface/fmis/restView', 'verb' => 'GET,HEAD'),
    'bb/public/product/<id:\w+>' => array('bidmanage/interface/product/restView', 'verb' => 'GET,HEAD'),
	'bb/public/packagedate/<id:\w+>' => array('bidmanage/interface/packagedate/restView', 'verb' => 'GET,HEAD'),
    'bb/public/common/<id:\w+>' => array('bidmanage/interface/common/restView', 'verb' => 'GET,HEAD'),
    'bb/public/packageplan/<id:\w+>' => array('bidmanage/interface/packageplan/restView', 'verb' => 'GET,HEAD'),
    'bb/public/channel/<id:\w+>' => array('bidmanage/interface/channel/restView', 'verb' => 'GET,HEAD'),
    'bb/public/clsrecommend/<id:\w+>' => array('bidmanage/interface/clsrecommend/restView', 'verb' => 'GET,HEAD'),
    'bb/public/config/<id:\w+>' => array('bidmanage/interface/config/restView', 'verb' => 'GET,HEAD'),

    array('bidmanage/interface/user/restCreate', 'pattern' => 'bb/public/user/(create-)?<func:\w+>', 'verb' => 'POST,HEAD'),
    array('bidmanage/interface/bid/restCreate', 'pattern' => 'bb/public/bid/(create-)?<func:\w+>', 'verb' => 'POST,HEAD'),
    array('bidmanage/interface/fmis/restCreate', 'pattern' => 'bb/public/fmis/(create-)?<func:\w+>', 'verb' => 'POST,HEAD'),
    array('bidmanage/interface/product/restCreate', 'pattern' => 'bb/public/product/(create-)?<func:\w+>', 'verb' => 'POST,HEAD'),
	array('bidmanage/interface/packagedate/restCreate', 'pattern' => 'bb/public/packagedate/(create-)?<func:\w+>', 'verb' => 'POST,HEAD'),
	array('bidmanage/interface/packageplan/restCreate', 'pattern' => 'bb/public/packageplan/(create-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
	array('bidmanage/interface/channel/restCreate', 'pattern' => 'bb/public/channel/(create-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
	array('bidmanage/interface/clsrecommend/restCreate', 'pattern' => 'bb/public/clsrecommend/(create-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),
    array('bidmanage/interface/config/restCreate', 'pattern' => 'bb/public/config/(create-)?<func:\w+>', 'verb' => 'GET,POST,HEAD'),

    array('bidmanage/interface/user/restUpdate', 'pattern' => 'bb/public/user/(update-)?<func:\w+>', 'verb' => 'PUT,HEAD'),
    array('bidmanage/interface/bid/restUpdate', 'pattern' => 'bb/public/bid/(update-)?<func:\w+>', 'verb' => 'PUT,HEAD'),
    array('bidmanage/interface/fmis/restUpdate', 'pattern' => 'bb/public/fmis/(update-)?<func:\w+>', 'verb' => 'PUT,HEAD'),
    array('bidmanage/interface/product/restUpdate', 'pattern' => 'bb/public/product/(update-)?<func:\w+>', 'verb' => 'PUT,HEAD'),
    array('bidmanage/interface/packageplan/restCreate', 'pattern' => 'bb/public/packageplan/(update-)?<func:\w+>', 'verb' => 'PUT,HEAD'),

    array('bidmanage/interface/user/restDelete', 'pattern' => 'bb/public/user/(delete-)?<func:\w+>', 'verb' => 'DELETE,HEAD'),
    array('bidmanage/interface/bid/restDelete', 'pattern' => 'bb/public/bid/(delete-)?<func:\w+>', 'verb' => 'DELETE,HEAD'),
    array('bidmanage/interface/fmis/restDelete', 'pattern' => 'bb/public/fmis/(delete-)?<func:\w+>', 'verb' => 'DELETE,HEAD'),
    array('bidmanage/interface/product/restDelete', 'pattern' => 'bb/public/product/(delete-)?<func:\w+>', 'verb' => 'DELETE,HEAD'),
    array('bidmanage/interface/packageplan/restCreate', 'pattern' => 'bb/public/packageplan/(delete-)?<func:\w+>', 'verb' => 'DELETE,HEAD'),
    
    // apilib
    array('apilib/restCreate', 'pattern' => 'bb/apilib/<func:\w+>', 'verb' => 'GET,POST'),
    /* 以下为常规 url rules */
    'bb/<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
    'bb/user/<controller:\w+>/<action:\w+>'=>'bidmanage/forms/<controller>/<action>',
    'bb/<modules:\w+>/<controller:\w+>/<action:\w+>'=>'<modules>/forms/<controller>/<action>',
    'bb/<modules:\w+>/test/<controller:\w+>/<action:\w+>'=>'<modules>/test/<controller>/<action>',
    
    
);