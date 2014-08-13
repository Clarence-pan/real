<?php
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');

class PackIao {
	
	/**
	 * 从搜索接口获取数据
	 */
	public static function getPlaProduct($param) {
		$bbLog = new BBLog();
		$client = new RESTClient;
        
        $url = Yii::app()->params['PLA_HOST'] . 'ror/category/query';
        // $url = 'http://public-api.bj.pla.tuniu.org/ror/category/query';
        try {
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        	$response = $client->get($url, $param);
        	// 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface($param, $url, $response, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
			}
        } catch (Exception $e) {
        	// 打印日志
        	if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231300, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231300)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface($param, $url, $response, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
        	}
            // 抛异常
            throw $e;
        }
        
        return $response;
	}
	
	/**
	 * 获取产品经理
	 */
	public static function getManager() {
		$bbLog = new BBLog();
		$client = new RESTClient;
        
        $url = Yii::app()->params['CRM_HOST'] . 'restfulServer/salerres/manager-list';
//        $param['departmentId'] = array(1194,1291,163,1178,754,1429,828,32,278,27,863,1690);
        
        try {
            // 获取部门信息
            $productDao = new ProductDao();
            $departmentInfo = $productDao->queryDepartmentInfo();
            // 初始化接口请求参数
            $param['departmentId'] = array();
            // 设置部门ID参数
            if ($departmentInfo && is_array($departmentInfo)) {
                foreach ($departmentInfo as $tempInfo) {
                    array_push($param['departmentId'],$tempInfo['departmentId']);
                }
            }

            // 开启监控
            $posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        	$response = $client->get($url, $param);
        	if (!empty($response) && is_array($response) && $response['success'] 
        		&& !empty($response['data']) && is_array($response['data'])) {
        		// 填充日志
				if ($bbLog->isInfo()) {
					$bbLog->logInterface($param, $url, $response, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
				}
        		return $response['data'];	
        	} else {
        		throw new Exception('调用BOSS接口失败', 230003);
        	}
        	
        } catch (Exception $e) {
        	// 打印日志
        	if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231400, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231400)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface($param, $url, $response, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
        	}
            // 抛异常
            throw $e;
        }
	}
	
	/**
	 * 上下线网站产品
	 */
	public static function onOffLineTuniuProduct($param) {
		$bbLog = new BBLog();
		$client = new RESTClient;
        
        $url = Yii::app()->params['TUNIU_HOST'] . 'interface/siteRecommend/bbRecommend';
        // $url = 'http://172.30.20.200/interface/siteRecommend/bbRecommend';
        
        try {
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        	$response = $client->post($url, $param);
        	
        	if (!empty($response) && is_array($response) && $response['success']) {
        		// 填充日志
				if ($bbLog->isInfo()) {
					$bbLog->logInterface($param, $url, $response, chr(49), $posM, 200, __METHOD__.'::'.__LINE__);
				}
        		return true;	
        	} else {
        		throw new Exception();
        	}
        	
        } catch (Exception $e) {
        	// 打印错误日志
        	if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231100, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231100)], $response, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface($param, $url, $response, chr(49), $posM, 500, __METHOD__.'::'.__LINE__);
        	}
            // 抛异常
            throw new Exception('调用网站接口失败', 231000);
        }
	}
	
	/**
     * 包场直接扣费
     *
     * @author p-sunhao 20140613
     * @param $reqParams
     * @return bool
     */
    public function directFinance($reqParams)
    {
    	$bbLog = new BBLog();
    	$client = new RESTClient;
        $url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyBid/';
		
        $apiParams = array(
                'agency_id' => $reqParams['agency_id'],
                'amt' => $reqParams['amt']
        );
        
        $params = array(
            'func' => 'direct_reduct',
            'params' => $apiParams,
        );
        try{
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $res = $client->post($url, $params);
            //记录接口监控日志
            if($res['success']){
                // 填充日志
				if ($bbLog->isInfo()) {
					$bbLog->logInterface($params, $url, $res, chr(49), $posM, 400, __METHOD__.'::'.__LINE__);
				}
                return $res['data']['expense_id'];
            }else{    
                throw new Exception();
            }
        }catch (Exception $e){
            // 打印日志
            if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231205, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231205)], $res, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface($params, $url, $res, chr(49), $posM, 500, __METHOD__.'::'.__LINE__);
            }
            return false;
        }
    }

}
