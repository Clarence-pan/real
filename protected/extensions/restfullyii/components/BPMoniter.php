<?php

class BPMoniter {
	
	// 监控常量
	const MONITER = 'moniter';
	
	// 性能监控数组集合
	static $moniter = array();
	
	/**
	 * 创建性能监控
	 */ 
	public static function createMoniter($pos) {
		// 如果垃圾key达到了512个，则一把全部清理
		if (512 <= count(self::$moniter)) {
			foreach($moniter as $moniterKey=>$moniterVal) {
				unset($moniter[$moniterKey]);
			}
		}
		// 生成唯一的监控KEY
		$moniterKey = self::bpmGuid($pos);
		// 添加监控初始时间
		self::$moniter[$moniterKey] = self::getCurTime();
		// 返回key
		return $moniterKey;
	}

	/**
	 * 获取监视结果
	 */
	public static function getMoniter($moniterKey) {
		if (!empty(self::$moniter[$moniterKey])) {
			$useTime = self::getCurTime() - self::$moniter[$moniterKey];
			unset(self::$moniter[$moniterKey]);
			return $useTime;
		} else {
			return -1;
		}
	}
	
	/**
	 * 结束监控
	 */
	public static function endMoniter($moniterKey, $limit, $endLine) {
		if (!empty(self::$moniter[$moniterKey]) && !empty($limit) && !empty($endLine)) {
			$useTime = self::getCurTime() - self::$moniter[$moniterKey];
			unset(self::$moniter[$moniterKey]);
			// 如果超出指标，则插入异常日志
			if (intval($useTime) > intval($limit)) {
				new BBException(231099, '代码块执行超时，性能有问题！用时：'.$useTime, $moniterKey.chr(45).$endLine);
			}
		}
	}
	
	/**
	 * 销毁监视器实例
	 */
	public static function destroyMoniterSingle($moniterKey) {
		if (!empty(self::$moniter[$moniterKey])) {
			unset(self::$moniter[$moniterKey]);
		}
	}
	
	/**
	 * 获取当前时间戳
	 */
	public static function getCurTime() {
		return floor(microtime(true)*1000);
	}
	
	/**
	 * 生成性能监控GUID
	 */
	static function bpmGuid($pos) {
		$guid = "";
		// 判断是否调用系统函数
		if (function_exists('com_create_guid')){
			// 调用系统函数
	        $guid = com_create_guid();
	    }else{
	    	// 获取当前时间戳
	        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
	        // 获取一个随机数
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        // 初始化一个横线"-"
	        $hyphen = chr(45); 
	        
	        // 生成一个唯一的ID
	        $guid = chr(125)
	        		.substr($charid, 0, 8).$hyphen
	                .substr($charid, 8, 4).$hyphen
	                .substr($charid,12, 4).$hyphen
	                .substr($charid,16, 4).$hyphen
	                .substr($charid,20,12).chr(125);
		}
        // 返回GUID
        return $pos.$hyphen
        		.$guid.$hyphen
	            .self::MONITER;;
	}
	
}
?>
