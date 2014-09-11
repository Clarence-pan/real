<?php
/** 操作类 | Hagrid 打包计划
 * Hagrid models.
 * @author p-sunhao@2014-06-10
 * @version 1.0
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
 
/**
 * cps操作类
 * @author p-sunhao@2014-08-30
 */
class CpsMod {
	
	/**
	 * 获取费率
	 */
	public function getExpenseRatio($params) {
    	// 返回结果
        return BuckbeekIao::getExpenseRatio($params);
    }
	
    /**
     * 配置费率
     * 
     * @param array $params
     * @return array
     */
    public function configExpenseRatio($params) {
    	// 返回结果
        return BuckbeekIao::configExpenseRatio($params);
    }
    
 }
?>
