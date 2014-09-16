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

function processSendEmailScripts(){
    class SendEmail
    {
        public function SendEmailToAll(){
        	
	        Yii::import('application.modules.bidmanage.models.product.ProductMod');
	        Yii::import('application.modules.bidmanage.models.release.ReleaseEmailMod');
	        
	        $pro = new ProductMod();
	        $release = new ReleaseEmailMod();
	        $productArray = $pro->getOneDayBidProduct();
	        if(count($productArray) == 0){
	        	return;
	        }
	        $params = $release->genEmaiBody($productArray);
	        
	        Yii::import('application.models.EmaiTool');
            $mail = new EmaiTool();
	        $mail->sendEmail($params);
	    }    
    }
    //设置脚本运行时控选项为不限
    set_time_limit(0);
    $email = new SendEmail();
    $email->SendEmailToAll();
}

//ScriptRuner::run('processSendEmailScripts');
