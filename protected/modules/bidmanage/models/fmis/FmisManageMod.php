<?php
Yii::import('application.modules.bidmanage.dal.iao.FmisIao');//充值
Yii::import('application.modules.bidmanage.dal.iao.HagridIao');//退款
Yii::import('application.modules.bidmanage.dal.dao.fmis.FmisManageDao');//发票
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao');//对账
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');//对账
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');

class FmisManageMod {
    private $manageDao;//发票
    private $userManage;
    private $commonDao;
    private $bidProductDao;
    private $productDao;
    private $bbLog;
    
    function __construct() {
    	$this->manageDao = new FmisManageDao();//发票
        $this->userManage = new UserManageDao();
    	$this->commonDao = new CommonDao();
    	$this->bidProductDao = new BidProductDao();
        $this->productDao = new ProductDao();
        $this->bbLog = new BBLog();
    }
       
    /**
     * [fmis]充值
     * @param array $params
     * @return array
     */
    public function createRecharge($params) {
        $response = FmisIao::createRecharge($params);
        return $response;
    }

    /**
     * [fmis]财务已开发票回调接口
     * @param array $params
     * @return boolean
     */
    public function updateFmisInvoice($params) {
    	$result = $this->manageDao->updateFmisInvoice($params);
    	return $result;
    }

    /**
     * 获取竞价成功次数
     * @param agency_id
     * @return 竞价成光数量
     */
    public function getBidCount($agencyID) {
    	// 查询account_id
    	$accRe = $this->userManage->getAccountInfoByAgentIdArr($agencyID);
    	// 若有accountid，则查询竞价数量，否则返回0
    	if (empty($accRe)) {
    		// 没有竞价
    		return array();
    	} else {
    		// 查询竞价成功数量
    		$countRe = $this->manageDao->getBidCount($accRe);
    		// 初始化返回数组
    		$reArr = array();
    		// 初始化数量标记
    		$countFlag = 0;
    		// 整合account_id和agency_id数据
    		foreach ($accRe as $accObj) {
    			// 初始化插入结果
    			$reObj = array();
    			foreach ($countRe as $countObj) {
    				// 若account_id相等，则初始化结果数据
    				if ($accObj['id'] == $countObj['account_id']) {
    					// 设置账户ID
    					$reObj['account_id'] = $countObj['account_id'];
    					// 设置数量
    					$reObj['count'] = $countObj['coun'];
    					// 设置agency_id
    					$reObj['agency_id'] = $accObj['vendor_id'];
    					// 设置消费金额
    					// $reObj['consumption'] = $countObj['consumption'];
    					// 满足条件，中断循环
    					break;
    				} else {
    					// 标记加1
    					$countFlag++;
    				}
    			}
    			// 如果标记等于$countRe的大小，则初始化空结果集
    			if ($countFlag == count($countRe)) {
    				// 设置账户ID
    				$reObj['account_id'] = $accObj['id'];
    				// 设置数量为0
    				$reObj['count'] = 0;
    				// 设置agency_id
    				$reObj['agency_id'] = $accObj['vendor_id'];
    			}
    			// 重置数量标记
    			$countFlag = 0;
    			// 添加结果集
    			array_push($reArr, $reObj);
    		}
    		return $reArr;
    	}
	}
    
    /**
     * 插入供应商的消息信息
     *
     * @author wenrui 20131212
     * @param $param
     * @return boolean
     */
    public function insertMsg($param) {
    	// 调用插入方法
    	$result = $this->manageDao->insertMsg($param);
    	return $result;
    }   
    
    /**
     * 过期供应商预算
     */
    public function overdateAgency($param) {
    	// 初始化返回结果
    	$result = array();
	 	// 设置空数据
    	$result['data'] = array();
    	// 初始化每个供应商子账号总余额的查询参数
    	$paramDb['agencyIdArr'] = '';
    	$paramDbCoupon['agencyIdArr'] = '';
    	// 牛币
    	if(!empty($param['niu'])){
    		// 整合每个供应商子账号总余额的查询参数
	    	foreach ($param['niu'] as $paramObj) {
	    		$paramDb['agencyIdArr'] = $paramDb['agencyIdArr'].$paramObj['agency_id'].',';
	    	}
	    	// 过滤每个供应商子账号总余额的查询参数
	    	$paramDb['agencyIdArr'] = substr($paramDb['agencyIdArr'], 0, strlen($paramDb['agencyIdArr']) - 1);
	    	try {
		    	// 获取每个供应商子账号的总余额
	    		$subTotalBudget = $this->userManage->queryOverAgencyTotalBudget($paramDb);
	    		// 具体判断每个有金额过期的供应商是否需要过期子账户预算
	    		foreach($param['niu'] as $paramObj) {
	    			foreach($subTotalBudget as $subTotalBudgetObj) {
	    				// 如果agencyID匹配，且父账户和子账户之间的差额小于过期金额，则过期子账户预算
	    				if($paramObj['agency_id'] == $subTotalBudgetObj['agency_id'] && ($paramObj['available_balance'] - $subTotalBudgetObj['available_balance']) < $paramObj['amt']) {
	    					// 初始化差额变量
	    					$imbalance = $paramObj['amt'] - ($paramObj['available_balance'] - $subTotalBudgetObj['available_balance']);
	    					// 过期子账户预算
	    					$this->userManage->overAgencyBudget($paramObj, $imbalance);
	    					// 中断里层循环
	    					break;
	    				}
	    			}
	    		}
	    	} catch(Exception $e) {
	    		// 设置错误编码
	    		$result['errorCode']=230001;
	    		// 设置错误状态
	    		$result['success'] = false;
	    		// 设置错误信息
	    		$result['msg'] = '牛币扣款失败！';
	    	}
    	}
    	// 赠币
    	if(!empty($param['coupon'])){
    		// 整合每个供应商子账号总余额的查询参数
	    	foreach ($param['coupon'] as $paramObj) {
	    		$paramDbCoupon['agencyIdArr'] = $paramDbCoupon['agencyIdArr'].$paramObj['agency_id'].',';
	    	}
	    	$paramDbCoupon['agencyIdArr'] = substr($paramDbCoupon['agencyIdArr'], 0, strlen($paramDbCoupon['agencyIdArr']) - 1);
	    	try {
		    	// 获取每个供应商子账号的总余额
	    		$subTotalBudgetCoupon = $this->userManage->queryOverAgencyTotalBudgetCoupon($paramDbCoupon);
	    		// 具体判断每个有金额过期的供应商是否需要过期子账户预算
	    		foreach($param['coupon'] as $paramObj) {
	    			foreach($subTotalBudgetCoupon as $subTotalBudgetObj) {
	    				// 如果agencyID匹配，且父账户和子账户之间的差额小于过期金额，则过期子账户预算
	    				if($paramObj['agency_id'] == $subTotalBudgetObj['agency_id'] && ($paramObj['coupon_available_balance'] - $subTotalBudgetObj['coupon_available_balance']) < $paramObj['amt']) {
	    					// 初始化差额变量
	    					$imbalance = $paramObj['amt'] - ($paramObj['coupon_available_balance'] - $subTotalBudgetObj['coupon_available_balance']);
	    					// 过期子账户预算
	    					$this->userManage->overAgencyBudgetCoupon($paramObj, $imbalance);
	    					// 中断里层循环
	    					break;
	    				}
	    			}
	    		}
	    	} catch(Exception $e) {
	    		// 设置错误编码
	    		$result['errorCode']=230001;
	    		// 设置错误状态
	    		$result['success'] = false;
	    		// 设置错误信息
	    		$result['msg'] = '赠币扣款失败！';
	    	}
    	}
    	// 设置成功编码
		$result['errorCode']=230000;
		// 设置成功状态
		$result['success'] = true;
		// 设置成功信息
		$result['msg'] = '扣款成功！';
    	// 返回结果
    	return $result;
    }
    
    /**
	 * 财务账户增加牛币和赠币的区分后，需要对财务表老数据进行更新操作
	 * 
	 * add by wenrui 2014-04-21
	 */
    public function update($data){
    	$result = array();
    	$returnMsg = "";
    	foreach($data as $account){
    		$result = FmisIao::update($account);
    		$returnMsg .= $result['msg']."</br>";
    	}
    	return $returnMsg;
    }
    
    /**
	 * 查询消费明细列表
	 */
	public function getExpenseInfo($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, "查询消费明细列表", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}
		
		// 初始化返回结果
		$result = array();
		$result['rows'] = array();
		$result['count'] = 0;
		$rows = array();
		$expenseType = $param['expenseType'];
		$expenseName = DictionaryTools::getExpenseType($param['expenseType']);

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 添加监控示例
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			$flag = Symbol::BPM_EIGHT_HUNDRED;
			
			// 整合参数
			$queryParam['accountId'] = $param['accountId'];
			$queryParam['startDate'] = Symbol::EMPTY_STRING;
			$queryParam['endDate'] = Symbol::EMPTY_STRING;
			if (empty($param['startDate']) && empty($param['endDate'])) {
				$queryParam['startDate'] = date('Y-m-d',time() - 6*24*60*60);
				$queryParam['endDate'] = date(Sundry::TIME_Y_M_D);
			}
			if (!empty($param['startDate'])) {
				$queryParam['startDate'] = $param['startDate'];
			}
			if (!empty($param['endDate'])) {
				$queryParam['endDate'] = $param['endDate'];
			}
			
			// 分类查询消费记录
			if (intval(chr(49)) == $expenseType) {
				// 竞拍
				$queryParam['start'] = $param['start'];
				$queryParam['limit'] = $param['limit'];
				
				// 查询竞拍消耗
				$reDb = $this->manageDao->getBidExpenseInfo($queryParam);
				
				// 整合数据
				$result['count'] = $reDb['count']['countRe'];
				if (!empty($reDb['rows']) && is_array($reDb['rows'])) {
					$rowsTemp = $reDb['rows'];
					unset($reDb);
					foreach ($rowsTemp as $rowsTempObj) {
						$temp = array();
						$temp['expenseTime'] = $rowsTempObj['add_time'];
						$temp['expenseType'] = $expenseType;
						$temp['expenseName'] = $expenseName;
						$temp['niuAmt'] = $rowsTempObj['bid_price_niu'];
						$temp['couponAmt'] = $rowsTempObj['bid_price_coupon'];
						array_push($rows, $temp);
					}
					$result['rows'] = $rows;
				}
			} else if (intval(chr(50)) == $expenseType) {
				// 打包计划
				$queryParam['start'] = $param['start'];
				$queryParam['limit'] = $param['limit'];
				// 查询分类页打包消耗
				$reDb = $this->manageDao->getPackExpenseInfo($queryParam);
				
				// 整合数据
				$result['count'] = $reDb['count']['countRe'];
				if (!empty($reDb['rows']) && is_array($reDb['rows'])) {
					$rowsTemp = $reDb['rows'];
					unset($reDb);
					foreach ($rowsTemp as $rowsTempObj) {
						$temp = array();
						$temp['expenseTime'] = $rowsTempObj['add_time'];
						$temp['expenseType'] = $expenseType;
						$temp['expenseName'] = $expenseName;
						$temp['niuAmt'] = $rowsTempObj['plan_price'];
						$temp['couponAmt'] = chr(48);
						array_push($rows, $temp);
					}
					$result['rows'] = $rows;
				}
			} else if (intval(chr(51)) == $expenseType) {
				// 线下扣款
				// 查询供应商ID
				var_dump($param);die;
				$user = $this->userManage->readUser(array('id' => $param['accountId']));
				if (empty($user) || empty($user['vendorId'])) {
					// 抛异常
					throw new BBException(ErrorCode::ERR_231656, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231656)], BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."供应商账户不存在，发生异常");
				}
				
				// 查询财务
				$fmisParam['agency_id'] = intval($user['vendorId']);
				$fmisParam['start_date'] = $queryParam['startDate'];
				$fmisParam['end_date'] = $queryParam['endDate'];
				$fmisParam['remark'] = intval(chr(48));
				$fmisParam['start'] = Symbol::MINUS_ONE;
				$fmisParam['limit'] = Symbol::MINUS_ONE;
				$fmisDb = FinanceIao::getExpenseInfo($fmisParam);
				
				// 查询本地其他扣费的fmisId
				// 查询竞拍消耗
				$reBid = $this->manageDao->getBidExpenseInfo($queryParam);
				// 查询分类页打包消耗
				$rePack = $this->manageDao->getPackExpenseInfo($queryParam);
				
				// 整合数据
				if (!empty($fmisDb['rows']) || !empty($fmisDb['rowsCharge'])) {
					
					// 初始化临时结果集
					$tempRe = array();
					
					// 获取fmisId
					$fmisId = array();
					if (!empty($fmisDb['rows'])) {
						$reBidRows = $reBid['rows'];
						$rePackRows = $rePack['rows'];
						foreach ($reBidRows as $reBidRowsObj) {
							array_push($fmisId, $reBidRowsObj['fmis_id']);
							array_push($fmisId, $reBidRowsObj['fmis_id_coupon']);
						}
						foreach ($rePackRows as $rePackRowsObj) {
							array_push($fmisId, $rePackRowsObj['fmis_id']);
						}
						$fmisId = array_unique($fmisId);
						unset($reBidRows);
						unset($rePackRows);
					}
					unset($reBid);
					unset($rePack);
					
					// 添加充值负数的记录
					$chargeExpense = &$fmisDb['rowsCharge'];
					foreach ($chargeExpense as $chargeExpenseObj) {
						$tempReObj = array();
						$tempReObj['expenseTime'] = $chargeExpenseObj['add_time'];
						$tempReObj['expenseType'] = $expenseType;
						$tempReObj['expenseName'] = $expenseName;
						$tempReObj['niuAmt'] = chr(48);
						$tempReObj['couponAmt'] = chr(48);
						if (chr(48) == $chargeExpenseObj['currency_type'] || chr(49) == $chargeExpenseObj['currency_type']) {
							$tempReObj['niuAmt'] = $chargeExpenseObj['amt'];
						} else if (chr(50) == $chargeExpenseObj['currency_type']) {
							$tempReObj['couponAmt'] = $chargeExpenseObj['amt'];
						}
						array_push($tempRe, $tempReObj);
					}
					unset($chargeExpense);
					unset($fmisDb['rowsCharge']);
					
					// 添加其他线下扣费
					$expense = &$fmisDb['rows'];
					foreach ($expense as $expenseObj) {
						$tempReObj = array();
						if (!in_array($expenseObj['id'], $fmisId)) {
							$tempReObj['expenseTime'] = $expenseObj['add_time'];
							$tempReObj['expenseType'] = $expenseType;
							$tempReObj['expenseName'] = $expenseName;
							$tempReObj['niuAmt'] = chr(48);
							$tempReObj['couponAmt'] = chr(48);
							if (chr(48) == $expenseObj['currency_type'] || chr(49) == $expenseObj['currency_type']) {
								$tempReObj['niuAmt'] = $expenseObj['amt'];
							} else if (chr(50) == $expenseObj['currency_type']) {
								$tempReObj['couponAmt'] = $expenseObj['amt'];
							}
							array_push($tempRe, $tempReObj);
						}
					}
					unset($expense);
					unset($fmisDb['rows']);
					unset($fmisDb);
					unset($fmisId);
					
					// 按添加时间排序
					// 排序包场结果
					foreach ($tempRe as $key => $row) {
			            $addTimeCol[$key] = $row['expenseTime'];
			        }
			        array_multisort($addTimeCol, SORT_DESC, $tempRe);
					
					// 整合结果
					$result['rows'] = array_slice($tempRe,$param['start'],$param['limit']);
					$result['count'] = count($tempRe);
				}
				
			} else if (intval(chr(52)) == $expenseType) {
				// 过期
				// 查询供应商ID
				$user = $this->userManage->readUser(array('id' => $param['accountId']));
				if (empty($user) || empty($user['vendorId'])) {
					// 抛异常
					throw new BBException(ErrorCode::ERR_231656, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231656)], BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."供应商账户不存在，发生异常");
				}
				
				// 查询财务
				$fmisParam['agency_id'] = intval($user['vendorId']);
				$fmisParam['start_date'] = $queryParam['startDate'];
				$fmisParam['end_date'] = $queryParam['endDate'];
				$fmisParam['remark'] = intval(chr(49));
				$fmisParam['start'] = $param['start'];
				$fmisParam['limit'] = $param['limit'];
				$fmisDb = FinanceIao::getExpenseInfo($fmisParam);
				
				// 整合数据
				if (!empty($fmisDb['rows']) && is_array($fmisDb['rows'])) {
					$result['count'] = CommonTools::getEmptyNum($fmisDb['count']);
					$rowsTemp = $fmisDb['rows'];
					unset($fmisDb);
					foreach ($rowsTemp as $rowsTempObj) {
						$temp = array();
						$temp['expenseTime'] = $rowsTempObj['add_time'];
						$temp['expenseType'] = $expenseType;
						$temp['expenseName'] = $expenseName;
						$temp['niuAmt'] = chr(48);
						$temp['couponAmt'] = chr(48);
						if (chr(48) == $rowsTempObj['currency_type'] || chr(49) == $rowsTempObj['currency_type']) {
							$temp['niuAmt'] = $rowsTempObj['amt'];
						} else if (chr(50) == $rowsTempObj['currency_type']) {
							$temp['couponAmt'] = $rowsTempObj['amt'];
						}
						
						array_push($rows, $temp);
					}
					$result['rows'] = $rows;
				}
									
			}
			// 结束监控示例
			BPMoniter::endMoniter($posTry, $flag, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."获取供应商消耗信息异常", $e);
        }
        
        // 返回结果
        return $result; 
	}
    
}
?>