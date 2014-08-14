<?php
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');

class ChannelMod {
	
	/**
     * 查询招客宝的频道页区块信息
     * 
     * @param array $params
     * @return array
     */
    public function getChannelinfobycity($params) {
    	// 返回结果
        return BuckbeekIao::getChannelinfobycity($params);
    }

	/**
	 * 保存全局配置
	 */
	public function saveOverallConfig($params) {
		// 返回结果
        return BuckbeekIao::saveOverallConfig($params);
	}

	/**
	 * 保存特殊非统一配置
	 */
	public function saveSpecialNoConfig($params) {
		// 返回结果
        return BuckbeekIao::saveSpecialNoConfig($params);
	}
	
	/**
	 * 保存特殊统一配置
	 */
	public function saveSpecialYesConfig($params) {
		// 返回结果
        return BuckbeekIao::saveSpecialYesConfig($params);
	}
	
 	/**
     * 查询招客宝的频道页特殊配置列表信息
     */
    public function getSpecialconfig($params) {
    	// 返回结果
        return BuckbeekIao::getSpecialconfig($params);
    }     
     

}
