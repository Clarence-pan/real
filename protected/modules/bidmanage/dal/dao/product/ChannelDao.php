<?php

Yii::import('application.dal.dao.DaoModule');

class ChannelDao extends DaoModule {
	
    /**
     * 查询招客宝的频道页区块信息
     * @param array $param
     * @return array
     */
    public function queryChannelChosenAdKey($param) {
        if (!$param['startCityCode']) {
            return array();
        }
        $startCityCode = intval($param['startCityCode']);
        $channelId = " ";
        if(!empty($param['channelId'])){
        	$channelId = ' and c.channel_id= '.$param['channelId'];
        }
        
        $sql = "SELECT a.ad_key AS adKey,a.ad_name AS adName,a.start_city_code AS startCityCode,
        		CONCAT(b.show_start_date, ' ~ ', b.show_end_date) AS showDate FROM ba_ad_position a LEFT JOIN bid_show_date b ON a.show_date_id = b.id LEFT JOIN ba_ad_position_type c ON c.id = a.type_id
        		WHERE a.del_flag = 0 AND b.del_flag = 0 AND c.del_flag = 0 AND DATE_FORMAT(CONCAT(b.bid_start_date, CONCAT(' ', b.bid_start_time)), '%Y-%m-%d %H') <= '".date('Y-m-d H')."' 
        		  AND DATE_FORMAT(CONCAT(b.bid_end_date, CONCAT(' ', b.bid_end_time)), '%Y-%m-%d %H') > '".date('Y-m-d H')."' AND c.ad_key_type = 5 AND a.start_city_code = ".$startCityCode .$channelId .
				" GROUP BY a.ad_key ORDER BY a.ad_name ASC ";
        return $this->dbRW->createCommand($sql)->queryAll();
    }
    

	/**
	 * 查询招客宝的频道页区块信息
	 */
	public function queryChannelChannelForBB($param) {
		try {
			// 初始化SQL
			$sql = "SELECT DISTINCT a.channel_id AS channelId, a.channel_name AS channelName 
					FROM ba_ad_position_type a LEFT JOIN ba_ad_position b ON a.id = b.type_id LEFT JOIN bid_show_date c ON b.show_date_id = c.id
					WHERE CONCAT(DATE_FORMAT(c.bid_start_date, '%Y-%m-%d'), ' ', IF(c.bid_start_time<10,CONCAT('0',c.bid_start_time),c.bid_start_time)) <= '".date('Y-m-d H')."' 
					AND CONCAT(DATE_FORMAT(c.bid_end_date, '%Y-%m-%d'), ' ', IF(c.bid_end_time<10,CONCAT('0',c.bid_end_time),c.bid_end_time)) >= '".date('Y-m-d H')."' 
					AND b.start_city_code = ".$param['startCityCode']." AND b.del_flag = 0 AND a.del_flag = 0 AND c.del_flag = 0 AND a.is_open = 0 AND b.is_open = 0 AND a.ad_key_type = 5 ";
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}

	/**
	 * 查询海格的频道页区块信息
	 */
	public function queryChannelChannelForHA($param) {
		try {
			// 初始化SQL
			$sql = "SELECT DISTINCT channel_id AS channelId, channel_name AS channelName
					FROM ba_ad_position_type WHERE del_flag= 0 start_city_code = ".$param['startCityCode']." AND is_open = 0 AND ad_key_type = 5";
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
    /**
     * 查询招客宝的频道页特殊配置列表信息
     */
	public function querySpecialconfig($param) {
		try {
	    //运营计划ID
	    $showDateId = intval($param['showDateId']);    
        $adName = ($param['adName']) ? " and a.ad_name like \"%".$param['adName']."%\"" : " ";
        $isMajor = (intval($param['isMajor']) == 2)? " ": " and b.is_minor=".intval($param['isMajor']);
        $startCityCode = ($param['startCityCode']) ? " and a.start_city_code=".intval($param['startCityCode']) : " ";
        $start = ($param['start']) ? intval($param['start']) : 0;
        $limit = ($param['limit']) ? intval($param['limit']) : 10000;
		$sql = "SELECT a.id as id,a.show_date_id as showDateId,a.ad_key AS adKey,a.ad_name AS adName,a.start_city_code AS startCityCode,d.name AS startCityName," .
					"a.floor_price AS floorPrice,a.ad_product_count AS adProductCount,a.coupon_use_percent AS couponUsePercent FROM ba_ad_position a " .
					"LEFT JOIN ba_ad_position_type b ON a.type_id=b.id LEFT JOIN bid_show_date c ON a.show_date_id=c.id LEFT JOIN " .
					"departure d ON a.start_city_code=d.code WHERE 1=1  ".$adName . $isMajor . $startCityCode ." AND a.del_flag=0 and " .
							"a.is_open=0 and b.is_open=0 and c.del_flag=0 and d.mark=0 and b.ad_key_type = 5 and " .
							"a.show_date_id=".$showDateId .
							" ORDER BY a.type_id  DESC LIMIT " .$start." ,".$limit;
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
    /**
     * 查询招客宝的频道页特殊配置列表信息总数
     */
     
	public function querySpecialconfigCount($param) {
		try {
	    //运营计划ID
	    $showDateId = intval($param['showDateId']);    
        $adName = ($param['adName']) ? " and a.ad_name like '%".$param['adName']."%'" : " ";
        $isMajor = (intval($param['isMajor']) == 2)? " ": " and b.is_minor=".intval($param['isMajor']);
        $startCityCode = ($param['startCityCode']) ? " and a.start_city_code=".intval($param['startCityCode']) : " ";
		$sql = "SELECT count(0) as count FROM ba_ad_position a " .
					"LEFT JOIN ba_ad_position_type b ON a.type_id=b.id LEFT JOIN bid_show_date c ON a.show_date_id=c.id LEFT JOIN " .
					"departure d ON a.start_city_code=d.code WHERE 1=1  ".$adName . $isMajor . $startCityCode ." AND a.del_flag=0 and " .
							"a.is_open=0 and b.is_open=0 and c.del_flag=0 and d.mark=0 and b.ad_key_type = 5 and " .
							"a.show_date_id=".$showDateId ;

			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}     
		
	/**
	 * 查询已存在的广告位信息
	 */
	public function queryExistAdkey($param) {
		try {
			// 初始化SQL
			$sql = "SELECT a.ad_key AS adKey, a.start_city_code AS startCityCode b.channel_id as channelId" .
					"FROM ba_ad_position a LEFT JOIN ba_ad_position_type b ON a.type_id = b.id " .
					"WHERE a.show_date_id = 256 AND b.channel_id IN (".$param['channelIds'].") AND a.del_flag = 0 AND b.del_flag = 0 AND a.start_city_code IN (".$param['startCityCodes'].") ORDER BY a.start_city_code ASC ";
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	} 
	
	/**
	 * 删除已存在的广告位
	 */
	public function deleteExistAdkey($param) {
		try {
			// 初始化SQL
			$sql = "UPDATE ba_ad_position a LEFT JOIN ba_ad_position_type b ON a.type_id = b.id SET a.del_flag = 1 WHERE a.show_date_id = ".$param['showDateId']." AND b.channel_id IN (".$param['channelIds'].") AND a.del_flag = 0 AND b.del_flag = 0 AND a.start_city_code IN (".$param['startCityCodes'].") ";
			// 查询供应商信息
			$this->dbRW->createCommand($sql)->execute();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 保存频道页全局
	 */
	public function saveOverallConfig($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			$rows = $param['rows'];
			foreach ($rows as $rowsObj) {
				// 插入全局配置
				$sql = "INSERT INTO ba_ad_position 
					(
					ad_key, 
					ad_name, 
					start_city_code, 
					floor_price,  
					ad_product_count, 
					show_date_id, 
					add_uid, 
					add_time, 
					update_uid, 
					update_time, 
					coupon_use_percent, 
					is_major, 
					type_id, 
					ad_key_type
					) SELECT 	
					ad_key, ad_name, start_city_code, 
					".$rowsObj['floorPrice'].", ".$rowsObj['adProductCount'].", ".$param['showDateId'].", 4333, NOW(), 4333, NOW(), 
					'".$rowsObj['couponUsePercent']."',is_minor, id, ad_key_type FROM 
					ba_ad_position_type 
					WHERE
					start_city_code IN (".$param['startCityCodes'].")
					AND
					ad_key_type = 5
					AND
					channel_id = ".$rowsObj['channelId']."
					AND
					del_flag = 0
					AND
					is_open = 0 ";
				$this->dbRW->createCommand($sql)->execute();
			}
			
			// 提交事务会真正的执行数据库操作
    		$transaction->commit();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
	}
	
	/**
	 * 同步频道页位置数据
	 */
	public function syncChannelPositon($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			
			// 删除原有广告位
			$sql = "update ba_ad_position_type set del_flag = 1 where ad_key_type = 5 ";
			$this->dbRW->createCommand($sql)->execute();
			// 循环插入数据
			foreach ($param as $paramObj) {
				$this->dbRW->createCommand($paramObj)->execute();
			}
			
			// 提交事务会真正的执行数据库操作
    		$transaction->commit();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
	}
	
	/**
	 * 保存特殊非统一配置
	 */
	public function saveSpecialNoConfig($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			$rows = $param['rows'];
			
			// 循环插入数据
			foreach ($rows as $rowsObj) {
				$sql = "UPDATE ba_ad_position 
						SET
						floor_price = '".$rowsObj['floorPrice']."' , 
						ad_product_count = '".$rowsObj['adProductCount']."' , 
						coupon_use_percent = '".$rowsObj['couponUsePercent']."'
						WHERE
						id = '".$rowsObj['id']."' 
						AND
						show_date_id = ".$param['showDateId'];
				$this->dbRW->createCommand($sql)->execute();
			}
			
			// 提交事务会真正的执行数据库操作
    		$transaction->commit();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
	}

	/**
	 * 保存特殊统一配置
	 */
	public function saveSpecialYesConfig($param) { 
		try {
			// 初始化动态SQL
			$dySql = "";
			if (!empty($param['isMajor'])) {
				$dySql = $dySql." and is_major =".$param['isMajor'];
			}
			if (!empty($param['startCityCode'])) {
				$dySql = $dySql." and start_city_code =".$param['startCityCode'];
			}
			if (!empty($param['adName'])) {
				$dySql = $dySql." and ad_name like '%".$param['adName']."%'";
			}
			
			// 初始化SQL
			$sql = "UPDATE ba_ad_position SET floor_price = ".$param['floorPrice'].", ad_product_count = ".$param['adProductCount'].", " .
					"coupon_use_percent = ".$param['couponUsePercent']." WHERE del_flag = 0 AND is_open = 0 AND show_date_id = ".$param['showDateId'].$dySql;
			// 保存特殊统一配置
			return $this->dbRW->createCommand($sql)->execute();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
}