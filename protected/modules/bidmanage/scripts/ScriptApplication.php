<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wuhuanhong
 * Date: 8/16/12
 * Time: 7:54 PM
 * To change this template use File | Settings | File Templates.
 */
// require_once '/opt/tuniu/www/html/framework/yii.php';
require_once dirname(__FILE__).'/../../../../../framework/yii.php';
date_default_timezone_set('PRC');
//自动重连数据库，类似于mysql_ping()
define('PDO_PING_FLAG', true);

class ScriptApplication extends CApplication {

    private $func;

    public function processRequest() {
        $f = $this->func;
        $f();
    }

    public function setScript($function) {
        $this->func = $function;
    }

    public function setdefaultController(){

    }

}

class ScriptRuner {

    static function run($function, $config='') {
        if (empty($config)) {
            $config = require_once dirname(__FILE__) . '/../../../config/script_config.inc.php';
        }
        $r = Yii::createApplication('ScriptApplication', $config);
        $r->setScript($function);
        $r->run();
    }

}
