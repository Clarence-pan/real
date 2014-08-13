<?php
/**
 *  * Promotion Fmis views
 * @author xiongyun@2013-01-04
 * @version 1.0
 * @func doRestGetFinanceInfo
 * @func doRestGetStatement
 * @func doRestGetStatementFile
 * @func doRestPostRefund
 */
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.models.fmis.FmisManageMod');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.models.fmis.FmisBidInfo');
Yii::import('application.modules.bidmanage.models.fmis.StatementMod');
Yii::import('application.modules.bidmanage.models.user.StaBbEffectMod');

class FmisController extends restUIServer {

    private $bidInfo;
    private $manage;
    private $fmis;
    private $statement;
    private $_staBbEffectMod;

    function __construct() {
        $this->bidInfo = new FmisBidInfo();
        $this->manage = new UserManageMod();
        $this->fmis = new FmisManageMod();
        $this->statement = new StatementMod();
        $this->_staBbEffectMod = new StaBbEffectMod;
    }

    /**
     * $client = new restful_client();
     * $request_data = array('accountId'=>1 ,'token'=>"XXXXXXXXXXXXXXXXXXXXXX");
     * $response = $client->request(RESTFUL_GET, $url, $request_data);
     *
     * @mapping /promotion
     * @method GET
     * @param string $url
     * @param  array $data {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b"}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc   推广概况
     */
    public function doRestGetPromotion($url, $data) {

//        if($data['accountId'] <= 0){
//            $this->returnRest(array(), false, 230008, '输入参数有误，accountId必须为合法约定数值');
//        }
        if($this->getAccountId() <= 0){
            $this->returnRest(array(), false, 230008, '输入参数有误，accountId必须为合法约定数值');
        }
        $params = array(
            'accountId' => $this->getAccountId(),
            'startDate' =>  $data['startDate'] ? $data['startDate'] : '0000-00-00' ,
            'endDate' =>  $data['endDate'] ? $data['endDate'] : '0000-00-00' ,
            'isPaied' => intval($data['isPaied']),
            'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
            'productLineName' => $data['productLineName'],
            'productId' => $data['productId'],
            'productName' => $data['productName'],
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 20,
			
        );
		$resData = $this->_staBbEffectMod->getStaEffectArrByDate($params, $this->getAccountId());
        $spreadInfo['spreadInfo'] = $resData;
        $bidFinanceInfo = $this->bidInfo->getBidFinanceInfo($this->getAccountId());
        $bidFinanceInfo = array_merge($bidFinanceInfo,$spreadInfo);

        $this->_returnData ['success'] = true;
        $this->_returnData ['msg'] = __CLASS__ . '.' . __FUNCTION__;
        $this->_returnData['data'] = $bidFinanceInfo;
        $this->renderJson();
    }

	/**
     * 接口：分页查询供应商消息信息
     *
     * @author wenrui 20131213
     * @param $param
     * @return 
     */
	public function doRestGetQuerryMsg($url, $param) {
		// 获取当前登录供应商的accountId
		$accountId = intval($this->getAccountId());
		if($accountId > 0){
			// accountId传入查询条件数组
			$param['accountId'] = $accountId;
		}else{
			// 获取供应商id出错
			$this->returnRest(array(), false, 230008, 'error:获取当前登录供应商accountId出错');
		}
		// 判断$data内参数是否正常
		if(empty($param)){
			// 如果传入参数为空返回错误信息
			$this->returnRest(array(), false, 230008, 'error:消息分页查询传入参数为空');
		}else{
			// 查询数据总数
			$count = $this->manage->countMsg($param);
			// 调用分页查询供应商消息列表
			$result = $this->manage->querryMsg($param);
			$data['rows'] = $result;
			$data['count'] = $count;
			$this->returnRest($data, true, 230000, 'success');
		}
	}
	
	/**
     * 接口：分页查询供应商充值记录
     *
     * @author wenrui 20131225
     * @param $param
     * @return 
     */
	public function doRestGetRechargeHist($url, $param) {
		// 获取当前登录供应商的accountId
		$accountId = $this->getAccountId();
		if(empty($accountId)){
			// 获取供应商accountId出错
			$this->returnRest(array(), false, 230008, 'error:获取当前登录供应商accountId出错');
		}else{
			// 获取供应商的vendorId
			$info = $this->manage->read(array('id'=>$accountId));
			$vendorId = $info['vendorId'];
			if(empty($vendorId)){
				// 未找到供应商vendorId
				$this->returnRest(array(), false, 230008, 'error:未找到当前供应商的vendorId');
			}else{
				$param['agencyId'] = $vendorId;
				// 查询供应商充值记录
				$result = $this->manage->querryRechargeHist($param);
				$this->returnRest($result, true, 230000, 'success');
			}
		}
	}
	
	/**
	 * 财务账户增加牛币和赠币的区分后，需要对财务表老数据进行更新操作
	 * 
	 * add by wenrui 2014-04-21
	 */
	public function doRestPostUpdate($param){
		echo "=>对财务账户数据更新开始</br>";
		$data = $param['data'];
		if(!empty($data)&&is_Array($data)){
			$resultMsg = $this->fmis->update($data);
			echo $resultMsg;
		}else{
			echo "入参数组不正确，请校验</br>";
		}
		echo "=>对财务账户数据更新结束</br>";
	}
    
    /**
	 * 查询消费明细列表
	 */
	public function doRestGetExpenseinfo($url, $data) {
		
		$result = $this->genrateReturnRest();
		try {
			// 校验参数
			if (!empty($data['expenseType']) && is_numeric($data['expenseType']) && isset($data['start']) 
				&& !empty($data['limit']) && is_numeric($data['start']) && is_numeric($data['limit']) ) {
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
			} else {
				
				// 查询财务账户报表
				$data['data'] = $this->getAccountId();
				$result = $this->fmis->getExpenseInfo($data);
				
				// 整合结果，自定义编码和语句
				$result['data'] = $data;
				$result['errorCode'] = ErrorCode::ERR_231500;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
				
				// 返回结果
				$this->returnRestStand($result);
			}
		} catch(BBException $e) {
			$result['success'] = false;
			if (intval(chr(48)) != $e->getErrCode()) {
				$result['errorCode'] = $e->getErrCode();
				$result['msg'] = $e->getErrMessage();
			} else {
				$result['errorCode'] = ErrorCode::ERR_231000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			}
			
			// 返回结果
			$this->returnRestStand($result);
		} catch(Exception $e) {
			// 注入异常和日志
			new BBException($e->getCode(), $e->getMessage());
			$result['success'] = false;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
	}
	
}