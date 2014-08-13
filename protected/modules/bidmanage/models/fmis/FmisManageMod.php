<?php
Yii::import('application.modules.bidmanage.dal.iao.FmisIao');//充值
Yii::import('application.modules.bidmanage.dal.iao.HagridIao');//退款
Yii::import('application.modules.bidmanage.dal.dao.fmis.FmisManageDao');//发票
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao');//对账
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');//对账
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');

class FmisManageMod {
    private $manageDao;//发票
    private $userManage;
    private $commonDao;
    private $bidProductDao;
    private $productDao;
    
    function __construct() {
    	$this->manageDao = new FmisManageDao();//发票
        $this->userManage = new UserManageDao();
    	$this->commonDao = new CommonDao();
    	$this->bidProductDao = new BidProductDao();
        $this->productDao = new ProductDao();
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
}
?>