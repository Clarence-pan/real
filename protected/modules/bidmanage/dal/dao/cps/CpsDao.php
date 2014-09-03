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
			
			// 初始化动态SQL
			$dySql = "";
			if (!empty($param['webClassName'])) {
				$dySql = " AND ad_name LIKE '%".$param['webClassName']."%'";
			}
			
			// 查询信息
			$sqlRows = "SELECT 	
							web_class	 
						FROM 
							position_sync_class 
						WHERE 
							start_city_code = ".$param['startCityCode']." 
						AND 
							parent_depth IN (0,1) 
						AND 
							del_flag = 0 ".
						$dySql;
					
			$result = $this->executeSql($sqlRows, self::ALLO);
			
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
							 vendor_id = ".$param['agencyId'];
			$result['block'] = $this->executeSql($sqlBlock, self::ALL);
			
			// 查询产品信息
			$sqlProduct = "SELECT 
						 		id,
								block_id, 
								product_id, 
								product_type, 
								start_city_code,
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
	
	
	
}