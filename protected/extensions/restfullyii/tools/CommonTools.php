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
	
	/**
	 * 获取两个日期中间隔的天数
	 */
	public static function intervalDate($begDate,$endDate) {
	    $date = array();
	    $begTime = strtotime($begDate) + Symbol::ONE_DAY_SECOND;
	    $endTime = strtotime($endDate);
	    while($begTime < $endTime) {
	        $date[] = date(Sundry::TIME_Y_M_D, $begTime);
	        $begTime += Symbol::ONE_DAY_SECOND;
	    }
	    return $date;
	}

    /**
     * 判断是否是有效的参数，只有空字符串（""）和null值是无效的，其他值包括0都是有效的
     * @param $param mixed
     * @return bool
     */
    public static function isValidParam($param) {
        return !is_null($param) and $param !== "";
    }

    /**
     * 将CPS的推广管理报表中的值进行转换，从数值转换为字符串
     * @param $valuesRows array 数据库查询结果，要求是array( array('key' => value), ... )的格式
     * @return mixed 结果
     */
    public static function mapCpsShowReportValues(&$valuesRows){
        foreach ($valuesRows as &$row) {
            $row['purchaseState'] = ($row['purchaseState'] ? "已结算" : "未结算");
            $row['invoiceState'] = ($row['invoiceState'] ? "已开" : "未开");
        }
        return $valuesRows;
    }

    /**
     * 转换编码，从utf-8到gbk
     * @param $values mixed  可以是数组，也可以是字符串，也可以是数组的数组
     * @return mixed  里面的字符串都被从utf-8转换为gbk编码
     */
    public static function mapUtf8ToGbk(&$values) {
        if (is_array($values)){
            foreach ($values as &$value) {
                $value = self::mapUtf8ToGbk($value);
            }
        } else if (is_string($values)){
            $values = iconv('utf-8', 'gbk', $values);
        } else {
            $values = strval($values);
            $values = iconv('utf-8', 'gbk', $values);
        }
        return $values;
    }

    /**
     * 将SQL语句中的参数用给定的参数进行填充
     * @param $sql  string SQL语句，里面的参数是用:key的形式指定的
     * @param $params array 参数，格式: array(":key" => value, ...)
     * @return 填充参数后的SQL语句
     */
    public static function fillSqlParams($sql, $params) {
        foreach ($params as $key => $value){
            $sql = str_replace($key, "'" . mysql_real_escape_string(strval($value)) . "'", $sql);
        }
        return $sql;
    }

}
?>
