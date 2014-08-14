<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/11/12
 * Time: 5:02 PM
 * Description: ScriptsController.php
 */
require_once dirname(__FILE__).'/../../models/user/StaIntegrateMod.php';
define(_TEST_LEVEL_, 11);
define(_TRUE_LEVEL_, 12);
class ScriptsController extends CController
{
    private $_relIndexCoreMod;

    private $_relChnlHotMod;

    private $_relSiteClsMod;

    private $_relMessageMod;

    private $_staIntegrateMod;

    function __construct()
    {
        $this->_staIntegrateMod = new StaIntegrateMod();
    }

    /**
     * 开发调试使用
     */
    public function actionTest()
    {
        echo '$TNSRV_INTERFACE_LOCATION:' .  getenv("TNSRV_INTERFACE_LOCATION");
        var_dump($_SERVER);
    }

    public function actionReleaseSta()
    {
        //权限约定
        if($_GET['password'] != '06b8f6d2f44740f0893044642dae3963'){
            echo '对不起，您不具备执行权限。请联系开发人员，非常感谢！';
            return;
        }

        if(!empty($_GET['date'])){
            define('STA_DATE', strval($_GET['date']));
        }else{
            define('STA_DATE', date("Y-m-d", strtotime('-1 day')));
        }

        print_r('当前执行统计的日期为：' . STA_DATE);
        $this->_staIntegrateMod->runStatisticTask();
        print_r('<br />=> 运行完毕');
    }


}
