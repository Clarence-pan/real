<?php
/** 操作类 | Hagrid 打包计划
 * Hagrid models.
 * @author p-sunhao@2014-06-10
 * @version 1.0
 * @func getAgencyInfo
 * @func doRestPostPackageDate
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
 
/**
 * 打包时间操作类
 * @author p-sunhao@2013-11-12
 */
class PackagePlanMod {
	
    /**
     * 获取供应商信息
     * 
     * @param array $params
     * @return array
     */
    public function getAgencyInfo($params) {
    	// 返回结果
        return BuckbeekIao::getAgencyInfo($params);
    }
    
    /**
	 * 获取搜索的产品列表
	 */
	public function getPlaProduct($params) {
		// 返回结果
        return BuckbeekIao::getPlaProduct($params);
	}
	
	/**
	 * 获取打包计划列表
	 */
	public function getPackagePlans($params) {
		// 返回结果
        return BuckbeekIao::getPackagePlans($params);
	}
	
	/**
	 * 获取打包计划产品详情
	 */
	public function getPlanProductDetail($params) {
		// 返回结果
        return BuckbeekIao::getPlanProductDetail($params);
	}
	
	/**
	 * 保存打包计划
	 */
	public function savePackPlan($params) {
		// 返回结果
        return BuckbeekIao::savePackPlan($params);
	}
    
    /**
	 * 发布打包计划
	 */
	public function submitPackPlan($params) {
		// 返回结果
        return BuckbeekIao::submitPackPlan($params);
	}
	
	/**
	 * 保存打包计划线路
	 */
	public function savePackPlanProduct($params) {
		// 返回结果
        return BuckbeekIao::savePackPlanProduct($params);
	}
	
	/**
	 * 删除打包计划
	 */
	public function deletePackPlan($params) {
		// 返回结果
        return BuckbeekIao::deletePackPlan($params);
	}
    
    /**
	 * 获取打包计划状态
	 */
	public function getPlanStatus($params) {
		// 返回结果
        return BuckbeekIao::getPlanStatus($params);
	}
    
 }
?>
