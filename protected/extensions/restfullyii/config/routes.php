<?php
  return array(
    /* moudules 下的 interface 访问 */
//    'api/app/<controller:\w+>' => array('app/interface/<controller>/restList', 'verb' => 'GET'),
//    'api/app/<controller:\w+>/<id:\w+>' => array('app/interface/<controller>/restView', 'verb' => 'GET'),
//    'api/app/<controller:\w+>/<id:\w+>/<var:\w*>' => array('app/interface/<controller>/restView', 'verb' => 'GET'),
//    'api/app/<controller:\w+>/<id:\w+>/<var:\w*>/<var2:\w*>' => array('app/interface/<controller>/restView', 'verb' => 'GET'),
//    array('app/interface/<controller>/restUpdate', 'pattern' => 'api/app/<controller:\w+>/<id:\d+>', 'verb' => 'PUT'),
//    array('app/interface/<controller>/restUpdate', 'pattern' => 'api/app/<controller:\w+>/<var:\w+>/<id:\d+>', 'verb' => 'PUT'),
//    array('app/interface/<controller>/restDelete', 'pattern' => 'api/app/<controller:\w+>/<id:\d+>', 'verb' => 'DELETE'),
//    array('app/interface/<controller>/restDelete', 'pattern' => 'api/app/<controller:\w+>/<var:\w+>/<id:\d+>', 'verb' => 'DELETE'),
//    array('app/interface/<controller>/restCreate', 'pattern' => 'api/app/<controller:\w+>', 'verb' => 'POST'),
//    array('app/interface/<controller>/restCreate', 'pattern' => 'api/app/<controller:\w+>/<func:\w+>', 'verb' => 'POST'),
    /* 默认controller 下的 interface 目录访问 */
    'api/<controller:\w+>' => array('interface/<controller>/restList', 'verb' => 'GET'),
    'api/<controller:\w+>/<id:\w+>' => array('interface/<controller>/restView', 'verb' => 'GET'),
    'api/<controller:\w+>/<id:\w+>/<var:\w*>' => array('interface/<controller>/restView', 'verb' => 'GET'),
    'api/<controller:\w+>/<id:\w+>/<var:\w*>/<var2:\w*>' => array('interface/<controller>/restView', 'verb' => 'GET'),
    array('interface/<controller>/restUpdate', 'pattern' => 'api/<controller:\w+>/<id:\d+>', 'verb' => 'PUT'),
    array('interface/<controller>/restUpdate', 'pattern' => 'api/<controller:\w+>/<var:\w+>/<id:\d+>', 'verb' => 'PUT'),
    array('interface/<controller>/restDelete', 'pattern' => 'api/<controller:\w+>/<id:\d+>', 'verb' => 'DELETE'),
    array('interface/<controller>/restDelete', 'pattern' => 'api/<controller:\w+>/<var:\w+>/<id:\d+>', 'verb' => 'DELETE'),
    array('interface/<controller>/restCreate', 'pattern' => 'api/<controller:\w+>', 'verb' => 'POST'),
    array('interface/<controller>/restCreate', 'pattern' => 'api/<controller:\w+>/<func:\w+>', 'verb' => 'POST'),
      );
