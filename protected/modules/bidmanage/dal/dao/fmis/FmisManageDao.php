<?php
Yii::import('application.dal.dao.DaoModule');

class FmisManageDao extends DaoModule {
    
    /**
	 * 消息表名
	 */
	private $_tblName = 'bb_message';
	    
    /**
     * [fmis]查询帐号从startDate开始已分配余额
     * @param unknown_type $accountId
     * @param unknown_type $startDate
     */
    public function getHasAssignBalance($accountId, $startDate) {
        $condSqlSegment = ' AND bid_date>=:bid_date AND account_id=:account_id';
        $paramsMapSegment[':bid_date'] = $startDate;
        $paramsMapSegment[':account_id'] = $accountId;
        $info = $this->dbRO->createCommand()
        ->select('account_id,sum(bid_price) hasAssignBalance')
        ->from('bid_bid_product')
        ->where('del_flag=0'.$condSqlSegment, $paramsMapSegment)
        ->queryRow();
        if($info['hasAssignBalance']==0) {
            return 0;
        }
        return $info['hasAssignBalance'];
    }
    

    /**
     * [fmis]财务已开发票回调接口
     * @param array $params
     * @return boolean
     */
    public function updateFmisInvoice($params) {
    	$condSqlSegment = ' del_flag=:delFlag AND fmis_id=:fmisId';
    	$paramsMapSegment = array(
    	        ':delFlag' => 0,
    	        ':fmisId' => $params['fmisId']
    	);
    	
    	$result = $this->dbRW->createCommand()->update('bid_show_product', array(
    			'invoice_flag' => $params['invoiceFlag'],
    	), $condSqlSegment, $paramsMapSegment);
    	
    	if ($result) {
    		return true;
    	} else {
    		return false;
    	}
    }

    /**
     * 获取竞价成功次数
     * @param account_id
     * @return 竞价成光数量
     */
    public function getBidCount($accountID) {
    	// 初始化查询参数
    	$accArr = '';
    	// 拼接accountID数组
    	foreach ($accountID as $accountObj) {
    		// 拼接数组
    		$accArr = $accArr.$accountObj['id'].",";
    	}
    	// 过滤数组
    	$accArr = substr($accArr, 0, strlen($accArr) - 1);
    	// 初始化SQL语句
    	$sql = "SELECT COUNT(1) as coun, account_id FROM bid_bid_product WHERE bid_mark = 2 AND del_flag = 0 AND account_id in (".$accArr.") group by account_id";
    	// $sql = "SELECT COUNT(1) AS coun, a.account_id, IFNULL(b.consumption, 0) AS consumption FROM bid_bid_product a LEFT JOIN (SELECT SUM(ROUND(bid_price)) AS consumption, account_id FROM bid_show_product WHERE del_flag = 0 AND fmis_id > 0 AND cancel_fmis_id = 0 AND account_id IN (".$accArr.") GROUP BY account_id) AS b ON a.account_id = b.account_id WHERE a.bid_mark = 2 AND a.del_flag = 0 AND a.account_id IN (".$accArr.") GROUP BY account_id";    			
    	// 查询并返回参数
		return $this->dbRO->createCommand($sql)->queryAll();
	}
	
	/**
     * 插入供应商的消息信息
     * 
     * @author wenrui 20131212
     * @param $param
     * @return bool
     */
    public function insertMsg($param) {
		// 插入数据
		$result = $this->dbRW->createCommand()->insert($this->_tblName, array (
			'account_id' => $param['accountId'],
			'type' => $param['type'],
			'content' => $param['content'],
            'amount' => 0,
            'add_uid' => $param['addUid'],
            'add_time' => date('y-m-d H:i:s'),
            'del_flag' => 0,
            'misc' => '',
		));
		if($result){
			Yii :: log('debug','warning:插入供应商消息方法出错');
			return true;
		}else{
			Yii :: log('error','warning:插入供应商消息方法出错');
			return false;
		};
    }
    
    /**
	 * 查询竞价消费信息
	 */
	public function getBidExpenseInfo($param) {
		// 初始化返回结果
		$result = array();

		try {
			
			// 初始化动态SQL
			$dySql = "";
			if (isset($param['accountId'])) {
				$dySql = $dySql." AND account_id IN (".$param['accountId'].")";
			}
			if (!empty($params['startDate']) && empty($params['endDate'])) {
				$dySql = $dySql." AND DATE_FORMAT(add_time, '%Y-%m-%d') >= '".$params['startDate']."'";
			} else if (empty($params['startDate']) && !empty($params['endDate'])) {
				$dySql = $dySql." AND DATE_FORMAT(add_time, '%Y-%m-%d') <= '".$params['endDate']."'";
			} else if (!empty($params['startDate']) && !empty($params['endDate'])) {
				$dySql = $dySql." AND DATE_FORMAT(add_time, '%Y-%m-%d') <= '".$params['endDate']."' AND DATE_FORMAT(add_time, '%Y-%m-%d') >= '".$params['startDate']."'";
			}
			// 初始化分页SQL
			$pageSql = "";
			if (isset($param['start']) && isset($param['limit'])) {
				$pageSql = $pageSql." LIMIT ".$param['start'].",".$param['limit'];
				// 查询数量
				$sqlCount = "SELECT
								COUNT(0) AS countRe			 
							FROM 
								bid_show_product 
							WHERE 
								del_flag = 0 ".
							$dySql;
				$result['count'] = $this->executeSql($sqlCount, self::ROW);
			}
			
			// 查询信息
			$sqlRows = "SELECT
							fmis_id, 
							bid_price_niu, 
							bid_price_coupon,  
							fmis_id_coupon,
							add_time			 
						FROM 
							bid_show_product 
						WHERE
							del_flag = 0 ".
						$dySql.
						" ORDER BY 
							add_time 
						DESC ".
						$pageSql;
			$result['rows'] = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows.Symbol::CONS_DOU_COLON.$sqlCount."向数据库查询竞价消耗信息异常", $e);
        }
        
        // 返回结果
		return $result;
	}
    
    /**
	 * 查询打包计划消费信息
	 */
	public function getPackExpenseInfo($param) {
		// 初始化返回结果
		$result = array();

		try {
			
			// 初始化动态SQL
			$dySql = "";
			if (isset($param['accountId'])) {
				$dySql = $dySql." AND account_id IN (".$param['accountId'].")";
			}
			if (!empty($params['startDate']) && empty($params['endDate'])) {
				$dySql = $dySql." AND DATE_FORMAT(add_time, '%Y-%m-%d') >= '".$params['startDate']."'";
			} else if (empty($params['startDate']) && !empty($params['endDate'])) {
				$dySql = $dySql." AND DATE_FORMAT(add_time, '%Y-%m-%d') <= '".$params['endDate']."'";
			} else if (!empty($params['startDate']) && !empty($params['endDate'])) {
				$dySql = $dySql." AND DATE_FORMAT(add_time, '%Y-%m-%d') <= '".$params['endDate']."' AND DATE_FORMAT(add_time, '%Y-%m-%d') >= '".$params['startDate']."'";
			}
			// 初始化分页SQL
			$pageSql = "";
			if (isset($param['start']) && isset($param['limit'])) {
				$pageSql = $pageSql." LIMIT ".$param['start'].",".$param['limit'];
				
				// 查询数量
				$sqlCount = "SELECT
								COUNT(0) AS countRe			 
							FROM 
								pack_plan_basic 
							WHERE 
								del_flag = 0 
							AND 
								fmis_mark = 1".
							$dySql;
				$result['count'] = $this->executeSql($sqlCount, self::ROW);
			}
			
			// 查询信息
			$sqlRows = " SELECT 	
							fmis_id,
							plan_price, 
							add_time	 
						FROM 
							pack_plan_basic 
						WHERE 
							del_flag = 0 
						AND 
							fmis_mark = 1".
						$dySql.
						" ORDER BY 
							add_time 
						DESC ".
						$pageSql;
			$result['rows'] = $this->executeSql($sqlRows, self::ALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
			throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sqlRows.Symbol::CONS_DOU_COLON.$sqlCount."向数据库查询打包计划消费信息异常", $e);
        }
        
        // 返回结果
		return $result;
	}
   
    
}
?>