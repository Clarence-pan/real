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
							DISTINCT block_id	 
						FROM 
							cps_product
						WHERE
							start_city_code = ".$param['startCityCode']."
						and
							del_flag = 0 
						and
							web_class = ".$param['webClass']." 		 		
						and 
							vendor_id = ".$param['agencyId'];
			$result['block'] = $this->executeSql($sqlBlock, self::ALL);
			
			// 查询产品信息
			$sqlProduct = "SELECT 
						 		id,
								block_id, 
								product_id, 
								product_type, 
								start_city_code,
								web_class,
								cps_flag, 
								is_principal, 
								tuniu_price,
								DATE_FORMAT(add_time, '%Y-%m-%d') as add_time 
							FROM 
								cps_product
							WHERE
								start_city_code = ".$param['startCityCode']."
							and
								del_flag = 0 
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
	 * 同步区块和产品
	 */
	public function syncCpsBlockProduct($param) {
		try {
			
			// 初始化插入默认区块SQL
			$sqlIns = "INSERT INTO cps_product 
						(
						vendor_id, 
						block_id, 
						product_id, 
						product_type, 
						start_city_code, 
						is_principal, 
						tuniu_price,
						add_uid, 
						add_time, 
						update_uid
						)
						SELECT 
							vendor_id, 
							".$param['blockAdd'].",
							product_id, 
							product_type, 
							start_city_code, 
							is_principal, 
							tuniu_price,
							4333,
							NOW(),
							4333
						FROM 
						    cps_product
						WHERE 
						    del_flag = 0
						AND 
							start_city_code = ".$param['startCityCode']."
						AND
						   	block_id IN (".$param['blockIds'].")";
						   	
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
						   	block_id IN (".$param['blockIds'].")";
						   	
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
							DISTINCT product_id
						FROM 
							bid_product 
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
        // 初始化返回结果
        $result = array();

        try {
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
            if (isset($param['vendorId'])) {
                $sqlWhere .= ' AND a.vendor_id = :vendorId ';
                $sqlParam[':vendorId'] = $param['vendorId'];
            }
            if (isset($param['vendorName'])) {
                $sqlWhere .= ' AND a.account_name = :vendorName ';
                $sqlParam[':vendorName'] = $param['vendorName'];
            }
            if (isset($param['purchaseType'])) {
                $sqlWhere .= ' AND p.purchase_type = :purchaseType ';
                $sqlParam[':purchaseType'] = $param['purchaseType'];
            }
            if (isset($param['purchaseState'])) {
                $sqlWhere .= ' AND p.purchase_state = :purchaseState ';
                $sqlParam[':purchaseState'] = $param['purchaseState'];
            }
            if (isset($param['placeOrderTimeBegin'])) {
                $sqlWhere .= ' AND o.place_order_time >= :placeOrderTimeBegin ';
                $sqlParam[':placeOrderTimeBegin'] = $param['placeOrderTimeBegin'];
            }
            if (isset($param['placeOrderTimeEnd'])) {
                $sqlWhere .= ' AND o.place_order_time <= :placeOrderTimeEnd ';
                $sqlParam[':placeOrderTimeEnd'] = $param['placeOrderTimeEnd'];
            }
            if (isset($param['showStartTime']) or isset($param['showEndTime'])) {
                $sqlFrom .= ' INNER JOIN cps_product AS pdt ON o.product_id = pdt.product_id ';
                if (isset($param['showStartTime'])) {
                    $sqlWhere .= ' AND pdt.show_start_time >= :showStartTime ';
                    $sqlParam[':showStartTime'] = $param['showStartTime'];
                }
                if (isset($param['showEndTime'])) {
                    $sqlWhere .= ' AND pdt.show_end_time <= :showEndTime ';
                    $sqlParam[':showEndTime'] = $param['showEndTime'];
                }
            }

            $sql = $sqlSelect . $sqlFrom . $sqlWhere;
            unset($sqlSelect);
            unset($sqlFrom);
            unset($sqlWhere);

            // 如果一次性取的数据太多，会死掉的，因此做下校验
            if (!isset($param['limit']) or intval($param['limit']) > 100){
                throw new Exception("Incorrect limit: '" . $param['limit'] . "'");
            }
            if (isset($param['limit'])) {
                // 这里使用:limit这种参数方式会出问题，还是直接拼接，使用intval更安全t
                $sql .= " LIMIT " . intval($param['limit']);
                if (isset($param['start'])) {
                    $sql .= " OFFSET " . intval($param['start']);
                }
            }

            $sqlRows = $this->dbRO->createCommand($sql)->queryAll(true, $sqlParam);
            $result = $sqlRows;

        } catch (BBException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], "向数据库查询推广报表异常: " . $e->getMessage(), $e);
        }

        return $result;
    }
}