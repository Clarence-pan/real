<?php
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
//product
class ProductIao {

    public static function getAgencyProductList($productIdType) {
        $client = new RESTClient();
        
        $url = Yii::app()->params['TUNIU_HOST'] . 'interface/restful_interface.php';
        $requestParamsArr = array(
            'func' => 'product.queryRouteBasicInfoArray',
            'params' => $productIdType,
        );
        $format = 'encrypt';
        try {
            $responseArr = $client->get($url, $requestParamsArr, $format);

            //记录接口监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '查询CRM产品接口-BB', 1, 'chuhui', 0, 0, $url, json_encode($requestParamsArr), json_encode($responseArr));

            if ($responseArr['success'] && !empty($responseArr['data'])) {
                return $responseArr;
            } else {
                return array();
            }
        } catch (Exception $e) {

            //记录接口监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '查询CRM产品接口-BB', 1, 'chuhui', -1, 0, $url, json_encode($requestParamsArr), json_encode($e->getTraceAsString()));

            return array();
        }
    }
    
    /**
     * 查询网站预定城市（出发城市列表）
     *
     * @author chenjinlong 20140124
     * @return mixed
     */
    public static function getMultiCityInfoFromTuniu()
    {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'] . 'interface/MultiCity/getMultiCityInfo';
        $params = array();
        // 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        $responseArr = $client->get($url, $params);
        // 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $responseArr, chr(48), $posM, 700, __METHOD__.'::'.__LINE__);
		}
        return $responseArr;
    }

    public static function getProductAllInfoById($productIds) {
        $client = new RESTClient();
        $url = Yii::app()->params['CRM_HOST'] . 'restfulServer/catres';

        $requestParamsArr = array();
        $requestParamsArr['method'] = 'getProductCatInfoByRouteIds';
        $requestParamsArr['param'] = array(
            'list' => array(
                'outorder_id' => $productIds
            )
        );

        $responseArr = $client->post($url, $requestParamsArr);
        return $responseArr;
    }

    public static function getManager() {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['CRM_HOST'] . 'restfulServer/salerres/manager-list';

        // 获取部门信息
        $productDao = new ProductDao();
        $departmentInfo = $productDao->queryDepartmentInfo();
        // 初始化接口请求参数
        $requestParamsArr['departmentId'] = array();
        // 设置部门ID参数
        if ($departmentInfo && is_array($departmentInfo)) {
            foreach ($departmentInfo as $tempInfo) {
                array_push($requestParamsArr['departmentId'],$tempInfo['departmentId']);
            }
        }

//        $requestParamsArr = array('departmentId' => array(1194,1291,163,1690));
		// 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        $responseArr = $client->get($url, $requestParamsArr);
        // 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($requestParamsArr, $url, $responseArr, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
		}
        return $responseArr;
    }

    /**
     * 调用NB查询BI所有数据
     */
    public static function getAllBiInfo($param) {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['NB_HOST'] . 'restful/vendor/all-bi-info';
		// 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        $responseArr = $client->post($url, $param);
        // 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($param, $url, $responseArr, chr(49), $posM, 1000, __METHOD__.'::'.__LINE__);
		}
        return $responseArr;
    }

    /**
     * 调用NB查询BI数据
     */
    public static function getBiInfo($param) {
    	$bbLog = new BBLog();
        $client = new RESTClient();
        $url = Yii::app()->params['NB_HOST'] . 'restful/vendor/bi-info';
		// 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        $responseArr = $client->post($url, $param);
        // 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($param, $url, $responseArr, chr(49), $posM, 1000, __METHOD__.'::'.__LINE__);
		}
        return $responseArr;
    }

    public static function readProductLineIdStr($param) {
        $client = new RESTClient();
        $url = Yii::app()->params['HAGRID_HOST'] . 'hg/public/product/productlineid';

        $responseArr = $client->get($url, $param);
        return $responseArr;
    }

    public static function getWebClassInfo($param) {
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'] . 'interface/restful_interface.php';
        $requestParamsArr = array(
            'func' => 'product.querySiteClsInfo',
            'params' => $param,
        );
        $responseArr = $client->get($url, $requestParamsArr);
        return $responseArr;
    }
    
    /**
	 * 查询分类页列表
	 */
	public static function getClassificationList($params) {
		// 初始化客户端调用对象
		$client = new RESTClient();
		// 初始化URL地址
        $url = Yii::app()->params['TUNIU_HOST'] . 'interface/restful_interface.php';
        // 初始化参数
        $requestParamsArr = array(
            'func' => 'product.querySiteClsInfoByPager',
            'params' => $params,
        );
        // 调用接口
        $responseArr = $client->get($url, $requestParamsArr);
        // 返回参数
        return $responseArr;
	}
	
	/**
	 * 新招客宝3.0获取相似产品
	 */
	public static function getSimiliarProductNew($param) {
		// 初始化客户端调用对象
		$client = new RESTClient();
		// 初始化URL地址
        $url = Yii::app()->params['TUNIU_HOST'] . 'interface/restful_interface.php';
        // 初始化参数
        $requestParamsArr = array(
            'func' => 'product.querySimilarProductList',
            'params' => $param,
        );
        // 调用接口
        $responseArr = $client->get($url, $requestParamsArr);
        // 返回参数
        return $responseArr;
	}
	
	/**
	 * 获取签证产品列表
	 */
	public static function getVisaProducts($param){
		// 初始化客户端调用对象
        $client = new RESTClient();
	    $url = Yii::app()->params['CRM_HOST'] . 'restfulServer/visares/visa';
	    try {
	        $response = $client->get($url, $param);
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
