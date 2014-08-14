<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-8-4
 * Time: 下午5:54
 */
Yii::import('application.modules.manage.models.user.ConfigMod');

class ConfigController extends restUIServer {

    // 声明引用的类
    private $_configMod;

    /**
     * 构造函数，初始化变量
     */
    function __construct() {
        $this->_configMod = new ConfigMod();
    }

    /**
     * 获取赠币配置列表
     */
    public function doRestGetCouponConfigList($url, $data) {
        $result = $this->_configMod->getCouponConfigList($data);
        if ($result['success']) {
            $this->returnRest($result['data']);
        } else {
            $this->returnRest($result['msg'], true, 230015, $result['msg']);
        }
    }

    /**
     * 插入供应商赠币配置
     */
    public function doRestPostAddCouponConfig($data) {
        $result = $this->_configMod->saveCouponConfig($data);
        if ($result['success']) {
            $this->returnRest($result['success']);
        } else {
            $this->returnRest($result['msg'], true, 230015, $result['msg']);
        }
    }

    /**
     * 删除供应商赠币配置
     */
    public function doRestPostDelCouponConfig($data) {
        $result = $this->_configMod->delCouponConfig($data);
        if ($result['success']) {
            $this->returnRest($result['success']);
        } else {
            $this->returnRest($result['msg'], true, 230015, $result['msg']);
        }
    }
}