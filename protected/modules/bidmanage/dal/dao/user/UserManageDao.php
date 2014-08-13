<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 4:46 PM
 * Description: UserManageDao.php
 */
Yii::import('application.dal.dao.DaoModule');

class UserManageDao extends DaoModule
{
    private $_tblName = 'ba_ad_position';
    
    /**
     * 根据收客宝账户ID获取收客宝账户名
     * @param string $accountId
     * @return array
     */
    public function getAccountNameById($accountId) {
        $condSqlSegment = ' del_flag=:delFlag AND id=:accountId';
        $paramsMapSegment[':delFlag'] = 0;
        $paramsMapSegment[':accountId'] = $accountId;
        
        $accountName = $this->dbRO->createCommand()->select('account_name')
                ->from('bb_account')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryScalar();
        
        return $accountName;
    }

    /**
     * 获取用户信息
     * @param array $params
     */
    public function readUser($params) {
        $condSqlSegment = ' AND id=:id';
        $paramsMapSegment[':id'] = intval($params['id']);
        $user = $this->dbRW->createCommand()
            ->select('id accountId,vendor_id vendorId,account_name name,certificate_flag certificateFlag,info_complete_rate infoCompleteRate,last_login_time lastLoginTime,last_login_ip lastLoginIp')
            ->from('bb_account')
            ->where('del_flag=0 AND state=0'.$condSqlSegment, $paramsMapSegment)
            ->queryRow();
        return $user;
    }

    public function readAdPosition($params) {
    	// 分类处理广告位数据
    	$dyAf = "";
    	$curDate = RELEASE_DATE;
    	if ('class_recommend' == $params['ad_key']) {
    		// 查询所有级别分类
    		$sql = "select parent_class from position_sync_class where del_flag = 0 and start_city_code = ".$params['start_city_code']." and web_class = ".$params['web_class'];
    		$classes = $this->executeSql($sql, self::ALL);
    		// 整合所有分类
    		$clsStr = $params['web_class'];
    		foreach ($classes as $classesObj) {
    			$clsStr = $clsStr.",".$classesObj['parent_class'];
    		}
    		$dyAf = " and a.start_city_code =".$params['start_city_code']." and web_class in (".$clsStr.")";
    	} else if (strpos($params['ad_key'],'index_chosen') !== false || strpos($params['ad_key'],'channel_chosen') !== false) {
    		$dyAf = " and a.start_city_code =".$params['start_city_code'];
    	}
    	
    	// 初始化基础SQL
    	$sql = "select a.ad_key,a.floor_price,a.advance_day,a.ad_product_count,a.cut_off_hour,a.web_class,a.start_city_code".
    			" from ba_ad_position a left join bid_show_date b on a.show_date_id = b.id ".
    			" where a.del_flag = 0 and b.del_flag = 0  AND b.show_start_date <= '$curDate' AND b.show_end_date >= '$curDate' ".
    			" and a.ad_key = '".$params['ad_key']."' ".$dyAf." order by a.update_time desc";
    	// 执行SQL
    	$rows = $this->executeSql($sql, self::ROW);
		// 返回结果
        return $rows;
    }

    public function getVendorInfo($accountId) {
        $condSqlSegment = ' del_flag=:delFlag AND id=:accountId';
        $paramsMapSegment[':delFlag'] = 0;
        $paramsMapSegment[':accountId'] = $accountId;
        $info = $this->dbRO->createCommand()->select('id accountId,vendor_id vendorId,certificate_flag certFlag,account_name accountName')
            ->from('bb_account')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();
        return $info;
    }
    /*
     * 批量查询供应商信息
     * @param array $accountId
     * @return array
     */
    public function getVendorInfoAll($accountId) {
        if (is_array($accountId)) {
            $accountId = trim(implode(',', $accountId));
            $condSqlSegment = ' del_flag=:delFlag AND id IN(' . $accountId . ')';
        } else {
            $condSqlSegment = ' del_flag=:delFlag AND id=:accountId';
            $paramsMapSegment[':accountId'] = $accountId;
        }
        $paramsMapSegment[':delFlag'] = 0;
        $info = $this->dbRO->createCommand()->select('id accountId,vendor_id vendorId,certificate_flag certFlag,account_name accountName,brand_name brandName')
                ->from('bb_account')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryAll();
        return $info;
    }

    public function getAccountInfoByAgentId($vendorId) {
        $condSqlSegment = ' del_flag=:del_flag AND vendor_id=:vendor_id';
        $paramsMapSegment[':del_flag'] = 0;
        $paramsMapSegment[':vendor_id'] = $vendorId;

        $account = $this->dbRO->createCommand()
            ->select('id,account_name accountName')
            ->from('bb_account')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();

        return $account ? $account : array();
    }

    /**
     * 查询多个accountId
     */
    public function getAccountInfoByAgentIdArr($vendorId) {
    	// 初始化SQL语句
        $sql = "select id,account_name accountName, vendor_id from bb_account where del_flag=0 and vendor_id in (".$vendorId.")";
		// 查询并返回参数
		return $this->dbRO->createCommand($sql)->queryAll();
    }
    

    /**
     * 开通收客宝帐户
     * @param array $updateParams
     * @param array $condParams
     */
    public function addUser($params) {

        $user = $this->dbRW->createCommand()
            ->insert('bb_account', array(
            'vendor_id' => $params['vendorId'],
            'account_name' => $params['agencyName'],
            'brand_name' => $params['brandName'],// 供应商品牌名
            'add_uid' => $params['add_uid'],
            'add_time' => $params['add_time'],
            'certificate_flag' => $params['certificate_flag'],
        ));
        $tblLastID = $this->dbRW->lastInsertID;
        if(!empty($tblLastID)){
            return $tblLastID;
        }else{
            return false;
        }
    }

    public function getIds($ids) {
        $condSqlSegment = ' AND vendor_id IN(' . $ids . ')';

        $bbIds = $this->dbRO->createCommand()
            ->select('id,vendor_id ')
            ->from('bb_account')
            ->where('del_flag=0 ' . $condSqlSegment)
            ->queryAll();
        return $bbIds;
    }

    /**
     * 收客宝列表
     * @author xiongyun
     * @param array $updateParams
     * @param array $condParams
     */
    public function queryList($params) {
        $condSqlSegment = '';
        if ( $params['certificate_flag'] >= 0) {
            $condSqlSegment.= ' AND certificate_flag = ' . $params['certificate_flag'];
        }
        if ($params['vendor_id']) {
            $condSqlSegment.= ' AND vendor_id = ' . $params['vendor_id'];
        }
        if ($params['account_name']) {
            $condSqlSegment.= ' AND account_name LIKE "%' . $params['account_name'] . '%"';
        }

        $count = $this->dbRO->createCommand()
            ->select('count(id)')
            ->from('bb_account')
            ->where('del_flag=0 AND state=0' . $condSqlSegment)
            ->queryScalar();

        $rows = $this->dbRO->createCommand()
            ->select('id,vendor_id agency_id,certificate_flag certificateFlag,account_name accountName')
            ->from('bb_account')
            ->where('del_flag=0 AND state=0' . $condSqlSegment)
            ->limit($params['limit'], $params['start'])
            ->queryAll();

        return array('count' => $count, 'rows' => $rows);
    }

    /**
     * 更新上次登录时间
     * @param array $updateParams
     * @param array $condParams
     */
    public function updateLoginInfo($updateParams, $condParams) {
        $cond = 'id=:id AND del_flag=0';
        $params = array(':id' => $condParams['id']);
        $result = $this->dbRW->createCommand()
            ->update('bb_account', array(
            'last_login_ip' => $updateParams['last_login_ip'],
            'last_login_time' => $updateParams['last_login_time'],
        ), $cond, $params);
        if(!empty($result)) {
            return true;
        }else {
            return false;
        }
    }

    public function auditVendorInfo($data) {
        $cond = ' id=:id AND del_flag=0';
        $params = array(':id' => $data['accountId']);
        $result = $this->dbRW->createCommand()
            ->update('bb_account', array(
            'certificate_flag' => $data['certFlag'],
            'update_time' => date('Y-m-d H:i:s'),
        ), $cond, $params);
        if(!empty($result)) {
            return true;
        }else {
            return false;
        }
    }

    /**
     * 删除收客宝账户之收客宝端
     *
     * @author chenjinlong 20121217
     * @param $vendorId
     * @return bool
     */
    public function deleteBuckbeekAccount($vendorId) {
        $udtResult = $this->dbRW->createCommand()->update('bb_account',
            array(
                'del_flag' => 1,
            ),
            'vendor_id=:vendor_id AND del_flag=0',array(':vendor_id'=>$vendorId));
        if($udtResult)
            return true;
        else
            return false;
    }

    public function updateAccountDataLevel($data) {
    	$cond = 'id=:id AND del_flag=0';
    	$params = array(':id' => $data['accountId']);
    	$result = $this->dbRW->createCommand()
    	->update('bb_account', array(
    			'certificate_flag' => $data['certFlag'],
    			'info_complete_rate' => $data['infoCompleteRate'],
    	), $cond, $params);
    	return true;
    }
    
    public function getAccountIdArrByVendorName($vendorName) {
    	$vendorSql = " del_flag = 0 AND account_name LIKE '%" . $vendorName . "%'";
    	$paramsMapSegment[':vendorName'] = $vendorName;
    	$accountId = $this->dbRO->createCommand()
	    	->select('id')
	    	->from('bb_account')
	    	->where($vendorSql, $paramsMapSegment)
	    	->queryAll();
    	return $accountId;
    }

    /**
     * 查询当前所有正常招客宝账户列表
     * (注：管理员后门使用的函数)
     *
     * @author chenjinlong 20131210
     * @return array
     */
    public function getAllInUseAccountIdArr()
    {
        $start = $_GET['start'] > 0 ? intval($_GET['start']) : 0;
        $limit = $_GET['limit'] > 0 ? intval($_GET['limit']) : 1;

        $vendorSql = " del_flag=0 AND state=0";
        $accountIdCol = $this->dbRO->createCommand()
                                ->select('id')
                                ->from('bb_account')
                                ->where($vendorSql)
                                ->limit($limit, $start)
                                ->queryColumn();
        if(!empty($accountIdCol)){
            return $accountIdCol;
        }else{
            return array();
        }
    }

    /**
     * 查询是否拥有跟团、自助游、门票的权限
     * @param $data
     */
    public function getAuthority($data) {
        $cond = ' account_id=:account_id AND del_flag=0';
        $params = array(':account_id' => $data);
        $result = $this->dbRO->createCommand()
            ->select('account_id accountId, is_gt isGt, is_diy isDiy, is_ticket isTicket')
            ->from('bb_account_config')
            ->where($cond, $params)
            ->queryRow();
        if(!empty($result)){
            return $result;
        }else{
            return array();
        }
    }

    /**
     * 分页查询供应商消息信息
     *
     * @author wenrui 20131213
     * @param $data
     * @return array
     */
	public function querryMsg($param){
		// 初始化sql语句
		$sql = "SELECT id,type,content,add_time FROM bb_message WHERE account_id=".$param['accountId']." AND type BETWEEN 5 AND 7 AND del_flag=0 ORDER BY add_time DESC LIMIT ".$param['start'].",".$param['limit'];
		// 查询并返回参数
		return $this->dbRO->createCommand($sql)->queryAll();
	}
	
	/**
     * 计算消息数据的数量
     *
     * @author wenrui 20131213
     * @param 
     * @return array
     */
	public function countMsg($param){
		// 初始化sql语句
		$sql = "SELECT COUNT(*) AS count FROM bb_message WHERE account_id=".$param['accountId']." AND type BETWEEN 5 AND 7 AND del_flag=0";
		// 查询并返回参数
		$count = $this->dbRO->createCommand($sql)->queryAll();
		return $count[0]['count'];
	}
	
	/**
     * 查询供应商充值记录
     *
     * @author wenrui 20131225
     * @param $param
     * @return array
     */
	public function querryRechargeHist($param){
		// 初始化对象
		$client = new RESTClient;
		// 调用对象的链接
		$url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyRecharge/';
		// 接口调用传参格式化
		$params = array(
            'func' => 'querryRechargeHist',
            'params' => $param,
        );
		try{
			// 调用财务查询充值记录接口
            $respArr = $client->get($url, $params);
            if($respArr['success']){
            	// 返回成功数据
                return $respArr['data'];
            }else{
            	CommonSysLogMod::log(__FUNCTION__, '财务查询供应商充值历史记录-查询失败', 1, 'wenrui', 0, 0, json_encode($url), json_encode($params));
            	// 返回空数组
                return array();
            }
        }catch (Exception $e){
            CommonSysLogMod::log(__FUNCTION__, '财务查询供应商充值历史记录-捕获异常', 1, 'wenrui', 0, 0, json_encode($url), json_encode($params), json_encode($e->getTraceAsString()));
            return array();
        }
	}

    /**
     * 查询所有供应商品牌名为空的供应商信息
     *
     * @author huangxun 20130114
     * @return $result
     */
    public function getVendorIdByPage(){
        $info = $this->dbRO->createCommand()
            ->select('vendor_id vendorId')
            ->from('bb_account')
            ->where(' del_flag=0 and brand_name=""')
            ->queryAll();
        return $info;
    }

    /**
     * 更新供应商品牌名
     *
     * @author huangxun 20130114
     * @return $result
     */
    public function updateBrandName($params){
        $cond = 'vendor_id=:vendorId';
        $param = array(':vendorId' => $params['id']);
        try {
            $this->dbRW->createCommand()->update('bb_account', array(
                'brand_name' => $params['brandName'],// 供应商品牌名
                'update_time' => date('Y-m-d H:i:s'),
            ), $cond, $param);
        } catch (Exception $e) {
            Yii::log($e);
            return false;
        }
        return true;
    }
    
    /**
     * 查询供应商预算信息
     */
    public function queryAgencybudget($params){
    	try {
    		// 预初始化动态SQL
    		$dySql = '';
			// 若有登录名查询条件，则初始化SQL查询条件
			if ((empty($params['subAgency']) || '' == $params['subAgency']) && !$params['isFather']) {
				// 默认子账号精确查询
				$dySql = " AND login_name = '".$params['subAgencyDefault']."'";
			} else if (!empty($params['subAgency']) && '' != $params['subAgency'] && $params['isFather']) {
				// 父账号模糊查询
				$dySql = " AND login_name like '%".$params['subAgency']."%'";
			} else if (!empty($params['subAgency']) && '' != $params['subAgency'] && !$params['isFather']) {
				// 子账号精确查询
				$dySql = " AND login_name = '".$params['subAgency']."'";
			}
    		// 初始化SQL语句
			$sql = "SELECT
					  id
					, agency_id AS agencyId
					, account_id AS accountId
					, login_name AS subAgency
					, FLOOR(balance) AS balance
					, FLOOR(available_balance) AS availiableBalance
					, ROUND(balance - consumption) AS totalBalance
					, ROUND(balance - consumption - available_balance) AS frozenBalance
					, ROUND(consumption) as consumption
					, FLOOR(coupon_balance) AS couponBalance
					, FLOOR(coupon_available_balance) AS couponAvailiableBalance
					, ROUND(coupon_balance - coupon_consumption) AS couponTotalBalance
					, ROUND(coupon_balance - coupon_consumption - coupon_available_balance) AS couponFrozenBalance
					, ROUND(coupon_consumption) as couponConsumption
					, IF((balance - consumption - available_balance = 0)&&(coupon_balance - coupon_consumption - coupon_available_balance = 0), 0, 1) AS delStatus
					FROM bb_sub_account_fmis WHERE del_flag = 0 AND account_id = ".$params['accountId'].$dySql." ORDER BY update_time DESC LIMIT ".$params['start'].", ".$params['limit'];
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryAll();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row;
			} else {
				// 为空，返回空数组
				return array ();
			}
    	} catch(Exception $e) {
			// 抛异常
			throw $e;
		}
    }
    
	/**
     * 查询供应商预算信息数量
     */
    public function queryAgencybudgetCount($params){
    	try {
    		// 预初始化动态SQL
    		$dySql = '';
    		// 若有登录名查询条件，则初始化SQL查询条件
			if ((empty($params['subAgency']) || '' == $params['subAgency']) && !$params['isFather']) {
				// 默认子账号精确查询
				$dySql = " AND login_name = '".$params['subAgencyDefault']."'";
			} else if (!empty($params['subAgency']) && '' != $params['subAgency'] && $params['isFather']) {
				// 父账号模糊查询
				$dySql = " AND login_name like '%".$params['subAgency']."%'";
			} else if (!empty($params['subAgency']) && '' != $params['subAgency'] && !$params['isFather']) {
				// 子账号精确查询
				$dySql = " AND login_name = '".$params['subAgency']."'";
			}
    		// 初始化SQL语句
			$sql = "SELECT count(*) as count FROM bb_sub_account_fmis WHERE del_flag = 0 AND account_id = ".$params['accountId'].$dySql;
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row['count'];
			} else {
				// 为空，返回0
				return 0;
			}
    	} catch(Exception $e) {
			// 抛异常
			throw $e;
		}
    }
    
    /**
     * 插入供应商预算信息
     */
    public function insertAgencybudget($params){
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 初始化SQL语句
			$sql = "SELECT count(*) as count FROM bb_sub_account_fmis WHERE del_flag = 0 and login_name = '".$params['subAgency']."'";
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 若有重复数据，则返回false
			if (0 < $row['count']) {
				// 提交事务会真正的执行数据库操作
    			$transaction->commit();
				// 返回false
				return false;
			} else {
				// 没有重复数据，执行插入
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_fmis', array(
					'agency_id' => $params['agencyId'], 
	        		'account_id' => $params['accountId'], 
					'login_name' => $params['subAgency'], 
					'balance' => $params['balance'], 
					'available_balance' => $params['balance'], 
					'consumption' => 0,
					'del_flag' => 0,
					'add_uid' => $params['accountId'], 
					'add_time' => date('y-m-d H:i:s',time()), 
					'update_uid' => $params['accountId'],
					'update_time' => date('y-m-d H:i:s',time()),
					'misc' => '',
					'coupon_balance' => $params['couponBalance'],
					'coupon_available_balance' => $params['couponBalance'],
					'coupon_consumption' => 0
        	 	));
			}
			// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => 0, 
        		'account_id' => $params['accountId'], 
				'type' => 3, 
				'content' => "{"
							."\"content\":\"".$params['agencyId']."为".$params['subAgency']."创建了预算账户<br/>上次可用金额：0牛币(0牛币+0赠币)<br/>当前可用金额：".(round($params['balance'])+round($params['couponBalance']))."牛币(".round($params['balance'])."牛币+".round($params['couponBalance'])."赠币)<br/>上次预算：0牛币(0牛币+0赠币)<br/>当前预算：".(round($params['balance'])+round($params['couponBalance']))."牛币(".round($params['balance'])."牛币+".round($params['couponBalance'])."赠币)\","
							."\"login_name\":\"".$params['subAgency']."\","
							."\"agencyId\":".$params['agencyId'].","
							."\"balance\":".$params['balance']
							."}",
				'login_name' => $params['subAgency'],
				'add_uid' => $params['accountId'], 
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
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
		// 操作成功返回true
    	return true;
    }
    
    /**
     * 更新供应商预算信息
     */
    public function updateAgencybudget($params){
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 初始化SQL语句
			$sql = "SELECT FLOOR(balance) as balance, FLOOR(available_balance) as available_balance, FLOOR(coupon_balance) as coupon_balance, FLOOR(coupon_available_balance) as coupon_available_balance FROM bb_sub_account_fmis WHERE del_flag = 0 and id = '".$params['id']."'";
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 设置总预算
			$balance = floatval($params['balance']) + floatval($row['balance']) - floatval($row['available_balance']);
			$couponBalance = floatval($params['couponBalance']) + floatval($row['coupon_balance']) - floatval($row['coupon_available_balance']);
    		// 初始化过滤条件
    	    $cond = 'id=:id AND del_flag=0';
    		$param = array(':id' => $params['id']);
    		
    		// 更新数据库
    		$result = $this->dbRW->createCommand()->update('bb_sub_account_fmis', array(
    			'balance' => intval($balance),
				'available_balance' => intval($params['balance']),
				'coupon_balance' => intval($couponBalance),
				'coupon_available_balance' => intval($params['couponBalance']), 
    			'update_uid' => $params['accountId'],
				'update_time' => date('y-m-d H:i:s',time()),
    		), $cond, $param);
    		
    		// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => 0, 
        		'account_id' => $params['accountId'], 
				'type' => 3, 
				'content' => "{\"content\":\"".$params['subAgency']."预算账户被分配了预算<br/>上次可用金额：".(round($row['available_balance'])+round($row['coupon_available_balance']))."牛币(".round($row['available_balance'])."牛币+".round($row['coupon_available_balance'])."赠币)<br/>当前可用金额：".(round($params['balance'])+round($params['couponBalance']))."牛币(".round($params['balance'])."牛币+".round($params['couponBalance'])."赠币)<br/>上次预算：".(round($row['balance'])+round($row['coupon_balance']))."牛币(".round($row['balance'])."牛币+".round($row['coupon_balance'])."赠币)<br/>当前预算：".(round($balance)+round($couponBalance))."牛币(".round($balance)."牛币+".round($couponBalance)."赠币)\"}", 
				'login_name' => $params['subAgency'],
				'add_uid' => $params['accountId'], 
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
       	 	));
    		// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 删除供应商预算
     */
    public function deleteAgencybudget($param){
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 查询供应商冻结状况
    		$sql = "SELECT IF((balance - available_balance - consumption = 0)&&(coupon_balance - coupon_available_balance - coupon_consumption = 0), 0, 1) AS flag  FROM  bb_sub_account_fmis WHERE id = ".$param['id'];
    	    // 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 如果flag不为0，则说明有冻结，不给删除
			if (1 == $row['flag']) {
				// 提交事务会真正的执行数据库操作
    			$transaction->commit();
    			// 返回失败标记 
				return false;
			}	
			// 查询供应商冻结状况
    		$sql = "SELECT login_name FROM bb_sub_account_fmis WHERE id = ".$param['id'];
    	    // 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 初始化日志删除过滤条件
    		$cond = 'login_name=:login_name';
    		$params = array(':login_name' => $row['login_name']);
	    	// 更新数据库进行日志删除
    		$result = $this->dbRW->createCommand()->update('bb_sub_account_log', array(
    			'del_flag' => 1
    		), $cond, $params);    	
			// 初始化账号删除过滤条件
    		$cond = 'id=:id AND del_flag=0';
    		$params = array(':id' => $param['id']);
	    	// 更新数据库进行账号删除
    		$result = $this->dbRW->createCommand()->update('bb_sub_account_fmis', array(
    			'del_flag' => 1,  
    			'update_uid' => $param['accountId'],
				'update_time' => date('y-m-d H:i:s',time()),
    		), $cond, $params);
    	    // 提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 查询已分配预算总额
     */
    public function queryBudgetTotal($params){
    	try {
    		// 初始化SQL语句
			$sql = "SELECT FLOOR(SUM(available_balance)) AS balance,FLOOR(SUM(coupon_available_balance)) AS coupon_balance FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id = ".$params['agencyId']." AND login_name != '".$params['subAgency']."'";
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row;
			} else {
				// 为空，返回0
				return 0;
			}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }    
    
    /**
     * 获取一个父供应商的所有子供应商的可用金额总和 
     */
    public function queryAgencyTotalBudget($agencyID){
    	try {
    		// 初始化SQL语句
			$sql = "SELECT IFNULL(SUM(available_balance), 0) as available_balance,IFNULL(SUM(coupon_available_balance), 0) as coupon_available_balance FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id = ".$agencyID;
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return array('niu'=>$row['available_balance'],'coupon'=>$row['coupon_available_balance']);
			} else {
				// 为空，返回0
				return 0;
			}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }
    
    /**
     * 查询子账余额
     */
    public function querySubAgencyTotalBudget($subAgency){
    	try {
    		// 初始化SQL语句
			$sql = "SELECT available_balance,coupon_available_balance FROM bb_sub_account_fmis WHERE del_flag = 0 AND login_name = '".$subAgency."'";
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return array('niu'=>$row['available_balance'],'coupon'=>$row['coupon_available_balance']);
			} else {
				// 为空，返回0
				return 0;
			}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }
    public function queryOverAgencyTotalBudgetCoupon($params){
    	try {
    		if($params['agencyIdArr']){
    			// 初始化SQL语句
				$sql = "SELECT agency_id, SUM(coupon_available_balance) as coupon_available_balance FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id in (".$params['agencyIdArr'].") group by agency_id";
				// 查询并返回参数
				$row = $this->dbRW->createCommand($sql)->queryAll();
				// 判断返回结果是否为空
				if (!empty ($row) && is_array($row)) {
					// 不为空，返回查询结果
					return $row;
				} else {
					// 为空，返回空
					return array();
				}
    		}else{
    			return array();
    		}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }
    
    /**
     * 获取供应商总余额
     */
    public function queryOverAgencyTotalBudget($params){
    	try {
    		if($params['agencyIdArr']){
    			// 初始化SQL语句
				$sql = "SELECT agency_id, SUM(available_balance) as available_balance FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id in (".$params['agencyIdArr'].") group by agency_id";
				// 查询并返回参数
				$row = $this->dbRW->createCommand($sql)->queryAll();
				// 判断返回结果是否为空
				if (!empty ($row) && is_array($row)) {
					// 不为空，返回查询结果
					return $row;
				} else {
					// 为空，返回空
					return array();
				}
    		}else{
    			return array();
    		}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }
    
    /**
     * 过期供应商子账户余额
     */
    public function overAgencyBudget($params, $imbalance) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 初始化SQL语句查询供应商子账户详细信息
			$sql = "SELECT id, agency_id, account_id, login_name, balance, available_balance, consumption FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id = ".$params['agency_id']." order by update_time asc";
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryAll();
			// 初始化需要削减的差额
			$toImbalance = $params['amt'];
			// 循环过期供应商子账户余额
			foreach($row as $rowObj) {
				// 初始化过滤条件
    			$cond = 'id=:id AND del_flag=0';
    			$params = array(':id' => $rowObj['id']);
				// 判断每个供应商的余额是否够扣
				if ($toImbalance > $rowObj['available_balance']) {
					// 不够扣，将该子账号余额置0，累计消费金额直接加原有余额
					// 更新数据库，扣款
    				$result = $this->dbRW->createCommand()->update('bb_sub_account_fmis', array(
    					'available_balance' => 0,  
    					'consumption' => ($rowObj['consumption'] + $rowObj['available_balance']),
						'update_time' => date('y-m-d H:i:s',time()),
    				), $cond, $params);
    				// 削减差额
    				$toImbalance = $toImbalance - $rowObj['available_balance'];
    				// 插入子供应商管理日志
					$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
						'bid_id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 6, 
						'content' => "{\"content\":\"".$rowObj['login_name']."发生了财务过期<br/>上次可用牛币：".round($rowObj['available_balance'])."牛币<br/>当前可用牛币：0牛币<br/>过期金额：".round($rowObj['available_balance'])."牛币\"}", 
						'login_name' => $rowObj['login_name'],
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
   	 				// 插入消息表日志
					$result = $this->dbRW->createCommand()->insert('bb_message', array(
						'id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 8, 
						'content' => $rowObj['login_name']."发生了财务过期，过期金额：".round($rowObj['available_balance'])."牛币。", 
						'amount' => 0,
						'del_flag' => 0,
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
				} else {
					// 够扣，扣完，跳出循环，该供应商扣款结束
			    	// 更新数据库，扣款
    				$result = $this->dbRW->createCommand()->update('bb_sub_account_fmis', array(
    					'available_balance' => ($rowObj['available_balance'] - $toImbalance),  
    					'consumption' => ($rowObj['consumption'] + $toImbalance),
						'update_time' => date('y-m-d H:i:s',time()),
    				), $cond, $params); 
    			    // 插入子供应商管理日志
					$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
						'bid_id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 6, 
						'content' => "{\"content\":\"".$rowObj['login_name']."发生了财务过期<br/>上次可用牛币：".round($rowObj['available_balance'])."牛币<br/>当前可用牛币：".round($rowObj['available_balance'] - $toImbalance)."牛币<br/>过期金额：".round($toImbalance)."牛币\"}", 
						'login_name' => $rowObj['login_name'],
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
   	 				// 插入消息表日志
					$result = $this->dbRW->createCommand()->insert('bb_message', array(
						'id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 8, 
						'content' => $rowObj['login_name']."发生了财务过期，过期金额：".round($toImbalance)."牛币。", 
						'amount' => 0,
						'del_flag' => 0,
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
    				// 跳出循环
    				break;
				}
			}
    		// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
	}
    
    public function overAgencyBudgetCoupon($params, $imbalance) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 初始化SQL语句查询供应商子账户详细信息
			$sql = "SELECT id, agency_id, account_id, login_name, coupon_balance, coupon_available_balance, coupon_consumption FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id = ".$params['agency_id']." order by update_time asc";
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryAll();
			// 初始化需要削减的差额
			$toImbalance = $params['amt'];
			// 循环过期供应商子账户余额
			foreach($row as $rowObj) {
				// 初始化过滤条件
    			$cond = 'id=:id AND del_flag=0';
    			$params = array(':id' => $rowObj['id']);
				// 判断每个供应商的余额是否够扣
				if ($toImbalance > $rowObj['coupon_available_balance']) {
					// 不够扣，将该子账号余额置0，累计消费金额直接加原有余额
					// 更新数据库，扣款
    				$result = $this->dbRW->createCommand()->update('bb_sub_account_fmis', array(
    					'coupon_available_balance' => 0,  
    					'coupon_consumption' => ($rowObj['coupon_consumption'] + $rowObj['coupon_available_balance']),
						'update_time' => date('y-m-d H:i:s',time()),
    				), $cond, $params);
    				// 削减差额
    				$toImbalance = $toImbalance - $rowObj['coupon_available_balance'];
    				// 插入子供应商管理日志
					$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
						'bid_id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 6, 
						'content' => "{\"content\":\"".$rowObj['login_name']."发生了财务过期<br/>上次可用赠币：".round($rowObj['coupon_available_balance'])."赠币<br/>当前可用赠币：0赠币<br/>过期金额：".round($rowObj['coupon_available_balance'])."赠币\"}", 
						'login_name' => $rowObj['login_name'],
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
   	 				// 插入消息表日志
					$result = $this->dbRW->createCommand()->insert('bb_message', array(
						'id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 8, 
						'content' => $rowObj['login_name']."发生了财务过期，过期金额：".round($rowObj['coupon_available_balance'])."赠币。", 
						'amount' => 0,
						'del_flag' => 0,
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
				} else {
					// 够扣，扣完，跳出循环，该供应商扣款结束
			    	// 更新数据库，扣款
    				$result = $this->dbRW->createCommand()->update('bb_sub_account_fmis', array(
    					'coupon_available_balance' => ($rowObj['coupon_available_balance'] - $toImbalance),  
    					'coupon_consumption' => ($rowObj['coupon_consumption'] + $toImbalance),
						'update_time' => date('y-m-d H:i:s',time()),
    				), $cond, $params); 
    			    // 插入子供应商管理日志
					$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
						'bid_id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 6, 
						'content' => "{\"content\":\"".$rowObj['login_name']."发生了财务过期<br/>上次可用赠币：".round($rowObj['coupon_available_balance'])."赠币<br/>当前可用赠币：".round($rowObj['coupon_available_balance'] - $toImbalance)."赠币<br/>过期金额：".round($toImbalance)."赠币\"}", 
						'login_name' => $rowObj['login_name'],
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
   	 				// 插入消息表日志
					$result = $this->dbRW->createCommand()->insert('bb_message', array(
						'id' => 0,
       					'account_id' => $rowObj['account_id'], 
						'type' => 8, 
						'content' => $rowObj['login_name']."发生了财务过期，过期金额：".round($toImbalance)."赠币。", 
						'amount' => 0,
						'del_flag' => 0,
						'add_uid' => $rowObj['account_id'], 
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
   	 				));
    				// 跳出循环
    				break;
				}
			}
    		// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
	}
    
    /**
     * 冻结供应商子账户
     */
    public function freezeSubAgency($param) {
    	// 初始化日志ID变量
    	$lastId = '';
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 预初始化刚刚插入日志的ID
       	 	$lastId = 0; 
       	 	// 初始化SQL语句查询供应商子账户详细信息
			$sql = "SELECT id, agency_id, account_id, login_name, balance, available_balance, consumption, coupon_balance, coupon_available_balance, coupon_consumption FROM bb_sub_account_fmis WHERE del_flag = 0 AND login_name = '".$param['login_name']."' and agency_id = ".$param['agency_id'];
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
    		// 判断是不是第一次出价
    		if (empty($param['bid_id']) || 0 == $param['bid_id']) {
    			// 第一次出价
    			// 初始化更新账户余额SQL
  				$sql = "update bb_sub_account_fmis set available_balance = available_balance - ".$param['amt_niu'].",coupon_available_balance = coupon_available_balance - ".$param['amt_coupon']." where del_flag = 0 and login_name = '".$param['login_name']."' and agency_id = ".$param['agency_id'];
  				// 更新账户余额
				$result = $this->dbRW->createCommand($sql)->execute();
				// 插入日志
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
					'bid_id' => $param['bid_id'],
       				'account_id' => $param['account_id'], 
					'type' => 4, 
					'content' => "{\"content\":\"".$param['login_name']."发生了财务冻结<br/>广告位：".$param['ad_name']."<br/>上次可用金额：".(round($row['available_balance'])+round($row['coupon_available_balance']))."牛币(".round($row['available_balance'])."牛币+".round($row['coupon_available_balance'])."赠币)<br/>当前可用金额：".(round(floatval($row['available_balance']) - floatval($param['amt_niu']))+round(floatval($row['coupon_available_balance']) - floatval($param['amt_coupon'])))."牛币(".round(floatval($row['available_balance']) - floatval($param['amt_niu']))."牛币+".round(floatval($row['coupon_available_balance']) - floatval($param['amt_coupon']))."赠币)<br/>当前冻结：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>当前解冻：0牛币(0牛币+0赠币)<br/>排名：".$param['ranking']."\"}", 
					'login_name' => $param['login_name'],
					'add_uid' => $param['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()),
					'misc' => ''
	       	 	));
	       	 	// 获取刚刚插入日志的ID
       	 		$lastId = $this->dbRW->lastInsertID;
       	 		// 提交事务会真正的执行数据库操作
    			$transaction->commit();
    			// 冻结成功返回最后插入的ID
				return $lastId;
    		}
			// 初始化SQL语句查询冻结前金额属于哪个账户    		
			$sql = "SELECT login_name, account_id FROM bid_bid_product WHERE id = ".$param['bid_id'];
			// 查询并返回参数
			$loginName = $this->dbRW->createCommand($sql)->queryRow();
			// 预初始化冻结差额
			$imbalanceNiu = 0;
			// 判断这条冻结记录是否是同一账号下这个登录名的
			if (0 == strcmp($loginName['login_name'], $param['login_name']) && 0 == strcmp($loginName['account_id'], $param['account_id'])) {
				// 属于同一账号下同一个登录名
				// 计算冻结差额
				$imbalanceNiu = $param['amt_niu'] - $param['old_amt_niu'];
				$imbalanceCoupon = $param['amt_coupon'] - $param['old_amt_coupon'];
				// 判断是否需要操作数据库，冻结子供应商账户
	    		if (0 != $imbalanceNiu) {
    				// 初始化更新账户余额SQL
    				$sql = "update bb_sub_account_fmis set available_balance = available_balance - ".$imbalanceNiu.",coupon_available_balance = coupon_available_balance - ".$imbalanceCoupon." where del_flag = 0 and login_name = '".$param['login_name']."' and agency_id = ".$param['agency_id'];
    				// 更新账户余额
					$result = $this->dbRW->createCommand($sql)->execute();
	    		}
    			// 插入日志
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
					'bid_id' => $param['bid_id'],
       				'account_id' => $param['account_id'], 
					'type' => 4, 
					'content' => "{\"content\":\"".$param['login_name']."发生了财务冻结<br/>广告位：".$param['ad_name']."<br/>上次可用金额：".(round($row['available_balance'])+round($row['coupon_available_balance']))."牛币(".round($row['available_balance'])."牛币+".round($row['coupon_available_balance'])."赠币)<br/>当前可用金额：".(round(floatval($row['available_balance']) - floatval($imbalanceNiu))+round(floatval($row['coupon_available_balance']) - floatval($imbalanceCoupon)))."牛币(".round(floatval($row['available_balance']) - floatval($imbalanceNiu))."牛币+".round(floatval($row['coupon_available_balance']) - floatval($imbalanceCoupon))."赠币)<br/>当前冻结：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>当前解冻：".(round($param['old_amt_niu'])+round($param['old_amt_coupon']))."牛币(".round($param['old_amt_niu'])."牛币+".round($param['old_amt_coupon'])."赠币)<br/>排名：".$param['ranking']."\"}", 
					'login_name' => $param['login_name'],
					'add_uid' => $param['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()),
					'misc' => ''
	       	 	));
	       	 	// 获取刚刚插入日志的ID
       	 		$lastId = $this->dbRW->lastInsertID;
			} else if (0 != strcmp($loginName['login_name'], $param['login_name']) && 0 == strcmp($loginName['account_id'], $param['account_id'])) {
				// 设置产品编号
				if (empty($param['product_id'])) {
					$param['product_id'] = 0;
				}
				// 同一账号下的不同登录名
				// 计算冻结差额
				$imbalanceNiu = $param['amt_niu'];
				$imbalanceCoupon = $param['amt_coupon'];
				// 解冻上一个登录名
				// 初始化解冻上一个登录名SQL
    			$sql = "update bb_sub_account_fmis set available_balance = available_balance + ".$param['old_amt_niu'].",coupon_available_balance = coupon_available_balance + ".$param['old_amt_coupon']." where del_flag = 0 and login_name = '".$loginName['login_name']."' and account_id = ".$loginName['account_id'];
    			// 更新账户余额
				$result = $this->dbRW->createCommand($sql)->execute();
    			// 插入日志
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
					'bid_id' => $param['bid_id'], 
        			'account_id' => $loginName['account_id'], 
					'type' => 7, 
					'content' => "{\"content\":\"".$loginName['login_name']."发生了财务解冻<br/>广告位：".$param['ad_name']."<br/>当前解冻：".(round($param['old_amt_niu'])+round($param['old_amt_coupon']))."牛币(".round($param['old_amt_niu'])."牛币+".round($param['old_amt_coupon'])."赠币)<br/>产品编号：".$param['product_id']."<br/>排名：".$param['ranking']."\"}", 
					'login_name' => $loginName['login_name'],
					'add_uid' => $loginName['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()),
					'misc' => ''
       	 		));
       	 		// 冻结这个登录名
   	 			// 初始化更新账户余额SQL
  				$sql = "update bb_sub_account_fmis set available_balance = available_balance - ".$imbalanceNiu.",coupon_available_balance = coupon_available_balance - ".$imbalanceCoupon." where del_flag = 0 and login_name = '".$param['login_name']."' and agency_id = ".$param['agency_id'];
  				// 更新账户余额
				$result = $this->dbRW->createCommand($sql)->execute();
				// 插入日志
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
					'bid_id' => $param['bid_id'],
       				'account_id' => $param['account_id'], 
					'type' => 4, 
					'content' => "{\"content\":\"".$param['login_name']."发生了财务冻结<br/>广告位：".$param['ad_name']."<br/>上次可用金额：".(round($row['available_balance'])+round($row['coupon_available_balance']))."牛币(".round($row['available_balance'])."牛币+".round($row['coupon_available_balance'])."赠币)<br/>当前可用金额：".(round(floatval($row['available_balance']) - floatval($imbalanceNiu))+round(floatval($row['coupon_available_balance']) - floatval($imbalanceCoupon)))."牛币(".round(floatval($row['available_balance']) - floatval($imbalanceNiu))."牛币+".round(floatval($row['coupon_available_balance']) - floatval($imbalanceCoupon))."赠币)<br/>当前冻结：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>当前解冻：0牛币(0牛币+0赠币)<br/>排名：".$param['ranking']."\"}", 
					'login_name' => $param['login_name'],
					'add_uid' => $param['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()),
					'misc' => ''
	       	 	));
	       	 	// 获取刚刚插入日志的ID
       	 		$lastId = $this->dbRW->lastInsertID;
			} else if (0 == strcmp($loginName['account_id'], $param['account_id']) && (0 == strcmp($loginName['login_name'], $row['agency_id']) || 0 == strcmp($loginName['login_name'], 'admin@'.$row['agency_id']))) {
				// 属于同一账号，但上一次出价冻结是父供应商
				// 计算冻结差额
				$imbalanceNiu = $param['amt_niu'];
				$imbalanceCoupon = $param['amt_coupon'];
				// 冻结这个登录名
   	 			// 初始化更新账户余额SQL
  				$sql = "update bb_sub_account_fmis set available_balance = available_balance - ".$imbalanceNiu.",coupon_available_balance = coupon_available_balance - ".$imbalanceCoupon." where del_flag = 0 and login_name = '".$param['login_name']."' and agency_id = ".$param['agency_id'];
  				// 更新账户余额
				$result = $this->dbRW->createCommand($sql)->execute();
				// 插入日志
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
					'bid_id' => $param['bid_id'],
       				'account_id' => $param['account_id'], 
					'type' => 4, 
					'content' => "{\"content\":\"".$param['login_name']."发生了财务冻结<br/>广告位：".$param['ad_name']."<br/>上次可用金额：".(round($row['available_balance'])+round($row['coupon_available_balance']))."牛币(".round($row['available_balance'])."牛币+".round($row['coupon_available_balance'])."赠币)<br/>当前可用金额：".(round(floatval($row['available_balance']) - floatval($imbalanceNiu))+round(floatval($row['coupon_available_balance']) - floatval($imbalanceCoupon)))."牛币(".round(floatval($row['available_balance']) - floatval($imbalanceNiu))."牛币+".round(floatval($row['coupon_available_balance']) - floatval($imbalanceCoupon))."赠币)<br/>当前冻结：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>当前解冻：0牛币(0牛币+0赠币)<br/>排名：".$param['ranking']."\"}", 
					'login_name' => $param['login_name'],
					'add_uid' => $param['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()),
					'misc' => ''
	       	 	));
	       	 	// 获取刚刚插入日志的ID
       	 		$lastId = $this->dbRW->lastInsertID;
			}
    		// 提交事务会真正的执行数据库操作
    		$transaction->commit();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 抛异常
            throw $e;
		}
		// 冻结成功返回最后插入的ID
		return $lastId;
    }
    
    /**
     * 解冻子账户冻结金额
     */
    public function unfreezeSubAgency($loginName, $amtNiu, $amtCoupon, $failItem, $bidId) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 设置产品编号
			if (empty($failItem['product_id'])) {
				$failItem['product_id'] = 0;
			}
    		// 初始化更新账户余额SQL
    		$sql = "update bb_sub_account_fmis set available_balance = available_balance + ".$amtNiu.",coupon_available_balance=coupon_available_balance+".$amtCoupon." where del_flag = 0 and login_name = '".$loginName."' and account_id = ".$failItem['account_id'];
    		// 更新账户余额
			$result = $this->dbRW->createCommand($sql)->execute();
			// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => $bidId, 
        		'account_id' => $failItem['account_id'], 
				'type' => 7, 
				'content' => "{\"content\":\"".$loginName."发生了财务解冻<br/>广告位：".$failItem['ad_name']."<br/>当前解冻：".(round($amtNiu)+round($amtCoupon))."牛币(".round($amtNiu)."牛币+".round($amtCoupon)."赠币)<br/>产品编号：".$failItem['product_id']."<br/>排名：--\"}", 
				'login_name' => $loginName,
				'add_uid' => $failItem['account_id'], 
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
       	 	));
    		// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 对子供应商进行扣款和解冻
     */
    public function dedcutSubAgencyAccount($param) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 设置产品编号
			if (empty($param['product_id'])) {
				$param['product_id'] = 0;
			}
			// 设置产品类型
			if (empty($param['product_type'])) {
				$param['product_type'] = 0;
			}
			// 设置出发城市
			if (empty($param['start_city_code'])) {
				$param['start_city_code'] = 0;
			}
			// 设置分类ID
			if (empty($param['web_class'])) {
				$param['web_class'] = 0;
			}
			// 设置排名
			if (empty($param['ranking'])) {
				$param['ranking'] = '';
			} else {
				$param['ranking'] = "<br/>排名：".$param['ranking']; 
			}
			
    		// 初始化需要退还的金额
    		$toReturnNiu = $param['limit_amt_niu'] - $param['amt_niu'];
    		$toReturnCoupon = $param['limit_amt_coupon'] - $param['amt_coupon'];
    		// 初始化更新账户余额SQL
    		$sql = "update bb_sub_account_fmis set available_balance = available_balance + ".$toReturnNiu.", consumption = consumption + ".$param['amt_niu'].",coupon_available_balance = coupon_available_balance + ".$toReturnCoupon.", coupon_consumption = coupon_consumption + ".$param['amt_coupon']." where del_flag = 0 and login_name = '".$param['login_name']."' and account_id = ".$param['account_id'];
    		// 更新账户余额
			$result = $this->dbRW->createCommand($sql)->execute();	
			// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => $param['bid_id'], 
        		'account_id' => $param['account_id'], 
				'type' => 5, 
				'content' => "{\"content\":\"".$param['login_name']."在推广中发生了财务扣款<br/>广告位：@@@<br/>当前出价：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>当前最高出价：".(round($param['limit_amt_niu'])+round($param['limit_amt_coupon']))."牛币(".round($param['limit_amt_niu'])."牛币+".round($param['limit_amt_coupon'])."赠币)<br/>当前扣款：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>当前解冻：".(round($toReturnNiu)+$toReturnCoupon)."牛币(".round($toReturnNiu)."牛币+".$toReturnCoupon."赠币)<br/>产品编号：".$param['product_id']."<br/>排名：".$param['ranking']."\",
							\"product_id\":".$param['product_id'].",
							\"product_type\":".$param['product_id'].",
							\"search_keyword\":\"".$param['search_keyword']."\",
							\"ad_key\":\"".$param['ad_key']."\",
							\"web_class\":".$param['web_class'].",
							\"start_city_code\":".$param['start_city_code']."
							}",
				'login_name' => $param['login_name'],
				'add_uid' => $param['account_id'],
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
       	 	));
			// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 	 
    	} catch (Exception $e) {
    		// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 对子供应商进行解冻
     */
    public function unfreezeSubAgencyAccount($param) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 设置产品编号
			if (empty($param['product_id'])) {
				$param['product_id'] = 0;
			}
			// 设置产品类型
			if (empty($param['product_type'])) {
				$param['product_type'] = 0;
			}
			// 设置出发城市
			if (empty($param['start_city_code'])) {
				$param['start_city_code'] = 0;
			}
			// 设置分类ID
			if (empty($param['web_class'])) {
				$param['web_class'] = 0;
			}
    		// 初始化更新账户余额SQL
    		$sql = "update bb_sub_account_fmis set available_balance = available_balance + ".$param['amt_niu'].",coupon_available_balance = coupon_available_balance + ".$param['amt_coupon']." where del_flag = 0 and login_name = '".$param['login_name']."' and account_id = ".$param['account_id'];
    		// 更新账户余额
			$result = $this->dbRW->createCommand($sql)->execute();	
			// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => $param['bid_id'], 
        		'account_id' => $param['account_id'], 
				'type' => 7, 
				'content' => "{\"content\":\"".$param['login_name']."发生了财务解冻<br/>广告位：@@@<br/>当前解冻：".(round($param['amt_niu'])+round($param['amt_coupon']))."牛币(".round($param['amt_niu'])."牛币+".round($param['amt_coupon'])."赠币)<br/>产品编号：".$param['product_id']."<br/>排名：--\",
							\"product_id\":".$param['product_id'].",
							\"product_type\":".$param['product_id'].",
							\"search_keyword\":\"".$param['search_keyword']."\",
							\"ad_key\":\"".$param['ad_key']."\",
							\"web_class\":".$param['web_class'].",
							\"start_city_code\":".$param['start_city_code'].",
							\"flag\":1}",
				'login_name' => $param['login_name'],
				'add_uid' => $param['account_id'],
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
       	 	));	
			// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 	 
    	} catch (Exception $e) {
    		// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 冻结子账户个性化
     */
    public function freezeSubAgencyVas($subVasFreeze, $param, $amt, $oldAmt, $row) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
			// 纯解冻
			if (!empty($row) && is_array($row)) {
				// 循环插入解冻日志
				foreach ($row as $rowObj) {
					// 初始化判断标记
					$countFlag = 0;
					foreach ($subVasFreeze as $subVasFreezeObj) {
						// 如果bidID匹配，则累加解冻金额
    					if ($subVasFreezeObj['bidId'] != $rowObj['bid_id']) {
    						$countFlag++;
    					}
					}
					// 设置产品编号
					if (empty($rowObj['product_id'])) {
						$rowObj['product_id'] = 0;
					}
					// 若该记录是纯解冻记录，则插入日志
					if ($countFlag == count($subVasFreeze)) {
						// 查询供应商登录名
						$sql = "select login_name, account_id from bid_bid_product where del_flag = 0 and id = ".$rowObj['bid_id'];
		    			// 查询账户初始信息
						$accountInfo = $this->dbRW->createCommand($sql)->queryRow();
						// 初始化更新账户余额SQL
    					$sql = "update bb_sub_account_fmis set available_balance = available_balance + ".$rowObj['bid_price']." where del_flag = 0 and login_name = '".$accountInfo['login_name']."' and account_id = ".$accountInfo['account_id'];
			    		// 更新账户余额
						$result = $this->dbRW->createCommand($sql)->execute();
						// 插入日志
						$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
							'bid_id' => $rowObj['bid_id'], 
        					'account_id' => $accountInfo['account_id'], 
							'type' => 7, 
							'content' => "{\"content\":\"".$accountInfo['login_name']."发生了财务解冻<br/>广告位：".$param['viewName']."<br/>当前解冻：".round($rowObj['bid_price'])."牛币<br/>" .
								"产品编号：".$rowObj['product_id']."<br/>排名：".$rowObj['ranking']."\"}", 
							'login_name' => $accountInfo['login_name'],
							'add_uid' => $accountInfo['account_id'],
							'add_time' => date('y-m-d H:i:s',time()),
							'misc' => ''
       		 			));
					}
				}
			}
    		// 循环添加日志
    		foreach ($subVasFreeze as $subVasFreezeObj) {
    			// 设置解冻金额为0
    			$unfreeze = 0;
    			// 获取匹配的解冻金额
    			foreach ($row as $rowObj) {
    				// 如果bidID匹配，则累加解冻金额
    				if ($subVasFreezeObj['bidId'] == $rowObj['bid_id']) {
    					$unfreeze = $rowObj['bid_price'];
    					// 跳出里层循环
    					break;
    				}
    			}
    			// 若金额不等，则插入日志
    			if ($subVasFreezeObj['price'] != $unfreeze) {
    				// 查询供应商登录名
					$sql = "select login_name, account_id from bid_bid_product where del_flag = 0 and id = ".$subVasFreezeObj['bidId'];
		    		// 查询账户初始信息
					$accountInfo = $this->dbRW->createCommand($sql)->queryRow();
					// 查询该登录名可用金额
					$sql = "select available_balance from bb_sub_account_fmis where del_flag = 0 and login_name = '".$accountInfo['login_name']."' and account_id =".$accountInfo['account_id'];
					// 查询账户初始信息
					$accountInfoBalance = $this->dbRW->createCommand($sql)->queryRow();
					// 初始化账户可用金额临时变量
					$tempAvailable = $accountInfoBalance['available_balance'];
    				// 设置本次余额
    				$tempNowAvailable = $tempAvailable - $subVasFreezeObj['price'] + $unfreeze;
    				// 初始化更新账户余额SQL
    				$sql = "update bb_sub_account_fmis set available_balance = ".$tempNowAvailable." where del_flag = 0 and login_name = '".$accountInfo['login_name']."' and account_id = ".$accountInfo['account_id'];
			    	// 更新账户余额
					$result = $this->dbRW->createCommand($sql)->execute();
					// 插入日志
					$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
						'bid_id' => $subVasFreezeObj['bidId'], 
	        			'account_id' => $accountInfo['account_id'], 
						'type' => 4, 
						'content' => "{\"content\":\"".$accountInfo['login_name']."发生了财务冻结<br/>广告位：".$param['viewName']."<br/>上次可用牛币：".round($tempAvailable)."牛币<br/>" .
							"当前可用牛币：".round($tempNowAvailable)."牛币<br/>当前冻结：".round($subVasFreezeObj['price'])."牛币<br/>当前解冻：".round($unfreeze)."牛币" .
    						"<br/>排名：".$subVasFreezeObj['ranking']."\"}", 
						'login_name' => $accountInfo['login_name'],
						'add_uid' => $accountInfo['account_id'],
						'add_time' => date('y-m-d H:i:s',time()),
						'misc' => ''
	       	 		));	
    			}
    		}	
			// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 	 
    	} catch (Exception $e) {
    		// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 联想查询供应商，校验查询供应商
     */
    public function querySubagencyassociate($param) {
    	try {
			// 初始化SQL语句
			$sql = "SELECT agency_id, login_name FROM bb_sub_account_fmis WHERE del_flag = 0 AND agency_id = ".$param['agencyId'];
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryAll();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row;
			} else {
				// 为空，返回空数组
				return array ();
			}
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
	        throw $e;
		}
    }
    
    /**
     * 解冻子账户冻结金额
     */
    public function handlefreezeSubAgency($param, $bidId) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
    		// 初始化新账户余额SQL
    		$sql = "select login_name, account_id, bid_price, max_limit_price,bid_price_niu,max_limit_price_niu,bid_price_coupon,max_limit_price_coupon from bid_bid_product where id = ".$bidId;
    		// 查询竞价供应商信息
			$bidInfo = $this->dbRW->createCommand($sql)->queryRow();
			// 如果是新增记录或自己的记录，则不处理
			if (!empty($bidInfo) && 0 == strcmp($bidInfo['account_id'], $param['account_id']) && 0 != strcmp($bidInfo['login_name'], $param['login_name'])) {
				// 同一账号下的不同账号竞价同一条记录，对上一竞价者进行解冻
				// 解冻账号
				// 初始化更新账户余额SQL
    			$sql = "update bb_sub_account_fmis set available_balance = available_balance + ".$bidInfo['max_limit_price_niu'].",coupon_available_balance = coupon_available_balance + ".$bidInfo['max_limit_price_coupon']." where del_flag = 0 and login_name = '".$bidInfo['login_name']."' and account_id = ".$bidInfo['account_id'];
			    // 更新账户余额
				$result = $this->dbRW->createCommand($sql)->execute();
				// 预设空产品ID
				if (empty($param['product_id']) || 0 == $param['product_id']) {
					$param['product_id'] = 0;
				}
				// 插入日志
				$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
					'bid_id' => $bidId, 
	        		'account_id' => $bidInfo['account_id'], 
					'type' => 7, 
					'content' => "{\"content\":\"".$bidInfo['login_name']."发生了财务解冻<br/>广告位：".$param['ad_name']."<br/>当前解冻：".(round($bidInfo['max_limit_price_niu'])+round($bidInfo['max_limit_price_coupon']))."牛币(".round($bidInfo['max_limit_price_niu'])."牛币+".round($bidInfo['max_limit_price_coupon'])."赠币)<br/>产品编号：".$param['product_id']."<br/>排名：--\"}", 
					'login_name' => $bidInfo['login_name'],
					'add_uid' => $bidInfo['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()),
					'misc' => ''
       	 		));
			} else {
				// 提交事务会真正的执行数据库操作
    			$transaction->commit(); 
				// 新增记录或自己的记录，不处理
				return array();
			}
    		// 提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 更据竞价ID查询登录名
     */
    public function queryBidLoginName($bid) {
    	// 初始化新账户余额SQL
    	$sql = "select login_name, account_id, bid_price, max_limit_price from bid_bid_product where id = ".$bid;
    	// 查询竞价供应商信息
		$bidInfo = $this->dbRW->createCommand($sql)->queryRow();
		// 返回结果
		return $bidInfo;
	}
    
    /**
     * 插入供应商配置
     */
    public function insertConfigAgency($param) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			foreach ($param['agencyIds'] as $agencyId) {
				// 插入供应商配置
		    	$result = $this->dbRW->createCommand()->insert('agency_config', array(
		            'agency_id' => intval($agencyId),
		            'is_retail' => intval($param['isRetail']),
		            'is_sale' => intval($param['isSale']),
		            'is_whole_sale' => intval($param['isWholeSale']),
		            'is_open' => intval($param['isOpen']),
		            'del_flag' => 0,
		            'add_time' => date('Y-m-d H:i:s'),
		            'update_time' => date('Y-m-d H:i:s')
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
    }
    
    /**
	 * 查询供应商配置
	 */
	public function queryAgencyConfig($param) {
		$agencyCon = array();
		
		try {
			// 初始化SQL
			$sql = "SELECT a.is_open as isOpen FROM agency_config a LEFT JOIN bb_account b ON a.agency_id = b.vendor_id WHERE b.id = ".$param['accountId']." and b.del_flag = 0 and a.del_flag = 0";
			// 查询相关产品
			$agencyCon = $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		// 返回数组
		return $agencyCon;
	}
    
    
}