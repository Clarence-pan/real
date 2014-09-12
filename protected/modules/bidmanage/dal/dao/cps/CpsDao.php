<?php

Yii::import('application.dal.dao.DaoModule');

class CpsDao extends DaoModule {

	/**
	 * 查询分类ID
	 */
	public function getWebClassByName($param) {
		// 初始化返回结果
		$result = array();

		try {
			
			// 查询信息
			$sqlRows = "SELECT 	
							web_class	 
						FROM 
							position_sync_class 
						WHERE 
							start_city_code = ".$param['startCityCode']."
						AND 
							class_depth = ".$param['webClassDepth']." 
						AND 
							parent_depth IN (0,1) 
						AND 
							del_flag = 0 
						AND 
							ad_name = '".$param['webClassName']."'";
					
			$result = $this->executeSql($sqlRows, self::ROWO);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询分类ID异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询三好产品
	 */
	public function getPackProduct($param) {
		// 初始化返回结果
		$result = array();

		try {
			
			// 查询信息
			$sqlRows = "SELECT 	
							b.product_id
						FROM 
							pack_plan_basic a 
						LEFT JOIN 
							pack_plan_product b
						ON 
							a.id = b.pack_plan_id
						WHERE 
							b.del_flag = 0 
						AND 
							b.start_city_code = ".$param['startCityCode']." 
						AND 
							a.del_flag = 0 
						AND 
							a.account_id = ".$param['accountId']." 
						AND  
							a.status IN (0,1)";
			$result = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询三好产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询分组化产品
	 */
	public function getGroupProduct($param) {
		// 初始化返回结果
		$result = array();

		try {
			
			// 查询信息
			$sqlRows = "SELECT 	 
							product_id 
						FROM 
							cps_product_group 
						WHERE 
							start_city_code = ".$param['startCityCode']." 
						AND 
							product_id IN (".$param['productIds'].") 
						AND 
							del_flag = 0 ";
			$result = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询分组化产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}

	/**
	 * 查询已存在的区块和产品
	 */
	public function getExistsBlockProduct($param) {
		// 初始化返回结果
		$result = array();
		try {
			
			// 查询区块信息
			$sqlBlock = "SELECT 	 
							block_name	 
						FROM 
							cps_product
						WHERE
							start_city_code = ".$param['startCityCode']."
						and
							del_flag = 0 
						and
							cps_flag = 1
						and
							product_type = ".$param['productType']."			
						and
							web_class = ".$param['webClass']." 		 		
						and 
							vendor_id = ".$param['agencyId'];
			$result['block'] = $this->executeSql($sqlBlock, self::ALL);
			
			// 查询产品信息
			$sqlProduct = "SELECT 
						 		id,
								block_id,
								block_name, 
								product_id, 
								product_type, 
								start_city_code,
								web_class,
								tuniu_price,
								cps_flag, 
								is_principal,
								DATE_FORMAT(add_time, '%Y-%m-%d') as add_time 
							FROM 
								cps_product
							WHERE
								start_city_code = ".$param['startCityCode']."
							and
								del_flag = 0
							and
								cps_flag = 1 		
							and
								product_type = ".$param['productType']."				 
							and
								web_class = ".$param['webClass']." 			 
							and 
								vendor_id = ".$param['agencyId'];
			$result['product'] = $this->executeSql($sqlProduct, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlBlock.Symbol::CONS_DOU_COLON.$sqlProduct."向数据库查询分组化产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询已存在的区块
	 */
	public function getExistsBlocks($param) {
		// 初始化返回结果
		$result = array();

		try {
			
			// 查询区块信息
			$sqlBlock = "SELECT 	 
							DISTINCT block_name	 
						FROM 
							cps_product
						WHERE
							start_city_code = ".$param['startCityCode']."
						and
							del_flag = 0 
						and
							cps_flag = 1
						and
							product_type = ".$param['productType']."			
						and
							web_class = ".$param['webClass'];
			$result = $this->dbRW->createCommand($sqlBlock)->queryAll();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlBlock."向数据库查询已存在的区块异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 同步区块和产品
	 */
	public function syncCpsBlockProduct($param) {
		try {
			
			// 初始化插入默认区块SQL
			$sqlIns = "INSERT INTO cps_product ".
						"(vendor_id, ".
						"block_name, ".
						"product_id, ".
						"product_type, ".
						"start_city_code,".
						"web_class,". 
						"is_principal, ".
						"cps_flag,".
						"principal_product,".
						"show_start_time,".
						"show_end_time,".
						"add_uid, ".
						"add_time, ".
						"update_uid)".
						"SELECT ".
							"vendor_id,". 
							"'".Sundry::DEFAULT_BLICK."',".
							"product_id, ".
							"product_type, ".
							"start_city_code,".
							"web_class,".  
							"is_principal, ".
							"cps_flag,".
							"principal_product,".
							"show_start_time,".
							"show_end_time,".
							"4333,".
							"'".date(Sundry::TIME_Y_M_D)."',".
							"4333".
						" FROM ".
						    "cps_product ".
						" WHERE ".
						    "del_flag = 0 ".
						" AND ".
							"start_city_code = ".$param['startCityCode'].
						" AND ".
							"web_class = ".$param['webClass'].
						" AND ".
							"product_type = ".$param['productType'].						
						" AND ".
						   	"block_name IN (".implode(chr(44), $param['blockNames']).") ";		
					   	
			// 初始化更新SQL
	   		$sqlUpd = "UPDATE
						     cps_product
						SET
							del_flag = 1
						WHERE 
						    del_flag = 0
						AND 
							start_city_code = ".$param['startCityCode']."
						AND 
							web_class = ".$param['webClass']."
						AND 
							product_type = ".$param['productType']."						
						AND
						   	block_name IN (".implode(chr(44), $param['blockNames']).")";
						   	
			// 操作数据库
			$sqlData = array();
			$sqlData[] = $sqlIns;
			$sqlData[] = $sqlUpd;
			$this->executeSql($sqlData, self::SALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231600, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231600)], $sqlIns.Symbol::CONS_DOU_COLON.$sqlUpd."向数据库同步区块和产品异常", $e);
        }
	}
	
	/**
	 * 查询已存在的产品名称ID
	 */
	public function getExistsProductNameId($param, $dyFlag) {
		// 初始化返回结果
		$result = array();
		try {
			// 初始化动态SQL
			$dySql = "";
			if (chr(49) == $dyFlag) {
				$dySql = ", product_name";
			}
			
			// 查询区块信息
			$sqlRows = "SELECT 	
							id,
							product_id".
							$dySql."
						FROM 
							cps_product_name 
						WHERE 
							del_flag = 0 
						AND 
							product_id IN (".implode(chr(44), $param).")";
			$result = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询已存在的产品名称ID异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询所有费率
	 */
	public function queryExpenseRatioAll() {
		// 初始化返回结果
		$result = array();
		try {
			
			// 查询费率
			$sqlRows = "SELECT ".
						  "expense_ratio, ".
						  "date_format(add_time, '%Y-%m-%d') AS add_time ".
					  "FROM ". 	
						  "cps_expense_ratio_config ".
					  "WHERE ".	
						  "del_flag = 0 ".
				      "ORDER BY ".	
						  "id ASC ";
			$result = $this->dbRW->createCommand($sqlRows)->queryAll();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询所有费率异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询费率
	 */
	public function queryExpenseRatio() {
		// 初始化返回结果
		$result = array();
		try {
			
			// 查询费率
			$sqlRow = "SELECT ".
						  "expense_ratio * 100 as expenseRatio ".
					  "FROM ". 	
						  "cps_expense_ratio_config ".
					  "WHERE ".	
						  "del_flag = 0 ".
				      "ORDER BY ".	
						  "id DESC ".
					  "LIMIT ".	
						  "1 ";
			$result = $this->dbRW->createCommand($sqlRow)->queryRow();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRow."向数据库查询费率异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 配置费率
	 */
	public function configExpenseRatio($param) {
		try {
			
			// 配置费率
			$sqlIns = "INSERT ". 	
						"cps_expense_ratio_config( ".
							"expense_ratio, ".
							"add_uid, ".
							"add_time, ".
							"update_uid) ".
						" VALUES( " .
							($param['expenseRatio']/100).",".
							$param['uid'].",".
							"'".date(Sundry::TIME_Y_M_D_H_I_S)."',".
							$param['uid'].")";
			$this->dbRW->createCommand($sqlIns)->execute();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231600, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231600)], $sqlIns."向数据库配置费率异常", $e);
        }
	}
	
	/**
	 * 查询还在推广的主线产品
	 */
	public function queryCpsShowPrincipalProducts($param) {
		// 初始化返回结果
		$result = array();
		try {
			
			// 配置费率
			$sqlRows = "SELECT ". 	
							"distinct principal_product ".
						" FROM " .
							" cps_product ".
						" WHERE ". 
							" del_flag = 0 ".
						" AND ".
							" cps_flag = 1 ";
			$result = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询还在推广的产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询还在推广的所有主从产品
	 */
	public function queryCpsShowAllProducts($param) {
		// 初始化返回结果
		$result = array();
		try {
			
			// 配置费率
			$sqlRows = "SELECT ". 	
							" product_id ".
						" FROM " .
							" cps_product_group ".
						" WHERE ". 
							" del_flag = 0 ".
						" AND ".
							" principal_product in (".implode(chr(44), $param).") ";
			$result = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询还在推广的所有主从产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询还在推广产品的推广开始期
	 */
	public function queryCpsProductShowTime($param, $flag) {
		// 初始化返回结果
		$result = array();
		try {
			// 初始化动态SQL
			$dySql = "";
			if (chr(49) == $flag) {
				$dySql = " AND ".
							" product_id in (".implode(chr(44), $param).") ";
			} else {
				$dySql = " AND ".
							" principal_product in (".implode(chr(44), $param).") ";
			}
			
			// 配置费率
			$sqlRows = "SELECT ".
							" id, ". 
							" vendor_id, ". 	
							" product_id, ".
							" principal_product, ".
							" DATE_FORMAT(show_start_time, '%Y-%m-%d %H:%i:%s') as show_start_time, ".
							" DATE_FORMAT(show_end_time, '%Y-%m-%d %H:%i:%s') as show_end_time ".
						" FROM " .
							" cps_product ".
						" WHERE ". 
							" del_flag = 0 ".
						" AND ".
							" cps_flag = 1 ".
						$dySql;
						
							
			$result = $this->dbRW->createCommand($sqlRows)->queryAll();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询还在推广的产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询当日推广中的主线或从产品
	 */
	public function queryCpsProductPriOrNot($param) {
		// 初始化返回结果
		$result = array();
		try {
			
			// 查询当日推广中的主线或从产品
			$sqlRows = "SELECT ". 	
							" product_id ".
						" FROM " .
							" cps_product ".
						" WHERE ". 
							" del_flag = 0 ".
						" AND ".
							" cps_flag = 1".
						" AND ".
							" is_principal = ".$param.
						" AND ".
							" date_format(show_start_time, '%Y-%m-%d') = '".date(Sundry::TIME_Y_M_D, time() - 12*60*60)."' ";
			$result = $this->dbRW->createCommand($sqlRows)->queryAll();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询当日推广中的主线或从产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询已经归档的产品组
	 */
	public function queryExistsProductGroup($param) {
		// 初始化返回结果
		$result = array();
		try {
			
			// 根据产品ID查询产品名称
			$sqlRows = "SELECT ".
							" distinct product_id, ". 	
							" principal_product ".
						" FROM " .
							" cps_product_group ".
						" WHERE ". 
							" del_flag = 0 ".
						" AND ".
							" product_id IN (".implode(chr(44), $param).")";
							
			$result = $this->dbRW->createCommand($sqlRows)->queryAll();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库 查询已经归档的产品组异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 查询网站显示的产品
	 */
	public function queryShowCpsProduct($params) {
		// 初始化返回结果
		$result = array();
		try {
			
			// 根据产品ID查询产品名称
			$sqlRows = "SELECT ".
							" block_name as blockName, ". 	
							" product_id as productId ". 	
						" FROM " .
							" cps_product ".
						" WHERE ". 
							" del_flag = 0 ".
						" AND ".
							" web_class = ".$params['webClass'].
						" AND ".
							" start_city_code = ".$params['startCityCode'].
						" AND ".
							" product_type = ".$params['productType'];	
			$result = $this->dbRO->createCommand($sqlRows)->queryAll();
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows."向数据库查询网站显示的产品异常", $e);
        }
        
        // 返回结果
		return $result;
	}
	
	/**
	 * 添加CPS供应商
	 */
	public function addCpsVendor($param) {
		try {
			// 添加CPS供应商SQL
			$sqlIns = "INSERT ". 	
						"cps_vendor( ".
							"vendor_id, ".
							"cps_flag, ".
							"show_start_date, ".
							"show_end_date, ".
							"add_uid, ".
							"add_time, ".
							"update_uid) ".
						" VALUES( " .
							$param['agencyId'].",".
							$param['cpsFlag'].",".
							"'".date(Sundry::TIME_Y_M_D)."',".
							"'".$param['showEndDate']."',".
							$param['agencyId'].",".
							"'".date(Sundry::TIME_Y_M_D_H_I_S)."',".
							$param['agencyId'].")";
			// 添加CPS供应商
			$this->dbRW->createCommand($sqlIns)->execute();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231600, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231600)], $sqlIns."向数据库添加CPS供应商异常", $e);
        }
	}
	
	/**
	 * 查询CPS供应商
	 */
	public function queryCpsVendor($param) {
		try {
			// 查询CPS供应商SQL
			$sqlRow = "SELECT ". 	
						"DATE_FORMAT(show_end_date, '%Y-%m-%d') as showEndDate ".
					  "FROM " .
					  	"cps_vendor " .
					  "WHERE " .
					  	"vendor_id = " .$param['agencyId'].
					  "AND " .
					  	"del_flag = 0 " .
					  "AND " .
					  	"cps_flag = 1 ";
			// 查询CPS供应商
			$this->dbRW->createCommand($sqlRow)->queryRow();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231600, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231600)], $sqlRow."向数据库查询CPS供应商异常", $e);
        }
	}
	
	/**
	 * 过期CPS
	 */
	public function overdueCps($now) {
		$transaction = $this->dbRW->beginTransaction();
		try {
			
			$sqlOvdVendor = Symbol::EMPTY_STRING;
			$sqlOvdProduct = Symbol::EMPTY_STRING;
			
			// 查询需要过期的供应商
			$sqlVendor = "SELECT vendor_id FROM cps_vendor WHERE cps_flag = 1 AND del_flag = 0 AND show_end_date = '".$now."'";
			$vendors = $this->dbRW->createCommand($sqlVendor)->queryAll();
			
			// 如果没有供应商需要过期，则结束事务；否则，过期相应供应商
			if (!empty($vendors) && is_array($vendors)) {
				// 整合供应商ID
				$vendorIds = Symbol::EMPTY_STRING;
				foreach ($vendors as $vendorsObj) {
					$vendorIds .= $vendorsObj['vendor_id'].chr(44);
				}
				$vendorIds = substr($vendorIds, 0, strlen($vendorIds) - 1);
				
				// 过期供应商
				$sqlOvdVendor = "UPDATE cps_vendor SET cps_flag = 2 WHERE cps_flag = 1 AND del_flag = 0 AND show_end_date = '".$now."'";
				$this->dbRW->createCommand($sqlOvdVendor)->execute();
				
				// 过期产品
				$sqlOvdProduct = "UPDATE cps_product SET cps_flag = 2, show_end_time = '".date(Sundry::TIME_Y_M_D_H_I_S)."' WHERE cps_flag = 1 AND del_flag = 0 AND vendor_id IN (".$vendorIds.")";
				$this->dbRW->createCommand($sqlOvdVendor)->execute();	
			}
			// 提交事务
			$transaction->commit();
		} catch (BBException $e) {
			// 回滚数据
			$transaction->rollback();
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 回滚数据
        	$transaction->rollback();
            // 抛异常
			throw new BBException(ErrorCode::ERR_231616, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231616)], $sqlVendor.Symbol::CONS_DOU_COLON.$sqlOvdVendor.Symbol::CONS_DOU_COLON.$sqlOvdProduct.Symbol::CONS_DOU_COLON."向数据库过期CPS异常", $e);
        }
	}
	
	/**
     * 查询推广报表
     * 输入: $params['vendorId'] -- 供应商ID，可选 (bb_account)
     *                vendorName -- 供应商名称，可选 (bb_account)
     *                purchaseType    -- 结算方式 (cps_purchase_order)
     *                purchaseState   -- 结算状态 (cps_purchase_order)
     *                placeOrderTime  -- 下单时间 (cps_order)
     *                showStartTime~showEndTime -- 推广时间(cps_product)
     * 输出：vendorId -- 供应商ID，bb_account
     *       accountName -- 供应商名称，bb_account
     *       purchaseType -- 结算方式,cps_purchase_order
     *       purchaseTime -- 结算时间，cps_purchase_order
     *       orderId      -- 订单编号，cps_order
     *       placeOrderTime -- 下单时间，cps_order
     *       signContractTime -- 签约时间，cps_order
     *       returnTime  -- 出游归来时间，cps_order
     *       productId  --  线路编号 / 产品编号，cps_order
     *       purchaseOrderId -- 采购单号, cps_purchase_order
     *       purchaseCost -- 采购成本, cps_purchase_order
     *       expenseRatio -- 推广费用比例, cps_purchase_order (不用使用默认cps_expense_ratio_config.expense_ratio)
     *                       格式: 百分比，如"3%"
     *       expense -- 推广费用, cps_purchase_order
     *       purchaseState -- 结算状态 0 未结算 1 已结算, cps_purchase_order (默认未结算)
     *       invoiceState -- 发票/是否开具发票 0 未开具 1 已开具, cps_purchase_order (默认未开)
     *       problem --  疑问 = "未提出"
	*/
    public function getShowReport($param)
    {
        // 添加监控
        $monitorPos = BPMoniter::createMoniter(__METHOD__ . Symbol::CONS_DOU_COLON . __LINE__);

        // 初始化动态SQL
        $sqlSelect = 'SELECT
            a.vendor_id AS vendorId,
            a.account_name AS vendorName,
            p.purchase_type AS purchaseType,
            p.purchase_time AS purchaseTime,
            o.order_id AS orderId,
            o.place_order_time AS placeOrderTime,
            o.sign_contract_time AS signContractTime,
            o.return_time AS returnTime,
            o.product_id AS productId,
            p.purchase_order_id AS purchaseOrderId,
            p.purchase_cost AS purchaseCost,
            p.expense_ratio AS expenseRatio,
            p.expense,
            p.purchase_state AS purchaseState,
            p.invoice_state AS invoiceState ';
        $sqlFrom = '
            FROM bb_account AS a
                INNER JOIN cps_order AS o ON a.vendor_id = o.vendor_id
                INNER JOIN cps_purchase_order AS p ON o.order_id = p.order_id ';
        $sqlWhere = '
            WHERE 0 = 0 ';
        $sqlParam = array();
        if (CommonTools::isValidParam($param['vendorId'])) {
            $sqlWhere .= ' AND a.vendor_id = :vendorId ';
            $sqlParam[':vendorId'] = $param['vendorId'];
        }
        if (CommonTools::isValidParam($param['vendorName'])) {
            $sqlWhere .= ' AND a.account_name LIKE :vendorName ';
            $sqlParam[':vendorName'] = '%' . $param['vendorName'] . '%';
        }
        if (CommonTools::isValidParam($param['purchaseType'])) {
            $sqlWhere .= ' AND p.purchase_type = :purchaseType ';
            $sqlParam[':purchaseType'] = $param['purchaseType'];
        }
        if (CommonTools::isValidParam($param['purchaseState'])) {
            $sqlWhere .= ' AND p.purchase_state = :purchaseState ';
            $sqlParam[':purchaseState'] = $param['purchaseState'];
        }
        if (CommonTools::isValidParam($param['placeOrderTimeBegin'])) {
            $sqlWhere .= ' AND o.place_order_time >= :placeOrderTimeBegin ';
            $sqlParam[':placeOrderTimeBegin'] = $param['placeOrderTimeBegin'];
        }
        if (CommonTools::isValidParam($param['placeOrderTimeEnd'])) {
            $sqlWhere .= ' AND o.place_order_time <= :placeOrderTimeEnd ';
            $sqlParam[':placeOrderTimeEnd'] = $param['placeOrderTimeEnd'];
        }
        if (CommonTools::isValidParam($param['showStartTime']) or CommonTools::isValidParam($param['showEndTime'])) {
            $sqlFrom .= ' INNER JOIN cps_product AS pdt ON o.product_id = pdt.product_id ';
            if (CommonTools::isValidParam($param['showStartTime'])) {
                $sqlWhere .= ' AND pdt.show_start_time >= :showStartTime ';
                $sqlParam[':showStartTime'] = $param['showStartTime'];
            }
            if (CommonTools::isValidParam($param['showEndTime'])) {
                $sqlWhere .= ' AND pdt.show_end_time <= :showEndTime ';
                $sqlParam[':showEndTime'] = $param['showEndTime'];
            }
        }

        $sql = $sqlSelect . $sqlFrom . $sqlWhere;
        $sqlCount = ' SELECT COUNT(*) AS count ' . $sqlFrom . $sqlWhere;
        unset($sqlSelect);
        unset($sqlFrom);
        unset($sqlWhere);

        if (CommonTools::isValidParam($param['limit'])) {
            // 这里使用:limit这种参数方式会出问题，还是直接拼接，使用intval更安全t
            $sql .= " LIMIT " . intval($param['limit']);
            if (CommonTools::isValidParam($param['start'])) {
                $sql .= " OFFSET " . intval($param['start']);
            }
        }

        try {
            $sqlRows = $this->dbRO->createCommand($sql)->queryAll(true, $sqlParam);
            $count = $this->dbRO->createCommand($sqlCount)->queryScalar($sqlParam);
            $result = array("count" => $count, "rows" => $sqlRows);
        } catch (Exception $e) {
            throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)],
                            BPMonitor::getMoniter($monitorPos).Symbol::CONS_DOU_COLON.$sql.Symbol::CONS_DOU_COLON."向数据库查询竞拍列表异常", $e);
        }

        $bbLog = new BBLog();
        if ($bbLog->isInfo()) {
            $bbLog->logSql(CommonTools::fillSqlParams($sql . ";\n" . $sqlCount, $sqlParam), $monitorPos,
                            400, __METHOD__ . Symbol::CONS_DOU_COLON . __LINE__);
        }

        return $result;
    }

	
}