<?php
/**
 * BidProductIao.php
 * @copyright
 * @abstract  收客宝竞价产品接口
 * @author ZhangZheng
 * @version
 */

Yii::import('application.modules.bidmanage.models.user.UserManageMod');

class BidProductIao {
	const PRODUCT_API_PATH = "restfulServer/routeres";
	
	/**
	 * 获取供应商产品列表接口
	 * @param array $params
	 * @return array
	 */
	public static function getVendorProductList($params) {
	    $client = new RESTClient();
	    $url = Yii::app()->params['CRM_HOST'].self::PRODUCT_API_PATH.'/condlists';
	    try {
            $params['warnState'] =-1;// 接口中待有库存预警查询功能， -1 表示 忽略此条件
	        $response = $client->get($url, $params);
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
     * 招客宝改版-根据主产品编号，查询其所有主从线路的预订城市信息
     *
     * @author chenjinlong 20131118
     * @param $params
     * Contains Keys:
     * "productId":"",
     * @return array
     * Sub-Array Contains Keys:
     * "productId":"产品编号",
     * "name":"出发城市名称",
     * "code":"出发城市编号",
     * "letter":"出发城市拼音缩写",
     */
    public static function getAllProductStartCityArr($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['CRM_HOST'] . 'restfulServer/agencyprdres/start-city';
        $response = array();
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '查询主从线路信息异常', 11, 'wenrui', -20, 0, json_encode(array()));
            return array();
        }

        if($response['success']) {
        	CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '查询主从线路成功', 11, 'wenrui', 20, 0, json_encode($response));
            return $response['data'];
        }
		CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '查询主从线路信息出错', 11, 'wenrui', -20, 0, json_encode($response));
        return array();
    }
	
	/**
	 * 获取供应商新添加产品信息接口
	 * @param array $productIds
	 * @return array
	 
	public static function getProduct($productIds) {
	    $client = new RESTClient();
	    $url = Yii::app()->params['CRM_HOST'].self::PRODUCT_API_PATH.'/lists';
	    
	    $params['productIds'] = $productIds;
	    
	    try {
	        $response = $client->get($url, $params);
	    }catch(Exception $e) {
	        Yii::log($e, 'warning');
	        return array();
	    }
	    
	    if($response['success']) {
	        return $response['data'];
	    }
	    
	    return array();
	}
	 */

	/**
	 * 获取产品分类接口
	 * @param array $productIds
	 * @return array
	 */
	public static function getProductClassification($productArr) {
	    $client = new RESTClient();
	    $url = Yii::app()->params['TUNIU_HOST'].'interface/restful_interface.php';
	    
	    $params = array(
	            'func' => 'product.queryRouteBasicInfoArray',
	            'params' => $productArr
	        );
	    $posM = BPMoniter::createMoniter(__METHOD__.':'.__LINE__);
	    try {
	        $response = $client->get($url, $params);
	    }catch(Exception $e) {
	    	BPMoniter::getMoniter($posM);
	        Yii::log($e, 'warning');
	        return array();
	    }
	    BPMoniter::endMoniter($posM, 50, __LINE__);
	    if($response['success']) {
	        return $response['data'];
	    }
	    
	    return array();
	}
	/**
	 * 根据供应商id获取自助游3.0和门票接口
	 * @param number $vendorId
	 */
	public static function getTicketAndDiyProductListByParam($params){
		$client = new RESTClient();
        $url = Yii::app()->params['PLA_HOST'].'ror/product/query';
        $returnRes = array();
        try {
        	//根据产品ids获取自助游3.0及门票产品信息
        	$paramArr = array();
        	if(!empty($params['productIds'])){
        		foreach($params['productIds'] as $productId){
        			$param = array();
                    $param['productId'] = $productId;
        			$resOne = $client->get($url, $param);
        			if(!$res){
        				$res = $resOne;
        			}else{
        				$res['data']['rows']= array_merge($res['data']['rows'],$resOne['data']['rows']);
        			}
        		}
        	}else{
        		//根据供应商id获取自助游3.0及门票产品信息
//        		$isAdmin = AdminTool::isAdmin($params);
                // 根据account_id查询该账号是否拥有跟团、自助游、门票的权限
                $userManageMod = new UserManageMod();
                $authority = $userManageMod->getAuthority($params['accountId']);
        		if(!$authority){
        			return array();
        		}
	            $paramArr['start'] = $params['start'];
	            $paramArr['limit'] = $params['limit'];
	            if($params['startCityCode']){
                    $paramArr['bookCityCode'] = $params['startCityCode'];
	                //$paramArr['departCityCode'] = $params['startCityCode'];
	            }
                
                if($params['searchKey']){
	               $paramArr['productName'] = $params['searchKey'];
	            }
                if ($params['checkerFlag'] == 2) {
                    $paramArr['status'] = 1;
                }
                // 5-邮轮
                if($params['productType'] == 5){
                    $paramArr['isShow'] = 1;
                    $paramArr['productType'] = ConstDictionary::transBbProductTypeToNgboss($params['productType']);
                    if ($params['vendorId'] > 0) {
                        $paramArr['vendorId'] = $params['vendorId'];
                    }
                }

	            //$paramArr['status'] = $params['checkerFlag'] == 2 ? 1 : 0;
	            if($params['productType'] == 33 && intval($authority['isTicket']) == 1){
	            	$paramArr['productType'] = 2;
	            	$paramArr['isShow'] = 1;
                }elseif($params['productType'] == 33 && intval($authority['isTicket']) != 1) {
                    return array();
                }
                if($params['productType'] == 3 && intval($authority['isDiy']) == 1){
                    $paramArr['productTopType'] = 2;
                    $paramArr['isShow'] = 1;
                }elseif($params['productType'] == 3 && intval($authority['isDiy']) != 1) {
                    return array();
                }
                if(!empty($params['productId'])){
                	$paramArr['productId'] = $params['productId'];
                }
	            $paramArr['sortorder'] = 'desc';
                $paramArr['sortname'] = 'opTime';	
	            $res = $client->get($url, $paramArr);
        	}
            
            if($res['success']){
	            for($i = 0 ; $i < count($res['data']['rows']) ; $i++)
	            {  
	                $returnRes[$i]['productId']     = $res['data']['rows'][$i]['productId'];
	                $returnRes[$i]['vendorId']      = $res['data']['rows'][$i]['vendorId'];
	                $returnRes[$i]['productName']   = $res['data']['rows'][$i]['productName'];// 省多少钱的 
	                $returnRes[$i]['productType']   = ConstDictionary::transNgbossProductTypeToBb($res['data']['rows'][$i]['productType']);
	                $returnRes[$i]['startCityCode'] = $res['data']['rows'][$i]['departCityCode']; 
	                $returnRes[$i]['startCityName'] = $res['data']['rows'][$i]['departCityName'];
	                $returnRes[$i]['beginCityCode'] = $res['data']['rows'][$i]['departCityCode']; 
                    $returnRes[$i]['beginCityName'] = $res['data']['rows'][$i]['departCityName']; 
	                $returnRes[$i]['price']         = $res['data']['rows'][$i]['tuniuPrice'];//最低价
	                $returnRes[$i]['tuniuPrice']    = $res['data']['rows'][$i]['tuniuPrice'];
	                $returnRes[$i]['checkerFlag']   = $res['data']['rows'][$i]['status'] + 1;
	                $returnRes[$i]['agencyProductName'] = $res['data']['rows'][$i]['productName'];
	                $returnRes[$i]['updateTime']    = $res['data']['rows'][$i]['opTime']; 
	                //门票产品做特殊处理
                    if($res['data']['rows'][$i]['productType'] == 2)
                    {   
                    	$spotRes = json_decode($res['data']['rows'][$i]['journey'],true);
                        $returnRes[$i]['spotCode']          = $spotRes[0]['resource'][0]['spotCode'];
                        $returnRes[$i]['productType']       = 33;//门票 
                        $returnRes[$i]['economizeMoney']    = $spotRes[0]['resource'][0]['priceMarked'] - $res['data']['rows'][$i]['tuniuPrice'];
//                        $returnRes[$i]['productName']       = '[门票]<'.$spotRes[0]['resource'][0]['spotName'].'>在线预订最高可省'.$returnRes[$i]['economizeMoney'].'元';
                        $returnRes[$i]['productName']       = (empty($spotRes[0]['resource'][0]['spotName'])) ?
                            $res['data']['rows'][$i]['productName'] : $spotRes[0]['resource'][0]['spotName'].' | '.$res['data']['rows'][$i]['productName'];
                        $returnRes[$i]['agencyProductName'] = $spotRes[0]['resource'][0]['spotName'];
                        $returnRes[$i]['productId']         = $spotRes[0]['resource'][0]['spotCode'];
                        $returnRes[$i]['updateTime']        = $spotRes[0]['resource'][0]['opTime'];
                        $returnRes[$i]['ticketProductId']  = $res['data']['rows'][$i]['productId'];
                    }
	            }
            }
            $res['data']['rows'] = $returnRes;
            return $res['data'];
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }        
        
        return array();
	}
	
    /**
     * 获取景点url的holiday_id
     * @param array $ticketId
     * @return array
     */
    public static function getSpotHolidayId($ticketId) {
        $client = new RESTClient();
        $url = Yii::app()->params['SNC_HOST'].'guide/front/ticketid/'.$ticketId;

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
     * 招客宝改造-查询相似产品
     *
     * @author chenjinlong 20131114
     * @param $params
     * @return array
     */
    public static function getSimilarProductList($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'].'interface/restful_interface.php';

        $params = array(
            'func' => 'product.querySimilarProductList',
            'params' => $params
        );

        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }

        if($response['success']) {
            return $response['data'];
        }

        return array();
    }
	
}
?>