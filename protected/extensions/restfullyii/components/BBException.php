<?php
/*
 * Created on 2014-7-28
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
class BBException extends Exception {
	
	// 错误编码
	private $errorCode = null;
	
	// 错误信息
	private $errorMsg = null;
	
	// 错误位置
	private $errorPos = null;
	
	// 错误数据
	private $errorData = null;
	
	// 错误时间
	private $errorTime = null;
	
	/**
	 * 构造函数
	 */
	function __construct($errCode=array(), $errMsg="", $errData="", $e) {
		// 编码和消息
		$this->initCodenMsg($errCode, $errMsg);
		// 位置
		if (!empty($e)) {
			$this->errorPos = CommonTools::getErrPos($e);
		} else {
			$this->errorPos = CommonTools::getErrPos($this);
		}
		// 异常数据
		$this->errorData = $errData;
		// 异常时间
		$this->errorTime = date('Y-m-d H:i:s');
		// 填充日志
		$BBLog = new BBLog();
		if ($BBLog->isInfo()) {
			$BBLog->logException($this->getErrCode(), $this->getErrMessage(), $this->errorData, $this->errorPos);
		}
	}
	
	/**
	 * 初始化异常编码和信息
	 */
	function initCodenMsg($errCode, $errMsg) {
		if (is_numeric($errCode) && !empty(ErrorCode::$errorCodeMap[strval($errCode)])) {
			$this->errorCode = $errCode;
			$this->errorMsg = ErrorCode::$errorCodeMap[strval($errCode)];
		} else if (is_numeric($errCode) && !empty($errMsg)) {
			$this->errorCode = $errCode;
			$this->errorMsg = $errMsg;
		} else {
			$this->errorCode = ErrorCode::ERR_231000;
			$this->errorMsg = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
		}
	}
	
	/**
	 * 获取异常编码
	 */
	public function getErrCode() {
		if (null == $this->errorCode) {
			return $this->getCode();
		} else {
			return $this->errorCode;
		}
	}
	
	/**
	 * 获取异常说明
	 */
	public function getErrMessage() {
		if (null == $this->errorMsg) {
			return $this->getMessage();
		} else {
			return $this->errorMsg;
		}
	}
	
	/**
	 * 获取异常数据
	 */
	public function getErrData() {
		return $this->errorData;
	}
	
	/**
	 * 获取异常时间
	 */
	public function getErrTime() {
		return $this->errorTime;
	}
	
}
?>
