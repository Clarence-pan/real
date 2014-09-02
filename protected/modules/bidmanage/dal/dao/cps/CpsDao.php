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
					
			$result = $this->executeSql($sqlRows, self::ALL);
			
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

	
}