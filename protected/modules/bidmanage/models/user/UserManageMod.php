<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 4:59 PM
 * Description: UserManageMod.php
 */
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.iao.FmisIao');
Yii::import('application.modules.bidmanage.dal.iao.HagridIao');
Yii::import('application.modules.bidmanage.models.iao.IaoFmisMod');
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');
Yii::import('application.modules.bidmanage.dal.iao.AgencyIao');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.dal.iao.BossIao');

class UserManageMod
{
    //统一定义招客宝登录token过期时间
    const TOKEN_EXPIRE_MINUTES = 30;

    private $manageDao;
    private $_iaoFmisMod;

    function __construct() {
        $this->manageDao = new UserManageDao();
        $this->_iaoFmisMod = new IaoFmisMod;
    }

    public function read($params) {
        $user = $this->manageDao->readUser($params);
        return !empty($user) ? $user : array();
    }

    public function getVendorInfo($accountId) {
        $info = $this->manageDao->getVendorInfo($accountId);
        return !empty($info) ? $info : array();
    }

    public function getAccountInfoByAgentId($vendorId) {
        $accountInfo = $this->manageDao->getAccountInfoByAgentId($vendorId);
        return $accountInfo;
    }

    /**
     * 查询account数字
     */
    public function getAccountInfoByAgentIdArr($vendorId) {
        $accountInfo = $this->manageDao->getAccountInfoByAgentIdArr($vendorId);
        return $accountInfo;
    }

    public function addVerdorAccount($params) {
        $account = $this->manageDao->addUser($params);
        return $account;
    }

    public function getBBIds($params) {
        $BBIds = $this->manageDao->getIds($params);
        return $BBIds;
    }

    public function getVendorList($params) {
        $lists = $this->manageDao->queryList($params);
        return $lists;
    }

    /**
     * 创建token并存储到memcache
     * @param type $jsessionId
     * @return type
     */
    public function createToken($accountId,$jsessionId){
        list($usec, $sec) = explode(" ", microtime());
        $curTime = (float)$usec + (float)$sec;
        $token = md5($jsessionId.$curTime.$accountId);
        $token = substr($token,5,22);
        $minutes = self::TOKEN_EXPIRE_MINUTES;
        Yii::app()->memcache->set($jsessionId,$token,$minutes*60);
        return $token;
    }

    public function update($updateParams, $condParams) {
        $result = $this->manageDao->updateLoginInfo($updateParams, $condParams);
        return $result;
    }

    public function auditVendorInfo($data) {
        $result = $this->manageDao->auditVendorInfo($data);
        return $result;
    }

    /**
     * 删除收客宝账户之整合逻辑
     *
     * @author chenjinlong 20121217
     * @param $vendorId
     * @return array
     */
    public function deleteBuckbeekAccount($vendorId) {
        if($vendorId > 0){
            //检查财务充值历史总额是否为0
            $fmisTotalRechargeVal = $this->_iaoFmisMod->getTotalRechargeValue($vendorId);
            if($fmisTotalRechargeVal == 0){
                $execBBResult = $this->manageDao->deleteBuckbeekAccount(intval($vendorId));
                //重要操作记录日志
                CommonSysLogMod::log(__FUNCTION__, '删除收客宝账户', 1, 'chenjinlong', $vendorId, $fmisTotalRechargeVal, json_encode($execBBResult));
                if($execBBResult){
                    return array(
                        'flag' => true,
                        'msg' => '删除成功',
                    );
                }else{
                    return array(
                        'flag' => false,
                        'msg' => '删除失败',
                    );
                }
            }else{
                return array(
                    'flag' => false,
                    'msg' => '收客宝账户已存在财务充值历史，不可删除',
                );
            }
        }else{
            return array(
                'flag' => false,
                'msg' => '传入参数之供应商编号不合法',
            );
        }
    }

    /**
     * 根据收客宝账户编号，获取对应的二级供应商编号
     *
     * @author chenjinlong 20121210
     * @param $accountId
     * @return int
     */
    public function getVendorIdByAccountId($accountId)
    {
        $accountInfoArr = $this->read(array('id'=>$accountId));
        if(!empty($accountInfoArr) && is_array($accountInfoArr)){
            return $accountInfoArr['vendorId'];
        }else{
            return 0;
        }
    }

    public function getToken($tokenKey){
        $token = Yii::app()->memcache->get($tokenKey);
        return $token;
    }

    public function refreshToken($tokenKey){
        $token = Yii::app()->memcache->get($tokenKey);
        $minutes = self::TOKEN_EXPIRE_MINUTES;
        Yii::app()->memcache->set($tokenKey,$token,$minutes*60);
        return $token;
    }

    /**
     * 根据推广页面位置，查询容纳的推广产品数量
     *
     * @author chenjinlong 20130109
     * @param $adKey
     * @return int
     */
    public function getAdPositionCountByType($adKeyObj)
    {
        $positionRows = $this->manageDao->readAdPosition($adKeyObj);
        return $positionRows['ad_product_count'];
    }

    /**
     * 更新收客宝帐号数据完整度
     * @param unknown_type $dataLevel
     */
    public function updateAccountDataLevel($data) {
    	$result = $this->manageDao->updateAccountDataLevel($data);
    	return $result;
    }
           
        
    public function getAccountIdArrByVendorName($vendorName) {
    	$accountIdArr = $this->manageDao->getAccountIdArrByVendorName($vendorName);
    	return $accountIdArr;
    }
    
    /**
     * 查询是否拥有跟团、自助游、门票的权限
     * @param $data
     */
    public function getAuthority($params) {
        return $this->manageDao->getAuthority($params);
    }

   /**
     * 分页查询供应商消息信息
     *
     * @author wenrui 20131213
     * @param $param
     * @return $result
     */
	public function querryMsg($param){
		$result = $this->manageDao->querryMsg($param);
		return $result;
	}
	
	/**
     * 计算消息数据的数量
     *
     * @author wenrui 20131213
     * @param 
     * @return $count
     */
	public function countMsg($param){
		$count = $this->manageDao->countMsg($param);
		return $count;
	}
	
	/**
	 * 查询供应商充值记录
	 * 
	 * @author wenrui 20131225
     * @param $param
     * @return $result
	 */
	public function querryRechargeHist($param){
		// 查询结果
		$result = $this->manageDao->querryRechargeHist($param);
		// 数组为空直接返回
		if(empty($result)){
			return $result;
		}
		// 初始化数据
		$data = array ();
		// 对查询数据进行过滤
		foreach ($result['rows'] as $resObj) {
			// 对充值类型进行判断
			if ($resObj['isDiscount'] == 20) {
				$resObj['isDiscount'] = '优惠赠送';
			} else if ($resObj['isDiscount'] == 48){
				$resObj['isDiscount'] = '协议充值';
			} else if ($resObj['isDiscount'] == 49){
				$resObj['isDiscount'] = '日常充值';
			} else if ($resObj['isDiscount'] == 19){
				$resObj['isDiscount'] = '推广试用';
			} else if ($resObj['isDiscount'] == 18){
				$resObj['isDiscount'] = '积分兑换';
			} else if ($resObj['isDiscount'] >= 0 && $resObj['isDiscount'] <= 50){
				$resObj['isDiscount'] = '公司折扣';
			} else if ($resObj['isDiscount'] >= 51 && $resObj['isDiscount'] <= 100){
				$resObj['isDiscount'] = '自费充值';
			} else {
				$resObj['isDiscount'] = '其他充值类型';
			}
			array_push($data,$resObj);
		};
		// 将过滤后的数据重新塞进结果数组
		$result['rows'] = $data;
		return $result;
	}

    /**
     * 更新供应商品牌名脚本
     *
     * @author huangxun 20130114
     * @return $result
     */
    public function runBrandNameTask(){
        // 查询所有供应商品牌名为空的供应商信息
        $vendorId = $this->manageDao->getVendorIdByPage();
        $vendorIdArr = array();
        // 拼接供应商编号参数
        foreach($vendorId as $tempArr) {
            array_push($vendorIdArr,$tempArr['vendorId']);
        }
        // 使用循环每次10条来更新数据
        for ($i = 0; $i < count($vendorId); $i = $i + 10) {
            // 调用boss接口获取供应商信息
            $paramVendorIdArr = array_slice($vendorIdArr, $i, 10);
            $param = array('agencyId' => $paramVendorIdArr, 'limit' => -1);
            $res = BossIao::getAgencyAccountList($param);
            if ($res['rows']) {
                $vendorList = $res['rows'];
                // 循环更新供应商信息
                foreach ($vendorList as $tempList) {
                    $result = $this->manageDao->updateBrandName($tempList);
                    if (!$result) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * 获取供应商预算
     */
    public function getAgencybudget($param) {
    	// 预初始化返回结果
    	$result = array();
    	try {
    		// 查询数据库
    		$result['data']['rows'] = $this->manageDao->queryAgencybudget($param);
    		$result['data']['count'] = $this->manageDao->queryAgencybudgetCount($param);
    		// 设置成功编码
    		$result['errorCode']=230000;
    		// 设置成功状态
    		$result['success'] = true;
    		// 设置成功信息
    		$result['msg'] = '查询成功！';
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);    		
    		// 查询发生异常，返回错误数据
    		$result['data']['rows'] = array();
    		$result['data']['count'] = 0;
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '查询失败，数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 新增，修改和删除供应商预算
     */
    public function saveAgencybudget($param) {
    	// 预初始化返回结果
    	$result = array();
		// 根据类型，分类新增，修改和删除供应商预算
		if ('insert' == $param['type']) {
			// 新增
			$result = $this->insertAgencybudget($param['data']);
		} else if ('update' == $param['type']) {
			// 更新
			$result = $this->updateAgencybudget($param['data']);
		} else if ('delete' == $param['type']) {
			// 删除
			$result = $this->deleteAgencybudget($param['data']);
		} else {
			// 参数错误，返回错
			// 查询发生异常，返回错误数据
   			$result['data'] = array();
    		// 设置错误编码
  			$result['errorCode']=230002;
   			// 设置错误状态
   			$result['success'] = false;
    		// 设置错误信息
   			$result['msg'] = '操作失败，参数异常！';	
		}
    	// 返回结果
		return $result;
    }
    
    /**
     * 新增供应商预算
     */
    public function insertAgencybudget($param) {
    	// 初始化空数据
    	$result['data'] = array();
    	try {
    		// 校验子账号是否存在
    		// 初始化调用对象
    		$client = new RESTClient();
    		// 初始化远程调用地址
//        	$url = Yii::app()->params['NB_HOST'] . 'restful/vendor/query-agencysubexists';
        	$url = Yii::app()->params['ADMIN_HOST'] . 'admit/user/query-agencysubexists';
        	// 初始化调用参数
        	$remoteParams = array('loginName'=>$param['subAgency'], 'agencyId'=>$param['agencyId']);
        	// 调用远程接口
            $response = $client->get($url, $remoteParams);
            // 判断是否有该子账号
            if (empty($response) || !$response['success'] || 0 == $response['data']['count']) {
            	// 没有子账号返回错误
            	// 设置错误编码
    			$result['errorCode']=230005;
	    		// 设置错误状态
    			$result['success'] = false;
    			// 设置错误信息
    			$result['msg'] = '该子账号不存在，保存失败！';
    			// 返回结果
				return $result;
            }
    		// 初始化标记参数
    		$param['save_flag'] = 1;
    		// 获取上限
    		$budgetUp = $this->getBudgetup($param);
    		// 判断牛币是否超过上限
    		if (floatval($param['balance']) > floatval($budgetUp['data']['budgetUp'])) {
    			// 超过上限
    			// 设置错误编码
    			$result['errorCode']=230004;
	    		// 设置错误状态
    			$result['success'] = false;
    			// 设置错误信息
    			$result['msg'] = '牛币分配金额超过上限，保存失败！';
    			// 返回结果
				return $result;
    		}
    		// 判断赠币是否超过上限
    		if (floatval($param['couponBalance']) > floatval($budgetUp['data']['couponBudgetUp'])) {
    			// 超过上限
    			// 设置错误编码
    			$result['errorCode']=230004;
	    		// 设置错误状态
    			$result['success'] = false;
    			// 设置错误信息
    			$result['msg'] = '赠币分配金额超过上限，保存失败！';
    			// 返回结果
				return $result;
    		}
    		// 将数据插入数据库
    		$result['success'] = $this->manageDao->insertAgencybudget($param);
    		// 若数据不重复，则返回成功
    		if ($result['success']) {
    			// 设置成功编码
    			$result['errorCode']=230000;
	    		// 设置成功状态
    			$result['success'] = true;
    			// 设置成功信息
    			$result['msg'] = '保存成功！';
    		} else {
    			// 数据重复，返回错误
    			// 设置错误编码
    			$result['errorCode']=230003;
	    		// 设置错误状态
    			$result['success'] = false;
    			// 设置错误信息
    			$result['msg'] = '数据重复，保存失败！';
    		}
    		
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);    		
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '操作失败，数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 更新供应商预算
     */
    public function updateAgencybudget($param) {
    	// 初始化空数据
    	$result['data'] = array();
    	try {
    		// 初始化标记参数
    		$param['save_flag'] = 1;
    		// 获取上限
    		$budgetUp = $this->getBudgetup($param);
    		// 判断牛币是否超过上限
    		if (floatval($param['balance']) > floatval($budgetUp['data']['budgetUp'])) {
    			// 超过上限
    			// 设置错误编码
    			$result['errorCode']=230004;
	    		// 设置错误状态
    			$result['success'] = false;
    			// 设置错误信息
    			$result['msg'] = '分配牛币金额超过上限，保存失败！';
    			// 返回结果
				return $result;
    		}
    		// 判断赠币是否超过上限
    		if (floatval($param['couponBalance']) > floatval($budgetUp['data']['couponBudgetUp'])) {
    			// 超过上限
    			// 设置错误编码
    			$result['errorCode']=230004;
	    		// 设置错误状态
    			$result['success'] = false;
    			// 设置错误信息
    			$result['msg'] = '赠币分配金额超过上限，保存失败！';
    			// 返回结果
				return $result;
    		}
    		// 更新供应商预算信息
    		$result['success'] = $this->manageDao->updateAgencybudget($param);
    		// 设置错误编码
    		$result['errorCode']=230000;
    		// 设置错误信息
    		$result['msg'] = '编辑成功！';
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);    		
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '操作失败，数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 删除供应商预算
     */
    public function deleteAgencybudget($param) {
    	// 初始化空数据
    	$result['data'] = array();
    	try {
    		// 初始化判断标记
    		$flag = $this->manageDao->deleteAgencybudget($param);
    		$result['success'] = $flag;
    		// 如果为false，则提示不给删除
    		if ($flag) {
	    		// 设置成功编码
    			$result['errorCode']=230000;
    			// 设置成功信息
    			$result['msg'] = '删除成功！';
    		} else {
    			// 设置错误编码
    			$result['errorCode']=230007;
    			// 设置错误信息
    			$result['msg'] = '删除失败，该子账户有冻结金额，无法删除！';
    		}
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);    		
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '操作失败，数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 获取预算分配上限
     */
    public function getBudgetup($param) {
    	// 预初始化返回结果
    	$result = array();
    	try {
    		// 获取供应商总余额
    		$financeIaoInfo = FinanceIao::getAccountAvailableBalance($param['agencyId']);
    		// 设置subAgency参数
    		if (empty($param['subAgency']) || '' == $param['subAgency']) {
    			// 设置新增查询
    			$param['subAgency'] = '-1';	
    		}
    		// 将查询供应商总预算
    		$budget = $this->manageDao->queryBudgetTotal($param);
    		// 分类设置预算结果
    		if (!empty($param['save_flag']) && 1 == $param['save_flag']) {
    			// 设置可分配余额
    			$result['data']['budgetUp'] = floatval(floatval($financeIaoInfo['controlMoney']) - floatval($budget['balance']));
    			$result['data']['couponBudgetUp'] = floatval(floatval($financeIaoInfo['couponControlMoney']) - floatval($budget['coupon_balance']));
    		} else {
    			// 设置可分配余额
    			$result['data']['budgetUp'] = intval(floatval($financeIaoInfo['controlMoney']) - floatval($budget['balance']));
    			$result['data']['couponBudgetUp'] = intval(floatval($financeIaoInfo['couponControlMoney']) - floatval($budget['coupon_balance']));
    		}
    		// 设置错误编码
    		$result['errorCode']=230000;
    		// 设置错误状态
    		$result['success'] = true;
    		// 设置错误信息
    		$result['msg'] = '查询成功！';
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);    		
    		// 初始化空数据
    		$result['data'] = array();
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 扣除供应商子账户余额
     */
    public function dedcutSubAgency($param) {
    	try {
    		// 将数据插入数据库
    		$this->manageDao->dedcutSubAgencyAccount($param);
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);    		
		}
    }
    
    /**
     * 解冻供应商子账户余额
     */
    public function unfreezeSubAgency($param) {
    	try {
    		// 将数据插入数据库
    		$this->manageDao->unfreezeSubAgencyAccount($param);
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
		}
    }
    
    /**
     * 联想查询子供应商账户
     */
    public function getSubagencyassociate($param) {
    	// 预初始化返回结果
    	$result = array();
    	try {
 		    // 联想查询子供应商账户
    		// 初始化调用对象
    		$client = new RESTClient();
    		// 初始化远程调用地址
//        	$url = Yii::app()->params['NB_HOST'] . 'restful/vendor/query-agencysubassociate';
        	$url = Yii::app()->params['ADMIN_HOST'] . 'admit/user/query-agencysubassociate';
        	// 预初始化远程调用参数
        	$remoteParams = array();
        	// 分类初始化远程调用参数
        	if (!empty($param['subAgency']) && '' != $param['subAgency']) {
        		// 初始化登录名调用参数
        		$remoteParams = array('loginName'=>$param['subAgency'], 'agencyId'=>$param['agencyId']);
        	} else if (!empty($param['name']) && '' != $param['name']) {
        		// 初始化子供应商名称调用参数
        		$remoteParams = array('name'=>$param['name'], 'agencyId'=>$param['agencyId']);
        	}
        	// 调用远程接口
            $response = $client->get($url, $remoteParams);
    		// 将查询供应商总预算
    		$existSubAgency = $this->manageDao->querySubagencyassociate($param);
    		// 初始化rows结果
    		$rows = array();
    		// 若反回结果正常，则整合rows结果
    		if (!empty($response) && $response['success'] && !empty($response['data'])) {
    			// 初始化rows临时结果集
    			$tempRows = $response['data']['rows'];
    			// 分类整合结果
    			if ('query' == $param['operateFlag'] || 'queryAll' == $param['operateFlag']) {
    				// 查询，只联想已建立的子账户
    				// 循环整合结果
	    			foreach($tempRows as $k => $v) {
	    				// 设置agencyId结果维度
//	    				$tempRows[$k]['agencyId'] = $v['topAgencyId'];
	    				$tempRows[$k]['agencyId'] = $v['topUser'];
	    				// 设置subAgency结果维度
	    				$tempRows[$k]['subAgency'] = $v['loginName'];
	    				foreach($existSubAgency as $existSubAgencyObj) {    					
	    					// 若登录名和agencyId相等，则去除该元素
	    					if ($v['topAgencyId'] == $existSubAgencyObj['agency_id'] && 0 == strcmp($v['loginName'], $existSubAgencyObj['login_name'])) {
	    						// 添加该元素
	    						array_push($rows, $tempRows[$k]);
	    						// 中断里层循环
	    						break;
	    					}      
	    				}
	    			}
    			} else if ('insert' == $param['operateFlag']) {
    				// 新增，只联想没建立的子账户
	    			// 循环整合结果
	    			foreach($tempRows as $k => $v) {
	    				// 设置agencyId结果维度
	    				$tempRows[$k]['agencyId'] = $v['topAgencyId'];
	    				// 设置subAgency结果维度
	    				$tempRows[$k]['subAgency'] = $v['loginName'];
	    				foreach($existSubAgency as $existSubAgencyObj) {    					
	    					// 若登录名和agencyId相等，则去除该元素
	    					if ($v['topAgencyId'] == $existSubAgencyObj['agency_id'] && 0 == strcmp($v['loginName'], $existSubAgencyObj['login_name'])) {
	    						// 删除该元素
	    						unset($tempRows[$k]);
	    						// 中断里层循环
	    						break;
	    					}      
	    				}
	    			}
	    			// 填充结果
	    			$rows = array_merge($rows, $tempRows);
    			}
    			// 添加父供应商或管理员账号
    			if (0 == strcmp('queryAll', $param['operateFlag']) && $param['isFather'] && $param['subAgency'] == $param['agencyId']) {
    				$temp['id'] = -1;
    				$temp['subAgency'] = $param['agencyId'];
    				$temp['name'] = '父账号';
    				$temp['agencyId'] = $param['agencyId'];
    				// 添加该元素
	    			array_push($rows, $temp);
    			} else if (0 == strcmp('queryAll', $param['operateFlag']) && $param['isFather'] && $param['subAgency'] == 'admin@'.$param['agencyId']) {
    				$temp['id'] = -1;
    				$temp['subAgency'] = $param['subAgency'];
    				$temp['name'] = '管理员账号';
    				$temp['agencyId'] = $param['agencyId'];
    				// 添加该元素
	    			array_push($rows, $temp);
    			}
    		}
    		// 设置data类容
    		$result['data']['rows'] = $rows;
    		$result['data']['count'] = count($rows);
    		// 设置成功编码
    		$result['errorCode']=230000;
    		// 设置成功状态
    		$result['success'] = true;
    		// 设置成功信息
    		$result['msg'] = '查询成功！';
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 初始化空数据
    		$result['data'] = array();
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 保存供应商自营配置
     */
    public function saveConfigAgency($param) {
    	// 预初始化返回结果
    	$result = array();
    	try {
    		// 判断是哪种类型的保存
    		if (1 == $param['isOverall'] && 'insert' == $param['saveFlag']) {
    			$this->manageDao->insertConfigAgency($param);
    		}
    		// 设置data类容
    		$result['data'] = array();
    		// 设置成功编码
    		$result['errorCode']=230000;
    		// 设置成功状态
    		$result['success'] = true;
    		// 设置成功信息
    		$result['msg'] = '查询成功！';
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 初始化空数据
    		$result['data'] = array();
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '数据异常！';
		}
		// 返回结果
		return $result;
    }
    
    /**
     * 查询供应商配置
     */
    public function getAgencyConfig($param) {
    	// 预初始化返回结果
    	$result = array();
    	try {
    		// 设置data类容
    		$result['data']['isOpen'] = 0;
    		$isopen = $this->manageDao->queryAgencyConfig($param);
    		if (!empty($isopen)) {
    			$result['data']['isOpen'] = $isopen['isOpen'];
    		}
    		// 设置成功编码
    		$result['errorCode']=230000;
    		// 设置成功状态
    		$result['success'] = true;
    		// 设置成功信息
    		$result['msg'] = '查询成功！';
    	} catch(Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 初始化空数据
    		$result['data'] = array();
    		// 设置错误编码
    		$result['errorCode']=230001;
    		// 设置错误状态
    		$result['success'] = false;
    		// 设置错误信息
    		$result['msg'] = '数据异常！';
		}
		// 返回结果
		return $result;
    }
    
}
