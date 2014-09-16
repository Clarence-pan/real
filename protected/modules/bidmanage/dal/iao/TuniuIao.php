<?php
class TuniuIao {

    /**
     * 查询网站首页板块广告位列表
     *
     * @author chenjinlong 20140512
     * @param $startCityCode
     * @return array
     */
    public static function getTuniuAdList($startCityCode) {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'] . '/home/bb/' . $startCityCode;
        try {
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $response = $client->get($url, array());
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
			}
            if($response['success']) {
                return $response['data'];
            }else{
                return array();
            }
        }catch(Exception $e) {
            // 打印日志
            if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231103, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231103)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
            }
            return array();
        }
    }

    /**
     * 将网站首页广告位数据整理成一个二维数组
     *
     * @author chenjinlong 20140514
     * @param $startCityCode
     * @return array
     */
    public static function getTuniuAdListAsOneArray($startCityCode) {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'] . '/home/bb/' . $startCityCode;
        // $url = 'http://www.tuniu.com/home/bb/' . $startCityCode;
        try {
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $response = $client->get($url, array());
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
			}
            if($response['success']) {
                $tuniuAdList = is_array($response['data']) ? $response['data'] : array();
                $newAdKeyList = array();
                foreach($tuniuAdList as $blockList)
                {
                    $headerInfo = array(
                        'header_id' => $blockList['header']['id'],
                        'header_title' => $blockList['header']['title'],
                        'header_cat_type' => $blockList['header']['catType'],
                    );
                    foreach($blockList['items'] as $tabList)
                    {
                        //过滤ROR产品类型
                        $srcClassBrandTypes = !empty($tabList['classify']) ? explode(',', $tabList['classify']) : array();
                        $intersectClassBrandTypes = array_intersect($srcClassBrandTypes, array(1, 10, 12));

                        $newAdKeyList[] = array(
                            'start_city_code' => $startCityCode,
                            'ad_key' => 'index_chosen_' . $headerInfo['header_id'] . '_' . $tabList['id'],
                            'ad_name' => '首页-' . $headerInfo['header_title'] . '-' . $tabList['title'],
                            'category_ids' => !empty($tabList['destination']) ? json_encode(explode(',', $tabList['destination'])) : '[]',
                            'class_brand_types' => json_encode(array_values($intersectClassBrandTypes)),
                            'cat_types' => !empty($headerInfo['header_cat_type']) ? json_encode($headerInfo['header_cat_type']) : '[]',
                        );
                    }
                }
                return $newAdKeyList;
            }else{
                return array();
            }
        }catch(Exception $e) {
            // 打印日志
            if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231103, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231103)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
            }
            return array();
        }
    }
    
    /**
     * 查询网站频道页板块广告位列表
     *
     * @author p-sunhao 20140701
     * @param $startCityCode
     * @return array
     */
    public static function getTuniuChannelAdList($startCityCode) {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'] . '/interface/siteConfig/Channel';
        // $url = "http://172.30.20.200/interface/siteConfig/Channel";
        
        try {
        	$param['cityCode'] = $startCityCode;
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $response = $client->post($url,$param);
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
			}
            if($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }else{
                return array();
            }
        }catch(Exception $e) {
            // 打印日志
            if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231101, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231101)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
            }
            return array();
        }
    }

    /**
     * 获取网站广告位类型
     * @return array
     */
    public static function getAdAddList($params){
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'].'home/bb/'.intval($params['startCityCode']);
        // $url = 'http://www.tuniu.com/home/bb/'.intval($params);
        try {
            $response = $client->get($url);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 查询网站导航树
     *
     * @author p-sunhao 20140701
     * @param $startCityCode
     * @return array
     */
    public static function getTuniuLeftHeaderMenuInfo($param) {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'] . '/interface/leftHeaderMenu/GetLeftHeaderMenuInfo/';
        // $url = "http://www.tuniu.com/interface/leftHeaderMenu/GetLeftHeaderMenuInfo/";
        
        try {
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $response = $client->post($url,$param);
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 700, __METHOD__.'::'.__LINE__);
			}
            if($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }else{
                return array();
            }
        }catch(Exception $e) {
            // 打印日志
            if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231102, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231102)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface(array(), $url, $response, chr(48), $posM, 700, __METHOD__.'::'.__LINE__);
            }
            return array();
        }
    }

}

?>
