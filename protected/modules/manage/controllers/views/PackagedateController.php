<?php
/** UI呈现接口 | Hagrid 打包时间
 * Hagrid user interfaces for inner UI system.
 * @author p-sunhao@2013-11-12
 * @version 1.0
 * @func doRestGetPackageDate
 * @func doRestPostPackageDate
 */
Yii::import('application.modules.manage.models.date.PackageDateMod');

/**
 * 打包时间UI接口类
 * @author p-sunhao@2013-11-12
 */
class PackagedateController extends restfulServer{
	 
	/**
	 * 打包时间操作类
	 */
	private $_packageDateMod = null;
	
	/**
	 * 默认构造函数
	 */ 
	function __construct() {
		// 初始化时间操作类
		$this->_packageDateMod = new PackageDateMod();
	}
	
	/**
	 * 获得打包时间信息
	 * 
	 * @param $url
	 * @param $paramData
	 * @return array
	 */
	public function doRestGetPackagedate($url, $paramData) {
		// 查询打包时间
		$result = $this->_packageDateMod->getPackageDate($paramData);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 修改打包时间
	 * 
	 * @param $url
	 * @param $paramData
	 * @return array
	 */
	public function doRestPostPackagedate($paramData) {
		// 获得结果
		$result = $this->_packageDateMod->postPackageDate($paramData);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
    /**
     * 获取网站提供的有效预定城市（出发城市）列表
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestGetStartCity($url, $paramData) {
        $result = $this->_packageDateMod->getMultiCityInfo();
        $this->returnRest($result);
    }

    /**
     * 广告位管理列表
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestGetAdManageList($url, $paramData) {
        $result = $this->_packageDateMod->getAdManageList($paramData);
        $this->returnRest($result);
    }

    /**
     * 删除广告位
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestPostAdDel($paramData) {
        $result = $this->_packageDateMod->postAdDel($paramData);
        if (-1 == $result) {
            $this->returnRest($result, true, 230021, '该广告位已经参与竞拍, 不能删除！');
        } else {
            $this->returnRest($result);
        }
    }

    /**
     * 添加广告位类型列表
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestGetAdAddList($url, $paramData) {
        $result = $this->_packageDateMod->getAdAddList($paramData);
        $data = array('count' => 1, 'rows' => $result);
        $this->returnRest($data);
    }

    /**
     * 添加广告位
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestPostAdAdd($paramData) {
        $result = array();
        foreach ($paramData['addedList'] as $temp) {
            $classify = array();
            $destination = array();
            $catType = array();
            if ($temp['classify']) {
                $classify = explode(',',$temp['classify']);
            }
            if ($temp['destination']) {
                $destination = explode(',',$temp['destination']);
            }
            if ($temp['catType']) {
                $catType = explode(',',$temp['catType']);
            }
            $input = array(
                'adKey'=>$temp['adKey'],
                'adName'=>$temp['adName'],
                'startCityCode'=>$temp['startCityCode'],
                'categoryId'=>$destination,
                'classBrandTypes'=>$classify,
                'catType' => $catType,
                'addUid'=>$paramData['uid'],
                'addTime'=>date('Y-m-d H:i:s'),
                'adKeyType'=>(strpos($temp['adKey'],'channel_chosen') !== false) ? 5 : 1,
                'channelId'=>($temp['channelId']) ? $temp['channelId'] : 0,
                'channelName'=>($temp['channelName']) ? $temp['channelName'] : '',
                'blockId'=>($temp['blockId']) ? $temp['blockId'] : 0,
                'blockName'=>($temp['blockName']) ? $temp['blockName'] : '',
                'isMajor'=> $temp['isMajor']
            );
            // 过滤已添加广告位
            $indexAdInfo = $this->_packageDateMod->getAdManageList($input);
            if ($indexAdInfo && $indexAdInfo['count'] > 0) {
                continue;
            } else {
                $result = $this->_packageDateMod->postAdAdd($input);
            }
        }
        $this->returnRest($result);
    }

	/**
	 * 查询可添加和编辑包场的运营计划
	 * 
	 * @param $url
	 * @param $param
	 * @return array
	 */
	public function doRestGetBuyoutdate($url, $param) {
		// 查询可添加和编辑包场的运营计划
		$result = $this->_packageDateMod->getBuyoutDate($param);
		// 返回结果
		$this->returnRest($result);
	}

    /**
     * 查询打包日期详情
     *
     * @param $url
     * @param $param
     * @return array
     */
    public function doRestGetShowDateInfo($url, $param) {
        // 查询可添加和编辑包场的运营计划
        $result = $this->_packageDateMod->getShowDateInfo($param);
        // 返回结果
        $this->returnRest($result['data']);
    }

    /**
     * 保存打包计划日期
     *
     * @param $url
     * @param $param
     * @return array
     */
    public function doRestPostShowDateInfo($param) {
        // 查询可添加和编辑包场的运营计划
        $result = $this->_packageDateMod->saveShowDateInfo($param);
        // 返回结果
        $this->returnRest($result['data']);
    }

    /**
     * 查询广告位置信息
     *
     * @param $url
     * @param $param
     * @return array
     */
    public function doRestGetAdPositionInfo($url, $param) {
        // 查询可添加和编辑包场的运营计划
        $result = $this->_packageDateMod->getAdPositionInfo($param);
        // 返回结果
        $this->returnRest($result['data']);
    }
	
	/**
	 * 查询当前首页的广告位列表
	 * 
	 * @author wenrui 2014-06-05
	 */
	public function doRestGetIndexAdList($url, $param){
		if(!empty($param['showDateId'])){
			$result = $this->_packageDateMod->getIndexAdList($param);
			$this->returnRest($result['data'],$result['success'],$result['errorCode'],$result['msg']);
		}else{
			$this->returnRest(array(),false,0,'入参格式错误');
		}
	}
	
	/**
	 * 添加多个广告位的运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
	public function doRestPostAddPakDtList($param){
		if(!empty($param['ids'])){
			$result = $this->_packageDateMod->addPakDtList($param);
			$this->returnRest($result['data'],$result['success'],$result['errorCode'],$result['msg']);
		}else{
			$this->returnRest(array(),false,0,'入参格式错误');
		}
	}
	
	/**
	 * 添加运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
	public function doRestPostAddPakDt($param){
		if(!empty($param['showDateId'])){
			$result = $this->_packageDateMod->addPakDt($param);
			$this->returnRest($result['data'],$result['success'],$result['errorCode'],$result['msg']);
		}else{
			$this->returnRest(array(),false,0,'入参格式错误');
		}
	}
	
	/**
	 * 打开或关闭广告位
	 * 
	 * @author wenrui 2014-06-05
	 */
	public function doRestPostPackopenstatus($param){
		if(!empty($param['id'])){
			$result = $this->_packageDateMod->updatePackOpenStatus($param);
			$this->returnRest($result['data'],$result['success'],$result['errorCode'],$result['msg']);
		}else{
			$this->returnRest(array(),false,0,'入参格式错误');
		}
	}
	
}
?>
