<?php
/**
 * hagrid对外接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-06-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.product.ChannelMod');

class ChannelController extends restSysServer {

	private $channelMod;
    
    function __construct() {
        $this->channelMod = new ChannelMod();
    }
    
    
    /**
     * 查询招客宝的频道页区块信息
     */
    public function doRestGetChannelinfobycity($url, $data) {
    	// 若参数正确，则查询招客宝的频道页区块信息
    	if (!empty($data['startCityCode']) && (!empty($data['isMinor']) || 0 == $data['isMinor'])) {
            // 获取供应商信息
            $result = $this->channelMod->getChannelChannelForHA($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 保存全局配置
     */
    public function doRestPostOverallconfig($data) {
    	// 若参数正确，则保存全局配置
    	if (!empty($data['showDateId']) && !empty($data['startCityCodes']) && !empty($data['rows'])) {
            // 保存全局配置
            $result = $this->channelMod->saveOverallConfig($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 保存特殊非统一配置
     */
    public function doRestPostSpecialnoconfig($data) {
    	// 若参数正确，则保存特殊非统一配置
    	if (!empty($data['showDateId']) && !empty($data['rows'])) {
            // 保存特殊非统一配置
            $result = $this->channelMod->saveSpecialNoConfig($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 保存特殊统一配置
     */
    public function doRestPostSpecialyesconfig($data) {
    	// 若参数正确，则保存特殊非统一配置
    	if (!empty($data['showDateId']) && !empty($data['floorPrice']) && !empty($data['adProductCount']) && !empty($data['couponUsePercent'])) {
            // 保存特殊非统一配置
            $result = $this->channelMod->saveSpecialYesConfig($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }

    /**
     * 查询招客宝的频道页特殊配置列表信息
     */
    public function doRestGetSpecialconfig($url,$data) {
    	// 若参数正确，则查询

    	if (!empty($data['showDateId'])) {
            // 保存全局配置
            $result = $this->channelMod->getSpecialconfig($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
}