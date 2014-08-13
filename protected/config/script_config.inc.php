<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 04:46 PM
 * Description: script_config.inc.php
 */
$mainConfigArray = require_once(dirname(__FILE__).'/main_config.inc.php');

unset($mainConfigArray['preload']);
unset($mainConfigArray['defaultController']);
unset($mainConfigArray['components']['urlManager']);
unset($mainConfigArray['components']['log']);
unset($mainConfigArray['components']['stomp']);
unset($mainConfigArray['components']['cache']);

$extConfigArray = array();
$extConfigArray['import'][] = 'application.modules.bidmanage.models.release.*';
$extConfigArray['import'][] = 'application.modules.bidmanage.models.user.*';
$extConfigArray['import'][] = 'application.modules.bidmanage.models.product.*';

return CMap::mergeArray($mainConfigArray, $extConfigArray);