<?php

Yii::import('application.modules.bidmanage.models.fmis.FmisBidInfo');
Yii::import('application.modules.bidmanage.models.iao.IaoFinanceMod');
Yii::import('application.modules.bidmanage.dal.dao.pack.PackageplanDao');
Yii::import('application.modules.bidmanage.dal.iao.PackIao');
Yii::import('application.modules.bidmanage.dal.iao.ProductIao');
Yii :: import('application.modules.bidmanage.dal.iao.FinanceIao');

/**
 * 打包计划业务处理类
 */
class PackageplanMod {

	/**
	 * 打包计划dao
	 */
    private $packageplanDao;
    
    /**
     * 财务业务处理类
     */
    private $fmisBidInfo;
    
    /**
     * 财务扣款类
     */
    private $_iaoBidProduct;
    
    /**
     * 默认构造函数
     */
	function __construct() {
		// 初始化打包计划dao
		$this->packageplanDao = new PackageplanDao();
		// 初始化财务业务处理类
		$this->fmisBidInfo = new FmisBidInfo();
		// 初始化财务扣款类
		$this->_iaoBidProduct = new IaoFinanceMod();
	}

	/**
	 * 获取供应商信息
	 */
	public function getAgencyInfo($param) {
		// 初始化返回结果
		$result = array();
		// 因为是联动，所以查不到名称就返回空串，ID就返回0
		$data['agencyName'] = '';
		$data['agencyId'] = 0;
		$data['accountId'] = 0;
		
		try {
			// 查询数据
			$dataDb = $this->packageplanDao->queryAgencyInfo($param);
			
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				$data['agencyName'] = $dataDb['agencyName'];
				$data['agencyId'] = $dataDb['agencyId'];
				$data['accountId'] = $dataDb['accountId'];
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result;
	}
	
	/**
	 * 获取搜索列表
	 */
	public function getPlaProduct($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;
		
		try {
			// 初始化PLA调用参数
			$plaParam['currentPage'] = intval($param['start']) / intval($param['limit']) + 1;
			$plaParam['limit'] = $param['limit'];
			$plaParam['vendorId'] = $param['agencyId'];
			$plaParam['isQuery'] = true;
			// 产品类型   搜索  1：跟团旅游 2：自助游 5：公司旅游 6：景点门票 8：自驾游 10：签证 12：邮轮
//			switch ($param['productType']) {
//           		case 1:
//				    $plaParam['classBrandType'] = 1;
//				    break;
//				case 3:
//				    $plaParam['classBrandType'] = 2;
//				    break;
//				case 33:
//				    $plaParam['classBrandType'] = 6;
//				    break;
//				case 4:
//				    $plaParam['classBrandType'] = 10;
//				    break;
//				case 5:
//				    $plaParam['classBrandType'] = 12;
//				    break;				  					            
//				default:
//				    $plaParam['classBrandType'] = 1;
//				    break;
//        	}
			$plaParam['classBrandTypes'] = array(1, 2, 8);
			// 出发城市编码
			if (!empty($param['startCityCode'])) {
				$plaParam['cityCode'] = $param['startCityCode'];
			}
			// 产品名称
			if (!empty($param['productName'])) {
				$plaParam['key'] = $param['productName'];
			}
			// 线路类型  国内  周边
			if (!empty($param['catType'])) {
				$plaParam['productCatType'] = $param['catType'];
			}
			
			// 调用搜索接口查询
			$plaData = PackIao::getPlaProduct($plaParam);

			// 若调用成功，则整合数据
			if (!empty($plaData) && is_array($plaData) && $plaData['success'] 
				&& !empty($plaData['data']) && is_array($plaData['data'])) {
				if (0 != $plaData['data']['count'] && !empty($plaData['data']['rows']) && is_array($plaData['data']['rows'])) {
					$plaRows = $plaData['data']['rows'];
					
					// 查询已勾选产品
					$haveParam = array();
					$haveProducts = "";
					foreach ($plaRows as $plaRowsObj) {
						$haveProducts = $haveProducts.$plaRowsObj['productId'].",";
					}
					$haveProducts = substr($haveProducts, 0, strlen($haveProducts) - 1);
					$haveParam['productIds'] = $haveProducts;
					// 出发城市编码
					if (!empty($param['startCityCode'])) {
						$haveParam['startCityCode'] = $param['startCityCode'];
					}
					$haveData = $this->packageplanDao->queryHaveProducts($haveParam);
					foreach ($plaRows as $plaRowsObj) {
						$reRowsObj = array();
						$reRowsObj['startCityCode'] = 0;
						$reRowsObj['startCityName'] = '';
						$reRowsObj['productName'] = $plaRowsObj['productName'];
						$reRowsObj['isHave'] = 0;
						// 匹配勾选标记
						foreach ($haveData as $haveDataObj) {
							if (strval($plaRowsObj['productId']) === strval($haveDataObj['productId'])) {
								$reRowsObj['isHave'] = 1;
								break;
							}
						}
                        // 获取产品类型
                        $reRowsObj['productType'] = DictionaryTools::getTypeTool($plaRowsObj['classBrandId']);
						$reRowsObj['productId'] = $plaRowsObj['productId'];
						if (!empty($plaRowsObj['bookCity']) && is_array($plaRowsObj['bookCity'])) {
							$reRowsObj['startCityCode'] = $plaRowsObj['bookCity'][0]['bookCityCode'];
							$reRowsObj['startCityName'] = $plaRowsObj['bookCity'][0]['bookCityName'];
						}
						array_push($data['rows'], $reRowsObj);
					}
					$data['count'] = $plaData['data']['count'];
				}
				 
			} else {
				throw new Exception('调用搜索接口失败', 230003);
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result;
	}
    
    /**
	 * 获取供应商账户信息
	 */
	public function getAgencyConsumption($param) {
		// 初始化返回结果
		$result = array();
		$data = array();
		
		try {
			// 查询数据
			$dataDb = $this->packageplanDao->queryAgencyConsumption($param);
			// 查询供应商账户信息
			$fmisData = $this->fmisBidInfo->getBidFinanceInfo($param);
			
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)
				&& !empty($fmisData) && is_array($fmisData)) {
				$data['packConsumptionNum'] = $dataDb['consumption'];
				$data = array_merge($data, $fmisData);
			} else {
				throw new Exception('没有该供应商的打包计划账户数据', 230004);
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
	
	/**
	 * 获取打包计划列表
	 */
	public function getPackPlanList($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;			
		
		try {
			// 获取产品经理缓存
			$packManagers = Yii::app()->memcache->get('packManagerKey');
			if (empty($packManagers)) {
				$packManagers = PackIao::getManager();
				Yii::app()->memcache->set('packManagerKey', $packManagers, 86400);
			}
			// 初始化本地产品经理缓存
			$packLocalManagers = array();
			
			// 查询信息
			$dataDb = $this->packageplanDao->queryPackPlanList($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				// 查询数量			
				$dataCount = $this->packageplanDao->queryPackPlanCount($param);
				// 分类整合数据
				if (!empty($param['isHagrid'])) {
					// 海格列表
					foreach ($dataDb as $dataDbObj) {
						$rowsObj = array();
						$rowsObj['packPlanId'] = $dataDbObj['packPlanId'];
						$rowsObj['packPlanName'] = $dataDbObj['packPlanName'];
						$rowsObj['managerId'] = $dataDbObj['managerId'];
						$rowsObj['releaseDate'] = $dataDbObj['releaseDate'];
						$rowsObj['isAgencySubmit'] = $dataDbObj['isAgencySubmit'];
						$rowsObj['managerName'] = '';
						// 匹配产品经理ID  本地缓存
						foreach($packLocalManagers as $packLocalManagersObj) {
							if ($packLocalManagersObj['tuniuManagerId'] == $dataDbObj['managerId']) {
								$rowsObj['managerName'] = $packLocalManagersObj['tuniuManagerNickname'];
								break;
							}
						}
						// 匹配产品经理ID  memcahce缓存
						if ('' == $rowsObj['managerName']) {
							foreach($packManagers as $packManagersObj) {
								if ($packManagersObj['tuniuManagerId'] == $dataDbObj['managerId']) {
									$rowsObj['managerName'] = $packManagersObj['tuniuManagerNickname'];
									array_push($packLocalManagers, $packManagersObj);
									break;
								}
							}
						}
						$rowsObj['agencyName'] = $dataDbObj['agencyName'];
						$rowsObj['agencyId'] = $dataDbObj['agencyId'];
						$rowsObj['endDate'] = $dataDbObj['endDate'];
						$rowsObj['addDate'] = $dataDbObj['addDate'];
						$rowsObj['planPrice'] = intval($dataDbObj['planPrice']);
						
						// 分类设定推广状态  0 未发布  1 推广中 2 推广结束
						$rowsObj['planState'] = $dataDbObj['planStatus'];
						
						// 分类处理导出和列表的字段
						if (!empty($param['isExcel'])) {
							// 推广状态名称
							if (0 == $dataDbObj['planStatus']) {
								$rowsObj['planStateName'] = "未发布";
							} else if (1 == $dataDbObj['planStatus']) {
								$rowsObj['planStateName'] = "推广中";
							} else if (2 == $dataDbObj['planStatus']) {
								$rowsObj['planStateName'] = "推广结束";
							}
							// 是否供应商确认
							if (1 == $dataDbObj['isAgencySubmit']) {
								// 是
								$rowsObj['isAgencySubmitName'] = "是"; 
							} else if (2 == $dataDbObj['isAgencySubmit']) {
								// 否
								$rowsObj['isAgencySubmitName'] = "否"; 
							}
							// 产品ID
							$rowsObj['productArr'] = $dataDbObj['productArr']; 
						} else {
							// 产品ID							
							$productArr = explode(',', $dataDbObj['productArr']);
							$productArrData = array();
							if (count($productArr) > 3) {
								array_push($productArrData, $productArr[0]);
								array_push($productArrData, $productArr[1]);
								array_push($productArrData, $productArr[2]);
							} else {
								$productArrData = array_merge($productArrData, $productArr);
							}
							$rowsObj['productArr'] = $productArrData;
						}
						
						// 添加结果
						array_push($data['rows'], $rowsObj);
					}
				} else {
					// 招客宝列表
					foreach ($dataDb as $dataDbObj) {
						$rowsObj = array();
						$rowsObj['packPlanId'] = $dataDbObj['packPlanId'];
						$rowsObj['packPlanName'] = $dataDbObj['packPlanName'];
						$rowsObj['managerId'] = $dataDbObj['managerId'];
						$rowsObj['releaseDate'] = $dataDbObj['releaseDate'];
						$rowsObj['isAgencySubmit'] = $dataDbObj['isAgencySubmit'];
						$rowsObj['managerName'] = '';
						// 匹配产品经理ID  本地缓存
						foreach($packLocalManagers as $packLocalManagersObj) {
							if ($packLocalManagersObj['tuniuManagerId'] == $dataDbObj['managerId']) {
								$rowsObj['managerName'] = $packLocalManagersObj['tuniuManagerNickname'];
								break;
							}
						}
						// 匹配产品经理ID  memcahce缓存
						if ('' == $rowsObj['managerName']) {
							foreach($packManagers as $packManagersObj) {
								if ($packManagersObj['tuniuManagerId'] == $dataDbObj['managerId']) {
									$rowsObj['managerName'] = $packManagersObj['tuniuManagerNickname'];
									array_push($packLocalManagers, $packManagersObj);
									break;
								}
							}
						}
						$rowsObj['endDate'] = $dataDbObj['endDate'];
						$rowsObj['addDate'] = $dataDbObj['addDate'];
						$rowsObj['planPrice'] = intval($dataDbObj['planPrice']);
						// 分类设定推广状态  0 未发布  1 推广中 2 推广结束
						$rowsObj['planState'] = $dataDbObj['planStatus'];
						
						// 产品ID
						$productArr = explode(',', $dataDbObj['productArr']);
						$productArrData = array();
						if (count($productArr) > 3) {
							array_push($productArrData, $productArr[0]);
							array_push($productArrData, $productArr[1]);
							array_push($productArrData, $productArr[2]);
						} else {
							$productArrData = array_merge($productArrData, $productArr);
						}
						$rowsObj['productArr'] = $productArrData;
						
						// 添加结果
						array_push($data['rows'], $rowsObj);
					}
				}
				$data['count'] = $dataCount['countRe'];
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
	
	/**
	 * 查询打包计划产品详情
	 */
	public function getPlanProductDetail($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;			
		
		try {
			
			// 查询信息
			$dataDb = $this->packageplanDao->queryPlanProductDetail($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				// 查询数量			
				$dataCount = $this->packageplanDao->queryPlanProductDetailCount($param);
				// 整合结果
				$data['rows'] = array_merge($data['rows'], $dataDb);
				$data['count'] = $dataCount['countRe'];
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
	
	/**
	 * 获取推广列表
	 */
	public function getPackPlanSpreadList($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;		
		
		try {
			// 查询信息
			$dataDb = $this->packageplanDao->queryPackPlanSpreadList($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				// 查询数量			
				$dataCount = $this->packageplanDao->queryPackPlanSpreadCount($param);
				
				foreach($dataDb as &$dataDbObj){
					// 设置参数获取分类页信息
        			$webClassId = array();
        			$webClassData = array();
					array_push($webClassId,$dataDbObj['webClass']);
					
					// 调用网站接口获取分类信息
	                $webClassArr = array('webClassId' => $webClassId);
    	            $webClassInfo = ProductIao::getWebClassInfo($webClassArr);
        	        $webClassData[] = $webClassInfo['data'];
        	        
        	        // 增加上级分类的查找 
        			$webClassParentIdArr = array();
        	        foreach($webClassData as $subItem) {
                        if (!empty($subItem)) {
                            foreach($subItem as $iaoObj){
                                $webClassParentIdArr['webClassId'][] = $iaoObj['parentId'];
                            }
                        }
        			}
        			$parentWebClassInfoRows = ProductIao::getWebClassInfo($webClassParentIdArr);
        			
        			// 生成分类名称
        			foreach($webClassData as $tempStr){
        				if (intval($tempStr[$dataDbObj['webClass']]['id']) == intval($dataDbObj['webClass'])) {
                        	$parentWebClassStr = str_replace('目的地', '', strval($parentWebClassInfoRows['data'][$tempStr[$dataDbObj['webClass']]['parentId']]['classificationName']));
                        	$dataDbObj['webClassName'] = $dataDbObj['startCityName'] . "-" . $parentWebClassStr . '-' . $tempStr[$dataDbObj['webClass']]['classificationName'];
        				}
                    }
                    
					
					if (!empty($param['packState']) && 1 == $param['packState']) {
            			$dataDbObj['showEndDate'] = date('Y-m-d');
					}
					
					$pv = $this->packageplanDao->queryPv($dataDbObj);
					$dataDbObj['pvCount'] = $pv['pv'];
            	}
				
				// 整合结果
				$data['rows'] = array_merge($data['rows'], $dataDb);
				$data['count'] = $dataCount['countRe'];
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
	
	/**
	 * 保存打包计划
	 */
	public function savePackPlan($param) {
		// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			// 分类进行新增和更新操作
			if (1 == $param['saveFlag']) {
				// 新增
				$lastId = $this->packageplanDao->insertPackPlan($param);
				$data['packPlanId'] = $lastId;
			} else if (2 == $param['saveFlag']) {
				// 更新
				$this->packageplanDao->updatePackPlan($param);
				$data['packPlanId'] = $param['packPlanId'];
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '保存成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
    
    /**
	 * 发布打包计划
	 */
	public function submitPackPlan($param) {
		// 初始化返回结果
		$result = array();	
		
		try {
			
			/*** 第一步校验并发布计划start ***/
			
			// 校验线路数量
			$productCount = $this->packageplanDao->queryCountByPackPlan($param['packPlanId']);
			if (!empty($productCount) && 1 > $productCount['countRe']) {
				throw new Exception("打包计划中必须至少含有1条线路，无法发布计划！", 230019);
			}
			
			// 查询账户ID
			$accountId = "";
			if (!empty($param['agencyId'])) {
				$accountId = $this->packageplanDao->queryAgencyInfo($param['agencyId']);
			} else if (!empty($param['accountId'])) {
				// 查询供应商账户ID  支持BB
				$agencyId = $this->packageplanDao->queryAgencyId($param['accountId']);
				$param['agencyId'] = $agencyId['agencyId'];
				$accountId['accountId'] = $param['accountId'];
			}

			// 查询供应商账户信息
			if (!empty($accountId) && is_array($accountId) && !empty($accountId['accountId'])) {
				$fmisData = $this->fmisBidInfo->getBidFinanceInfo($accountId['accountId']);
			} else {
				throw new Exception("供应商账户不存在，无法发布计划！", 230009);
			}
			
			// 判断账户余额
			if (!empty($fmisData) && is_array($fmisData)) {
				if (floatval($fmisData['controlMoney']) < floatval($param['packPlanPrice'])) {
					throw new Exception("供应商账户余额不足，当前余额：".intval($fmisData['controlMoney']).", 无法发布计划！", 230015);
				}
			} else {
				throw new Exception("供应商账户校验失败，无法发布计划！", 230016);
			}
			
			// 查询所有相关线路
			$productArr = $this->packageplanDao->queryAllPackProducts($param);
			
			$productIds = "";
			if (!empty($productArr) && is_array($productArr)) {
				foreach($productArr as $productObj) {
					$productIds = $productIds.$productObj['productId'].",";
				}
				$productIds = substr($productIds, 0, strlen($productIds) - 1);
				
				// 查询重复的产品ID
				$douParam['productIds'] = $productIds;
				$productDouArr = $this->packageplanDao->queryDouId($douParam);
				// 如果有重复的产品ID，则报错
				if (!empty($productDouArr) && is_array($productDouArr)) {
					$productDouIds = "";
					foreach($productDouArr as $productDouObj) {
						$productDouIds = $productDouIds.$productDouObj['productId'].",";
					}
					$productDouIds = substr($productDouIds, 0, strlen($productDouIds) - 1);
					
					// 抛异常
					throw new Exception("您所发布的产品有正在推广中的，打包计划不能发布！产品：".$productDouIds, 230011);
				}
			}
			
			// 发布计划
			$param['status'] = 1;
			$this->packageplanDao->submitPackPlan($param);
			
			/*** 第一步校验并发布计划end ***/
			
			/*** 第二步调用网站接口start ***/
			
			if (!empty($productArr) && is_array($productArr)) {
				$tuniuParam = array();
				
				// 上线产品
				$tuniuParam['action'] = 'add';
				$tuniuParam['params'] = array();
				$tuniuParamObj['packageId'] = $param['packPlanId'];
				$tuniuParamObj['routes'] = array();
				foreach($productArr as $addObj) {
					$routesObj = array();
					$routesObj['id'] = $addObj['productId'];
                    // 获取产品类型
                    $routesObj['type'] = DictionaryTools::getTypeTool($addObj['productType']);
					array_push($tuniuParamObj['routes'], $routesObj);
				}
				array_push($tuniuParam['params'], $tuniuParamObj);
				PackIao::onOffLineTuniuProduct($tuniuParam);
			}
						
			/*** 第二步调用网站接口end ***/
			
			/*** 第三步调用财务接口start ***/
			
            //执行财务扣款
            $fmisParamsArr = array(
                 'agency_id' => $param['agencyId'],
                 'amt' => $param['packPlanPrice']
            );
            // 财务扣费
            $fmisIdDeduct = PackIao::directFinance($fmisParamsArr); 
            
            if (!empty($param['uid'])) {
            	$fmisParams['uid'] = $param['uid'];
            	$fmisParams['nickname'] = $param['nickname']; 
            } else {
            	$fmisParams['uid'] = $param['accountId'];
            	$fmisParams['nickname'] = '供应商'.$param['agencyId'];
            }
            
            // 更新财务状态
            if ($fmisIdDeduct) {
            	// 成功
            	$fmisParams['packPlanId'] = $param['packPlanId'];
            	$fmisParams['packPlanPrice'] = $param['packPlanPrice'];
            	$fmisParams['fmisId'] = $fmisIdDeduct;
            	$this->packageplanDao->updatePlanFmis($fmisParams);
            } else {
            	// 失败
            	$fmisParams['packPlanId'] = $param['packPlanId'];
            	$fmisParams['packPlanPrice'] = $param['packPlanPrice'];
            	$this->packageplanDao->updatePlanFmisFailLog($fmisParams);
            	// 抛异常
            	throw new Exception("财务扣款失败！", 230010);
            }
						
			/*** 第三步调用财务接口end ***/
			
			
			// 整合最终返回的正确结果
			$result['data'] = array();
			$result['success'] = true;
			$result['msg'] = '发布成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            
            // 判断是否需要发布回滚
            if (231000 == $e->getCode()) {
            	$param['status'] = 0;
            	$this->packageplanDao->submitPackPlan($param);
            }
            
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
    
    /**
	 * 保存打包计划线路
	 */
	public function savePackPlanProduct($param) {
		// 初始化返回结果
		$result = array();	
		
		try {
			
			/*** 第一步：筛选产品start ***/
			
			// 查询相关打包计划所有产品
			$productArr = $this->packageplanDao->queryAllPackProducts($param);
			
			// 初始化需要删除的产品
			$toDel = array();
			// 初始化需要新增的产品
			$toAdd = array();
			
			// 筛选数据
			if (!empty($param['productRows']) && is_array($param['productRows']) && empty($productArr)) {
				// 纯新增
				$toAdd = array_merge($toAdd, $param['productRows']);
			} else if (!empty($productArr) && is_array($productArr) && empty($param['productRows'])) {
				// 纯删除
				$toDel = array_merge($toDel, $productArr);
			} else if (!empty($productArr) && is_array($productArr) && !empty($param['productRows']) && is_array($param['productRows'])) {
				$productRows = $param['productRows'];
				// 过滤需要新增的
				foreach ($productRows as $productRowsObj) {
					$toAddCount = 0;
					foreach ($productArr as $productArrObj) {
						if (intval($productArrObj['productId']) != intval($productRowsObj['productId'])) {
							$toAddCount++;
						}					
					}
					if ($toAddCount == count($productArr)) {
						array_push($toAdd, $productRowsObj);
					}
				}
				// 过滤需要删除的
				foreach ($productArr as $productArrObj) {
					$toDelCount = 0;
					foreach ($productRows as $productRowsObj) {	
						if (intval($productArrObj['productId']) != intval($productRowsObj['productId'])) {
							$toDelCount++;
						}					
					}
					if ($toDelCount == count($productRows)) {
						array_push($toDel, $productArrObj);
					}
				}
			}
			
			// 校验计划
			$productIds = "";
			if (!empty($toAdd) && is_array($toAdd)) {
				foreach($toAdd as $productObj) {
					$productIds = $productIds.$productObj['productId'].",";
				}
				$productIds = substr($productIds, 0, strlen($productIds) - 1);
				
				// 查询重复的产品ID
				$douParam['productIds'] = $productIds;
				$douParam['packPlanId'] = $param['packPlanId'];
				$productDouArr = $this->packageplanDao->queryDouId($douParam);
				// 如果有重复的产品ID，则报错
				if (!empty($productDouArr) && is_array($productDouArr)) {
					$productDouIds = "";
					foreach($productDouArr as $productDouObj) {
						$productDouIds = $productDouIds.$productDouObj['productId'].",";
					}
					$productDouIds = substr($productDouIds, 0, strlen($productDouIds) - 1);
					
					// 抛异常
					throw new Exception("您所发布的产品有正在推广中的，以下产品不能发布，保存失败！产品：".$productDouIds, 230011);
				}
			}
			
			$param['toAdd'] = $toAdd;
			$param['toDel'] = $toDel;
			
			/*** 第一步：筛选产品end ***/
			
			/*** 第二步：调用网站接口start ***/
			
			
			// 若为未发布计划，则无需调用网站
			if (1 == $param['packState']) {
			
				$tuniuParam = array();
				
				// 上线产品
				if (!empty($param['toAdd']) && is_array($param['toAdd'])) {
					$tuniuParam['action'] = 'add';
					$tuniuParam['params'] = array();
					$tuniuParamObj['packageId'] = $param['packPlanId'];
					$tuniuParamObj['routes'] = array();
					foreach($param['toAdd'] as $addObj) {
						$routesObj = array();
						$routesObj['id'] = $addObj['productId'];
                        // 获取产品类型
                        $routesObj['type'] = DictionaryTools::getTypeTool($addObj['productType']);
						array_push($tuniuParamObj['routes'], $routesObj);
					}
					array_push($tuniuParam['params'], $tuniuParamObj);
					PackIao::onOffLineTuniuProduct($tuniuParam);
				}
				
				// 下线产品			
				if (!empty($param['toDel']) && is_array($param['toDel'])) {
					$tuniuParam['action'] = 'del';
					$tuniuParam['params'] = array();
					$tuniuParamObj['packageId'] = $param['packPlanId'];
					$tuniuParamObj['routes'] = array();
					foreach($param['toDel'] as $delObj) {
						$routesObj = array();
						$routesObj['id'] = $delObj['productId'];
						switch ($delObj['productType']) {
	           				case 1:
					            $routesObj['type'] = 1;
					            break;
					        case 2:
					           	$routesObj['type'] = 3;
					            break;
					        case 6:
					            $routesObj['type'] = 33;
					            break;
					        case 8:
					            $routesObj['type'] = 8;
					            break;
					        case 10:
					            $routesObj['type'] = 4;
					            break;
					        case 12:
					            $routesObj['type'] = 5;
					            break;
					        default:
					            $routesObj['type'] = 1;
					            break;
        				}
						array_push($tuniuParamObj['routes'], $routesObj);
					}
					array_push($tuniuParam['params'], $tuniuParamObj);
					PackIao::onOffLineTuniuProduct($tuniuParam);
					// 下线本地产品
					$this->packageplanDao->offLineProducts($param['toDel'], $param['packPlanId']);
				}
			
			}
			
			/*** 第二步：调用网站接口end ***/
			
			
			/*** 第三步：保存打包计划产品start ***/
			
			// 保存打包计划线路
			$productArr = $this->packageplanDao->savePackPlanProduct($param);
			
			/*** 第三步：保存打包计划产品end ***/
			
			/*** 第四步：更新产品表名称start ***/
			
			// 刷新产品名称
			if (!empty($param['toAdd']) && is_array($param['toAdd'])) {
				$productIds = "";
				foreach($param['toAdd'] as $addObj) {
					$productIds = $productIds.$addObj['productId'].",";
				}
				$productIds = substr($productIds, 0, strlen($productIds) - 1);
				// 查询已有产品
				$proParam['productIds'] = $productIds;
				$productIdObjs = $this->packageplanDao->queryBidProducts($proParam);
				
				$proAddParam['toAddPro'] = array();
				// 如果有已有产品，则过滤，否则全部新增
				if (!empty($productIdObjs) && is_array($productIdObjs)) {
					// 过滤产品
					foreach($param['toAdd'] as $addObj) {
						$count = 0;
						foreach($productIdObjs as $productIdObj) {
							if (intval($addObj['productId']) != intval($productIdObj['productId'])) {
								$count++;
							}
						}
						if ($count == count($productIdObjs)) {
							array_push($proAddParam['toAddPro'], $addObj);
						}
					}
					
				} else {
					// 全部新增
					$proAddParam['toAddPro'] = array_merge($proAddParam['toAddPro'], $param['toAdd']);
				}
				if (!empty($proAddParam['toAddPro']) && is_array($proAddParam['toAddPro'])) {
					// 新增产品
					$this->packageplanDao->insertBidProducts($proAddParam);
				}
				
			}
			
			/*** 第四步：更新产品表名称end ***/
			
			// 整合最终返回的正确结果
			$result['data'] = array();
			$result['success'] = true;
			$result['msg'] = '保存成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
    
    /**
     * 删除打包计划
     */
    public function deletePackPlan($param) {
    	// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			
			// 删除打包计划
			$this->packageplanDao->deletePackPlan($param);
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '删除成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
    }
    
    /**
     * 网站上线产品
     */
    public function tuniuOnLineProducts($param) {
    	// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			
			// 查询打包ID
			$dataParam = $param['data'];
			$productIds = "";
			foreach($dataParam as $dataParamObj) {
				$productIds = $productIds.$dataParamObj['productId'].",";
			}
			$productIds = substr($productIds, 0, strlen($productIds) - 1);
			$proParam['productIds'] = $productIds;
			$productIdObjs = $this->packageplanDao->queryTuniuProductPlan($proParam);
			
			// 整合数据
			foreach($dataParam as &$dataParamObj){
				foreach($productIdObjs as $productIdObj){
					if ($dataParamObj['productId'] == $productIdObj['productId']) {
						$dataParamObj['accountId'] = $productIdObj['accountId'];
						$dataParamObj['packPlanId'] = $productIdObj['packPlanId'];
                        // 获取产品类型
                        $dataParamObj['productType'] = DictionaryTools::getTypeTool($dataParamObj['productType']);
						break;
					}
				}
			}
			
			// 新增推广产品
			$this->packageplanDao->tuniuOnLineProducts($dataParam);
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '保存成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
    }
    
    /**
     * 网站下线产品
     */
    public function tuniuOffLineProducts($param) {
    	// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			
			// 查询打包ID
			$dataParam = $param['data'];
			$productIds = "";
			foreach($dataParam as $dataParamObj) {
				$productIds = $productIds.$dataParamObj['productId'].",";
			}
			$productIds = substr($productIds, 0, strlen($productIds) - 1);
			$proParam['productIds'] = $productIds;
			$productIdObjs = $this->packageplanDao->queryTuniuProductPlan($proParam);
			
			if (!empty($productIdObjs) && is_array($productIdObjs)) {
				// 整合数据
				foreach($dataParam as &$dataParamObj){
					foreach($productIdObjs as $productIdObj){
						if ($dataParamObj['productId'] == $productIdObj['productId']) {
							$dataParamObj['packPlanId'] = $productIdObj['packPlanId'];
                            // 获取产品类型
                            $dataParamObj['productType'] = DictionaryTools::getTypeTool($dataParamObj['productType']);
							break;
						}
					}
				}
				
				// 新增推广产品
				$this->packageplanDao->tuniuOffLineProducts($dataParam);
			}
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '保存成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
    }
    
    /**
     * 结束脚本
     */
    public function endPackPlan() {		
		
		try {
			
			/*** 第一步 查询需要下线的计划和产品start ***/
			
			// 查询需要下线的计划和产品
			$productArr = $this->packageplanDao->queryEndScriptInfo();
			
			/*** 第一步 查询需要下线的计划和产品end ***/
			
			/*** 第二步 调用网站接口start ***/
			
			// 下线产品			
			if (!empty($productArr['productIds']) && is_array($productArr['productIds'])) {
				$tuniuParam['action'] = 'del';
				$tuniuParam['params'] = array();
				$productIds = $productArr['productIds'];
				$packPlanIds = $productArr['packPlanIds'];
				foreach($packPlanIds as $packPlanObj) {
					$tuniuParamObj['packageId'] = $packPlanObj['packPlanId'];
					$tuniuParamObj['routes'] = array();
					foreach($productIds as $productObj) {
						if ($productObj['packPlanId'] == $packPlanObj['packPlanId']) {
							$routesObj = array();
							$routesObj['id'] = $packPlanObj['productId'];
                            // 获取产品类型
                            $routesObj['type'] = DictionaryTools::getTypeTool($packPlanObj['productType']);
        					array_push($tuniuParamObj['routes'], $routesObj);
						}
					}
					if (!empty($tuniuParamObj['routes']) && is_array($tuniuParamObj['routes'])) {
						array_push($tuniuParam['params'], $tuniuParamObj);
					}
				}
				PackIao::onOffLineTuniuProduct($tuniuParam);
			}
			
			/*** 第二步 调用网站接口end ***/
			
			/*** 第三步 下线本地打包计划和产品start ***/
			
			// 下线本地打包计划和产品
			$offParam['packPlanIds'] = '';
			if (!empty($productArr['packPlanIds']) && is_array($productArr['packPlanIds'])) {
				foreach ($productArr['packPlanIds'] as $productArrObj) {
					$offParam['packPlanIds'] = $offParam['packPlanIds'].$productArrObj['packPlanId'].',';
				}
				$offParam['packPlanIds'] = substr($offParam['packPlanIds'], 0, strlen($offParam['packPlanIds']) - 1);
				$this->packageplanDao->offLinePlansAndProducts($offParam);
			}
			
			/*** 第三步 下线本地打包计划和产品end ***/
			
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 抛异常
            throw $e;
        }
        
        // 返回结果
        return true; 
    }
    
    /**
     * 查询列表头数量
     */
    public function getPackPlanTotalCount($param) {
    	// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			
			// 查询数量
			$count = $this->packageplanDao->queryPackPlanTotalCount($param);
			$data = array_merge($data, $count);
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '保存成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
    }
    
    /**
     * 查询打包计划状态
     */
    public function getPlanStatus($param) {
    	// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			
			// 查询数量
			$status = $this->packageplanDao->queryPlanStatus($param['packPlanId']);
			
			// 整合最终返回的正确结果
			$result['data']['status'] = $status['status'];
			$result['success'] = true;
			$result['msg'] = '保存成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
    }
    
    /**
     * 查询打包计划新
     */
    public function stablePackPlan($param) {
    	// 初始化返回结果
		$result = array();
		$data = array();		
		
		try {
			
			// 查询数量
			$data = $this->packageplanDao->stablePackPlan($param['rows'], $param['flag']);
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '执行成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
    }
    
    public function updateTime() {
    	$this->packageplanDao->updateTime();
    }
    
}
?>