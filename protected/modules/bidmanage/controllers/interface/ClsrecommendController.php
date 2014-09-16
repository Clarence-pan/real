<?php
/**
 * hagrid对外接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-07-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.product.ClsrecommendMod');

class ClsrecommendController extends restSysServer {

	private $clsrecommendMod;
    
    function __construct() {
        $this->clsrecommendMod = new ClsrecommendMod();
    }
   
    
	/**
	 * 根据出发城市获取分类页
	 */
	public function doRestGetClassinfobycity($url, $data) {
		
		// 校验参数
		if (!empty($data['startCityCode']) && !empty($data['showDateId']) && 1 == 1) {
				$result = $this->clsrecommendMod->getClassInfoByCity($data);
			$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}

	}



	/**
	 * 保存分类页全局配置
	 */
	public function doRestPostOverallconfig($data) {
		
		// 校验参数
		if (!empty($data['rows']) && !empty($data['startCityCodes']) && !empty($data['showDateId']) && 1 == 1) {
				$result = $this->clsrecommendMod->saveOverallConfig($data);
			$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}

	}



	/**
	 * 保存分类页特殊配置
	 */
	public function doRestPostSpecialconfig($data) {
		
		// 校验参数
		if (!empty($data['rows']) && !empty($data['showDateId']) && !empty($data['classDepth'])
			&& !empty($data['floorPrice']) && !empty($data['adProductCount']) && !empty($data['couponUsePercent'])) {
				$result = $this->clsrecommendMod->saveSpecialConfig($data);
			$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}

	}



	/**
	 * 查询分类页特殊配置
	 */
	public function doRestGetSpecialconfig($url, $data) {
		
		// 校验参数
		if (!empty($data['classDepth']) && !empty($data['showDateId']) && 1 == 1) {
				$result = $this->clsrecommendMod->getSpecialConfig($data);
			$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}

	}
	
}