<?php

Yii::import('application.dal.dao.DaoModule');
//Yii::import('application.modules.bidmanage.dal.dao.fmis.StatementDao');
//Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');

class PackageplanDao extends DaoModule {

	/**
	 * 查询供应商信息
	 */
	public function queryAgencyInfo($param) {
		try {
			// 初始化SQL
			$sql = "SELECT id AS accountId, vendor_id AS agencyId, brand_name AS agencyName FROM bb_account WHERE vendor_id = ".$param." AND state = 0 AND del_flag = 0";
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询供应商账户
	 */
	public function queryAgencyId($param) {
		try {
			// 初始化SQL
			$sql = "SELECT vendor_id AS agencyId FROM bb_account WHERE id = ".$param." AND state = 0 AND del_flag = 0";
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 根据打包ID查询供应商账户
	 */
	public function queryAccountIdByPackId($param) {
		try {
			// 初始化SQL
			$sql = "SELECT account_id AS accountId FROM pack_plan_basic WHERE id = ".$param['packPlanId'];
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}

	/**
	 * 查询供应商已消费金额
	 */
	public function queryAgencyConsumption($param) {
		try {
			// 初始化SQL
			$sql = "SELECT IFNULL(SUM(plan_price), 0) AS consumption FROM pack_plan_basic WHERE account_id = ".$param." AND del_flag = 0 AND fmis_mark = 1 AND STATUS = 1";
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询打包计划总列表
	 */
	public function queryPackPlanList($param) {
		try {
			// 初始化分页动态SQL
			$pageSql = "";
			if ((!empty($param['start']) || intval(chr(48)) == $param['start']) && !empty($param['limit'])) {
				$pageSql = "limit ".$param['start'].",".$param['limit'];
			}
			// 初始化供应商信息动态SQL
			$agencyInfoSql = Symbol::EMPTY_STRING;
			$agencyAccountSql = Symbol::EMPTY_STRING;
			if (!empty($param['isHagrid'])) {
				$agencyInfoSql = ", c.brand_name AS agencyName, c.vendor_id AS agencyId ";
				$agencyAccountSql = "LEFT JOIN " .
									 "bb_account AS c " .
								 "ON " .
									 "a.account_id = c.id ";
			}
			
			// 初始化动态SQL查询条件
			$whereSql = Symbol::EMPTY_STRING;
			// 产品经理ID
			if (!empty($param['managerId'])) {
				$whereSql = $whereSql." AND " .
										  "a.manager_id =".intval($param['managerId']);
			}
			// 打包计划名称
			if (!empty($param['packPlanName'])) {
				$whereSql = $whereSql." AND " .
										  "a.plan_name like '%".strval($param['packPlanName'])."%'";
			}
			// 推广开始时间，结束时间
			if (!empty($param['startAddDate']) && empty($param['endAddDate'])) {
				// 有开始时间，没有结束时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') >= '".strval($param['startAddDate'])."'";
			} else if (empty($param['startAddDate']) && !empty($param['endAddDate'])) {
				// 有结束时间，没有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') <= '".strval($param['endAddDate'])."'";
			} else if (!empty($param['startAddDate']) && !empty($param['endAddDate'])) {
				// 有结束时间，有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') >= '".strval($param['startAddDate'])."'" .
									  " AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') <= '".strval($param['endAddDate'])."'";
			}
			// 发布开始时间，结束时间
			if (!empty($param['releaseStartDate']) && empty($param['releaseEndDate'])) {
				// 有开始时间，没有结束时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') >= '".strval($param['releaseStartDate'])."'";
			} else if (empty($param['releaseStartDate']) && !empty($param['releaseEndDate'])) {
				// 有结束时间，没有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') <= '".strval($param['releaseEndDate'])."'";
			} else if (!empty($param['releaseStartDate']) && !empty($param['releaseEndDate'])) {
				// 有结束时间，有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') >= '".strval($param['releaseStartDate'])."'" .
									  " AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') <= '".strval($param['releaseEndDate'])."'";
			}
			
			// 供应商账户
			if (!empty($param['accountId'])) {
				$whereSql = $whereSql." AND " .
										  "a.account_id = ".intval($param['accountId']);
			}
			// 供应商ID
			if (!empty($param['isHagrid']) && !empty($param['agencyId'])) {
				$whereSql = $whereSql." AND " .
										  "c.vendor_id = ".intval($param['agencyId']) .
									  " AND " .
										  "c.del_flag = 0";
			}
			// 打包状态
//			if (empty($param['isHagrid'])) {
//				// BB用
//				$whereSql = $whereSql." AND " .
//										  "a.status in (1,2) ";
//			} else 
			if (intval(chr(48)) == $param['packStatus']) {
				// 未推广
				$whereSql = $whereSql." AND " .
										  "a.status = ".intval($param['packStatus']);
			} else if (intval(chr(50)) == $param['packStatus']) {
				// 推广中
				$whereSql = $whereSql." AND " .
										  "a.status = 1 ";
			} else if (intval(chr(51)) == $param['packStatus']) {
				// 推广结束
				$whereSql = $whereSql." AND " .
										  "a.status = 2 ";
			}
			
			// 是否供应商发布
			if (!empty($param['isAgencySubmit'])) {
				// 1 是 2否
				$whereSql = $whereSql." AND " .
										  "a.is_agency_submit = ".$param['isAgencySubmit']." ";
			}
			
			// 初始化SQL
			$sql = "SELECT  " .
						"a.id AS packPlanId, a.plan_name AS packPlanName, a.account_id AS accountId, a.is_agency_submit AS isAgencySubmit, " .
						"a.manager_id AS managerId, DATE_FORMAT(a.end_date, '%Y-%m-%d') AS endDate, DATE_FORMAT(a.add_time, '%Y-%m-%d') AS addDate, DATE_FORMAT(a.release_time, '%Y-%m-%d') AS releaseDate," .
						"a.plan_price AS planPrice, a.status AS planStatus, IFNULL(GROUP_CONCAT(b.product_id), '')  AS productArr " .
						$agencyInfoSql .
					"FROM " .
						"pack_plan_basic AS a " .
					"LEFT JOIN " .
						"pack_plan_product AS b " .
					"ON " .
						"a.id = b.pack_plan_id " .
					"AND " .
						"b.del_flag = 0 " .
					$agencyAccountSql .
					"WHERE " .
						"a.del_flag = 0 " .
					"AND " .
						"a.id != -1 " .
					$whereSql .
					" GROUP BY a.id " .
					"ORDER BY a.id DESC " .
					$pageSql;
			// 查询列表信息
			return $this->dbRO->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询打包计划总列表数量
	 */
	public function queryPackPlanCount($param) {
		try {
			// 初始化供应商信息动态SQL
			$agencyAccountSql = Symbol::EMPTY_STRING;
			if (!empty($param['isHagrid'])) {
				$agencyAccountSql = "LEFT JOIN " .
									 "bb_account AS c " .
								 "ON " .
									 "a.account_id = c.id ";
			}
			
			// 初始化动态SQL查询条件
			$whereSql = Symbol::EMPTY_STRING;
			// 产品经理ID
			if (!empty($param['managerId'])) {
				$whereSql = $whereSql." AND " .
										  "a.manager_id =".intval($param['managerId']);
			}
			// 打包计划名称
			if (!empty($param['packPlanName'])) {
				$whereSql = $whereSql." AND " .
										  "a.plan_name like '%".strval($param['packPlanName'])."%'";
			}
			// 开始时间，结束时间
			if (!empty($param['startAddDate']) && empty($param['endAddDate'])) {
				// 有开始时间，没有结束时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') >= '".strval($param['startAddDate'])."'";
			} else if (empty($param['startAddDate']) && !empty($param['endAddDate'])) {
				// 有结束时间，没有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') <= '".strval($param['endAddDate'])."'";
			} else if (!empty($param['startAddDate']) && !empty($param['endAddDate'])) {
				// 有结束时间，有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') >= '".strval($param['startAddDate'])."'" .
									  " AND " .
										  "DATE_FORMAT(a.add_time, '%Y-%m-%d') <= '".strval($param['endAddDate'])."'";
			}
			// 发布开始时间，结束时间
			if (!empty($param['releaseStartDate']) && empty($param['releaseEndDate'])) {
				// 有开始时间，没有结束时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') >= '".strval($param['releaseStartDate'])."'";
			} else if (empty($param['releaseStartDate']) && !empty($param['releaseEndDate'])) {
				// 有结束时间，没有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') <= '".strval($param['releaseEndDate'])."'";
			} else if (!empty($param['releaseStartDate']) && !empty($param['releaseEndDate'])) {
				// 有结束时间，有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') >= '".strval($param['releaseStartDate'])."'" .
									  " AND " .
										  "DATE_FORMAT(a.release_time, '%Y-%m-%d') <= '".strval($param['releaseEndDate'])."'";
			}
			// 供应商账户
			if (!empty($param['accountId'])) {
				$whereSql = $whereSql." AND " .
										  "a.account_id = ".intval($param['accountId']);
			}
			// 供应商ID
			if (!empty($param['isHagrid']) && !empty($param['agencyId'])) {
				$whereSql = $whereSql." AND " .
										  "c.vendor_id = ".intval($param['agencyId']) .
									  " AND " .
										  "c.del_flag = 0";
			}
			// 打包状态
//			if (empty($param['isHagrid'])) {
//				// BB用
//				$whereSql = $whereSql." AND " .
//										  "a.status in (1,2) ";
//			} else 
			if (intval(chr(48)) == $param['packStatus']) {
				// 未推广
				$whereSql = $whereSql." AND " .
										  "a.status = ".intval($param['packStatus']);
			} else if (intval(chr(50)) == $param['packStatus']) {
				// 推广中
				$whereSql = $whereSql." AND " .
										  "a.status = 1 ";
			} else if (intval(chr(51)) == $param['packStatus']) {
				// 推广结束
				$whereSql = $whereSql." AND " .
										  "a.status = 2 ";
			}
			
			// 是否供应商发布
			if (!empty($param['isAgencySubmit'])) {
				// 1 是 2否
				$whereSql = $whereSql." AND " .
										  "a.is_agency_submit = ".$param['isAgencySubmit']." ";
			}
			
			// 初始化SQL
			$sql = "SELECT COUNT(0) as countRe FROM (SELECT " .
						"DISTINCT a.id " .
					"FROM " .
						"pack_plan_basic AS a " .
					"LEFT JOIN " .
						"pack_plan_product AS b " .
					"ON " .
						"a.id = b.pack_plan_id " .
					"AND " .
						"b.del_flag = 0 " .
					$agencyAccountSql .
					"WHERE " .
						"a.del_flag = 0 " .
					"AND " .
						"a.id != -1 " .
					$whereSql .
					" GROUP BY a.id ) t";
			// 查询数量
			return $this->dbRO->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询打包计划产品详情列表
	 */
	public function queryPlanProductDetail($param) {
		try {
			// 初始化分页动态SQL
			$pageSql = Symbol::EMPTY_STRING;
			if ((!empty($param['start']) || intval(chr(48)) == $param['start']) && !empty($param['limit'])) {
				$pageSql = "limit ".$param['start'].",".$param['limit'];
			}
			// 初始化SQL
			$sql = "SELECT " .
						"DISTINCT a.product_id AS productId, a.product_type AS productType, a.start_city_code AS startCityCode, " .
						"IFNULL(b.name, '') AS startCityName, IFNULL(c.product_name, '') AS productName " .
					"FROM " .
						"pack_plan_product AS a " .
					"LEFT JOIN " .
						"departure AS b " .
					"ON " .
						"a.start_city_code = b.code " .
					"AND " .
						"b.mark = 0 " .
					"LEFT JOIN " .
						"bid_product AS c " .
					"ON " .
						"a.product_id = c.product_id " .
					"AND " .
						"c.del_flag = 0 " .
					"WHERE " .
						"a.pack_plan_id = " .$param['packPlanId'].
					" AND " .
						"a.del_flag = 0 " .
					"AND " .
						"a.id != 0 " .
					"ORDER BY " .
						"a.update_time DESC " .
					$pageSql;
			// 查询数量
			return $this->dbRO->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询打包计划产品详情数量
	 */
	public function queryPlanProductDetailCount($param) {
		try {
			// 初始化SQL
			$sql = "SELECT count(DISTINCT a.product_id) as countRe " .
					"FROM " .
						"pack_plan_product AS a " .
					"LEFT JOIN " .
						"departure AS b " .
					"ON " .
						"a.start_city_code = b.code " .
					"AND " .
						"b.mark = 0 " .
					"LEFT JOIN " .
						"bid_product AS c " .
					"ON " .
						"a.product_id = c.product_id " .
					"AND " .
						"c.del_flag = 0 " .
					"WHERE " .
						"a.pack_plan_id = " .$param['packPlanId'].
					" AND " .
						"a.del_flag = 0 " .
					"AND " .
						"a.id != 0";
			// 查询数量
			return $this->dbRO->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}

	/**
	 * 查询推广列表
	 */
	public function queryPackPlanSpreadList($param) {
		try {
			// 初始化分页动态SQL
			$pageSql = Symbol::EMPTY_STRING;
			if ((!empty($param['start']) || intval(chr(48)) == $param['start']) && !empty($param['limit'])) {
				$pageSql = " limit ".$param['start'].",".$param['limit'];
			}
			// 初始化动态SQL查询条件
			$whereSql = Symbol::EMPTY_STRING;
			// 出发城市编码
			if (!empty($param['startCityCode'])) {
				$whereSql = $whereSql." AND " .
										  "a.start_city_code =".intval($param['startCityCode']);
			}
			// 产品ID
			if (!empty($param['productId'])) {
				$whereSql = $whereSql." AND " .
										  "a.product_id =".intval($param['productId']);
			}
			// 账户ID
			if (!empty($param['accountId'])) {
				$whereSql = $whereSql." AND " .
										  "a.account_id =".intval($param['accountId']);
			}
			// 推广状态
			if (!empty($param['packState']) && 1 == $param['packState']) {
				// 推广中
				$whereSql = $whereSql." AND " .
										  "a.status = 1 ";
			} else if (!empty($param['packState']) && intval(chr(50)) == $param['packState']) {
				// 推广结束
				$whereSql = $whereSql." AND " .
										  "a.status = 0 ";
			}
			// 开始时间，结束时间
			if (!empty($param['showStartDate']) && empty($param['showEndDate'])) {
				// 有开始时间，没有结束时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.show_start_date, '%Y-%m-%d') >= '".strval($param['showStartDate'])."'";
			} else if (empty($param['showStartDate']) && !empty($param['showEndDate'])) {
				// 有结束时间，没有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.show_end_date, '%Y-%m-%d') <= '".strval($param['showEndDate'])."'";
			} else if (!empty($param['showStartDate']) && !empty($param['showEndDate'])) {
				// 有结束时间，有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.show_start_date, '%Y-%m-%d') >= '".strval($param['showStartDate'])."'" .
									  " AND " .
										  "DATE_FORMAT(a.show_end_date, '%Y-%m-%d') <= '".strval($param['showEndDate'])."'";
			}
			
			// 初始化SQL
			$sql = "SELECT " .
						"a.pack_plan_id AS packPlanId, a.product_type AS productType, a.product_id AS productId, a.start_city_code AS startCityCode, " .
						"DATE_FORMAT(a.show_start_date, '%Y-%m-%d') AS showStartDate, DATE_FORMAT(a.show_end_date, '%Y-%m-%d') AS showEndDate, " .
						"a.web_class AS webClass, IFNULL(b.name, '') AS startCityName, IFNULL(c.product_name, '') AS productName, " .
						" e.plan_name AS packPlanName " .
					"FROM " .
						"pack_show_product AS a " .
					"LEFT JOIN " .
						"departure AS b " .
					"ON " .
						"a.start_city_code = b.code " .
					"AND " .
						"b.mark = 0 " .
					"LEFT JOIN " .
						"bid_product AS c " .
					"ON " .
						"a.product_id = c.product_id " .
					"AND " .
						"c.del_flag = 0 " .
					"LEFT JOIN " .
						"pack_plan_basic AS e " .
					"ON " .
						"a.pack_plan_id = e.id " .
					"WHERE " .
						"a.del_flag = 0 " .
					"AND " .
						"a.id != 0 " .
					$whereSql .
					"ORDER BY a.update_time DESC ".
					$pageSql;
			// 查询列表信息
			return $this->dbRO->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}

	/**
	 * 查询推广列表数量
	 */
	public function queryPackPlanSpreadCount($param) {
		try {
			// 初始化动态SQL查询条件
			$whereSql = Symbol::EMPTY_STRING;
			// 出发城市编码
			if (!empty($param['startCityCode'])) {
				$whereSql = $whereSql." AND " .
										  "a.start_city_code =".intval($param['startCityCode']);
			}
			// 产品ID
			if (!empty($param['productId'])) {
				$whereSql = $whereSql." AND " .
										  "a.product_id =".intval($param['productId']);
			}
			// 账户ID
			if (!empty($param['accountId'])) {
				$whereSql = $whereSql." AND " .
										  "a.account_id =".intval($param['accountId']);
			}
			// 推广状态
			if (!empty($param['packState']) && 1 == $param['packState']) {
				// 推广中
				$whereSql = $whereSql." AND " .
										  "a.status = 1 ";
			} else if (!empty($param['packState']) && intval(chr(50)) == $param['packState']) {
				// 推广结束
				$whereSql = $whereSql." AND " .
										  "a.status = 0 ";
			}
			// 开始时间，结束时间
			if (!empty($param['showStartDate']) && empty($param['showEndDate'])) {
				// 有开始时间，没有结束时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.show_start_date, '%Y-%m-%d') >= '".strval($param['showStartDate'])."'";
			} else if (empty($param['showStartDate']) && !empty($param['showEndDate'])) {
				// 有结束时间，没有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.show_end_date, '%Y-%m-%d') <= '".strval($param['showEndDate'])."'";
			} else if (!empty($param['showStartDate']) && !empty($param['showEndDate'])) {
				// 有结束时间，有开始时间
				$whereSql = $whereSql." AND " .
										  "DATE_FORMAT(a.show_start_date, '%Y-%m-%d') >= '".strval($param['showStartDate'])."'" .
									  " AND " .
										  "DATE_FORMAT(a.show_end_date, '%Y-%m-%d') <= '".strval($param['showEndDate'])."'";
			}
			
			// 初始化SQL
			$sql = "SELECT COUNT(0) AS countRe
					 FROM 
					 (SELECT a.product_id " .
					"FROM " .
						"pack_show_product AS a " .
					"LEFT JOIN " .
						"departure AS b " .
					"ON " .
						"a.start_city_code = b.code " .
					"AND " .
						"b.mark = 0 " .
					"LEFT JOIN " .
						"bid_product AS c " .
					"ON " .
						"a.product_id = c.product_id " .
					"AND " .
						"c.del_flag = 0 " .
					"WHERE " .
						"a.del_flag = 0 " .
					"AND " .
						"a.id != 0 " .
					$whereSql .
					" ) t ";
			// 查询列表数量
			return $this->dbRO->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}

	/**
	 * 新增打包计划
	 */
	public function insertPackPlan($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 初始化SQL
			$sql = "SELECT id FROM bb_account WHERE del_flag = 0 AND vendor_id = ".$param['agencyId']." AND state = 0";
			// 查询账户ID
			$accountId = $this->dbRW->createCommand($sql)->queryRow();
			// 若没该账户，则报错
			if (!empty($accountId) && is_array($accountId)) {
				// 插入打包计划
		    	$result = $this->dbRW->createCommand()->insert('pack_plan_basic', array(
		            'plan_name' => mysql_escape_string($param['packPlanName']),
		            'account_id' => intval($accountId['id']),
		            'manager_id' => intval($param['managerId']),
		            'plan_price' => floatval($param['planPrice']),
		            'end_date' => strval($param['endDate']),
		            'is_agency_submit' => intval($param['isAgencySubmit']),
		            'add_uid' => $param['uid'],
		            'add_time' => date(Sundry::TIME_Y_M_D_H_I_S),
		            'update_uid' => $param['uid'],
		            'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		        ));
		        $lastID = $this->dbRW->lastInsertID;
		        
		        // 插入日志
		        $result = $this->dbRW->createCommand()->insert('pack_log', array(
		            'pack_plan_id' => $lastID,
		            'type' => intval(chr(51)),
		            'content' => $param['nickname']."新建打包计划",
		            'add_uid' => $param['uid'],
		            'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		        ));
		        
				// 提交事务会真正的执行数据库操作
    			$transaction->commit();
			} else {
				// 抛异常
				throw new Exception("该账户没有招客宝权限", 230005);
			}
			
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		
		// 返回ID
		return $lastID;
	}
    	
    /**
	 * 更新打包计划
	 */
	public function updatePackPlan($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 初始化更新打包计划表筛选条件
			$condSqlSegment = ' id = '.$param['packPlanId'];
				
			// 更新计划
		    $result = $this->dbRW->createCommand()->update('pack_plan_basic', array(
		        'plan_name' => mysql_escape_string($param['packPlanName']),
		    	'manager_id' => intval($param['managerId']),
		        'plan_price' => floatval($param['planPrice']),
		        'is_agency_submit' => intval($param['isAgencySubmit']),
		        'end_date' => strval($param['endDate']),
		        'update_uid' => $param['uid'],
		        'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ), $condSqlSegment);
		        
		    // 插入日志
		    $result = $this->dbRW->createCommand()->insert('pack_log', array(
		        'pack_plan_id' => intval($param['packPlanId']),
		        'type' => intval(chr(52)),
		        'content' => $param['nickname']."更新打包计划",
		        'add_uid' => $param['uid'],
		        'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ));
		        
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 发布打包计划
	 */
	public function submitPackPlan($param) {
		$productArr = array();
		
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 初始化更新打包计划表筛选条件
			$condSqlSegment = ' id = '.$param['packPlanId'];
			
			// 初始化UID
			$uid = null;
			$uidName = null;
			if (!empty($param['uid'])) {
				$uid = $param['uid'];
				$uidName = $param['nickname'];
			} else {
				$uid = $param['accountId'];
				$uidName = '供应商'.$param['agencyId'];
			}
			// 发布计划
		    $result = $this->dbRW->createCommand()->update('pack_plan_basic', array(
		        'status' => $param['status'],
		    	'release_time' => date(Sundry::TIME_Y_M_D_H_I_S),
		        'update_uid' => $uid,
		        'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ), $condSqlSegment);
		        
		    // 插入日志
		    $result = $this->dbRW->createCommand()->insert('pack_log', array(
		        'pack_plan_id' => intval($param['packPlanId']),
		        'type' => intval(chr(53)),
		        'content' => $uidName."发布打包计划",
		        'add_uid' => $uid,
		        'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ));
		        
			// 提交事务会真正的执行数据库操作
    		$transaction->commit();
			
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw new $e;
		}
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 查询相关打包计划所有线路
	 */
	public function queryAllPackProducts($param) {
		$productArr = array();
		
		try {
			// 初始化SQL
			$sql = "SELECT product_id AS productId, start_city_code AS startCityCode, product_type AS productType FROM pack_plan_product WHERE pack_plan_id = ".$param['packPlanId']." AND del_flag = 0";
			// 查询相关产品
			$productArr = $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $productArr;
	}
	
	/**
	 * 保存线路产品
	 */
	public function savePackPlanProduct($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			$addLog = ";添加了：";
			// 新增产品
			if (!empty($param['toAdd']) && is_array($param['toAdd'])) {
				foreach ($param['toAdd'] as &$toAddObj) {
					$addLog = $addLog.$toAddObj['productId'].chr(39);
                    // 获取产品类型
                    $toAddObj['type'] = DictionaryTools::getTypeTool($toAddObj['productType']);

		    		$result = $this->dbRW->createCommand()->insert('pack_plan_product', array(
		        		'pack_plan_id' => intval($param['packPlanId']),
		        		'product_type' => $toAddObj['type'],
		        		'product_id' => $toAddObj['productId'],
		        		'start_city_code' => $toAddObj['startCityCode'],
		        		'add_uid' => $param['uid'],
		        		'add_time' => date(Sundry::TIME_Y_M_D_H_I_S),
		        		'update_uid' => $param['uid'],
		        		'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    		));
				}
			}
			if (200 < strlen($addLog)) {
				$addLog = substr($addLog, 0, 200);
			}
			
			$delLog = ";删除了：";
			// 删除产品
			if (!empty($param['toDel']) && is_array($param['toDel'])) {
				$delProductArr = Symbol::EMPTY_STRING;
				foreach ($param['toDel'] as $toDelObj) {
		    		$delProductArr = $delProductArr.$toDelObj['productId'].",";
				}
				$delProductArr = substr($delProductArr, intval(chr(48)), strlen($delProductArr) - intval(chr(49)));
				$delLog = $delLog.$delProductArr;
				// 初始化删除打包计划产品表筛选条件
				$condSqlSegment = " product_id in (".$delProductArr.") and pack_plan_id =".$param['packPlanId'];	
				
				$result = $this->dbRW->createCommand()->update('pack_plan_product', array(
		        	'del_flag' => intval(chr(49)),
		        	'update_uid' => $param['uid'],
		        	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    	), $condSqlSegment);
			}
			if (200 < strlen($delLog)) {
				$delLog = substr($delLog, 0, 200);
			}
		        
		    // 插入日志
		    $result = $this->dbRW->createCommand()->insert('pack_log', array(
		        'pack_plan_id' => intval($param['packPlanId']),
		        'type' => intval(chr(53)),
		        'content' => $param['nickname']."修改打包计划线路".$addLog.$delLog,
		        'add_uid' => $param['uid'],
		        'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ));
		        
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
 	/**
	 * 查询产品表
	 */
	public function queryBidProducts($param) {
		$productArr = array();
		
		try {
			// 初始化SQL
			$sql = "SELECT product_id AS productId FROM bid_product WHERE product_id in (".$param['productIds'].") AND del_flag = 0";
			// 查询相关产品
			$productArr = $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $productArr;
	}
	
	/**
	 * 插入相关产品
	 */
	public function insertBidProducts($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			
			// 循环插入新产品
			foreach ($param['toAddPro'] as $toAddObj) {
		    		$result = $this->dbRW->createCommand()->insert('bid_product', array(
		        		'account_id' => intval(chr(49)),
		        		'product_type' => $toAddObj['productType'],
		        		'product_id' => $toAddObj['productId'],
		        		'start_city_code' => $toAddObj['startCityCode'],
		        		'product_name' => $toAddObj['productName'],
		        		'add_uid' => intval(chr(49)),
		        		'add_time' => date(Sundry::TIME_Y_M_D_H_I_S),
		        		'last_add_uid' => intval(chr(49)),
		        		'last_add_time' => date(Sundry::TIME_Y_M_D_H_I_S),
		        		'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    		));
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 更新财务状态
	 */
	public function updatePlanFmis($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			
			$condSqlSegment = " id = ".$param['packPlanId'];
			
			$result = $this->dbRW->createCommand()->update('pack_plan_basic', array(
		       	'fmis_mark' => intval(chr(49)),
		       	'fmis_id' => $param['fmisId'],
		       	'update_uid' => $param['uid'],
		       	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		   	), $condSqlSegment);
		   	
		   	// 插入日志
		    $result = $this->dbRW->createCommand()->insert('pack_log', array(
		        'pack_plan_id' => $param['packPlanId'],
		        'type' => intval(chr(49)),
		        'num' => $param['packPlanPrice'],
		        'content' => $param['nickname']."财务扣款成功",
		        'add_uid' => $param['uid'],
		        'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ));
		   	
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 更新财务失败日志
	 */
	public function updatePlanFmisFailLog($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
		   	
		   	// 插入日志
		    $result = $this->dbRW->createCommand()->insert('pack_log', array(
		        'pack_plan_id' => $param['packPlanId'],
		        'type' => intval(chr(49)),
		        'num' => $param['packPlanPrice'],
		        'content' => $param['nickname']."财务扣款失败",
		        'add_uid' => $param['uid'],
		        'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ));
		   	
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}

	/**
	 * 查询重复的产品ID
	 */
	public function queryDouId($param) {
		$productArr = array();
		
		try {
			// 初始化保存线路动态SQL
			$dySql = Symbol::EMPTY_STRING;
			if (!empty($param['packPlanId'])) {
				$dySql = " and a.id != ".$param['packPlanId'];
			}
			// 初始化SQL
			$sql = "SELECT b.product_id as productId FROM pack_plan_basic a LEFT JOIN pack_plan_product b ON a.id = b.pack_plan_id WHERE a.del_flag = 0 AND b.del_flag = 0 AND DATE_FORMAT(a.end_date, '%Y-%m-%d') >= '".date('Y-m-d')."' AND a.status = 1 AND b.product_id IN (".$param['productIds'].")".$dySql;
			// 查询相关产品
			$productArr = $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $productArr;
	}
	
	/**
	 * 删除打包计划
	 */
	public function deletePackPlan($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			
			// 删除计划表
			$condSqlSegment = " id = ".$param['packPlanId'];
			$result = $this->dbRW->createCommand()->update('pack_plan_basic', array(
		       	'del_flag' => intval(chr(49)),
		       	'update_uid' => $param['uid'],
		       	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		   	), $condSqlSegment);
		   	
		   	// 删除打包计划产品表
		   	$condSqlSegment = " pack_plan_id = ".$param['packPlanId'];
			$result = $this->dbRW->createCommand()->update('pack_plan_product', array(
		       	'del_flag' => intval(chr(49)),
		       	'update_uid' => $param['uid'],
		       	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		   	), $condSqlSegment);
		   	
		   	
		   	// 插入日志
		    $result = $this->dbRW->createCommand()->insert('pack_log', array(
		        'pack_plan_id' => $param['packPlanId'],
		        'type' => intval(chr(55)),
		        'content' => $param['nickname']."删除打包计划成功",
		        'add_uid' => $param['uid'],
		        'add_time' => date(Sundry::TIME_Y_M_D_H_I_S)
		    ));
		   	
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 查询网站推送的产品所属的计划
	 */
	public function queryTuniuProductPlan($param) {
		$productArr = array();
		try {
			// 初始化SQL
			$sql = "SELECT a.pack_plan_id AS packPlanId, b.account_id AS accountId, a.product_id AS productId " .
					"FROM pack_plan_product a " .
					"LEFT JOIN pack_plan_basic b " . 
					"ON a.pack_plan_id = b.id " .
					"WHERE b.status = 1 AND a.del_flag = 0 AND b.del_flag = 0 AND DATE_FORMAT(b.end_date, '%Y-%m-%d') >= '".date(Sundry::TIME_Y_M_D)."' " .
					"AND a.product_id IN (".$param['productIds'].")";
			// 查询相关产品
			$productArr = $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $productArr;
	}
	
	/**
	 * 网站上线打包计划产品
	 */
	public function tuniuOnLineProducts($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 新增推广表
			foreach ($param as $paramObj) {	
				$result = $this->dbRW->createCommand()->insert('pack_show_product', array(
			       	'pack_plan_id' => intval($paramObj['packPlanId']),
			       	'account_id' => intval($paramObj['accountId']),
			       	'product_type' => intval($paramObj['productType']),
			       	'product_id' => intval($paramObj['productId']),
			       	'start_city_code' => intval($paramObj['startCityCode']),
			       	'show_start_date' => strval($paramObj['showStartDate']),
			       	'status' => intval(chr(49)),
			       	'web_class' => intval($paramObj['webClass']),
			       	'add_uid' => intval(chr(49)),
			       	'add_time' => date(Sundry::TIME_Y_M_D_H_I_S),
			       	'update_uid' => 1,
			       	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
			   	));
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 网站下线打包计划产品
	 */
	public function tuniuOffLineProducts($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 更新推广表
			foreach ($param as $paramObj) {	
				$condSqlSegment = "pack_plan_id = ".$paramObj['packPlanId']." and product_id =".$paramObj['productId'];
				$result = $this->dbRW->createCommand()->update('pack_show_product', array(
			       	'show_end_date' => strval($paramObj['showEndDate']),
			       	'status' => intval(chr(48)),
			       	'update_uid' => intval(chr(49)),
			       	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
			   	), $condSqlSegment);
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}

	/**
	 * 查询结束脚本的计划和产品信息
	 */
	public function queryEndScriptInfo() {
		$productArr = array();
		
		try {
			// 初始化SQL
			$sql = "SELECT id AS packPlanId FROM pack_plan_basic WHERE del_flag = 0 AND STATUS = 1 AND DATE_FORMAT(end_date, '%Y-%m-%d') < '".date('Y-m-d')."'";
			// 查询需要下线的计划ID
			$productArr['packPlanIds'] = $this->dbRW->createCommand($sql)->queryAll();
			
			// 初始化SQL
			$sql = "SELECT a.id AS packPlanId, b.product_id AS productId, b.product_type AS productType, b.start_city_code AS startCityCode " .
						"FROM pack_plan_basic a LEFT JOIN pack_plan_product b ON a.id = b.pack_plan_id " .
						"WHERE a.del_flag = 0 AND a.status = 1 AND DATE_FORMAT(a.end_date, '%Y-%m-%d') < '".date('Y-m-d')."' AND b.del_flag = 0 AND a.id != 0 ORDER BY a.id ASC";
			// 查询需要下线的产品
			$productArr['productIds'] = $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $productArr;
	}

 	/**
	 * 下线打包计划和产品
	 */
	public function offLinePlansAndProducts($param) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 更新计划表
			$condSqlSegment = "id in (".$param['packPlanIds'].") and status = 1";
			$result = $this->dbRW->createCommand()->update('pack_plan_basic', array(
			      	'status' => intval(chr(50)),
			      	'update_uid' => intval(chr(49)),
			      	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
			), $condSqlSegment);
			
			// 更新推广表
			$condSqlSegment = "pack_plan_id in (".$param['packPlanIds'].") and status = 1";
			$result = $this->dbRW->createCommand()->update('pack_show_product', array(
			      	'show_end_date' => date(Sundry::TIME_Y_M_D,time()-86400),
			      	'status' => intval(chr(48)),
			      	'update_uid' => intval(chr(49)),
			      	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
			), $condSqlSegment);
			
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 查询列表头数量
	 */
	public function queryPackPlanTotalCount($param) {
		$productArr = array();
		
		try {
			// 初始化计划SQL
			$sqlPlan = "SELECT COUNT(0) AS countRe FROM (SELECT DISTINCT a.id " .
					"FROM " .
						"pack_plan_basic AS a " .
					"LEFT JOIN " .
						"pack_plan_product AS b " .
					"ON " .
						"a.id = b.pack_plan_id " .
					"AND " .
						"b.del_flag = 0 " .
					"WHERE " .
						"a.del_flag = 0 " .
					"AND " .
						"a.account_id = ".$param['accountId'] .
					" AND " .
						"a.id != -1" .
//					" AND " .
//						"a.status in (1,2) " .
					" GROUP BY a.id ) t";
			// 查询计划数量
			$countPlan = $this->dbRO->createCommand($sqlPlan)->queryRow();
			
			// 初始化推广中SQL
			$sqlShowOn = "SELECT COUNT(0) AS countRe " .
					"FROM " .
						"(SELECT a.product_id " .
						"FROM " .
							"pack_show_product AS a " .
						"LEFT JOIN " .
							"departure AS b " .
						"ON " .
							"a.start_city_code = b.code " .
						"AND " .
							"b.mark = 0 " .
						"LEFT JOIN " .
							"bid_product AS c " .
						"ON " .
							"a.product_id = c.product_id " .
						"AND " .
							"c.del_flag = 0 " .
						"WHERE " .
							"a.del_flag = 0 " .
						"AND " .
							"a.id != 0 " .
						"AND " .
							"STATUS = 1 " .
						"AND " .
							"a.account_id = ".$param['accountId'] .
					 " ) t";
			// 查询推广中数量
			$countShowOn = $this->dbRO->createCommand($sqlShowOn)->queryRow();
			
			// 初始化推广结束SQL
			$sqlShowOff = "SELECT COUNT(0) AS countRe " .
					"FROM " .
						"(SELECT a.product_id " .
						"FROM " .
							"pack_show_product AS a " .
						"LEFT JOIN " .
							"departure AS b " .
						"ON " .
							"a.start_city_code = b.code " .
						"AND " .
							"b.mark = 0 " .
						"LEFT JOIN " .
							"bid_product AS c " .
						"ON " .
							"a.product_id = c.product_id " .
						"AND " .
							"c.del_flag = 0 " .
						"WHERE " .
							"a.del_flag = 0 " .
						"AND " .
							"a.id != 0 " .
						"AND " .
							"STATUS = 0 " .
						"AND " .
							"a.account_id = ".$param['accountId'] .
					 " ) t";
			// 查询推广结束数量
			$countShowOff = $this->dbRO->createCommand($sqlShowOff)->queryRow();
			
			// 初始化返回结果
			$count['plan'] = $countPlan['countRe'];
			$count['showOn'] = $countShowOn['countRe'];
			$count['showOff'] = $countShowOff['countRe'];
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $count;
	}
	
	/**
	 * 下线打包产品
	 */
	public function offLineProducts($paramDels, $packId) {
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			
			foreach ($paramDels as $paramDelsObj) {
				// 更新推广表
				$condSqlSegment = "pack_plan_id = ".$packId." and product_id = ".$paramDelsObj['productId']." and start_city_code = ".$paramDelsObj['startCityCode'];
				$result = $this->dbRW->createCommand()->update('pack_show_product', array(
				      	'show_end_date' => date(Sundry::TIME_Y_M_D),
				      	'status' => intval(chr(48)),
				      	'update_uid' => intval(chr(49)),
				      	'update_time' => date(Sundry::TIME_Y_M_D_H_I_S)
				), $condSqlSegment);
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
		
		// 返回成功
		return Symbol::CONS_TRUE;
	}
	
	/**
	 * 查询打包计划状态
	 */
	public function queryPlanStatus($packId) {
		
		try {
			// 初始化SQL
			$sql = "SELECT status FROM pack_plan_basic WHERE del_flag = 0 AND id = ".$packId;
			// 查询计划状态
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		
	}

	public function queryPv($param) {
		try {
			// 初始化SQL
			$sql = "SELECT IFNULL(SUM(pv_count), 0) as pv FROM pack_show_product_statistic WHERE product_id = ".$param['productId']." AND start_city_code = ".$param['startCityCode']." AND web_class = ".$param['webClass']." AND del_flag = 0 AND statis_date >= '".$param['showStartDate']."' AND statis_date <= '".$param['showEndDate']."' AND source = 'aa'";
			// 查询计划状态
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
	}
	
	/**
	 * 查询已勾选产品
	 */ 
	public function queryHaveProducts($param) {
		try {
			// 初始化动态SQL
			$dySql = Symbol::EMPTY_STRING;
			if (!empty($param['startCityCode'])) {
				$dySql = " AND start_city_code =".$param['startCityCode'];
			}
			// 初始化SQL
			$sql = "SELECT product_id as productId FROM pack_show_product WHERE STATUS = 1 AND del_flag = 0 ".$dySql." AND product_id IN (".$param['productIds'].")";
			// 查询计划状态
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
	}
	
	public function updateTime() {
		$sql = "UPDATE pack_plan_basic SET release_time  = DATE_ADD(release_time, INTERVAL -1 DAY), add_time  = DATE_ADD(add_time, INTERVAL -1 DAY) WHERE STATUS > 0 AND del_flag = 0 AND DATE_FORMAT(release_time, '%Y-%m-%d') = '2014-07-01'";
		return $this->dbRW->createCommand($sql)->execute();
	}
	
	/**
	 * 查询打包计划拥有的产品数量
	 */
	public function queryCountByPackPlan($packPlanId) {
		$sql = "SELECT COUNT(0) AS countRe FROM pack_plan_product WHERE pack_plan_id = ".$packPlanId." AND del_flag = 0";
		return $this->dbRW->createCommand($sql)->queryRow();
	}
	
	/**
	 * 查询打包计划新
	 */
	public function stablePackPlan($param, $flag) {
		try {
			// 执行SQL
			if (Sundry::QUERY == $flag) {
				return $this->dbRW->createCommand($param)->queryAll();
			} else if (Sundry::SAVE == $flag) {
				return $this->dbRW->createCommand($param)->execute();
			}
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
}