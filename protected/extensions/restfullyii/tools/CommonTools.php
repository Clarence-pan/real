<?php

class CommonTools {
	
	/**
	 * 获取异常错误位置
	 */
	public static function getErrPos($e) {
		$trace = $e->getTrace();
		return $trace[0]['class'].'::'.$trace[0]['function'].'::'.$e->getLine();
	}
	
	/**
	 * 将空值转换位数字
	 */
	public static function getEmptyNum($param) {
		return empty($param) ? intval(chr(48)) : intval($param);
	}
	
	/**
	 * 获取是或否
	 */
	public static function getIsOrNot($param) {
		return empty($param) ? intval(chr(48)) : intval(chr(49));
	}
	
}
?>
