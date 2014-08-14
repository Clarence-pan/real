<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: xiongyun 
 * Date: 2013-01-29
 * Time: 1:51 PM
 * Description: statementMod.php
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
Yii::import('application.modules.manage.dal.iao.BossIao');
Yii::import('application.modules.manage.dal.dao.user.UserDao');

class statementMod{
    
    private $_userDao;

    function __construct() {
        $this->_userDao = new UserDao;
    }

    /**
     * 招客宝报表
     * @param $params
     * @return array
     */

    public function getReportForms($params) {
        $result = BuckbeekIao::getReportForms($params);
        return $result;
    }

    /**
     * 招客宝报表-查询所有的BI数据
     * @param $params
     * @return array
     */

    public function getBIInfo($params) {
        $result = BuckbeekIao::getBIInfo($params);
        return $result;
    }
    
    /**
     * 查询财务账户报表
     */
    public function getFmisCharts($params) {
        $result = BuckbeekIao::getFmisCharts($params);
        return $result;
    }
    
}
