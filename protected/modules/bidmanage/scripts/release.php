<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 2:35 PM
 * Description: release_product.php
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

if(!empty($_GET['date'])){
    define('RELEASE_DATE', strval($_GET['date']));
}else{
    define('RELEASE_DATE', date('Y-m-d'));
}
if(!empty($_GET['type'])){
    define('RELEASE_TYPE', intval($_GET['type']));
}else{
    define('RELEASE_TYPE', 0);
}
print_r('当前发布的产品日期限定为：'.RELEASE_DATE);

function processScripts(){
    class Release
    {
        private $_releaseMod;

        function __construct()
        {
            $this->_releaseMod = new ReleaseMod;
        }

        public function runRelease()
        {
        	Yii::app()->buckbeek_master->createCommand("SET SESSION wait_timeout=2*3600")->query();
            Yii::app()->buckbeek_slave->createCommand("SET SESSION wait_timeout=2*3600")->query();
            //招客宝产品推送
            $this->_releaseMod->run();
            print_r('<br />=> 推送至前台网站');
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '本次推广全部结束', 11, 'wenrui',0,0,'');

            //打印到页面呈现
            if(LOG_TO_FRONTPAGE)
                print_r('<br />=> 运行完毕');
        }

    }
    //设置脚本运行时控选项为不限
    set_time_limit(0);
    $sync = new Release();
    $sync->runRelease();

}

ScriptRuner::run('processScripts');
