<?php
/** UI呈现接口 | Hagrid 频道页
 * Hagrid user interfaces for inner UI system.
 * @author p-sunhao@2013-11-12
 * @version 1.0
 * @func doRestGetPackageDate
 * @func doRestPostPackageDate
 */
Yii::import('application.modules.manage.models.product.ChannelMod');

/**
 * 频道页UI接口类
 * @author p-sunhao@2013-11-12
 */
class ChannelController extends restfulServer{
	 
	/**
	 * 频道页操作类
	 */
	private $channelMod = null;
	
	/**
	 * 默认构造函数
	 */ 
	function __construct() {
		// 初始化频道页操作类
		$this->channelMod = new ChannelMod();
	}
	 
	/**
     * 查询招客宝的频道页区块信息
     */
    public function doRestGetChannelinfobycity($url, $data) {
        // 获取供应商信息
        $result = $this->channelMod->getChannelinfobycity($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 保存全局配置
     */
    public function doRestPostOverallconfig($data) {
        // 保存全局配置
        $result = $this->channelMod->saveOverallConfig($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
	
	/**
	 * 保存非统一特殊配置
	 */
	public function doRestPostSpecialnoconfig($data) {
        // 保存非统一特殊配置
        $result = $this->channelMod->saveSpecialNoConfig($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
	 * 保存统一特殊配置
	 */
    public function doRestPostSpecialyesconfig($data) {
        // 保存统一特殊配置
        $result = $this->channelMod->saveSpecialYesConfig($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
 	/**
     * 查询招客宝的频道页特殊配置列表信息
     */
    public function doRestGetSpecialconfig($url, $data) {
        // 获取供应商信息
        $result = $this->channelMod->getSpecialconfig($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }   
    
}
?>
