<?php
/**
 * Created by JetBrains PhpStorm.
 * User: huangxun
 * Date: 14-1-14
 * Time: 下午3:06
 * To change this template use File | Settings | File Templates.
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

function brandNameScript(){
    class UpdateBrandName {
        private $_userMod;

        function __construct() {
            $this->_userMod = new UserManageMod();
        }

        public function doUpdateBrandNameJob() {
            print_r('<br />=> 更新供应商品牌名');
            // 更新供应商品牌名
            $this->_userMod->runBrandNameTask();
            //打印到页面呈现
            if(LOG_TO_FRONTPAGE)
                print_r('<br />=> 运行完毕');
        }
    }

    //设置脚本运行时控选项为不限
    set_time_limit(0);
    $sync = new UpdateBrandName();
    $sync->doUpdateBrandNameJob();
}

ScriptRuner::run('brandNameScript');