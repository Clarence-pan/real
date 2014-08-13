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

function endPackPlan(){
    class EndPackPlan
    {
        public function endPackPlans(){
        	
	        Yii::import('application.modules.bidmanage.models.pack.PackageplanMod');
	        Yii::import('application.modules.bidmanage.models.release.ReleaseEmailMod');
	        
	        try {
	        	// 执行脚本
	     	    $packageplanMod = new PackageplanMod();
	        	$packageplanMod->endPackPlan();
	        	// 打印成功
            	echo '脚本执行成功，已正常过期打包计划！<br />';;
	        } catch (Exception $e) {
	    		// 打印错误日志
    	        Yii::log($e);
        	    // 打印异常
            	echo '执行脚本发生异常，脚本执行不成功！<br />';;
        	}
	    }    
    }
    //设置脚本运行时控选项为不限
    set_time_limit(0);
    $email = new EndPackPlan();
    $email->endPackPlans();
}

ScriptRuner::run('endPackPlan');
