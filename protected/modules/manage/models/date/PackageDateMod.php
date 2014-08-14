<?php
/** 操作类 | Hagrid 打包时间
 * Hagrid models.
 * @author p-sunhao@2013-11-12
 * @version 1.0
 * @func doRestGetPackageDate
 * @func doRestPostPackageDate
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
Yii::import('application.modules.manage.dal.iao.TuniuIao');

/**
 * 打包时间操作类
 * @author p-sunhao@2013-11-12
 */
class PackageDateMod {
	
    /**
     * 获取打包时间信息
     * @param array $params
     * @return array
     */
    public function getPackageDate($params) {
    	// 返回结果
        return BuckbeekIao::getPackageDate($params);
    }
    
	/**
     * 修改或添加打包时间信息
     * @param array $params
     * @return array
     */
    public function postPackageDate($params) {
	    // 返回结果
        return BuckbeekIao::postPackageDate($params);
    }
    /**
     * 获取网站提供的有效预定城市（出发城市）列表
     * @return array
     */
    public function getMultiCityInfo()
    {
        //设置缓存
        $key = md5(json_encode("getMultiCityInfo"));
        $data = Yii::app()->memcache->get($key);
        if (!empty($data)) {
            $result = $data;
        } else {
            $result = BuckbeekIao::getMultiCityInfo();
            if ($result) {
                Yii::app()->memcache->set(md5(json_encode("doRestGetStartCity")), $result, 43200);
            }
        }
        return $result;
    }

    /**
     * 广告位管理列表
     *
     * @param $params
     * @return array
     */
    public function getAdManageList($params)
    {
        return BuckbeekIao::getAdManageList($params);
    }

    /**
     * 删除广告位
     *
     * @param $params
     * @return array
     */
    public function postAdDel($params)
    {
        return BuckbeekIao::postAdDel($params);
    }

    /**
     * 添加广告位类型列表
     *
     * @param $params
     * @return array
     */
    public function getAdAddList($params)
    {
        // 初始化变量
        $temp = array();
        $result = array();
        $isExistArr = array();
        $isExistParam = array();
        $adList = array();
        if ($params['adKey'] == 'index_chosen') {
            // 首页网站数据获取
            $params['adName'] = '首页';
            $adList = TuniuIao::getAdAddList($params);
        } else if ($params['adKey'] == 'channel_chosen') {
            // 频道页网站数据获取
            $params['adName'] = '频道页';
            $adList = TuniuIao::getTuniuChannelAdList($params['startCityCode']);
        }
        // 结果处理
        if ($adList) {
            // 获取所有出发城市
            $startCityInfo = $this->getMultiCityInfo();
            // 循环拼接数据
            foreach ($adList as $tempAdArr) {
                $header = $tempAdArr['header'];
                $items = $tempAdArr['items'];
                foreach ($items as $tempItemsArr) {
                    $adKey = $params['adKey'] . '_' . $header['id'] . '_' . $tempItemsArr['id'];
                    $adName = '';
                    if ($params['adKey'] == 'index_chosen') {
                        $adName = $params['adName'] . '-' . $header['title'] . '-' . $tempItemsArr['title'];
                    } else if ($params['adKey'] == 'channel_chosen') {
                        $adName = $params['adName'] . '-' . $header['title'] . $params['adName'] . '-' . $tempItemsArr['title'];
                        $temp['channelId'] = $header['id'];
                        $temp['channelName'] = $header['title'];
                        $temp['blockId'] = $tempItemsArr['id'];
                        $temp['blockName'] = $tempItemsArr['title'];
                    }

                    $temp['adKey'] = $adKey;
                    $temp['adName'] = $adName;
                    $temp['startCityCode'] = $params['startCityCode'];
                    // 根据出发城市code获取name
                    if ($startCityInfo['all']) {
                        foreach ($startCityInfo['all'] as $tempArr) {
                            if ($tempArr['code'] == $temp['startCityCode']) {
                                $temp['startCityName'] = $tempArr['name'];
                                break;
                            } else {
                                $temp['startCityName'] = '';
                            }
                        }
                    }
                    // 判断出发城市是否是主营城市
                    if ($startCityInfo['major']) {
                        foreach ($startCityInfo['major'] as $tempArr) {
                            if ($tempArr['code'] == $temp['startCityCode']) {
                                $temp['isMajor'] = 1;
                                break;
                            } else {
                                $temp['isMajor'] = 0;
                            }
                        }
                    }

                    $temp['classify'] = $tempItemsArr['classify'];
                    $temp['destination'] = $tempItemsArr['destination'];
                    $temp['catType'] = implode(',', $header['catType']);
                    $result[] = $temp;
                    array_push($isExistArr,strval( $temp['adKey']));
                    $isExistParam = array('adKeyArr' => $isExistArr,'startCityCode' => $temp['startCityCode']);
                }
            }

            // 查询广告位是否已经存在
            $tempData = BuckbeekIao::getAdIsExist($isExistParam);
            foreach ($result as $k => $temp) {
                // 存在则把addFlag置为1，否则置为0
                if ($tempData) {
                    foreach ($tempData as $existAd) {
                        if ($temp['adKey'] == $existAd['adKey']) {
                            $result[$k]['addFlag'] = 1;
                            break;
                        } else {
                            $result[$k]['addFlag'] = 0;
                        }
                    }
                } else {
                    $result[$k]['addFlag'] = 0;
                }
            }
        }
        return $result;
    }

    /**
     * 添加广告位
     *
     * @param $params
     * @return array
     */
    public function postAdAdd($params)
    {
        $result = BuckbeekIao::postAdAdd($params);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询可添加和编辑包场的运营计划
     * @param array $params
     * @return array
     */
    public function getBuyoutDate($params) {
        // 返回结果
        return BuckbeekIao::getBuyoutDate($params);
    }

    /**
     * 查询打包日期详情
     * @param array $params
     * @return array
     */
    public function getShowDateInfo($params) {
        // 返回结果
        return BuckbeekIao::getShowDateInfo($params);
    }

    /**
     * 保存打包计划日期
     * @param array $params
     * @return array
     */
    public function saveShowDateInfo($params) {
        // 返回结果
        return BuckbeekIao::saveShowDateInfo($params);
    }

    /**
     * 查询广告位置信息
     * @param array $params
     * @return array
     */
    public function getAdPositionInfo($params) {
        // 返回结果
        return BuckbeekIao::getAdPositionInfo($params);
    }
    
    /**
     * 获取首页所有位置的配置信息
     * 
     * @author wenrui 2014-06-05
     */
    public function getIndexAdList($param){
    	$result = BuckbeekIao::getIndexAdList($param);
		return $result;
    }
    
    /**
	 * 添加多个广告位的运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
    public function addPakDtList($param){
    	$result = BuckbeekIao::addPakDtList($param);
    	return $result;
    }
    
	/**
	 * 添加运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
    public function addPakDt($param){
    	$result = BuckbeekIao::addPakDt($param);
    	return $result;
    }
    
    /**
	 * 打开或关闭广告位
	 */
    public function updatePackOpenStatus($param){
    	$result = BuckbeekIao::updatePackOpenStatus($param);
    	return $result;
    }
    
 }
?>
