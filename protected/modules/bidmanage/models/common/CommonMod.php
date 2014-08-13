<?php
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');

class CommonMod {
	
	private $commonDao;
	
	private $_iaoProductMod;
	
	function __construct() {
		$this->commonDao = new CommonDao();
		$this->_iaoProductMod = new IaoProductMod();
	}
	
	/**
	 * 出发城市
	 * @param unknown_type $cityCode
	 * @return Ambigous <multitype:, unknown>|multitype:
	 */
	public function getDepartCityInfo($cityCode) {
		$info = $this->commonDao->getDepartCityInfo($cityCode);
		if(!empty($info)){
			return $info;
		}else{
			return array();
		}
	}
	
	/**
	 * 广告位信息
	 * @param unknown_type $params
	 * @return Ambigous <multitype:, unknown>|multitype:
	 */
	public function readAdPosition($params) {
		$info = $this->commonDao->readAdPosition($params);
		if(!empty($info)){
			return $info;
		}else{
			return array();
		}
	}
	
	/**
	 * 获取后台需要用到的出发城市
	 */
	public function getBackCity() {
		$beginCityList = array();
		$memcacheKey = md5('CommonController.doRestGetStartCityBackground');
	    $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
	    if(!empty($finalBeginCityResult) && !empty($finalBeginCityResult['all']) && !empty($finalBeginCityResult['major']) 
	    	&& !empty($finalBeginCityResult['minor']) && !empty($finalBeginCityResult['list']) && !empty($finalBeginCityResult['isMajor']) 
	    	&& !empty($finalBeginCityResult['isMinor'])){
	        $beginCityList = $finalBeginCityResult;
	    } else {
	        $beginCityList = $this->_iaoProductMod->getMultiCityInfo();
	        $cityAll = $beginCityList['all'];
	        foreach ($cityAll as $cityAllObj) {
	         	$beginCityList['list'][$cityAllObj['code']] = $cityAllObj['name'];
	        }
	        $cityMajor = $beginCityList['major'];
	        $isMajor = array();
	        foreach ($cityMajor as $cityMajorObj) {
	         	array_push($isMajor, $cityMajorObj['code']);
	        }
	        $beginCityList['isMajor'] = $isMajor;
	        $cityMinor = $beginCityList['minor'];
	        $isMinor = array();
	        foreach ($cityMinor as $cityMinorObj) {
	         	array_push($isMinor, $cityMinorObj['code']);
	        }
	        $beginCityList['isMinor'] = $isMinor;
	        // 缓存24h
	        Yii::app()->memcache->set($memcacheKey, $beginCityList, 86400); 		
	    }
	    return $beginCityList;
	}
}

?>