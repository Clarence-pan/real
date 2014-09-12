<?php

class CpsIao {    
    
    /**
	 * 获取BOSS订单
	 */
	public static function getOrders() {
		$bbLog = new BBLog();
		$client = new RESTClient;
		
        // 初始化URL
        // $url = Yii::app()->params['CRM_HOST'] . 'interface/rest/server/order/OrderQueryInterface.php';
		$url = 'http://crm.tuniu.com/interface/rest/server/order/OrderQueryInterface.php';
		
		// 初始化返回结果
        $response = array();
        
        try {
        	
            // 初始化参数
            $params['func'] = "getOrderInfoByBackTime";
            $params['params']['back_time'] = date(Sundry::TIME_Y_M_D,time() - 12*60*60);

            // 开启监控
            $posM = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
        	$response = $client->get($url, $params);
        	if (empty($response) || !is_array($response) || !$response['success'] 
        		&& empty($response['data']) || !is_array($response['data'])) {
        		// 抛异常
				throw new BBException(ErrorCode::ERR_231400, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231400)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON."获取BOSS订单异常".$url.str_replace("\"", Symbol::EMPTY_STRING, json_encode($params)));
        	}
        	
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231400, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231400)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON."获取BOSS订单异常".$url.str_replace("\"", Symbol::EMPTY_STRING, json_encode($params)), $e);
        }
        
        // 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $response, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
		}
		
		// 返回结果
		return $response['data'];	
	}
	
	/**
	 * CPS查询搜索产品
	 */
	public static function queryCpsRorProduct($inputParams) {
		$client = new RESTClient;
        $inputParams['isQuery'] = 'true';

        // $uri = Yii::app()->params['PLA_HOST'] . 'ror/category/query';
        $uri = "http://public-api.bj.pla.tuniu.org/ror/category/query";
        try{
            $result = $client->get($uri, $inputParams);
            if($result['success'] && !empty($result['data']['rows'])){
            	return $result['data']['rows'];
            }else{
                return array();
            }
        }catch (Exception $e){
            return array();
        }
    }
    
    /**
	 * CPS查询财务采购单
	 */
	public static function queryCpsFmisOrder($params, $bbLog) {
		$client = new RESTClient;
		
		$fmisParam['func'] = 'getOrderPurchase';
		$fmisParam['params']['order_id'] = $params;
        // $uri = Yii::app()->params['FMIS_HOST'] . 'restfulApi/purchase';
        $uri = "http://fmis2.tuniu.com/restfulApi/purchase";
        try{
            $result = $client->get($uri, $fmisParam);
            if($result['success'] && !empty($result['data'])){
            	return $result['data'];
            }else{
                return array();
            }
        }catch (Exception $e){
        	if (isset($bbLog) && $bbLog->isInfo()) {
        		$BBLog->logException(ErrorCode::ERR_231657, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231657)], $fmisParam, __METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
        	}
            return array();
        }
    }
    
    /**
	 * CPS查询结算方式
	 */
	public static function queryCpsFmisWay($param, $bbLog) {
		$client = new RESTClient;
		
		$fmisParam['agencyId'] = $param;
		$fmisParam['start'] = chr(48);
		$fmisParam['limit'] = chr(49);
        // $uri = Yii::app()->params['FMIS_HOST'] . 'settlement/agency/bill/list/query';
        $uri = "http://fmis2.tuniu.com/settlement/agency/bill/list/query";
        try{
            $result = $client->get($uri, $fmisParam);
            if($result['success'] && !empty($result['data']['rows'])){
            	return $result['data']['rows'][0];
            }else{
                return array();
            }
        }catch (Exception $e){
        	if (isset($bbLog) && $bbLog->isInfo()) {
        		$BBLog->logException(ErrorCode::ERR_231658, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231658)], $fmisParam, __METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
        	}
            return array();
        }
    }
    
    /**
	 * 查询网站CPS区块
	 */
	public static function queryTuniuCpsBlocks($param) {
		$client = new RESTClient;
		$bbLog = new BBLog();
		
        // $uri = Yii::app()->params['TUNIU_HOST'] . 'interface/siteConfig/prdCps';
        $uri = "http://www.tuniutest2.com/interface/siteConfig/prdCps";
		
		// 初始化返回结果
        $response = array();
        
        try {

            // 开启监控
            $posM = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
        	$response = $client->post($uri, $param);
        	if (empty($response) || !is_array($response) || !$response['success'] 
        		&& empty($response['data']) || !is_array($response['data'])) {
        		// 抛异常
				throw new BBException(ErrorCode::ERR_231400, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231400)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON."获取BOSS订单异常".$uri.str_replace("\"", Symbol::EMPTY_STRING, json_encode($param)));
        	}
        	
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231400, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231400)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON."获取BOSS订单异常".$uri.str_replace("\"", Symbol::EMPTY_STRING, json_encode($param)), $e);
        }
        
        // 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($param, $uri, $response, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
		}
		
		// 返回结果
		return $response['data'];	
    }
    
}
