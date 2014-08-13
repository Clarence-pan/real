<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: wanglongsheng
 * Date: 13/3/5
 * Time: 2:35 PM
 * Description: generate_release_pdf.php
 */
require_once 'ScriptApplication.php';

//require_once('/opt/tuniu/www/hagrid/protected/extensions/restfullyii/components/RESTClient.php');
//require_once('/opt/tuniu/www/hagrid/protected/extensions/restfullyii/components/CURL.php');
//require_once('/opt/tuniu/www/hagrid/protected/extensions/restfullyii/components/ERestController.php');
//require_once('/opt/tuniu/www/hagrid/protected/modules/bidmanage/models/user/StaIntegrateMod.php');

echo '访问'.__FILE__.'成功<br />';
function processScripts(){

	class Release
	{

		public function runRelease()
		{
            Yii::import('application.modules.bidmanage.models.release.ScreenshotMod');
            $_screenshotMod = new ScreenshotMod();
            $_screenshotMod->generatePdf();
		}
	}

	//设置脚本运行时控选项为不限
	set_time_limit(0);
	$sync = new Release();
	$sync->runRelease();
}

ScriptRuner::run('processScripts');
