<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/24/12
 * Time: 10:21 PM
 * Description: run_release_sta.php
 */
/**
 * 该脚本已迁移至HAGRID-NJ执行
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

if(!empty($_GET['date'])){
    define('STA_DATE', strval($_GET['date']));
}else{
    define('STA_DATE', date("Y-m-d", strtotime('-1 day')));
}

function processScripts(){
    class ReleaseStatistic
    {
        private $_staIntegrateMod;

        function __construct()
        {
            $this->_staIntegrateMod = new StaIntegrateMod;
        }

        public function doReleaseStaJob()
        {
            $this->_staIntegrateMod->runStatisticTask();
        }

    }
    //设置脚本运行时控选项为不限
    set_time_limit(0);
    $sync = new ReleaseStatistic();
    $sync->doReleaseStaJob();
}

ScriptRuner::run('processScripts');
