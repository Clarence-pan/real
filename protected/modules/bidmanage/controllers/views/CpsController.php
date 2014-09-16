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
Yii::import('application.modules.bidmanage.models.cps.CpsMod');

class CpsController extends restUIServer {

	/**
	 * cps业务处理类
	 */
    private $cpsMod;

    function __construct() {
    	// 初始化cps业务处理类
        $this->cpsMod = new CpsMod();
    }
    
    
    /**
     * 获取CPS分类信息
     */
    public function doRestGetCpswebclassinfo($url, $data) {
    	$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (isset($data['startCityCode']) && is_numeric($data['startCityCode']) && !empty($data['classDepth'])
				 && is_numeric($data['classDepth'])) {
				// 获取CPS分类信息
				$resultMod = $this->cpsMod->getCpsWebClassInfo($data);
					
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
    
    /**
     * 获取CPS产品
     */
    public function doRestGetCpsproduct($url, $data) {
    	$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (isset($data['startCityCode']) && is_numeric($data['startCityCode']) && !empty($data['webClass'])
				 && !empty($data['webClassDepth']) && !empty($data['productType'])) {
				// 获取CPS产品
				$resultMod = $this->cpsMod->getCpsProduct($data);
					
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
    
    /**
     * 保存CPS产品
     */
    public function doRestPostCpsblock($data) {
    	$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (isset($data['blocks']) && is_array($data['blocks'])) {
				// 保存CPS产品
				$resultMod = $this->cpsMod->saveCpsProduct($data);
					
				// 整合结果，自定义编码和语句
				$result['data'] = $resultMod;
				$result['errorCode'] = ErrorCode::ERR_231501;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231501)];
					
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
    
    /**
     * 添加CPS供应商
     */
    public function doRestPostCpsvendor($data) {
    	$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (!empty($data['cpsFlag']) && !empty($data['showEndDate'])) {
				// 添加CPS供应商
				$this->cpsMod->addCpsVendor($data);
					
				// 整合结果，自定义编码和语句
				$result['errorCode'] = ErrorCode::ERR_231501;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231501)];
					
				// 返回结果
				$this->returnRestStand($result);
			} else {
				
				$result['success'] = Symbol::CONS_FALSE;
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
			}
		} catch(BBException $e) {
			$result['success'] = Symbol::CONS_FALSE;
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
			$result['success'] = Symbol::CONS_FALSE;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
    }
    
    /**
     * 查询CPS供应商
     */
    public function doRestGetCpsvendor($url, $data) {
    	$result = $this->genrateReturnRest();
		
		try {
			// 查询CPS供应商
			$resultMod = $this->cpsMod->getCpsVendor($data);
					
			// 整合结果，自定义编码和语句
			$result['data'] = $resultMod;
			$result['errorCode'] = ErrorCode::ERR_231500;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
					
			// 返回结果
			$this->returnRestStand($result);
		} catch(BBException $e) {
			$result['success'] = Symbol::CONS_FALSE;
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
			$result['success'] = Symbol::CONS_FALSE;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
    }
    
    /**
     * 查询CPS协议
     */
    public function doRestGetCpsagreement($url, $data) {
    	$result = $this->genrateReturnRest();
		
		try {
			// 查询CPS供应商
			$resultMod = $this->cpsMod->getCpsAgreement($data);
					
			// 整合结果，自定义编码和语句
			$result['data'] = $resultMod;
			$result['errorCode'] = ErrorCode::ERR_231500;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
					
			// 返回结果
			$this->returnRestStand($result);
		} catch(Exception $e) {
			// 注入异常和日志
			new BBException($e->getCode(), $e->getMessage());
			$result['success'] = Symbol::CONS_FALSE;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
    }
	
}