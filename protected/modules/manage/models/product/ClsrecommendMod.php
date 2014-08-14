<?php
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');

class ClsrecommendMod {

	/**
     * 根据出发城市获取分类页
     * 
     * @param array $params
     * @return array
     */
    public function getClassInfoByCity($params) {
    	// 返回结果
        return BuckbeekIao::getClassInfoByCity($params);
    }
    
    /**
     * 保存分类页全局配置
     * 
     * @param array $params
     * @return array
     */
    public function saveOverallConfig($params) {
    	// 返回结果
        return BuckbeekIao::saveClassOverallConfig($params);
    }
    
    /**
     * 保存分类页特殊配置
     * 
     * @param array $params
     * @return array
     */
    public function saveSpecialConfig($params) {
    	// 返回结果
        return BuckbeekIao::saveClassSpecialConfig($params);
    }
    
    /**
     * 查询分类页特殊配置
     * 
     * @param array $params
     * @return array
     */
    public function getSpecialConfig($params) {
    	// 返回结果
        return BuckbeekIao::getClassSpecialConfig($params);
    }
	
}
