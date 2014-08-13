<?php

Yii::import('application.dal.dao.DaoModule');

class ClsrecommendDao extends DaoModule {
	
	/**
     * 校验分类页广告位存在性
	 */
	public function queryClsrecomExists() {
		try {
			$sql = "SELECT id FROM position_sync_class WHERE del_flag = 0 limit 1";
			return $this->executeSql($sql, self::ROW);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
     * 查询区块报价信息
	 */
	public function queryBlockPrice($param) {
		try {
			$sql = "SELECT a.ad_name AS className, a.web_class AS classId, IFNULL(ROUND(b.floor_price), '') AS floorPrice, IFNULL(b.ad_product_count, '') AS adProductCount, IFNULL(b.coupon_use_percent, '') AS couponUsePercent 
			FROM position_sync_class a LEFT JOIN ba_ad_position b ON a.start_city_code = b.start_city_code AND a.web_class = b.web_class AND b.show_date_id = ".$param['showDateId']." AND b.del_flag = 0 
			WHERE a.del_flag = 0 AND a.start_city_code = ".$param['startCityCode']." AND a.class_depth = 1";
			return $this->executeSql($sql, self::ALL);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询已配置的全局广告位ID，分类ID
	 */
	public function queryOverallConfigId($param) {
		try {
			$sql = "SELECT id, web_class as webClass, start_city_code as startCityCode, parent_id as parentId, ad_key_type as adKeyType FROM ba_ad_position WHERE del_flag = 0 AND start_city_code IN (".$param['startCityCodes'].") AND show_date_id = ".$param['showDateId']." AND ad_key_type = ".$param['adKeyType']." ORDER BY start_city_code ASC";
			return $this->executeSql($sql, self::ALL);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}		
	
	/**
	 * 查询分类页对应的类型ID
	 */
	public function queryClsrecommendTypeId() {
		try {
			$sql = "SELECT id FROM ba_ad_position_type WHERE del_flag = 0 AND ad_key_type = 2 ";
			return $this->executeSql($sql, self::ROW);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}	
	
	/**
	 * 查询分类页特殊配置
	 */
	public function queryClsrecommendSpecial($param) {
		try {
			// 初始化动态SQL
			$dySql = "";
			if (!empty($param['startCityCode'])) {
				$dySql = $dySql." and start_city_code =".$param['startCityCode'];
			}
			if (2 != $param['isMajor']) {
				$dySql = $dySql." and is_major =".$param['isMajor'];
			}
			if (!empty($param['adName'])) {
				$dySql = $dySql." and ad_name like '%".$param['adName']."%'";
			}
			$sqlRows = "SELECT id, ad_key AS adKey, ad_name AS adName, start_city_code AS startCityCode, web_class AS classId, floor_price AS floorPrice, ad_product_count AS adProductCount, coupon_use_percent AS couponUsePercent, update_time AS updateTime ".
					"FROM ba_ad_position WHERE ad_key_type = ".$param['classDepth']." AND show_date_id = ".$param['showDateId']." AND del_flag = 0 ".$dySql."  ORDER BY update_time DESC LIMIT ".$param['start'].",".$param['limit'];
			$sqlCount = "SELECT count(0) as countRe " .
					"FROM ba_ad_position WHERE ad_key_type = ".$param['classDepth']." AND show_date_id = ".$param['showDateId']." AND del_flag = 0 ".$dySql;
			$result['rows'] = $this->executeSql($sqlRows, self::ALL);
			$count = $this->executeSql($sqlCount, self::ROW);
			$result['count'] = $count['countRe'];
			return $result;
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 根据出发城市新增分类页广告位
	 */
	public function insertClsrecomByCity($param) {
		try {
			$sqlRows = array();
			$classes = $param['webClasses'];
			foreach ($classes as $classesObj) {
				$sqlRowsTemp = "INSERT INTO ba_ad_position 
								(ad_key, ad_name, start_city_code, floor_price, ad_product_count, coupon_use_percent, show_date_id, 
								add_uid, add_time, update_uid, update_time, is_major, type_id, ad_key_type, web_class) " .
							   " SELECT 'class_recommend', ad_name, start_city_code, ".$classesObj['floorPrice'].", ".$classesObj['adProductCount'].", " 
							   .$classesObj['couponUsePercent'].", ".$param['showDateId'].", 4333, '".date('Y-m-d H:i:s')."', 4333, '".date('Y-m-d H:i:s')."'," .
							   	" is_major, ".$param['typeId'].", 20+class_depth, web_class from position_sync_class where start_city_code IN (".$param['startCityCodes'].") ".
								" AND del_flag = 0 AND (web_class = ".$classesObj['classId']." OR parent_class = ".$classesObj['classId'].");";
				array_push($sqlRows, $sqlRowsTemp);
			}
			$this->executeSql($sqlRows, self::SALL);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 删除原有广告位配置
	 */
	public function deleteClsrecomConfig($param) {
		try {
			$sql = "update ba_ad_position set del_flag = 1 where del_flag = 0 and show_date_id = ".$param['showDateId']." and start_city_code in (".$param['startCityCodes'].") and web_class in (".$param['webClasses'].")";
			$this->executeSql($sql, self::SROW);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 根据城市查询分类
	 */
	public function queryClassByCity($param) {
		try {
			$sql = "SELECT web_class as webClass, start_city_code as startCityCode, is_major as isMajor FROM position_sync_class WHERE start_city_code IN (".$param.") AND class_depth = 1 ORDER BY start_city_code ASC";
			return $this->executeSql($sql, self::ALL);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询特殊配置位置维度
	 */
	public function queryPositionWd($param) {
		try {
			// 初始化动态SQL
			$dySql = "";
			if (!empty($param['startCityCode'])) {
				$dySql = $dySql." and start_city_code =".$param['startCityCode'];
			}
			if (2 != $param['isMajor']) {
				$dySql = $dySql." and is_major =".$param['isMajor'];
			}
			// 初始化SQL
			$sqlAll = "SELECT web_class as webClass, start_city_code as startCityCode FROM ba_ad_position WHERE show_date_id = ".$param['showDateId']." AND del_flag = 0 AND ad_key_type = 21 ".$dySql;
			$sqlCls = "SELECT distinct web_class as webClass FROM ba_ad_position WHERE show_date_id = ".$param['showDateId']." AND del_flag = 0 AND ad_key_type = 21 ".$dySql;
			$result['all'] = $this->executeSql($sqlAll, self::ALL);
			$result['cls'] = $this->executeSql($sqlCls, self::ALL);
			return $result;
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询特殊配置同步表信息分类维度
	 */
	public function querySyncPositionInfoByClass($param, $wd) {
		try {
			// 初始化动态SQL
			$dySql = " CASE parent_class ";
			foreach($wd as $wdObj) {
				$dySql = $dySql." WHEN ".$wdObj['webClass']." THEN start_city_code IN (".$wdObj['startCityCodes'].") ";
			}
			$dySql = $dySql." ELSE 0=1 END ";
			if (!empty($param['startCityCode'])) {
				$dySql = $dySql." and start_city_code =".$param['startCityCode'];
			}
			if (2 != $param['isMajor']) {
				$dySql = $dySql." and is_major =".$param['isMajor'];
			}
			if (!empty($param['adName'])) {
				$dySql = $dySql." and ad_name like '%".$param['adName']."%'";
			}
			if (!empty($param['classDepth'])) {
				$dySql = $dySql." and class_depth = ".$param['classDepth'];
			}
			// 初始化SQL
			$sqlRows = "SELECT is_major AS isMajor, ad_name AS adName, web_class AS classId, start_city_code AS startCityCode, parent_class AS parentClass FROM position_sync_class ".
					" WHERE ".$dySql." AND del_flag = 0 AND class_depth = ".$param['classDepth']." AND parent_depth = 1 ORDER BY start_city_code ASC LIMIT ".$param['start'].", ".$param['limit'];
			$sqlCount = "SELECT count(id) as countRe FROM position_sync_class ".
					" WHERE ".$dySql." AND del_flag = 0 AND class_depth = ".$param['classDepth']." AND parent_depth = 1 ";
			$result['rows'] = $this->executeSql($sqlRows, self::ALL);
			$count = $this->executeSql($sqlCount, self::ROW);
			$result['count'] = $count['countRe'];
			
			// 返回结果
			return $result;
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询特殊配置同步表信息城市维度
	 */
	public function querySyncPositionInfoByCity($param, $wd) {
		try {
			// 初始化动态SQL
			$dySql = " CASE start_city_code ";
			foreach($wd as $wdObj) {
				$dySql = $dySql." WHEN ".$wdObj['startCityCode']." THEN web_class IN (".$wdObj['webClasses'].") ";
			}
			$dySql = $dySql." ELSE 0=1 END ";
			// 初始化SQL
			$sqlRows = "SELECT ad_name AS adName, web_class AS classId, start_city_code AS startCityCode, parent_class AS parentClass FROM position_sync_class ".
					" WHERE ".$dySql." AND del_flag = 0 AND class_depth = ".$param['classDepth']." AND parent_depth = 2 ORDER BY start_city_code ASC LIMIT 0, ".$param['limit'];
			
			// 返回结果
			return $this->executeSql($sqlRows, self::ALL);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询分类价格信息
	 */
	public function queryClassPriceInfo($param) {
		try {
			// 初始化SQL
			$sql = "SELECT start_city_code AS startCityCode, floor_price AS floorPrice, ad_product_count AS adProductCount, coupon_use_percent AS couponUsePercent, update_time AS updateTime, ad_key_type AS adKeyType, web_class AS webClass	FROM ba_ad_position where show_date_id = ".$param['showDateId']." and del_flag = 0 and start_city_code in (".$param['startCityCodes'].") order by ad_key_type asc ";
						
			// 返回结果
			return $this->executeSql($sql, self::ALL);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 删除特殊配置
	 */
	public function deleteSpecialConfig($param, $wd) {
		try {
			// 初始化动态SQL
			$dySql = " CASE start_city_code ";
			foreach($wd as $wdObj) {
				$dySql = $dySql." WHEN ".$wdObj['startCityCode']." THEN web_class IN (".$wdObj['webClasses'].") ";
			}
			$dySql = $dySql." ELSE 0=1 END ";
			// 初始化SQL
			$sqlRows = "UPDATE ba_ad_position set del_flag = 1 WHERE ".$dySql." AND del_flag = 0 AND show_date_id = ".$param['showDateId'];
			// 执行SQL
			$this->executeSql($sqlRows, self::SROW);
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询可以竞拍的分类ID
	 */
	public function queryBidWebClass($param) {
		try {
			// 查询已配置的父类ID
			$sqlFa = "SELECT DISTINCT web_class FROM ba_ad_position WHERE del_flag = 0 AND show_date_id in (".$param['showDateIds'].") AND ad_key_type = 21 AND start_city_code = ".$param['startCityCode'];
			$faRows = $this->executeSql($sqlFa, self::ALL);
			
			if (!empty($faRows) && is_array($faRows)) {
				// 查询可用的webClass
				$webStr = "";
				foreach($faRows as $faRowsObj) {
					$webStr = $webStr.$faRowsObj['web_class'].",";
				}
				$webStr = substr($webStr, 0, strlen($webStr) - 1);
				$sqlAv = "SELECT web_class, parent_class FROM position_sync_class WHERE del_flag = 0 AND parent_class IN (".$webStr.") AND  start_city_code = ".$param['startCityCode']." AND parent_depth = 1";
				// 执行SQL
				$dbAv = $this->executeSql($sqlAv, self::ALL);
				$ids = array();
				foreach ($dbAv as $dbAvObj) {
					array_push($ids, $dbAvObj['web_class']);
					array_push($ids, $dbAvObj['parent_class']);
				}
				$ids = array_unique($ids);
				return $ids;
			} else {
				return array();
			}
			
			
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
 
	
}