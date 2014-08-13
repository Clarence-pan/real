<?php
/**
 * UI呈现接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-06-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.product.ChannelMod');

class ChannelController extends restUIServer {

	private $channelMod;
    
    function __construct() {
        $this->channelMod = new ChannelMod();
    }
    
    /**
     * 查询招客宝的频道页区块信息
     */
    public function doRestGetChannelinfobycity($url, $data) {
    	// 若参数正确，则查询招客宝的频道页区块信息
    	if (!empty($data['startCityCode'])) {
            // 获取供应商信息
            $result = $this->channelMod->getChannelChannelForBB($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }

    /**
     * 查询当前可参与竞拍的频道页的广告位
     * @param array $param
     * @return array
     */
    public function doRestGetChannelchosenadkey($url, $data) {
     	// 若参数正确，则查询招客宝的频道页区块信息
    	if (!empty($data['startCityCode']) && !empty($data['channelId'])) { 
            // 获取供应商信息
	       $data['accountId'] = $this->getAccountId();
	       $result = $this->channelMod->getChannelChosenAdKey($data);
	       $this->returnRest($result);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}   	
    }
    
}