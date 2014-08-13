<?php

class CommonTools {
	
	/**
	 * 获取异常错误位置
	 */
	public static function getErrPos($e) {
		$trace = $e->getTrace();
		return $trace[0]['class'].'::'.$trace[0]['function'].'::'.$e->getLine();
	}
	
}
?>
