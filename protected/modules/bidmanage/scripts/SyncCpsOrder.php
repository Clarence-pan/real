<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 13/01/24
 * Time: 11:21 A
 * Description: run_release_sta.php
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

function syncCpsOrder(){
    class SyncCpsOrder
    {
        public function syncCpsOrders(){
        	
	        Yii::import('application.modules.bidmanage.models.cps.CpsMod');
	        
	        try {
	        	// 执行脚本
	     	    $cpsMod = new CpsMod();
	        	$cpsMod->syncCpsOrders();
	        	// 打印成功
            	echo '脚本执行成功，已同步CPS订单信息！<br />';;
	        } catch(BBException $e) {
				if (intval(chr(48)) != $e->getErrCode()) {
					echo '脚本执行失败！<br />'.$e->getErrCode().'<br />'.$e->getErrMessage().'<br />';
				} else {
					echo '脚本执行失败！<br />'.ErrorCode::ERR_231000.'<br />'.ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)].'<br />';
				}
			} catch(Exception $e) {
				// 注入异常和日志
				new BBException($e->getCode(), $e->getMessage());
				echo '脚本执行失败！<br />'.ErrorCode::ERR_231000.'<br />'.ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)].'<br />';
			}	
	    }    
    }
    //设置脚本运行时控选项为不限
    set_time_limit(0);
    $cpsOrder = new SyncCpsOrder();
    $cpsOrder->syncCpsOrders();
}

ScriptRuner::run('syncCpsOrder');
