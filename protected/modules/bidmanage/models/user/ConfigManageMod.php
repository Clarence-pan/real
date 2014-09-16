<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-8-4
 * Time: 下午1:34
 */
Yii::import('application.modules.bidmanage.dal.dao.user.ConfigManageDao');

class ConfigManageMod {

    // 声明引用的类
    private $_configManageDao;

    private $_bbLog;

    /**
     * 构造函数，初始化变量
     */
    function __construct() {
        $this->_configManageDao = new ConfigManageDao();
        $this->_bbLog = new BBLog();
    }

    /**
     * 获取赠币配置列表
     */
    public function getCouponConfigList($param) {
        // 初始化返回结果
        $result = array();

        // 逻辑执行
        try {
            // 添加监控
            $posTry = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);
            // 操作DB
            $result = $this->_configManageDao->queryCouponConfigList($param);

            // 结束监控
            BPMoniter::endMoniter($posTry, 500, __LINE__);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }

        // 返回结果
        return $result;
    }

    /**
     * 插入供应商赠币配置
     */
    public function saveCouponConfig($param) {
        // 填充日志
        if ($this->_bbLog->isInfo()) {
            $this->_bbLog->logMethod($param, "插入供应商赠币配置", __METHOD__.'::'.__LINE__, chr(50));
        }
        // 为日志创建监控
        $posLog = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);

        // 初始化返回结果
        $result = array();

        // 逻辑执行
        try {
            // 添加监控示例
            $posTry = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);

            // 操作DB
            $this->_configManageDao->insertCouponConfig($param);

            // 结束监控
            BPMoniter::endMoniter($posTry, 500, __LINE__);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }

        // 添加收尾日志，对应性能监控
        if ($this->_bbLog->isInfo()) {
            $this->_bbLog->logMethod($param, "插入供应商赠币配置", $posLog, chr(50));
        }

        // 返回结果
        return $result;
    }

    /**
     * 删除供应商赠币配置
     */
    public function delCouponConfig($param) {
        // 填充日志
        if ($this->_bbLog->isInfo()) {
            $this->_bbLog->logMethod($param, "删除供应商赠币配置", __METHOD__.'::'.__LINE__, chr(50));
        }
        // 为日志创建监控
        $posLog = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);

        // 初始化返回结果
        $result = array();

        // 逻辑执行
        try {
            // 添加监控
            $posTry = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);
            // 操作DB
            $this->_configManageDao->delCouponConfig($param);

            // 结束监控
            BPMoniter::endMoniter($posTry, 500, __LINE__);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }

        // 添加收尾日志，对应性能监控
        if ($this->_bbLog->isInfo()) {
            $this->_bbLog->logMethod($param, "删除供应商赠币配置", $posLog, chr(50));
        }

        // 返回结果
        return $result;
    }

    /**
     * 同步供应商赠币配置
     */
    public function synCouponConfig() {
        // 读取供应商赠币硬编码配置
        $constDictionary = ConstDictionary::$zeroNiuAmountAccountMapping;
        // 查询已存在的配置
        $existConfig = $this->_configManageDao->queryCouponConfigList(array());
        if ($existConfig['rows'] && is_array($existConfig['rows'])) {
            // 去掉重复的配置
            foreach ($constDictionary as $key => $tempConst) {
                foreach ($existConfig['rows'] as $tempExist) {
                    if ($tempConst == $tempExist['agencyId']) {
                        unset($constDictionary[$key]);
                        break;
                    }
                }
            }
        }
        // 操作DB
        if ($constDictionary && is_array($constDictionary)) {
            $this->_configManageDao->synCouponConfig($constDictionary);
        }
    }
}