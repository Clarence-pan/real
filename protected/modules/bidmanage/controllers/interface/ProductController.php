<?php
/**
 * 对外系统接口 | 收客宝产品相关
 * * Buckbeek account interfaces for outer system.
 * @author wanglongsheng@2013-01-04
 * @version 1.0
 * @func doRestGetBidShowList
 */
Yii::import('application.modules.bidmanage.models.product.ProductMod');
Yii::import('application.modules.bidmanage.models.product.ReleaseProductMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');

class ProductController extends restSysServer {
    /*interface*/
    private $_productMod;
    private $_releaseProductMod;
    private $packageDateDao;
    function __construct() {
        $this->_productMod = new ProductMod();
        $this->_releaseProductMod = new ReleaseProductMod;
        $this->packageDateDao = new PackageDateDao();
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = NULL
     * $response = $client->request(RESTFUL_POST, $url, $request_data);
     *
     * @mapping /product
     * @method POST
     * @param  
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】更新供应商产品信息
     */
    public function doRestGetProduct($data) {
        // 更新产品信息
        $this->_productMod->updateProductProcess();

        //打印到页面呈现
        $this->returnRest('更新供应商产品信息成功');
    }

    /**
     * 查询指定条件的竞价成功产品列表
     *
     * @author chenjinlong 20130314
     */
    /**
     * $client = new RESTClient();
     * $params = array();
     * $format = 'encrypt';
     * $res = $client->get($url, $params, $format);
     * @mapping /bidshowlist
     * @method GET
     * @author chenjinlong 20130314
     * @param array $urlVar
     * @param  array $requestData {"accountId":4333,"showDate":}
     * @return array {"success":true,"msg":"成功","errorCode":230000,"data":}
     * @desc  更新bb_effect统计数据
     */
    public function doRestGetBidShowList($urlVar, $requestData)
    {
        if(!empty($requestData['accountId']) || !empty($requestData['showDate'])){
            $showDate = strval($requestData['showDate']);
            $inParam = array(
                'account_id' => intval($requestData['accountId']),
                'show_date' => $showDate,
            );
            $relProductRows = $this->_releaseProductMod->getCustomDateShowProductArray($inParam);
            $renderRows = array();
            if(!empty($relProductRows)){
                foreach($relProductRows as $row)
                {
                    $renderRows[] = array(
                        'id' => $row['id'],
                        'accountId' => $row['account_id'],
                        'productId' => $row['product_id'],
                        'bidDate' => $showDate,
                        'adKey' => $row['ad_key'],
                        'catType' => $row['cat_type'],
                        'webClass' => $row['web_class'],
                        'startCityCode' => $row['start_city_code'],
                        'bidPrice' => $row['bid_price'],
                        'ranking' => $row['ranking'],
                        'bidId' => $row['bid_id'],
                        'productType' => $row['product_type'],
                    );
                }
            }
            $this->returnRest($renderRows);
        }else{
            $this->returnRest('', false, 230001, '参数不符合接口约定');
        }
    }

    /**
     * 招客宝改版-查询所有预订/出发城市信息
     *
     * @author chenjinlong 20131121
     * @param $urlVar
     * @param $requestData
     */
    public function doRestGetDeparturelist($urlVar, $requestData)
    {
        if(empty($requestData['cityCode'])){
            $this->returnRest('', false, 230001, '参数不符合接口约定');
        }else{
            $allDepartureCities = $this->_productMod->getAllDepartureInfo($requestData);
            if(!empty($allDepartureCities)){
                $this->returnRest($allDepartureCities);
            }else{
                $this->returnRest(array());
            }
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /hglist
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-查询推广产品列表
     */
    public function doRestGetHgList ($url, $data) {
        $params = array(
            'accountId' => $data['accountId'],
            'bidState' => $data['bidState'],
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
            'productId' => $data['productId'],
            'productName' => $data['productName'],
            'vendorId' => $data['vendorId'],
            'vendorName' => $data['vendorName'],
            'checkFlag' => $data['checkFlag'],
            'managerId' => $data['managerId'],
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10,
            'sortName' => $data['sortName'],
            'sortOrder' => $data['sortOrder'],
            'adName' => $data['adName'],
        );
        $hgProductList = $this->_productMod->getHgProductList($params);
        $hgProductCount = $this->_productMod->getHgProductListCount($params);
        if (count($hgProductList) > 0) {
            $this->returnRest(array('count' => $hgProductCount['count'], 'rows' => $hgProductList));
        } else {
            $this->returnRest(array('count' => 0, 'rows' => array()), true, 230000, array());
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /hgfile
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-查询推广产品列表
     */
    public function doRestGetHgFile ($url, $data) {
        $params = array(
            'accountId' => $data['accountId'],
            'bidState' => $data['bidState'],
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
            'productId' => $data['productId'],
            'productName' => $data['productName'],
            'vendorId' => $data['vendorId'],
            'vendorName' => $data['vendorName'],
            'checkFlag' => $data['checkFlag'],
            'managerId' => $data['managerId'],
            'start' => intval($data['start']),
            'limit' => intval($data['limit']),
            'sortName' => $data['sortName'],
            'sortOrder' => $data['sortOrder'],
            'adName' => $data['adName'],
        );
        $hgProductFile = $this->_productMod->getHgProductList($params);
        $this->returnRest($hgProductFile);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /manager
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-查询产品经理
     */
    public function doRestGetManager ($url, $data) {
        $params = array(
            'managerName' => $data['managerName'],
        );
        $managerInfo = $this->_productMod->getManagerName($params);
        $this->returnRest($managerInfo);
    }
    
    /**
     * 获取招客宝推广成功的产品
     * 
     * @author wenrui 2014-03-21
     */
    public function doRestGetShowProduct ($url, $data) {
    	// 判断广告位置字段
    	if (!empty($data["adKey"])) {
    		$adKey = $data["adKey"];
    		// 首页、专题页、品牌专区对应出发城市
    		// 分类页对应出发城市和分类id
    		// 搜索页对应出发城市和搜索关键字
    		if (((strpos($adKey, 'index_chosen') !== false || strpos($adKey, 'channel_chosen') !== false || $adKey == "special_subject" || 
    			$adKey == "brand_zone") && !empty($data["startCityCode"])) ||  ($adKey == "class_recommend" && !empty($data["startCityCode"]) && !empty($data["webClass"])) 
    			|| ($adKey == "search_complex" && !empty($data["startCityCode"]) && !empty($data["searchKeyword"]))) {

                $memKey = "doRestGetShowProduct_" . strval($adKey) . "_" . intval($data["startCityCode"]) . "_" . intval($data["webClass"]) .
                    "_" . strval($data["searchKeyword"]) . "_" . strval($data["showDate"]);
                $showProductList = Yii::app()->memcache->get($memKey);
                if(!empty($showProductList) && is_array($showProductList)){
                    $result = $showProductList;
                }else{
                    $result = $this->_productMod->getShowProduct($data);
                    foreach($result as &$row)
                    {
                        $row['contentType'] = ConstDictionary::$bbRorProductMapping[$row['contentType']];
                    }
                    //缓存0.5h
                    Yii::app()->memcache->set($memKey, $result, 1800);
                }
    			$this->returnRest($result);
    		} else {
    			$this->returnRest(array(),false,210000,"入参不齐全");
    		}
    	} else {
    		$this->returnRest(array(),false,210000,"入参不齐全");
    	}
    }
    
    /**
     * 获得广告位操作记录
     */
    public function doRestGetProducthis($url, $data) {
    	// 初始化返回结果
    	$result = array();
    	// 若参数正确，则查询广告位操作记录
    	if (!empty($data['bidId']) && is_numeric($data['bidId']) && !empty($data['viewState']) && is_numeric($data['viewState']) && (!empty($data['start']) || 0 == $data['start']) && is_numeric($data['start']) && !empty($data['limit']) && is_numeric($data['limit'])) {
    		// 查询广告位操作记录
    		$result = $this->_productMod->getProductHis($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    	
    }
    
    /**
     * 保存/编辑包场记录
     */
    public function doRestPostBuyout($data) {
    	// 若参数正确，则查询广告位操作记录
    	if (!empty($data['agencyId']) && !empty($data['showDateId']) && is_numeric($data['showDateId']) 
    		&& !empty($data['adKey']) && !empty($data['saveFlag']) && !empty($data['adKeyType']) && isset($data['webClass'])) {
            // 获取accountId
            $manage = new UserManageMod;
            $accountInfo = $manage->getAccountInfoByAgentId($data['agencyId']);
            $data['accountId'] = $accountInfo['id'];
    		// 查询广告位操作记录
    		$result = $this->_productMod->saveBuyout($data);
    		// 返回结果
    		$this->returnRest($result);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 获得包场信息
     */
    public function doRestGetBuyout($url, $data) {
    	// 若参数正确，则查询包场信息
    	if (!empty($data['bidState']) && is_numeric($data['bidState']) && (!empty($data['start']) || 0 == $data['start']) && is_numeric($data['start']) && !empty($data['limit']) && is_numeric($data['limit'])) {
    		// 查询包场信息
            $list = $this->_productMod->queryBuyout($data);
            $count = $this->_productMod->queryBuyoutCount($data);
            // 返回结果
            if (count($list) > 0) {
                $this->returnRest(array('count' => $count, 'rows' => $list));
            } else {
                $this->returnRest(array('count' => 0, 'rows' => array()));
            }
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 获得包场广告位类型
     */
    public function doRestGetBuyoutType($url, $data) {
        if (!empty($data['showDateId'])) {
            // 查询广告位类型
            $result = $this->_productMod->getBuyoutTypeName($data);
            // 返回结果
            $this->returnRest($result);
        } else {
            // 返回参数不正确
            $this->returnRest(array(), false, 210000, '请选择推广时间！');
        }
    }
    
    /**
     * 获得产品类型
     */
    public function doRestGetProductType($url, $data) {
        // 获取类型常量
        $result = ConstDictionary::$bbProductTypeList;
        // 初始化临时变量
        $rows = array();
        // 初始化最终结果变量
        $resultRows = array();
        foreach ($result as $key => $temp) {
            array_push($rows, array('productTypeId' => $key, 'productTypeName' => $temp));
        }
        // 如果为包含index_chosen的首页广告位时，过滤其产品种类权限
        if (strpos($data['adKey'],'index_chosen') !== false && $data['startCityCode']) {
            // 过滤可选的产品类型
            $adCategory = $this->packageDateDao->getAdCategory(array('adKey' => $data['adKey'], 'startCityCode' => $data['startCityCode']));
            if ($adCategory) {
                // 产品类型转换
                $convertResult = array();
                $classBrandTypes = json_decode($adCategory['classBrandTypes'],true);
                $globalBbProductType = ConstDictionary::$bbRorProductMapping;
                foreach ($globalBbProductType as $k => $temp) {
                    foreach ($classBrandTypes as $value) {
                        if ($temp == $value) {
                            array_push($convertResult,strval($k));
                        }
                    }
                }
                // 产品权限变更
                foreach ($rows as $key => $value) {
                    if (!in_array($value['productTypeId'],$convertResult)) {
                        unset($rows[$key]);
                    }
                }
                // 首页结果处理
                foreach ($rows as $value) {
                    array_push($resultRows, $value);
                }
            }
        } elseif ($data['adKey'] == 'brand_zone') {
            // 品牌专区特殊处理
            array_push($resultRows , array('productTypeId' => 500, 'productTypeName' => '供应商编号'));
        } else {
            $resultRows = $rows;
        }
        // 返回结果
        $this->returnRest($resultRows);
    }
    
    /**
     * 删除包场记录
     */
    public function doRestPostDelbuyout($data) {
    	// 若参数正确，则查询广告位操作记录
    	if (!empty($data['showDateId'])) {
    		// 查询广告位操作记录
    		$result = $this->_productMod->delBuyout($data);
    		// 返回结果
    		$this->returnRest($result);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 获得包场分类页信息
     */
    public function doRestGetWebClassInfo($url, $data) {
    	// 获取分类页广告位
    	$result = $this->_productMod->queryWebClassInfo($data);
    	// 返回结果
    	$this->returnRest($result);
    }

    /**
     * 查询包场搜索关键词
     */
    public function doRestGetSearchad($url, $data) {
        // 查询数据库
        $result = $this->_productMod->getKeywordData($data);
        // 返回结果
        $this->returnRest($result);
    }
    
    /**
     * 对外接口：删除招客宝广告位
     * 
     * @author wenrui 2014-05-28
     */
    public function doRestGetDelAdPosition($url, $data) {
    	if(!empty($data) && !empty($data['adKey']) && !empty($data['startCityCode'])){
    		$data['date'] = date('Y-m-d');
    		$result = $this->_productMod->delAdPosition($data);
    		$this->returnRest($result['data'],$result['flag'],0,$result['msg']);
    	} else {
    		$this->returnRest(array(),false,0,'删除失败：接口传入参数不符合规则');
    	}
    }
    
    public function doRestGetTestphp($url, $data) {
		// var_dump(__FUNCTION__);die;
		// var_dump(__CLASS__);die;
		// var_dump(__METHOD__);die;
		// var_dump(get_class());die;
		// var_dump(get_class_methods(ProductController));die;
		// var_dump(get_class_vars(ProductController));die;
		// var_dump(__LINE__);
//		var_dump($this->get_hash_table('interface_log', 'asdsad'));
//		var_dump($this->get_hash_table('interface_log', 'xcxzcx'));
//		var_dump(floor(microtime(true)*1000));
//		$test = array(1,2,3);
//		var_dump($test);
//		unset($test[2]);
//		var_dump($test);

//	    var_dump(memory_get_usage());
//	    $array = array_fill(1, 100, "laruence");
//	   //  var_dump($array);
//	    foreach ($array as $key => $value) {
//	        ${$value . $key} = NULL;
//	    }
//	    var_dump(memory_get_usage());
//	    foreach ($array as $key=> $value) {
//	        unset(${$value . $key});
//	    }
//	    var_dump(memory_get_usage());
//
//		var_dump($this->guid());die;

//	    var_dump(memory_get_usage());
//	    $array = array();
//	    $a = $this->guid();
//	    $array[$a] = floor(microtime(true)*1000);
//	    $b = $this->guid();
//	    $array[$b] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    $array[$this->guid()] = floor(microtime(true)*1000);
//	    var_dump($array);
//	    var_dump(memory_get_usage());
//	    unset($array[$a]);
//	    unset($array[$b]);
//	    var_dump($array);
//	    var_dump(memory_get_usage());
//
//		foreach($array as $key=>$val) {
//			unset($array[$key]);
//			break;
//		}
//		var_dump($array);
//	    var_dump(memory_get_usage());
//	    
//	    $arr = array();
//	    array_push($arr, 1);
//	    array_push($arr, 2);
//	    array_push($arr, 3);
//	    var_dump($arr);
//	    foreach($arr as $key=>$val) {
//			unset($arr[$key]);
//			break;
//		}
//		var_dump($arr);
//		
//		var_dump($this->guid());
//		var_dump(strlen($this->guid()));


		// 监控调用示例
//		$posUP = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);
//		$pos = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);
//		$array = array_fill(1, 1000000, "laruence");
//		BPMoniter::endMoniter($pos, 5, __LINE__);
//		$pos = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);
//		$array = array_fill(1, 1000000, "laruence");
//		var_dump(BPMoniter::getMoniter($pos));
//		// var_dump(BPMoniter::getMoniter($posUP));
//		BPMoniter::endMoniter($posUP, 10, __LINE__);
		
//		$e = new BBException();
		
//		try {
//			// 操作dB
//			// $db = $this->daoMod->test();
//			
//			// 如果是查询需要校验返回
//			
//			// 如果是列表需要整合rows和count
//			
//			// 整合最终返回非异常结果
//			// 返回的数据
//			$result['data'] = $data;
//			// 是否成功
//			$result['success'] = true;
//			// 提示  引用常量
//			$result['msg'] = '查询成功！';
//			// 编码  引用常量
//			$result['errorCode'] = 230000;
//            // 整合错误结果
//            throw new BBException();
//            			
//		} catch (Exception $e) {
//			var_dump($e->getCode(), $e->getMessage());
//
//        }
		$bbLog = new BBLog();
		
		try {
			throw new Exception();
		} catch(Exception $e) {
			if ($bbLog->isInfo()) {
				$bbLog->logException(ErrorCode::ERR_231201, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231201)], "111", CommonTools::getErrPos($e));
			}
		}

		die;
		
	}
	
	function guid(){
	    if (function_exists('com_create_guid')){
	        return com_create_guid();
	    }else{
	        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45);// "-"
	        // $uuid = chr(123)// "{"
			$uuid = substr($charid, 0, 8).$hyphen
	                .substr($charid, 8, 4).$hyphen
	                .substr($charid,12, 4).$hyphen
	                .substr($charid,16, 4).$hyphen
	                .substr($charid,20,12);
	                // .chr(125);// "}"
	        return $uuid;
	    }
	}
	
	function get_hash_table($table,$userid) {   
 		$str = crc32($userid);
 		var_dump($str);   
 		if($str<0){   
 			$hash = "0".substr(abs($str), 0, 1);   
 		}else{   
 			$hash = substr($str, 0, 2);   
 		}   
  
 		return $table."_".$hash;   
	} 

}