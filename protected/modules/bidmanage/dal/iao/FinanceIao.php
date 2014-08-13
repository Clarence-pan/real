<?php
class FinanceIao {

    private $_restfulClient;

    function __construct()
    {
        $this->_restfulClient = new RESTClient;
    }

	public static function getAccountAvailableBalance($agencyId) {
		$client = new RESTClient();
		$bbLog = new BBLog();
		$url = Yii::app()->params['FMIS_HOST'];
		$url = $url.'restfulApi/AgencyCount/';
		$params = array('agency_id' => $agencyId,'limit' => 1,
				'page' => 1,);
		$func = 'queryList';
		$params = array(
				'func' => $func,
				'params' => $params,
				
		);
		$format = 'encrypt';
		// 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
		$res = array();
		try {
			$res = $client->get($url, $params, $format);
		} catch(Exception $e) {
			if ($bbLog->isInfo()) {
				$bbLog->logException(ErrorCode::ERR_231200, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231200)], $res, CommonTools::getErrPos($e));
			}
		}
		
		// 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $res, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
		}
		if($res['data']) {
			$availableBalance = $res['data']['niu'][$agencyId]['available_balance'];
			$balance = $res['data']['niu'][$agencyId]['balance'];
			$couponAvailableBalance = $res['data']['coupon'][$agencyId]['coupon_available_balance'];
			$couponBalance = $res['data']['coupon'][$agencyId]['coupon_balance'];
		} else {
			$availableBalance = 0;
			$balance = 0;
			$couponAvailableBalance = 0;
			$couponBalance = 0;
		}
		$financeInfo = array('controlMoney'=>$availableBalance,'currentMoney' => $balance,'couponControlMoney'=>$couponAvailableBalance,'couponCurrentMoney' => $couponBalance);
		return $financeInfo;
	}
	
	public static function bidCutFinanceNew($agencyId, $amtNiu, $oldAmtNiu, $amtCoupon, $oldAmtCoupon) {
		$client = new RESTClient();
		$bbLog = new BBLog();
		$url = Yii::app()->params['FMIS_HOST'];
		$url = $url.'restfulApi/AgencyBid/';
		$params = array('agency_id' => $agencyId,
						'amt_niu' => $amtNiu,
						'old_amt_niu' => $oldAmtNiu,
						'amt_coupon' => $amtCoupon,
						'old_amt_coupon' => $oldAmtCoupon,
						);
		$func = 'bidding_new';
		$params = array(
				'func' => $func,
				'params' => $params,
		);
		$format = 'encrypt';
		// 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
		$res = array();
		try {
			$res = $client->get($url, $params, $format);
		} catch(Exception $e) {
			if ($bbLog->isInfo()) {
				$bbLog->logException(ErrorCode::ERR_231201, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231201)], $res, CommonTools::getErrPos($e));
			}
		}
		// 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $res, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
		}
		return $res;
	}
	
	public static function bidCutFinance($agencyId, $amt, $oldAmt) {
		$client = new RESTClient();
		$url = Yii::app()->params['FMIS_HOST'];
		$url = $url.'restfulApi/AgencyBid/';
		$params = array('agency_id' => $agencyId,
						'amt' => $amt,
						'old_amt' => $oldAmt,
						);
		$func = 'bidding';
		$params = array(
				'func' => $func,
				'params' => $params,
		);
		$format = 'encrypt';
		$res = $client->get($url, $params, $format);
		return $res;
	}

    /**
	 * 获取供应商累计消费金额
	 * 
	 * @param $agencyId
	 * @return array()  {"success":true,"errorCode":"0000","error_code":"0000","msg":"\u64cd\u4f5c\u6210\u529f","data":{"consumption":"0.00","agency_id":"1138"}}
	 */
	public static function getAgencyExp($agencyId) {
		// 初始化接口调用客户端对象
		$client = new RESTClient();
		// 初始化主机地址
		$url = Yii::app()->params['FMIS_HOST'];
		// 拼接接口调用URL
		$url = $url.'restfulApi/AgencyCount/';
		// 初始化接口调用参数  agency_id
		$params = array('agency_id' => $agencyId,);
		// 初始化接口调用方法参数
		$func = 'getAgencyExpense';
		// 整合接口调用参数
		$params = array(
				'func' => $func,
				'params' => $params,
		);
		// 设置返回数据格式
		$format = 'encrypt';
		// 使用GET方法调用接口，获取供应商累计消费金额
		$res = $client->get($url, $params, $format);
		// 返回结果
		return $res;
	}

    /**
     * 竞价成功，财务扣费或者扣费成功后的撤销扣费操作
     *
     * @author chenjinlong 20121209
     * @param $reqParams
     * @param $isCancelDeduct,true=撤销扣费，false=执行扣费
     * @return bool
     */
    public function bidSuccessFinance($reqParams, $isCancelDeduct)
    {
    	$bbLog = new BBLog();
        $url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyBid/';

        if($isCancelDeduct){
            $apiParams = array(
                'agency_id' => $reqParams['agency_id'],
                'amt' => $reqParams['amt'],
                'amt_coupon' => $reqParams['amt_coupon'],
                'serial_id' => $reqParams['serial_id'],
                'charge_against_id' => $reqParams['charge_against_id'],
            );
        }else{
            $apiParams = array(
                'agency_id' => $reqParams['agency_id'],
                'amt' => $reqParams['amt'],
                'amt_coupon' => $reqParams['amt_coupon'],
                'serial_id' => $reqParams['serial_id'],
                'charge_against_id' => 0,
            );
        }
        $params = array(
            'func' => 'bidded_success_new',
            'params' => $apiParams,
        );
        try{
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $res = $this->_restfulClient->post($url, $params);
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface($params, $url, $res, chr(49), $posM, 400, __METHOD__.'::'.__LINE__);
			}
            //记录接口监控日志
            if($res['success']){
                // CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'财务扣款-成功',11,'wuke', 14, $reqParams['agency_id'],0, json_encode($params), json_encode($res));
                return $res['data']['expense_id'];
            }else{
                // CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'财务扣款-失败',11,'wuke', -14, $reqParams['agency_id'],0, json_encode($params), json_encode($res));
                return false;
            }
        }catch (Exception $e){
        	if ($bbLog->isInfo()) {
        		$bbLog->logException(ErrorCode::ERR_231202, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231202)], $res, CommonTools::getErrPos($e));
        		$bbLog->setAuth();
        		$bbLog->logInterface($params, $url, $res, chr(49), $posM, 400, __METHOD__.'::'.__LINE__);
        	}
            //记录接口监控日志
            // CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'财务扣款-异常',11,'wuke', -14, $reqParams['agency_id'],0, json_encode($params), json_encode($e->getTraceAsString()));
            return false;
        }
    }

    /**
     * 竞价失败，财务将扣除的金额返还到该供应商的可支配余额中
     *
     * @author chenjinlong 20121209
     * @param $reqParams
     * @return bool
     */
    public function bidFailFinance($reqParams)
    {
    	$bbLog = new BBLog();
        $url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyBid/';
        $params = array(
            'func' => 'bidded_fail_new',
            'params' => array(
                'agency_id' => $reqParams['agency_id'],
                'amtNiu' => $reqParams['amtNiu'],
                'amtCoupon' => $reqParams['amtCoupon'],
            ),
        );
        try{
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $res = $this->_restfulClient->post($url, $params);
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface($params, $url, $res, chr(49), $posM, 400, __METHOD__.'::'.__LINE__);
			}
            //记录接口监控日志
            if($res['success']){
                // CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'财务解冻-成功',11,'wuke',15 , $reqParams['agency_id'], 0, json_encode($params), json_encode($res));
                return true;//$res['data']['expense_id'];
            }else{
                // CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'财务解冻-失败',11,'wuke',-15, $reqParams['agency_id'], 0, json_encode($params), json_encode($res));
                return false;
            }
        }catch (Exception $e){
        	if ($bbLog->isInfo()) {
				$bbLog->logException(ErrorCode::ERR_231203, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231203)], $res, CommonTools::getErrPos($e));
				$bbLog->setAuth();
				$bbLog->logInterface($params, $url, $res, chr(49), $posM, 400, __METHOD__.'::'.__LINE__);
        	}
            //记录接口监控日志
            // CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'财务解冻-异常',11,'wuke', -15, $reqParams['agency_id'], 0, json_encode($params), json_encode($e->getTraceAsString()));

            return false;
        }
    }

    /**
     * add by wuke 2013-12-19
     * 招客宝财务优化：查询供应商在推广日期当天及以后仍然有效的账户余额
     */
    public function getValidCharge($reqParams)
	{
		$bbLog = new BBLog();
	    $url = Yii::app()->params['FMIS_HOST'] . 'restfulApi/AgencyCount/';
	    $params = array(
	        'func' => 'getValidCharge',
	        'params' => array(
	            'agency_id' => $reqParams['agency_id'],
	            'show_start_date' => $reqParams['show_start_date'],
	        ),
	    );
	    
	    // 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
		$res = false;
		try {
			$res = $this->_restfulClient->get($url, $params);
		} catch(Exception $e) {
			if ($bbLog->isInfo()) {
				$bbLog->logException(ErrorCode::ERR_231204, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231204)], $res, CommonTools::getErrPos($e));
			}
		}
		
		// 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $res, chr(48), $posM, 400, __METHOD__.'::'.__LINE__);
		}
	    return $res;
	}
	
	/**
     * 查询财务账户列表
     */
    public static function getFmisCharts($reqParams)
	{
		// 初始化接口调用客户端对象
		$client = new RESTClient();
		$bbLog = new BBLog();
	    $url = Yii::app()->params['FMIS_HOST'] . 'restfulApi/AgencyCount/';
	    $params = array(
	        'func' => 'getNGCharts',
	        'params' => array(
	            'start_date' => $reqParams['startDate'],
	            'end_date' => $reqParams['endDate'],
	            'agency_id' => $reqParams['agencyId'],
	            'agency_name' => $reqParams['agencyName'],
	            'is_excel' => $reqParams['isExcel'],
	            'start' => $reqParams['start'],
	            'limit' => $reqParams['limit'],
	        ),
	    );
	    
	    // 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
		$res = false;
		try {
			$res = $client->get($url, $params);
			if (!$res['success'] || empty($res['data'])) {
				// 抛异常
				throw new BBException(ErrorCode::ERR_231206, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231206)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON.$url.Symbol::CONS_DOU_COLON.$params);
			}
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        }  catch(Exception $e) {
			// 抛异常
			throw new BBException(ErrorCode::ERR_231206, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231206)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON.$url.Symbol::CONS_DOU_COLON.$params,$e);
		}
		
		// 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $res, chr(48), $posM, 650, __METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
		}
		
	    return $res['data'];
	}
	
	/**
     * 查询财务消耗明细
     */
    public static function getExpenseInfo($reqParams)
	{
		// 初始化接口调用客户端对象
		$client = new RESTClient();
		$bbLog = new BBLog();
	    $url = Yii::app()->params['FMIS_HOST'] . 'restfulApi/AgencyCount/';
	    $params = array(
	        'func' => 'getExpenseInfo',
	        'params' => $reqParams,
	    );
	    
	    // 开启监控
		$posM = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
		$res = false;
		try {
			$res = $client->get($url, $params);
			if (!$res['success'] || empty($res['data'])) {
				// 抛异常
				throw new BBException(ErrorCode::ERR_231207, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231207)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON.$url.Symbol::CONS_DOU_COLON.$params);
			}
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        }  catch(Exception $e) {
			// 抛异常
			throw new BBException(ErrorCode::ERR_231207, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231207)], BPMoniter::getMoniter($posM).Symbol::CONS_DOU_COLON.$url.Symbol::CONS_DOU_COLON.$params,$e);
		}
		
		// 填充日志
		if ($bbLog->isInfo()) {
			$bbLog->logInterface($params, $url, $res, chr(48), $posM, 400, __METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
		}
		
	    return $res['data'];
	}
	
}

