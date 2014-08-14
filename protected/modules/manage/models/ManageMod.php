<?php
Yii::import('application.modules.manage.dal.iao.FinanceIao');
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');

class ManageMod {
	private $manageDao;
	
	function __construct() {
		$this->manageDao = new ManageDao();
	}
	
	public function getVendorInfoByAccountId($accountId) {
		$vendor = $this->manageDao->getVendorInfoByAccountId($accountId);
		return $vendor;
	}
	
	public function getVendorInfoByVendorId($vendorId) {
	    $vendor = $this->manageDao->getVendorInfoByVendorId($vendorId);
	    return $vendor;
	}
	
	public function updateVendorInfo($vendorInfo,$params) {
		if($params['cmpName'] != null && $params['cmpName'] != $vendorInfo['cmpName']) {
			$vendorInfo['cmpName'] = $params['cmpName'];
		}
		if($params['cmpPhone'] != null && $params['cmpPhone'] != $vendorInfo['cmpPhone']) {
			$vendorInfo['cmpPhone'] = $params['cmpPhone'];
		}
		if($params['contractor'] != null && $params['contractor'] != $vendorInfo['contractor']) {
			$vendorInfo['contractor'] = $params['contractor'];
		}
		if($params['contractorTel'] != null && $params['contractorTel'] != $vendorInfo['contractorTel']) {
			$vendorInfo['contractorTel'] = $params['contractorTel'];
		}
		if($params['contractorTel2'] != null && $params['contractorTel2'] != $vendorInfo['contractorTel2']) {
			$vendorInfo['contractorTel2'] = $params['contractorTel2'];
		}
		if($params['invoiceType'] != null && $params['invoiceType'] != $vendorInfo['invoiceType']) {
			$vendorInfo['invoiceType'] = $params['invoiceType'];
		}
		if($params['cmpBank'] != null && $params['cmpBank'] != $vendorInfo['cmpBank']) {
			$vendorInfo['cmpBank'] = $params['cmpBank'];
		}
		if($params['cmpAccount'] != null && $params['cmpAccount'] != $vendorInfo['cmpAccount']) {
			$vendorInfo['cmpAccount'] = $params['cmpAccount'];
		}
		if($params['taxNo'] != null && $params['taxNo'] != $vendorInfo['taxNo']) {
			$vendorInfo['taxNo'] = $params['taxNo'];
		}
		if($params['cmpAddress'] != null && $params['cmpAddress'] != $vendorInfo['cmpAddress']) {
			$vendorInfo['cmpAddress'] = $params['cmpAddress'];
		}
		if($params['attachUrl'] != null && $params['attachUrl'] != $vendorInfo['attachUrl']) {
			$vendorInfo['attachUrl'] = $params['attachUrl'];
		}
		$result = $this->manageDao->updateVendorInfo($vendorInfo);
		return $result;
	}
	
	public function insertVendorInfo($params) {
		$result = $this->manageDao->insertVendorInfo($params);
		return $result;
	}

    public function addRefundRequest($params) {
		$result = $this->manageDao->insertRefundRequest($params);
		return $result;
	}

    public function mdfRefundRequest($params) {
		$result = $this->manageDao->updateRefundRequest($params);
		return $result;
	}
	
	/**
	 * 获取退款列表
	 * @param array $params
	 * @return array
	 */
	public function readRefundList($params) {
	    if ($params['applicationDate']) {
	        $params['applicationDateArray'] = explode(',', $params['applicationDate']);
	    }
		if ($params['confirmDate']) {
	        $params['confirmDateArray'] = explode(',', $params['confirmDate']);
	    }
	    if ($params['backDate']) {
	        $params['backDateArray'] = explode(',', $params['backDate']);
	    }
	    
	    $refundCount = $this->manageDao->readRefundCount($params);
	    $refund = $this->manageDao->readRefundList($params);
	    
	    $refundList = array(
	            'count' => $refundCount,
	            'rows' => $refund
	    );
	    
	    return $refundList;
	}
	
	/**
	 * 退款审核
	 * @param array $params
	 * @return boolean
	 */
	public function updateRefundAudit($params) {
	    $result = $this->manageDao->updateRefundAudit($params);
	    
	    if ($result && 1 == intval($params['operateFlag'])) {
	        $refund = $this->manageDao->readRefundDetail($params['applicationId']);
	        
	        if (!empty($refund)) {
	            $refund['state'] = 1;
	            $response = FinanceIao::createRefund($refund);
	        }
	    } elseif ($result && -1 == intval($params['operateFlag'])) {
	        $refund = $this->manageDao->readRefundDetail($params['applicationId']);
	        
	        $messageParams = array(
	                'vendorId' => $refund['agency_id'],
	                'type' => 5,
	                'content' => '金额'.$refund['amt'].'元退款申请被退回',
	                'amount' => $refund['amt'],
	                'addUid' => $params['uid']
	        );
	        
	        $message = BuckbeekIao::insertMessage($messageParams);
	    }
	    
	    return $result;
	}
	
	/**
	 * 获取对账列表
	 * @param array $params
	 * @return array
	 */
	public function readReconciliationList($params) {
	    $response = BuckbeekIao::readReconciliationList($params);
	    return $response;
	}
	
	/**
	 * 获取对账明细
	 * @param array $params
	 * @return array
	 */
	public function readReconciliationDetail($params) {
	    $response = BuckbeekIao::readReconciliationDetail($params);
	    return $response;
	}
	
	/**
	 * 退款信息回调
	 * @param array $params
	 * @return boolean
	 */
	public function updateRefundFmis($params) {
	    $result = $this->manageDao->updateRefundFmis($params);
	    return $result;
	}
	
}
?>