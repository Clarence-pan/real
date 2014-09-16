<?php


/**
 * Coypright © 2013 Tuniu Inc. All rights reserved.
 * Author: p-sunhao
 * Date: 11/11/13
 * Time: 4:52 PM
 * Description: PackageDateDao.php
 */
Yii :: import('application.dal.dao.DaoModule');

/**
 * 打包时间操作数据库类
 * 
 * Author: p-sunhao
 */
class PackageDateDao extends DaoModule {

	/**
	 * 表名
	 */
	private $_tblName = 'bid_show_date';

	/**
	 * 查询打包时间信息
	 * 
	 * @param array $params
	 * @return array
	 */
	public function queryPackageDate($params) {
		// 初始化删除标记查询条件
		$condSqlSegment=' del_flag=:delFlag';
		// 设置删除标记参数
		$paramsMapSegment[':delFlag'] = intval(chr(48));
		// 设置排序参数
		$orderParam = 'id DESC';
		// 设置状态参数
		$statusP = "";
		try {
			if (intval(chr(48)) == $params['queryFlag'] && (intval(chr(48)) == $params['status'] || intval(chr(49)) == $params['status'])) {	
				$condSqlSegment.=' and status=:status';
				$paramsMapSegment[':status'] = $params['status'];
				$orderParam = 'show_start_date DESC';
				$statusP = "AND status=".$params['status'];
			}
		} catch(Exception $e) {}
		// 初始化共通查询结果字段集
		$queryResult = 'id as id, show_start_date as showStartDate, show_end_date as showEndDate, bid_start_date as bidStartDate, bid_end_date as bidEndDate, bid_end_time as bidEndTime, replace_end_time as replaceEndTime, status as status, release_time as releaseTime, misc as misc';
		
		try {
			// 判断一    判断是列表查询，对比批量查询还是对比单条查询
			if (intval(chr(48)) == $params['queryFlag']) {
		    	// 初始化SQL语句
		    	$sql = "SELECT IF((SELECT COUNT(b.id) FROM bid_bid_product AS b WHERE del_flag=0 and show_date_id = a.id), 3, 0) AS subStatus, id AS id, show_start_date AS showStartDate, show_end_date AS showEndDate, bid_start_date AS bidStartDate, bid_end_date AS bidEndDate, bid_start_time AS bidStartTime, bid_end_time AS bidEndTime, replace_end_time AS replaceEndTime, status AS status, release_time AS releaseTime, misc AS misc
		    			FROM bid_show_date AS a WHERE del_flag=0 ".$statusP." ORDER BY ".$orderParam." LIMIT ".$params['start'].", ".$params['limit']."";
				// 查询并返回参数
				$row = $this->dbRO->createCommand($sql)->queryAll();
			} else
				if (intval(chr(49)) == $params['queryFlag']) {
					// 对比单条查询
					// 初始化打包开始时间和结束时间查询条件
					$condSqlSegment .= ' and show_start_date=:showStartDate and show_end_date=:showEndDate and id!=:id';
					// 设置打包开始时间参数
					$paramsMapSegment[':showStartDate'] = $params['showStartDate'];
					// 设置打包结束参数
					$paramsMapSegment[':showEndDate'] = $params['showEndDate'];
					// 设置ID参数
					$paramsMapSegment[':id'] = $params['id'];
					// 对比批量查询
					$row = $this->dbRO->createCommand()->select($queryResult)->from($this->_tblName)->where($condSqlSegment, $paramsMapSegment)->queryAll();
				} else
					if (intval(chr(50)) == $params['queryFlag']) {
						// 多条  打包开始时间 >= 当前日期
						// 初始化打包开始时间和结束时间查询条件
						$condSqlSegment .= ' and show_start_date >=:currentTime';
						// 设置打包开始时间参数
						$paramsMapSegment[':currentTime'] = date('y-m-d',time());

						// 对比批量查询
						$row = $this->dbRO->createCommand()->select($queryResult)->from($this->_tblName)->where($condSqlSegment, $paramsMapSegment)->order('show_start_date DESC')->queryAll();
					} else if (intval(chr(51)) == $params['queryFlag']) {
						// 对比单条查询
						// 初始化打包开始时间和结束时间查询条件
						$condSqlSegment .= ' and show_start_date=:showStartDate and show_end_date=:showEndDate';
						// 设置打包开始时间参数
						$paramsMapSegment[':showStartDate'] = $params['showStartDate'];
						// 设置打包结束参数
						$paramsMapSegment[':showEndDate'] = $params['showEndDate'];
						// 对比批量查询
						$row = $this->dbRO->createCommand()->select($queryResult)->from($this->_tblName)->where($condSqlSegment, $paramsMapSegment)->queryAll();
					}
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

	/**
	 * 查询打包时间数量
	 * 
	 * @param $params
	 * @return array
	 */
	public function queryPackageCount($params) {
		// 初始化删除标记查询条件
		$condSqlSegment=' del_flag=:delFlag';
		// 设置删除标记参数
		$paramsMapSegment[':delFlag'] = intval(chr(48));
		try {
			if (intval(chr(48)) == $params['status'] || intval(chr(49)) == $params['status']) {
				$condSqlSegment.=' and status=:status';
				$paramsMapSegment[':status'] = $params['status'];
			}  
		} catch(Exception $e) {}
		try {
			// 查询数据库
			$row = $this->dbRO->createCommand()->select('count(1) as count')->from($this->_tblName)->where($condSqlSegment, $paramsMapSegment)->queryRow();
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 返回查询结果
		return $row;
	}	

	/**
	 * 插入打包时间信息
	 * 
	 * @param $params
	 * @return boolean
	 */
	public function insertPackageInfo($params, $adduid) {
		// 兼容旧版本，分类初始化竞拍开始时间点
		$bidStartTime = intval(chr(48))
		;
		if (!empty($params['bidStartTime'])) {
			$bidStartTime = $params['bidStartTime'];
		}
		
		$releaseTime = Sundry::RELEASETIME;
		try {
			if (intval(chr(49)) == $params['status']) {
				$releaseTime = date(Sundry::TIME_SY_M_D_H_I_S,time());
			}
		} catch(Exception $e) {}
		try {
			// 插入数据
			$result = $this->dbRW->createCommand()->insert($this->_tblName, array (
				'show_start_date' => $params['showStartDate'],
				'show_end_date' => $params['showEndDate'],
				'bid_start_date' => $params['bidStartDate'],
	            'bid_end_date' => $params['bidEndDate'],
	            'bid_end_time' => $params['bidEndTime'],
	            'bid_start_time' => $bidStartTime,
	            'replace_end_time' => $this->getYestodayTime($params['showStartDate']),
	            'status' => $params['status'],
	            'release_time' => $releaseTime ,
	            'add_uid' => $adduid,
	            'add_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
	            'update_uid' => $adduid,
	            'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
	            'del_flag' => intval(chr(48)),
	            'misc' => !empty($params['misc'])?strval($params['misc']):'',	
			));
			
            // 返回ID 
            return $this->dbRW->lastInsertID;
        } catch (Exception $e) {
            // 打印错误日志
            Yii :: log($e, 'warning');
            // 抛异常
            throw new Exception($e);
        }
	}

    /**
     * 查询打包计划是否已经被竞拍
     *
     * @param $params
     * @return array
     */
    public function packageIsBided($params) {
        // 初始化删除标记查询条件
        $condSqlSegment=' del_flag=:delFlag';
        // 设置删除标记参数
        $paramsMapSegment[':delFlag'] = intval(chr(48));
        if ($params['id']) {
            $condSqlSegment.=' and show_date_id=:id';
            $paramsMapSegment[':id'] = $params['id'];
        } else {
            return false;
        }
        try {
            // 查询数据库
            $row = $this->dbRO->createCommand()->select('count(1) as count')->from('bid_bid_product')->where($condSqlSegment, $paramsMapSegment)->queryRow();
        } catch (Exception $e) {
            // 打印错误日志
            Yii :: log($e, 'warning');
            // 抛异常
            throw new Exception($e);
        }
        if (intval($row['count']) > intval(chr(48))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询广告位是否已经被竞拍
     *
     * @param $params
     * @return array
     */
    public function adKeyIsBided($params) {
        // 初始化删除标记查询条件
        $condSqlSegment=' del_flag=:delFlag';
        // 设置删除标记参数
        $paramsMapSegment[':delFlag'] = intval(chr(48));
        if ($params['adKey']) {
            $condSqlSegment.=' and ad_key=:adKey';
            $paramsMapSegment[':adKey'] = $params['adKey'];
        }
        if ($params['startCityCode']) {
            $condSqlSegment.=' and start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $params['startCityCode'];
        }
        try {
            // 查询数据库
            $row = $this->dbRO->createCommand()->select('count(1) as count')->from('bid_bid_product')->where($condSqlSegment, $paramsMapSegment)->queryRow();
        } catch (Exception $e) {
            // 打印错误日志
            Yii :: log($e, 'warning');
            // 抛异常
            throw new Exception($e);
        }
        if (intval($row['count']) > intval(chr(48))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询新增的打包计划日期当前已经存在哪些位置
     *
     * @param $params
     * @return array
     */
    public function existPosition($params) {
        // 初始化删除标记查询条件
        $condSqlSegment=' a.del_flag=:delFlag';
        // 设置删除标记参数
        $paramsMapSegment[':delFlag'] = intval(chr(48));
        if ($params['showStartDate']) {
            $condSqlSegment.=' and a.show_end_date>=:showStartDate';
            $paramsMapSegment[':showStartDate'] = $params['showStartDate'];
        }
        if ($params['showEndDate']) {
            $condSqlSegment.=' and a.show_start_date<=:showEndDate';
            $paramsMapSegment[':showEndDate'] = $params['showEndDate'];
        }
        try {
            // 查询数据库
            $row = $this->dbRO->createCommand()
                ->select('DISTINCT (b.ad_key) ad_key')
                ->from('bid_show_date a')
                ->leftjoin('ba_ad_position b','a.id = b.show_date_id and b.del_flag = 0')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryAll();
        } catch (Exception $e) {
            // 打印错误日志
            Yii :: log($e, 'warning');
            // 抛异常
            throw new Exception($e);
        }
        if ($row) {
            return $row;
        } else {
            return array();
        }
    }

    /**
     * 查询打包计划日期是否已经存在
     *
     * @param $params
     * @return array
     */
    public function existShowDateInfo($params) {
        // 初始化删除标记查询条件
        $condSqlSegment=' del_flag=:delFlag';
        // 设置删除标记参数
        $paramsMapSegment[':delFlag'] = intval(chr(48));
        if ($params['showStartDate']) {
            $condSqlSegment.=' and show_end_date>=:showStartDate';
            $paramsMapSegment[':showStartDate'] = $params['showStartDate'];
        }
        if ($params['showEndDate']) {
            $condSqlSegment.=' and show_start_date<=:showEndDate';
            $paramsMapSegment[':showEndDate'] = $params['showEndDate'];
        }
        // 若为更新时则过滤本计划
        if (intval(chr(48)) === $params['updateFlag'] && $params['id']) {
            $condSqlSegment.=' and id != :id';
            $paramsMapSegment[':id'] = $params['id'];
        }
        try {
            // 查询数据库
            $row = $this->dbRO->createCommand()
                ->select('*')
                ->from('bid_show_date')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryAll();
        } catch (Exception $e) {
            // 打印错误日志
            Yii :: log($e, 'warning');
            // 抛异常
            throw new Exception($e);
        }
        if ($row) {
            return $row;
        } else {
            return array();
        }
    }

    /**
	 * 更新或删除打包时间信息
	 * 
	 * @param $params
	 * @return boolean
	 */
	public function updatePackageInfo($params, $uid) {
		
		// 初始化更新条件
		$condSqlSegment = ' id in ('.$params['id'].')';
		$releaseTime = Sundry::RELEASETIME;
		// 兼容旧版本，分类初始化竞拍开始时间点
		$bidStartTime = intval(chr(48));
		if (!empty($params['bidStartTime'])) {
			$bidStartTime = $params['bidStartTime'];
		}
		try {
			if (!empty($params['status']) && 1 == $params['status']) {
				$releaseTime = date(Sundry::TIME_SY_M_D_H_I_S,time());
			}
		} catch(Exception $e) {}
		try {
			// 判断是更新还是删除
			if (intval(chr(48)) == $params['updateFlag']) {
				// 更新
				$result = $this->dbRW->createCommand()->update($this->_tblName, array (
					'show_start_date' => $params['showStartDate'],
					'show_end_date' => $params['showEndDate'],
					'bid_start_date' => $params['bidStartDate'],
	            	'bid_end_date' => $params['bidEndDate'],
	            	'bid_end_time' => $params['bidEndTime'],
	            	'bid_start_time' => $bidStartTime,
	            	'replace_end_time' => $this->getYestodayTime($params['showStartDate']),
	            	'status' => $params['status'],
	            	'release_time' => $releaseTime,
	            	'update_uid' => $uid,
	            	'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
	            	'del_flag' => intval(chr(48)),
	            	'misc' => !empty($params['misc'])?strval($params['misc']):'',	
				), $condSqlSegment);
			} else {
				// 删除
				$result = $this->dbRW->createCommand()->update($this->_tblName, array (
					'del_flag' => 1,
					'update_uid' => $uid,
	            	'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
				), $condSqlSegment);
			}
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 判断数据是否正确更新
		if ($result) {
			// 更新正确，返回id
			return $params['id'];
		} else {
			// 更新错误，返回false
			return Symbol::CONS_FALSE;
		}

	}
	
	/**
	 * 返回昨天11点
	 * 
	 * @param $curTime
	 * @return date
	 */
	public function getYestodayTime($curTime) {
		// 返回昨天11点
		return date(Sundry::TIME_Y_M_D_H_I_S,mktime(date("s",strtotime($curTime)),date("i",strtotime($curTime)),date("H",strtotime($curTime)),date("m",strtotime($curTime)),date("d",strtotime($curTime))-1,date("Y",strtotime($curTime))));
		
	}


    /**
     * 招客宝改造-计算指定id的打包时间长度
     *
     * @author chenjinlong 20131118
     * @param $showDateId
     * @return int
     */
    public function countBidShowDays($showDateId)
    {
        $condSqlSegment = 'id=:id';
        $paramsMapSegment[':id'] = intval($showDateId);
        $value = $this->dbRO
                    ->createCommand()
                    ->select('DATEDIFF(show_end_date,show_start_date)+1 show_days_count')
                    ->from($this->_tblName)
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryScalar();
        return $value;
    }

    /**
     * 招客宝改造-查询指定呈现日期符合的打包时间ID数组
     *
     * @author chenjinlong 20131121
     * @param $specificShowDate
     * @return mixed
     */
    public function getShowDateIdArrBySpecificDate($specificShowDate)
    {
        $condSqlSegment = 'del_flag=0 and status=1 and show_start_date<=:show_date and show_end_date>=:show_date';
        $paramsMapSegment[':show_date'] = strval($specificShowDate);
        $column = $this->dbRO
            ->createCommand()
            ->select('id')
            ->from($this->_tblName)
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryColumn();
        return $column;
    }

    /**
     * 招客宝改造-根据打包日期ID，查询打包日期详情
     *
     * @author chenjinlong 20131128
     * @param $showDateId
     * @return mixed
     */
    public function getShowDateInfoById($showDateId)
    {
        $condSqlSegment = ' del_flag=0 AND id=:showDateId ';
        $paramsMapSegment[':showDateId'] = intval($showDateId);
        $row = $this->dbRW
            ->createCommand()
            ->select('*')
            ->from($this->_tblName)
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();
        if(!empty($row)){
            return $row;
        }else{
            return array();
        }
    }

    /**
     * 根据条件查询打包时间信息
     *
     * @author chenjinlong 20131203
     * @param $conditionParams
     * @return array
     */
    public function getTotalShowDateList($conditionParams)
    {
        $condSqlSegment = ' del_flag=0';
        $paramsMapSegment = array();
        if(isset($conditionParams['status'])){
            $condSqlSegment .= ' AND status=:status';
            $paramsMapSegment[':status'] = intval($conditionParams['status']);
        }
        $row = $this->dbRO
            ->createCommand()
            ->select('show_start_date showStartDate,show_end_date showEndDate,bid_start_date bidStartDate,bid_end_date bidEndDate,status,replace_end_time replaceEndTime')
            ->from($this->_tblName)
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();
        if(!empty($row)){
            return $row;
        }else{
            return array();
        }
    }
    
    /**
	 * 查询附加信息维度
	 * 
	 * @return $row
	 */
	public function queryExtWd() {
		// 初始化sql语句
		$sql = "SELECT id, position_type_id, vas_key, vas_name, ROUND(unit_floor_price) as unit_floor_price FROM ba_ad_vas_type WHERE del_flag = 0";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

    /**
     * 查询打包时间的广告位信息
     *
     * @param $param
     * @return $row
     */
    public function queryAdInfoNew($param) {
        // 初始化返回结果
        $result = array();

        try {
            // 初始化sql语句
            $sql = "SELECT
                    id,
                    show_date_id,
                    ad_key_type
                  FROM
                    ba_ad_position
                  WHERE
                    del_flag=0
                  AND
                    show_date_id IN (".$param.")
                  AND
                    ad_key_type in (" . implode(chr(44), BusinessType::$ADKEY_TYPE_ARRAY).")
                  GROUP BY
                    show_date_id,ad_key_type";

            // 查询并返回参数
            $result = $this->executeSql($sql, self::ALLO);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $result.Symbol::CONS_DOU_COLON."向数据库查询打包时间的广告位信息异常", $e);
        }

        // 返回结果
        return $result;
    }

	/**
	 * 查询已勾选的附加信息数据
	 * 
	 * @param $param
	 * @return $row
	 */
	public function queryVasInfo($param) {
		// 初始化sql语句
		$sql = "SELECT id, vas_key, vas_position, ROUND(floor_price) as floor_price, ad_position_id FROM ba_ad_vas WHERE del_flag=0 AND ad_position_id IN (".$param.")";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
	 * 查询广告详细信息
	 * 
	 * @param $param
	 * @return $row
	 */
	public function queryAdDetailInfo($param) {
		// 初始化sql语句
		$sql = "SELECT a.id, a.ad_key, a.ad_name, a.start_city_code, ROUND(a.unit_floor_price) as unit_floor_price, IFNULL(b.ad_product_count, '') AS ad_product_count, IFNULL(ROUND(b.floor_price), '') AS floor_price, IFNULL(b.show_date_id, '') AS show_date_id, IFNULL(b.coupon_use_percent, '') AS coupon_use_percent, IFNULL(b.id, -1) AS ad_position_id FROM ba_ad_position_type a LEFT JOIN ba_ad_position b ON a.ad_key=b.ad_key AND b.show_date_id=".$param." and b.del_flag = 0 where a.del_flag = 0 AND a.ad_key NOT LIKE 'index_chosen_%'";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

    /**
     * 查询所有首页广告信息
     *
     * @param $param
     * @return $row
     */
    public function queryIndexAllAdInfo($param) {
        // 初始化sql语句
        $sql = '';
        if ($param != strval(Symbol::MINUS_ONE)) {
            // 编辑和查看
            $sql = "SELECT IFNULL(ad_product_count, '') AS ad_product_count, IFNULL(ROUND(floor_price), '') AS floor_price, IFNULL(show_date_id, '') AS show_date_id, IFNULL(coupon_use_percent, '') AS coupon_use_percent, IFNULL(id, -1) AS ad_position_id FROM ba_ad_position WHERE del_flag = 0 AND show_date_id = ".$param." AND ad_key LIKE 'index_chosen_%' LIMIT 1";
        } else {
            // 新增
            $sql = "SELECT id, ad_key, ad_name, start_city_code, ROUND(unit_floor_price) AS unit_floor_price FROM ba_ad_position_type WHERE del_flag = 0 AND ad_key LIKE 'index_chosen_%' LIMIT 1";
        }
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryRow();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array ();
        }
    }

    /**
     * 查询首页老数据广告信息
     *
     * @param $param
     * @return $row
     */
    public function queryIndexAdInfo($param) {
        // 初始化sql语句
        $sql = "SELECT a.id, a.ad_key, a.ad_name, a.start_city_code, ROUND(a.unit_floor_price) as unit_floor_price, IFNULL(b.ad_product_count, '') AS ad_product_count, IFNULL(ROUND(b.floor_price), '') AS floor_price, IFNULL(b.show_date_id, '') AS show_date_id, IFNULL(b.coupon_use_percent, '') AS coupon_use_percent, IFNULL(b.id, -1) AS ad_position_id FROM ba_ad_position_type a LEFT JOIN ba_ad_position b ON a.ad_key=b.ad_key AND b.show_date_id=".$param." and b.del_flag = 0 WHERE a.ad_key = 'index_chosen'";
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryRow();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array ();
        }
    }
	
	/**
	 * 插入打包时间对应的广告位信息
	 * 
	 * @param $params
	 * @return boolean
	 */
	public function insertBaadPosition($params, $adduid, $dateId) {
		try {
			// 插入数据
			$result = $this->dbRW->createCommand()->insert('ba_ad_position', array (
				'ad_key' => $params['ad_key'],
				'ad_name' => $params['ad_name'],
                'start_city_code' => $params['start_city_code'],
				'floor_price' => $params['floor_price'],
	            'advance_day' =>  intval(chr(48)),
	            'cut_off_hour' =>  intval(chr(48)),
	            'ad_product_count' => $params['ad_product_count'],
	            'show_date_id' => $dateId,
	            'add_uid' => $adduid,
	            'add_time' => date('y-m-d H:i:s',time()),
	            'update_uid' => $adduid,
	            'update_time' => date('y-m-d H:i:s',time()),
	            'coupon_use_percent' => $params['coupon_use_percent'],
	            'type_id' => $params['type_id'],
	            'is_major' => $params['is_major'],
	            'ad_key_type' => $params['ad_key_type'],
			));
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 判断数据是否正确插入
		if ($result) {
			// 插入正确，返回插入数据的ID
			return $this->dbRW->lastInsertID;
		} else {
			// 插入错误，返回false
			return false;
		}
	}
	
	/**
	 * 插入打包时间广告位对应的附加信息
	 * 
	 * @param $params
	 * @return boolean
	 */
	public function insertBaadVas($params, $adduid, $positionId) {
		try {
			// 插入数据
			$result = $this->dbRW->createCommand()->insert('ba_ad_vas', array (
				'vas_key' => $params['vas_key'],
				'vas_position' => $params['vas_position'],
				'floor_price' => $params['floor_price'],
	            'ad_position_id' => $positionId,
	            'del_flag' => intval(chr(48)),
	            'misc' => Symbol::EMPTY_STRING,
	            'add_uid' => $adduid,
	            'add_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
	            'update_uid' => $adduid,
	            'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),	
			));
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 判断数据是否正确插入
		if ($result) {
			// 插入正确，返回true
			return true;
		} else {
			// 插入错误，返回false
			return false;
		}
	}

	/**
	 * 根据position_id或date_id删除位置表信息
	 * 
	 * @param string $param
	 * @param int $adduid
	 * @param int $flag
	 * @return boolean
	 */
	public function deleteBaadPosition($param, $adduid, $flag) {
		// 分类初始化更新条件
		if (intval(chr(48)) == $flag) {
			// 根据position_id删除位置表信息
			$condSqlSegment = ' id in ('.$param.')';
		} else {
			// 根据date_id删除位置表信息
			$condSqlSegment = ' show_date_id in ('.$param.')';
		}
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('ba_ad_position', array (
				'del_flag' => 1,
				'update_uid' => $adduid,
	        	'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
			), $condSqlSegment);
	    } catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 判断数据是否正确更新
		if ($result) {
			// 更新正确，返回true
			return true;
		} else {
			// 更新错误，返回false
			return false;
		}
	}
	
	/**
	 * 根据position_id或vas_id删除位置表信息
	 * 
	 * @param string $param
	 * @param int $adduid
	 * @param int $flag
	 * @return boolean
	 */
	public function deleteBaadVas($param, $adduid, $flag) {
		// 分类初始化更新条件
		if ( intval(chr(48)) == $flag) {
			// 根据position_id删除位置表信息
			$condSqlSegment = ' id in ('.$param.')';
		} else {
			// 根据date_id删除位置表信息
			$condSqlSegment = ' ad_position_id in ('.$param.')';
		}
		
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('ba_ad_vas', array (
				'del_flag' => intval(chr(49)),
				'update_uid' => $adduid,
	        	'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),), $condSqlSegment);
	    } catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
		// 判断数据是否正确更新
		if ($result) {
			// 更新正确，返回true
			return true;
		} else {
			// 更新错误，返回false
			return false;
		}
	}
	
	/**
	 * 更新位置表
	 * 
	 * @param $param
	 * @param $adduid
	 * @return boolean
	 */
	public function updateBaadPosition($param, $adduid) {
        // 初始化更新筛选条件
        $condSqlSegment = Symbol::EMPTY_STRING;
        $show_date_id = $param['show_date_id'];
        if (BusinessType::INDEX_CHOSEN_ALL == $param['ad_key']) {
            $condSqlSegment .= "show_date_id = $show_date_id AND ad_key LIKE '%index_chosen%'";
        } else {
            $condSqlSegment .= ' id = '.$param['ad_position_id'];
        }
		// 更新
		$result = $this->dbRW->createCommand()->update('ba_ad_position', array (
			'ad_product_count' => $param['ad_product_count'],
	       	'floor_price' => $param['floor_price'],
	       	'update_uid' => $adduid,
	        'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
	        'coupon_use_percent' => $param['coupon_use_percent'],
		), $condSqlSegment);
		// 判断数据是否正确更新
		if ($result) {
			// 更新正确，返回true
			return true;
		} else {
			// 更新错误，返回false
			return false;
		}
	}
	
	/**
	 * 发布打包时间
	 * 
	 * @param $param
	 * @param $adduid
	 * @return boolean
	 */
	public function submitPackage($param, $adduid) {
		// 初始化发布筛选条件
		$condSqlSegment = ' id = '.$param['id'];
		// 发布
		$result = $this->dbRW->createCommand()->update($this->_tblName, array (
			'status' => $param['status'],
			'update_uid' => $adduid,
	        'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
		), $condSqlSegment);
		// 判断数据是否正确发布
		if ($result) {
			// 发布正确，返回true
			return Symbol::CONS_TRUE;
		} else {
			// 发布错误，返回false
			return Symbol::CONS_FALSE;
		}
	}
	
	/**
	 * 根据date_id查询position_id
	 * 
	 * @param int $param
	 * @return array()
	 */
	public function queryPositionId($param) {
		// 初始化sql语句
		$sql = "SELECT id FROM ba_ad_position WHERE del_flag = 0 and show_date_id = ".$param;
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/** 更新位置表
	 * 
	 * @param $param
	 * @param $adduid
	 * @return boolean
	 */
	public function updateBaadVas($param, $adduid) {
		// 初始化更新筛选条件
		$condSqlSegment = ' id = '.$param['vas_id'];
		// 更新
		$result = $this->dbRW->createCommand()->update('ba_ad_vas', array (
			'vas_position' => $param['vas_position'],
			'floor_price' => $param['floor_price'],
			'update_uid' => $adduid,
	        'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
		), $condSqlSegment);
		// 判断数据是否正确更新
		if ($result) {
			// 更新正确，返回true
			return Symbol::CONS_TRUE;
		} else {
			// 更新错误，返回false
			return Symbol::CONS_FALSE;
		}
	}

    /**
     * 广告位管理列表
     *
     * @param $params
     * @return array
     */
    public function getAdManageList($params) {
        $condSqlSegment = Symbol::EMPTY_STRING;
        $paramsMapSegment = array();
        $condSqlSegment .= " del_flag=0";
        if (!empty($params['isOpen'])) {
            $condSqlSegment .= ' AND is_open = 0';
        }
        if ($params['startCityCode']) {
            $condSqlSegment .= ' AND start_city_code = :startCityCode';
            $paramsMapSegment[':startCityCode'] = $params['startCityCode'];
        }
        if ($params['adKey']) {
            $condSqlSegment .= ' AND ad_key = :adKey';
            $paramsMapSegment[':adKey'] = $params['adKey'];
        }
        if ($params['adName']) {
            $condSqlSegment .= ' AND ad_name LIKE :adName';
            $paramsMapSegment[':adName'] = '%' .$params['adName'] . '%';
        }
        // 区分广告位类型
        if ($params['adKeyType']) {
            $condSqlSegment .= ' AND ad_key LIKE :adKey';
            $paramsMapSegment[':adKey'] = $params['adKeyType'] . '%';
        }
        $count = $this->dbRO->createCommand()
            ->select('count(1) as count')
            ->from('ba_ad_position_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryScalar();
        $rows = $this->dbRO->createCommand()
            ->select('id, ad_key adKey, ad_name adName, start_city_code startCityCode, category_id categoryId, cat_type catType, class_brand_types classBrandTypes, add_time addTime, is_open isOpen')
            ->from('ba_ad_position_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->order('id DESC,add_time DESC')
            ->limit(($params['limit']) ? $params['limit'] : Symbol::TEN, ($params['start']) ? $params['start'] :  intval(chr(48)))
            ->queryAll();
        if ($count['count']) {
            return $result = array('count' => $count, 'rows' => $rows);
        } else {
            return array();
        }
    }

    /**
     * 广告位是否存在
     *
     * @param $params
     * @return array
     */
    public function getAdIsExist($params) {
        $condSqlSegment = Symbol::EMPTY_STRING;
        $paramsMapSegment = array();
        $condSqlSegment .= " del_flag=0";
        if ($params['adKeyArr'] && is_array($params['adKeyArr']) && $params['startCityCode']) {
            $adKeyArr = '"'.trim(implode('","', $params['adKeyArr'])).'"';
            $condSqlSegment .= ' AND start_city_code = '.$params['startCityCode'].' AND ad_key IN ('.$adKeyArr.')';
        } else {
            return array();
        }
        $result = $this->dbRO->createCommand()
            ->select('ad_key adKey, ad_name adName, start_city_code startCityCode')
            ->from('ba_ad_position_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryAll();
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * 根据广告位名称+预订城市，删除广告位
     *
     * @author chenjinlong 20140515
     * @param $params
     * @return array
     */
    public function postAdDelByName($params) {
        $condSqlSegment = " start_city_code = :start_city_code AND ad_name = :adName";
        $paramsMapSegment = array(':start_city_code' => $params['startCityCode'], ':adName' => $params['adName']);
        $data = $this->getAdManageList($params);
        if ($data) {
            $result = $this->dbRW->createCommand()->update('ba_ad_position_type', array(
                'del_flag' => 1,
                'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
                'misc' => strval($params['misc']),
            ), $condSqlSegment, $paramsMapSegment);
            // 更新成功返回true，否则返回false
            if ($result) {
                return Symbol::CONS_TRUE;
            } else {
                return Symbol::CONS_FALSE;
            }
        } else {
            return Symbol::CONS_FALSE;
        }
    }

    /**
     * 按adKey删除广告位类型
     *
     * @author chenjinlong 20140609
     * @param $params
     * @return bool
     */
    //回滚函数FROM Version 9693
    public function postAdDel($params) {
        $condSqlSegment = " start_city_code = :startCityCode AND ad_key = :adKey";
        $paramsMapSegment = array(':startCityCode' => $params['startCityCode'], ':adKey' => $params['adKey']);
        $data = $this->getAdManageList($params);
        if ($data) {
            $result = $this->dbRW->createCommand()->update('ba_ad_position_type', array(
                'del_flag' => intval(chr(49)),
                'update_time' => date('y-m-d H:i:s',time()),
                'misc' => strval($params['misc']),
            ), $condSqlSegment, $paramsMapSegment);
            // 更新成功返回true，否则返回false
            if ($result) {
                return Symbol::CONS_TRUE;
            } else {
                return Symbol::CONS_FALSE;
            }
        } else {
            return Symbol::CONS_FALSE;
        }
    }

    /**
     * 添加广告位
     *
     * @param $params
     * @return array
     */
    public function postAdAdd($params) {
        $this->dbRW->createCommand()
            ->insert('ba_ad_position_type', array(
                'ad_key' => $params['adKey'],
                'ad_name' => $params['adName'],
                'start_city_code' => $params['startCityCode'],
                'category_id' => $params['categoryId'],
                'class_brand_types' => $params['classBrandTypes'],
                'cat_type' => $params['catType'],
                'add_uid' => $params['addUid'],
                'add_time' => $params['addTime'],
                'update_uid' => $params['addUid'],
                'update_time' => $params['addTime'],
                'unit_floor_price' => intval(chr(48)),
                'del_flag' => intval(chr(48)),
                'misc' => strval($params['misc'])
            ));
        $tblLastID = $this->dbRW->lastInsertID;
        if(!empty($tblLastID)){
            return $tblLastID;
        }else{
            return Symbol::CONS_FALSE;
        }
    }

    /**
     * 添加广告位-new
     *
     * @param $params
     * @return array
     */
    public function postAdAddNew($params) {
        $this->dbRW->createCommand()
            ->insert('ba_ad_position_type', array(
                'ad_key' => $params['adKey'],
                'ad_name' => $params['adName'],
                'start_city_code' => $params['startCityCode'],
                'category_id' => $params['categoryId'],
                'class_brand_types' => $params['classBrandTypes'],
                'cat_type' => $params['catType'],
                'add_uid' => $params['addUid'],
                'add_time' => $params['addTime'],
                'update_uid' => $params['addUid'],
                'update_time' => $params['addTime'],
                'unit_floor_price' => intval(chr(48)),
                'del_flag' => intval(chr(48)),
                'misc' => $params['misc'] ? strval($params['misc']) : '',
                'is_open' => intval(chr(48)),
                'ad_key_type' => $params['adKeyType'] ? $params['adKeyType'] : intval(chr(48)),
                'channel_id' => $params['channelId'] ? $params['channelId'] : intval(chr(48)),
                'channel_name' => $params['channelName'] ? $params['channelName'] : '',
                'block_id' => $params['blockId'] ? $params['blockId'] : intval(chr(48)),
                'block_name' => $params['blockName'] ? $params['blockName'] : '',
                'is_minor'=> $params['isMajor'] ? $params['isMajor'] : intval(chr(48))
            ));
        $tblLastID = $this->dbRW->lastInsertID;
        if(!empty($tblLastID)){
            return $tblLastID;
        }else{
            return Symbol::CONS_FALSE;
        }
    }

    /**
     * 根据预订城市+adKey，更新广告位配置信息
     *
     * @author chenjinlong 20140519
     * @param $updateParams
     * @param $conditionParams
     */
    public function updateAdPositionType($updateParams, $conditionParams)
    {
        $condSqlSegment = ' del_flag=:del_flag AND start_city_code=:start_city_code AND ad_key=:ad_key';
        $paramsMapSegment = array(
            ':del_flag' => intval(chr(48)),
            ':start_city_code' => intval($conditionParams['start_city_code']),
            ':ad_key' => strval($conditionParams['ad_key']),
        );

        $result = $this->dbRW->createCommand()->update(
            'ba_ad_position_type',
            array(
                'ad_name' => strval($updateParams['ad_name']),
                'category_id' => strval($updateParams['category_ids']),
                'class_brand_types' => strval($updateParams['class_brand_types']),
                'cat_type' => strval($updateParams['cat_types']),
                'update_uid' => intval($updateParams['uid']),
                'update_time' => date('y-m-d H:i:s'),
                'misc' => strval($updateParams['misc']),
                'ad_key_type' => $updateParams['adKeyType'] ? $updateParams['adKeyType'] : intval(chr(48)),
                'is_minor'=> $updateParams['isMajor'] ? $updateParams['isMajor'] : intval(chr(48))
            ), $condSqlSegment, $paramsMapSegment);
    }

    /**
     * 更新某个广告位配置信息
     *
     * @param $updateParams
     * @param $conditionParams
     */
    public function updateOneAdPositionType($updateParams, $conditionParams)
    {
        $condSqlSegment = ' del_flag=:del_flag AND start_city_code=:start_city_code AND ad_key=:ad_key';
        $paramsMapSegment = array(
            ':del_flag' => intval(chr(48)),
            ':start_city_code' => intval($conditionParams['start_city_code']),
            ':ad_key' => strval($conditionParams['ad_key']),
        );

        $result = $this->dbRW->createCommand()->update(
            'ba_ad_position_type',
            array(
                'ad_name' => strval($updateParams['ad_name']),
                'category_id' => strval($updateParams['category_ids']),
                'class_brand_types' => strval($updateParams['class_brand_types']),
                'cat_type' => strval($updateParams['cat_types']),
                'update_uid' => intval($updateParams['uid']),
                'update_time' => date(Sundry::TIME_SY_M_D_H_I_S),
                'misc' => strval($updateParams['misc']),
                'channel_id' => $updateParams['channelId'] ? $updateParams['channelId'] : intval(chr(48)),
                'channel_name' => $updateParams['channelName'] ? $updateParams['channelName'] : '',
                'block_id' => $updateParams['blockId'] ? $updateParams['blockId'] : intval(chr(48)),
                'block_name' => $updateParams['blockName'] ? $updateParams['blockName'] : '',
                'ad_key_type' => $updateParams['adKeyType'] ? $updateParams['adKeyType'] : intval(chr(48)),
                'is_minor'=> $updateParams['isMajor'] ? $updateParams['isMajor'] : intval(chr(48))
            ), $condSqlSegment, $paramsMapSegment);
    }

    /**
     * 查询广告位产品种类
     *
     * @param $params
     * @return array
     */
    public function getAdCategory($params) {
        if (!$params['adKey'] && !$params['startCityCode']) {
            return array();
        }
        $condSqlSegment = ' del_flag=0 AND ad_key = :adKey AND start_city_code = :startCityCode';
        $paramsMapSegment[':adKey'] = $params['adKey'];
        $paramsMapSegment[':startCityCode'] = $params['startCityCode'];
        $result = $this->dbRO->createCommand()
            ->select('ad_key adKey, ad_name adName, start_city_code startCityCode, category_id categoryId, class_brand_types classBrandTypes,cat_type catType')
            ->from('ba_ad_position_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * 查询所有首页的广告位
     *
     * @param $params
     * @return array
     */
    public function getIndexAd() {
        $result = $this->dbRO->createCommand()
            ->select('id, ad_key adKey, ad_name adName, start_city_code startCityCode, is_minor isMajor')
            ->from('ba_ad_position_type')
            ->where("del_flag = 0 AND is_open = 0 AND ad_key_type = 1")
            ->queryAll();
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * 根据条件查询广告位配置信息
     *
     * @author chenjinlong 20140519
     * @param $conditions
     * @return array
     */
    public function getAdPositionTypeListByCond($conditions)
    {
        // 只查询首页广告位
        $condSqlSegment = ' ad_key LIKE "index_chosen%"';
        $paramsMapSegment = array();
        if(isset($conditions['del_flag'])){
            $condSqlSegment .= ' AND del_flag=:del_flag';
            $paramsMapSegment[':del_flag'] = intval($conditions['del_flag']);
        }else{
            $condSqlSegment .= ' AND del_flag=:del_flag';
            $paramsMapSegment[':del_flag'] = intval(chr(48));
        }
        if($conditions['start_city_code'] > intval(chr(48))){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = intval($conditions['start_city_code']);
        }
        if(!empty($conditions['ad_key'])){
            $condSqlSegment .= ' AND ad_key=:ad_key';
            $paramsMapSegment[':ad_key'] = strval($conditions['ad_key']);
        }
        $resultRows = $this->dbRO->createCommand()
            ->select('id,ad_key,ad_name,start_city_code')
            ->from('ba_ad_position_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryAll();
        if (!empty($resultRows)) {
            return $resultRows;
        } else {
            return array();
        }
    }
    
    /**
	 * 查询可添加和编辑包场的运营计划
	 */
	public function queryBuyoutDate($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 初始化竞价日期查询SQL
			$sql = "SELECT CONCAT(show_start_date, ' ~ ', show_end_date) AS showDate, id AS showDateId FROM bid_show_date WHERE del_flag = 0 AND STATUS = 1 AND '".date(Sundry::TIME_Y_M_D_H)."' < CONCAT(bid_start_date, ' ', IF(bid_start_time < 10, CONCAT('0', bid_start_time), bid_start_time))";
			// 查询竞价日期
    		$row = $this->dbRW->createCommand($sql)->queryAll();
			
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		
		// 返回数据
		return $row;
	}
	
	/**
     * 获取首页所有位置的配置信息
     * 
     * @author wenrui 2014-06-05
     */
	public function getIndexAdList($param){
		$condition = Symbol::EMPTY_STRING;
		if(!empty($param['showDateId'])){
			$showDateId = $param['showDateId'];
			$condition .= " AND show_date_id = '".$showDateId."'";
		}
		if(!empty($param['startCityCode'])){
			$startCityCode = $param['startCityCode'];
			$condition .= " AND start_city_code = ".$startCityCode;
		}
		if(!empty($param['adName'])){
			$adName = $param['adName'];
			$condition .= " AND ad_name LIKE '%".$adName."%'";
		}
		$start = empty($param['start']) ? intval(chr(48)) : $param['start'];
		$limit = empty($param['limit']) ? Symbol::TEN : $param['limit'];
		$limitCondition = " limit ".$start.",".$limit;
		$sql = "SELECT id,ad_key AS adKey,ad_name AS adName,start_city_code AS startCityCode,floor_price AS floorPrice,
					   ad_product_count AS adProductCount,show_date_id AS showDateId,coupon_use_percent AS couponUsePercent
				FROM ba_ad_position
				WHERE del_flag = 0 AND ad_key LIKE 'index_chosen%'".$condition.$limitCondition;
		$countSql = "SELECT count(1) AS count
				FROM ba_ad_position
				WHERE del_flag = 0 AND ad_key LIKE 'index_chosen%'".$condition;
		try{
			$row = $this->dbRW->createCommand($sql)->queryAll();
			$count = $this->dbRW->createCommand($countSql)->queryRow();
			return array('count'=>$count['count'],'rows'=>$row);
		}catch(Exception $e){
			return array();
		}
		return $row;
	}
	
	/**
     * 添加多个广告位的运营计划new
     * 
     * @author wenrui 2014-06-05
     */
	public function addPakDtList($param){
		$condition = Symbol::EMPTY_STRING;
		$floorPrice = empty($param['floorPrice']) ? intval(chr(48)) : $param['floorPrice'];
		$adProductCount = empty($param['adProductCount']) ? intval(chr(48)) : $param['adProductCount'];
		$couponUsePercent = empty($param['couponUsePercent']) ? intval(chr(48)) : $param['couponUsePercent'];
		$uid = empty($param['uid']) ? '' : $param['uid'];
		foreach($param['ids'] as $id) {
			$paramIdArr = $paramIdArr.$id.',';
		}
		$paramIdArr = substr($paramIdArr, intval(chr(48)), strlen($paramIdArr) - intval(chr(49)));
		$condition = " id IN (".$paramIdArr.")";
		try{
			$result = $this->dbRW->createCommand()->update('ba_ad_position', array (
				'floor_price' => $floorPrice,
				'ad_product_count' => $adProductCount,
				'coupon_use_percent' => $couponUsePercent,
				'update_uid' => $uid,
				'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
			), $condition);
		}catch(Exception $e){
			return Symbol::CONS_FALSE;
		}
		return Symbol::CONS_TRUE;
	}
	
	/**
     * 更新广告位配置信息
     * 
     * @author wenrui 2014-06-06
     */
	public function updateAdPosition($param){
        $showDateId = $param['showDateId'];
        $condSqlSegment = " show_date_id = ".$showDateId;
        if (BusinessType::INDEX_CHOSEN_ALL == $param['adKey']) {
            $condSqlSegment .= " AND ad_key LIKE 'index_chosen%'";
        } else {
            $adKey = $param['adKey'];
            $condSqlSegment .= " AND ad_key = '".$adKey."'";
        }
		// 更新
		$result = $this->dbRW->createCommand()->update('ba_ad_position', array (
			'ad_product_count' => $param['adProductCount'],
	       	'floor_price' => $param['floorPrice'],
	       	'update_uid' => $param['uid'],
	        'update_time' => date(Sundry::TIME_SY_M_D_H_I_S,time()),
	        'coupon_use_percent' => $param['couponUsePercent'],
		), $condSqlSegment);
		// 判断数据是否正确更新
		if ($result) {
			// 更新正确，返回true
			return Symbol::CONS_TRUE;
		} else {
			// 更新错误，返回false
			return Symbol::CONS_FALSE;
		}
	}

    /**
     * 查询广告位置信息
     *
     * @param $param
     * @return $row
     */
    public function getAdPositionInfo($adKey,$showDateId) {
        if (BusinessType::INDEX_CHOSEN_ALL == $adKey) {
            // 初始化首页sql语句
            $sql = "SELECT id, 'index_chosen_all' AS 'ad_key', start_city_code, IFNULL(ad_product_count, '') AS ad_product_count, IFNULL(floor_price, '') AS floor_price, IFNULL(show_date_id, '') AS show_date_id, IFNULL(coupon_use_percent, '') AS coupon_use_percent FROM ba_ad_position WHERE del_flag = 0 AND show_date_id = '$showDateId' AND ad_key LIKE 'index_chosen_%' LIMIT 1";
        } else {
            // 初始化sql语句
            $sql = "SELECT id, ad_key, start_city_code, IFNULL(ad_product_count, '') AS ad_product_count, IFNULL(floor_price, '') AS floor_price, IFNULL(show_date_id, '') AS show_date_id, IFNULL(coupon_use_percent, '') AS coupon_use_percent FROM ba_ad_position WHERE del_flag = 0 AND show_date_id = '$showDateId' AND ad_key = '$adKey'";
        }
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryRow();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array ();
        }
    }
    
    /**
     * 复制广告位数据
     *
     * @param $param
     * @return $row
     */
    public function copyPositionData($param, $dateId, $uid) {
    	try {
			// 初始化首页sql语句
	        $sql = "INSERT INTO buckbeek.ba_ad_position 
						(ad_key, ad_name, start_city_code, floor_price, advance_day, cut_off_hour, ad_product_count, show_date_id, add_uid, add_time, update_uid, update_time, del_flag, misc, coupon_use_percent, is_major, type_id)
							SELECT 	ad_key, 
							ad_name, 
							start_city_code, 
							floor_price, 
							advance_day, 
							cut_off_hour, 
							ad_product_count, 
							".$dateId.", 
							".$uid.", 
							'".date(Sundry::TIME_Y_M_D_H_I_S)."', 
							".$uid.", 
							'".date(Sundry::TIME_Y_M_D_H_I_S)."', 
							0, 
							'', 
							coupon_use_percent, 
							is_major, 
							type_id
							FROM 
							ba_ad_position 
							WHERE
							show_date_id = ".$param['copyId']."
							AND
							del_flag = 0";
	        // 执行SQL
	        $this->dbRW->createCommand($sql)->execute();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
    }
    
    /**
     * 打开关闭广告位
     *
     * @param $param
     * @return $row
     */
    public function updatePackOpenStatus($param) {
    	try {
			// 初始化sql语句
	        $sql = "UPDATE ba_ad_position_type SET is_open = ".$param['isOpen']." WHERE id = ".$param['id'];
	        // 执行SQL
	        $this->dbRW->createCommand($sql)->execute();
	        // 初始化sql语句
	        $sql = "UPDATE ba_ad_position SET is_open = ".$param['isOpen']." WHERE start_city_code = ".$param['startCityCode']." AND ad_key = '".$param['adKey']."' AND del_flag = 0";
	        // 执行SQL
	        $this->dbRW->createCommand($sql)->execute();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
    }
    
    /**
	 * 插入打包时间对应的广告位信息
	 * 
	 * @param $params
	 * @return boolean
	 */
	public function insertIndexPosition($params) {
		try {
			// 初始化SQL
			$sql = "INSERT INTO 
						ba_ad_position 
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
						is_open, 
						ad_key_type
						)
					SELECT 	
						ad_key, 
						ad_name, 
						start_city_code, 
						".$params['floorPrice'].",
						".$params['adProductCount'].",
						".$params['showDateId'].",
						".$params['uid'].", 
						'".date('Y-m-d H:i:s')."', 
						".$params['uid'].",
						'".date('Y-m-d H:i:s')."',  
						".$params['couponUsePercent'].",
						is_minor, 
						id, 
						is_open, 
						ad_key_type
					FROM 
						ba_ad_position_type 
					WHERE
						del_flag = 0 
					AND 
						is_open = 0 
					AND 
						ad_key_type = 1"; 
			// 插入数据
			$this->executeSql($sql, self::SROW);
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
    
}
?>
