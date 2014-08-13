<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-8-4
 * Time: 下午1:37
 */
Yii::import('application.modules.bidmanage.models.user.ConfigManageMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');

class ConfigController extends restSysServer {

    // 声明引用的类
    private $_configManageMod;
    private $_manageMod;

    /**
     * 构造函数，初始化变量
     */
    function __construct() {
        $this->_configManageMod = new ConfigManageMod();
        $this->_manageMod = new UserManageMod();
    }

    /**
     * 获取赠币配置列表
     */
    public function doRestGetCouponConfigList($url, $data) {
        $result = $this->genrateReturnRest();
        try {
            // 校验参数
            if (!isset($data['start']) || !isset($data['limit'])) {
                $result['success'] = false;
                $result['errorCode'] = ErrorCode::ERR_210000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
                // 返回参数不正确
                $this->returnRestStand($result);
            } else {
                // 调用mod层
                $data = $this->_configManageMod->getCouponConfigList($data);

                // 整合结果
                $result['data'] = $data;
                $result['errorCode'] = ErrorCode::ERR_231500;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];

                // 返回结果
                $this->returnRestStand($result);
            }
        } catch(BBException $e) {
            $result['success'] = false;
            if (0 != $e->getErrCode()) {
                $result['errorCode'] = $e->getErrCode();
                $result['msg'] = $e->getErrMessage();
            } else {
                $result['errorCode'] = ErrorCode::ERR_231000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            }

            // 返回结果
            $this->returnRestStand($result);
        } catch(Exception $e) {
            // 注入异常和日志
            new BBException($e->getCode(), $e->getMessage());
            $result['success'] = false;
            $result['errorCode'] = ErrorCode::ERR_231000;
            $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            // 返回结果
            $this->returnRestStand($result);
        }
    }

    /**
     * 插入供应商赠币配置
     */
    public function doRestPostAddCouponConfig($data) {
        $result = $this->genrateReturnRest();
        try {
            // 校验参数
            if (!isset($data['agencyId'])) {
                $result['success'] = false;
                $result['errorCode'] = ErrorCode::ERR_210000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
                // 返回参数不正确
                $this->returnRestStand($result);
            } else {
                // 查询供应商account帐号信息
                $account = $this->_manageMod->getAccountInfoByAgentId($data['agencyId']);
                if (empty($account)) {
                    $result['success'] = false;
                    $result['errorCode'] = ErrorCode::ERR_231702;
                    $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231702)];
                    // 该供应商没有收客宝帐号
                    $this->returnRestStand($result);
                } else {
                    // 设置accountId参数
                    $data['accountId'] = $account['id'];

                    // 首先查询是否已经添加
                    $couponConfigInfo = $this->_configManageMod->getCouponConfigList($data);
                    if ($couponConfigInfo['count'] >= 1 && $couponConfigInfo['rows']) {
                        $result['success'] = false;
                        $result['errorCode'] = ErrorCode::ERR_231612;
                        $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231612)];
                        // 该供应商没有收客宝帐号
                        $this->returnRestStand($result);
                    } else {
                        // 调用mod层
                        $data = $this->_configManageMod->saveCouponConfig($data);

                        // 整合结果
                        $result['data'] = $data;
                        $result['errorCode'] = ErrorCode::ERR_231502;
                        $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231502)];

                        // 返回结果
                        $this->returnRestStand($result);
                    }
                }
            }
        } catch(BBException $e) {
            $result['success'] = false;
            if (0 != $e->getErrCode()) {
                $result['errorCode'] = $e->getErrCode();
                $result['msg'] = $e->getErrMessage();
            } else {
                $result['errorCode'] = ErrorCode::ERR_231000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            }

            // 返回结果
            $this->returnRestStand($result);
        } catch(Exception $e) {
            // 注入异常和日志
            new BBException($e->getCode(), $e->getMessage());
            $result['success'] = false;
            $result['errorCode'] = ErrorCode::ERR_231000;
            $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            // 返回结果
            $this->returnRestStand($result);
        }
    }

    /**
     * 删除供应商赠币配置
     */
    public function doRestPostDelCouponConfig($data) {
        $result = $this->genrateReturnRest();
        try {
            // 校验参数
            if (!isset($data['id'])) {
                $result['success'] = false;
                $result['errorCode'] = ErrorCode::ERR_210000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
                // 返回参数不正确
                $this->returnRestStand($result);
            } else {
                // 调用mod层
                $data = $this->_configManageMod->delCouponConfig($data);

                // 整合结果
                $result['data'] = $data;
                $result['errorCode'] = ErrorCode::ERR_231506;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231506)];

                // 返回结果
                $this->returnRestStand($result);
            }
        } catch(BBException $e) {
            $result['success'] = false;
            if (0 != $e->getErrCode()) {
                $result['errorCode'] = $e->getErrCode();
                $result['msg'] = $e->getErrMessage();
            } else {
                $result['errorCode'] = ErrorCode::ERR_231000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            }

            // 返回结果
            $this->returnRestStand($result);
        } catch(Exception $e) {
            // 注入异常和日志
            new BBException($e->getCode(), $e->getMessage());
            $result['success'] = false;
            $result['errorCode'] = ErrorCode::ERR_231000;
            $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            // 返回结果
            $this->returnRestStand($result);
        }
    }
}