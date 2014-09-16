<?php

Yii::import('application.dal.dao.DaoModule');

class BBLog extends DaoModule {
	
	// 日志级别
	const LEVEL = 'debug';
	
	// 日志级别调试
	const LEVEL_DEBUG = 'debug';
	
	// 日志级别生产
	const LEVEL_INFO = 'info';
	
	private $auth = false;
	
	/**
	 * 判断是否调试
	 */
	public function isDebug() {
		if (self::LEVEL_DEBUG === self::LEVEL) {
			$this->auth = true;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 判断是否生产
	 */
	public function isInfo() {
		if (self::LEVEL_INFO === self::LEVEL || self::LEVEL_DEBUG === self::LEVEL) {
			$this->auth = true;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 设置认证通过
	 */
	public function setAuth() {
		$this->auth = true;
	}
	
	/**
	 * 记录异常日志
	 */
	public function logException($errorCode, $errorMsg, $errData, $posOrUid) {
		// 校验位置和参数
		if (empty($posOrUid) || empty($errorCode) || empty($errorMsg) || !$this->auth) {
			return;
		}
		// 字符串化异常数据，并切割
		$errorDataStr = "";
		if (!empty($errData) && is_array($errData)) {
			$errorDataStr = json_encode($errData);
		} else if (!empty($errData)) {
			$errorDataStr = strval($errData);
		}
		if (300 < strlen($errorDataStr)) {
			$errorDataStr = substr($errorDataStr, 0, 290);
			$errorDataStr = $errorDataStr.Sundry::SUSPENMSION;
		}
		$errorDataStr = str_replace("\"",Symbol::EMPTY_STRING,$errorDataStr);
		$errorMsg = str_replace("\"",Symbol::EMPTY_STRING,$errorMsg);
		
		try {
			// 将日志插入数据库
			$sql = "INSERT INTO exception_log " .
					"	(exception_position, exception_data, exception_code, exception_msg, add_time)" .
					"	VALUES('".$posOrUid."', \"".$errorDataStr."\", ".$errorCode.", \"".$errorMsg."\", '".date('Y-m-d H:i:s')."');";
			$this->dbRW->createCommand($sql)->execute();
		} catch(Exception $e) {}
		// 还原标记
		$this->auth = false;
	}
	
	/**
	 * 记录通用方法日志
	 */
	public function logMethod($content, $msg, $posOrUid, $type) {
		// 校验位置和参数
		if (empty($posOrUid) || empty($content) || empty($msg) || 150 < strlen($msg) || !$this->auth) {
			return;
		}
		// 获取单双表号
		$tableNum = $this->getTableNum();
		// 获取日志内容
		$dbCon = "";
		if (is_array($content)) {
			$dbCon = json_encode($content);
		} else {
			$dbCon = strval($content);
		}
		$dbCon = str_replace("\"",Symbol::EMPTY_STRING,$dbCon);
		$msg = str_replace("\"",Symbol::EMPTY_STRING,$msg);

		try {
			// 判断是否需要插入附加内容
			$extendId = 0;
			if (200 < strlen($dbCon)) {
				// 将日志附加内容插入数据库
				$sql = "INSERT INTO extend_log ".
						"	(extend_content)" .
						"	VALUES(\"".$dbCon."\");";
				$this->dbRW->createCommand($sql)->execute();
				$extendId = $this->dbRW->lastInsertID;
				$dbCon = substr($dbCon, 0, 190);	
				$dbCon = $dbCon.Sundry::SUSPENMSION;
			}	
			// 将日志插入数据库
			$sql = "INSERT INTO method_log_".$tableNum .
					"	(log_pos,content,extend_id,type,msg,add_time)" .
					"	VALUES('".$posOrUid."', \"".$dbCon."\", ".$extendId.", '".$type."', \"".$msg."\", '".date('Y-m-d H:i:s')."');";
			$this->dbRW->createCommand($sql)->execute();
		} catch(Exception $e) {}
		// 还原标记
		$this->auth = false;
		
	}
	
	
	/**
	 * 记录接口日志
	 */
	public function logInterface($param, $url, $return, $way, $posOrUid, $limit, $endPos) {
		// 校验位置和参数
		if (empty($posOrUid) || !isset($param) || empty($url) || !isset($way) || !$this->auth) {
			return;
		}
		// 获取切面性能监控
		$useTime = BPMoniter::getMoniter($posOrUid);
		
		// 获取单双表号
		$tableNum = $this->getTableNum();
		// 获取结果元素
		$dataCount = 0;
		$rowsCount = 0;
		$success = 1;
		if (!empty($return) && $return['success']) {
			$dataCount = count($return['data']);
			$rowsCount = count($return['data']['rows']);
			$success = 0;
		}
		// 获取接口参数
		$dbParam = "";
		if (is_array($param)) {
			$dbParam = json_encode($param);
		} else {
			$dbParam = strval($param);
		}
		$dbParam = str_replace("\"",Symbol::EMPTY_STRING,$dbParam);
		
		try {
			
			// 判断是否需要插入性能异常
			if (!empty($limit) && !empty($endPos) && $useTime > $limit) {
				$this->logException(231099, '代码块执行超时，性能有问题！用时：'.$useTime, "", $posOrUid.chr(45).$endPos);
			}
			
			// 判断是否需要将参数插入附加内容
			$paramsId = 0;
			if (200 < strlen($dbParam)) {
				// 将日志附加内容插入数据库
				$sql = "INSERT INTO extend_log ".
						"	(extend_content)" .
						"	VALUES(\"".$dbParam."\");";
				$this->dbRW->createCommand($sql)->execute();
				$paramsId = $this->dbRW->lastInsertID;
				$dbParam = substr($dbParam, 0, 190);
				$dbParam = $dbParam.Sundry::SUSPENMSION;
			}	
			
			// 将日志插入数据库
			$sql = "INSERT INTO interface_log_".$tableNum .
					"	(log_pos,url,way,params,params_id,data_count,rows_count,success,use_time,add_time)" .
					"	VALUES(\"".$posOrUid."\", \"".$url."\", '".$way."', \"".$dbParam."\", ".$paramsId.", ".$dataCount.", ".$rowsCount.", ".$success.", ".$useTime.", '".date('Y-m-d H:i:s')."');";
			$this->dbRW->createCommand($sql)->execute();
		} catch(Exception $e) {}
		// 还原标记
		$this->auth = false;
	}
		

	/**
	 * 记录SQL日志
	 */
	public function logSql($sqlStr, $posOrUid, $limit, $endPos) {

		// 校验位置和参数
		if (empty($posOrUid) || empty($sqlStr) || !$this->auth) {
			return;
		}
		// 获取切面性能监控
		$useTime = BPMoniter::getMoniter($posOrUid);
		
		// 获取接口参数
		$dbSql = $sqlStr;

		try {

			// 判断是否需要插入性能异常
			if (!empty($limit) && !empty($endPos) && $useTime > $limit) {
				$this->logException(231099, '代码块执行超时，性能有问题！用时：'.$useTime, "", $posOrUid.chr(45).$endPos);
			}
			// 判断是否需要将SQL插入附加内容
			$extendId = 0;
			if (500 < strlen($sqlStr)) {
				// 将日志附加内容插入数据库
				$sql = "INSERT INTO extend_log ".
						"	(extend_content)" .
						"	VALUES(\"".$sqlStr."\");";
				$this->dbRW->createCommand($sql)->execute();
				$extendId = $this->dbRW->lastInsertID;
				$dbSql = substr($sqlStr, 0, 490);	
				$dbSql = $dbSql.Sundry::SUSPENMSION;
			}

			// 将日志插入数据库
			$sql = "INSERT INTO sql_log ".
					"	(log_pos, sql_statement, extend_id, use_time, add_time)" .
					"	VALUES(\"".$posOrUid."\", \"".$dbSql."\", ".$extendId.", ".$useTime.", \"".date('Y-m-d H:i:s')."\");";

			$this->dbRW->createCommand($sql)->execute();

		} catch(Exception $e) {}
		// 还原标记
		$this->auth = false;
	}
	
	/**
	 * 获取表序号
	 */
	function getTableNum() {
		return intval(date('d')) % 2 == 0 ? '02' : '01';
	}
	
}

?>
