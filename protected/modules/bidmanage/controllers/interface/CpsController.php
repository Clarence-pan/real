<?php
/**
 * hagrid对外接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-06-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.cps.CpsMod');

class CpsController extends restSysServer {

	private $cpsMod;
    
    function __construct() {
        $this->cpsMod = new CpsMod();
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
					
				// 查询财务账户报表
				$data['accountId'] = $this->getAccountId();
				$resultMod = $this->fmis->getExpenseInfo($data);
				
				// 整合结果，自定义编码和语句
				$result['data'] = $resultMod;
				$result['errorCode'] = ErrorCode::ERR_231500;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
				
				// 返回结果
				$this->returnRestStand($result);
				
			} else {
				
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
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