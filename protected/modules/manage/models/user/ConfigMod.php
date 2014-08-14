<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-8-4
 * Time: 下午5:58
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');

class ConfigMod{

    /**
     * 获取赠币配置列表
     *
     * @param Params
     * @return bool
     */
    public function getCouponConfigList($params)
    {
        $result = BuckbeekIao::getCouponConfigList($params);
        return $result;
    }

    /**
     * 插入供应商赠币配置
     *
     * @param Params
     * @return bool
     */
    public function saveCouponConfig($params)
    {
        $result = BuckbeekIao::saveCouponConfig($params);
        return $result;
    }

    /**
     * 删除供应商赠币配置
     *
     * @param Params
     * @return bool
     */
    public function delCouponConfig($params)
    {
        $result = BuckbeekIao::delCouponConfig($params);
        return $result;
    }
}