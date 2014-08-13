<?php

Yii::import('application.modules.bidmanage.dal.dao.product.ClsrecommendDao');
Yii::import('application.modules.bidmanage.dal.iao.TuniuIao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');

Yii::import('application.modules.bidmanage.models.common.ComdbMod');
Yii::import('application.modules.bidmanage.models.common.CommonMod');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');


class ClsrecommendMod {

    private $clsrecommendDao;
    
    private $_iaoProductMod;
    
    private $_comdbMod;
    
    private $_commonMod;
	
    private $packageDateDao;
	
    private $_rorProductIao;
    
    const AD_KEY_COM = 'class_recommend';
    
    const AD_KEY_UNDERLINE = '_';
    
    const AD_NAME_CLASS = '分类页';
    
    const AD_NAME_HYPHEN  = '-';
    
    const COMMA = ",";
	
	const COMMA_UP = "'";
    
	function __construct() {
		$this->clsrecommendDao = new ClsrecommendDao();
		$this->_iaoProductMod = new IaoProductMod();
		$this->_comdbMod = new ComdbMod();
		$this->packageDateDao = new PackageDateDao();
		$this->_rorProductIao = new RorProductIao();
		$this->_commonMod = new CommonMod();
	}
	
	/**
	 * 根据出发城市获取分类页
	 */
	public function getClassInfoByCity($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;
		
		try {
			// 校验广告位接口存在性
			$existsId = $this->clsrecommendDao->queryClsrecomExists();
			if (empty($existsId)) {
				// 提示前端没同步分类页
				$data['count'] = -1;
			} else {
				// 同步了分类页
				
				// 查询已有报价
				$blockPri = $this->clsrecommendDao->queryBlockPrice($param);
				
				// 整合数据
				$data['rows'] = array_merge($data['rows'], $blockPri);
				$data['count'] = count($blockPri);
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
	 * 保存分类页全局配置
	 */
	public function saveOverallConfig($param) {
		// 初始化返回结果
		$result = array();
		
		try {
			// 第一步，初始化城市code参数，分类参数和新增修改集合
			$cities = $param['startCityCodes'];
			$citiesStr = "";
			$classes = $param['rows'];
			$classesStr = "";
			$insArr = array();
			
			foreach ($cities as $citiesObj) {
				$citiesStr = $citiesStr . $citiesObj['startCityCode'] . ",";
			}
			$citiesStr = substr($citiesStr, 0, strlen($citiesStr) - 1);
			
			foreach($classes as $classesObj) {
				$classesStr = $classesStr . $classesObj['classId'] . ",";
			}
			$classesStr = substr($classesStr, 0, strlen($classesStr) - 1);	
			
			// 第二步，删除已存在的分类页配置
			$delParam = array();
			$delParam['showDateId'] = $param['showDateId'];
			$delParam['startCityCodes'] = $citiesStr;
			$delParam['webClasses'] = $classesStr;
			$this->clsrecommendDao->deleteClsrecomConfig($delParam);

			// 第三步，查询每个城市下有多少一级分类
			$cityClasses = $this->clsrecommendDao->queryClassByCity($citiesStr);
			
			// 第四步，生成SQL数据集合
			$typeId = $this->clsrecommendDao->queryClsrecommendTypeId();
			if (!empty($typeId['id'])) {
				$typeId = $typeId['id'];
			} else {
				$typeId = 0;
			}
			foreach($classes as $classesObj) {
				foreach($cityClasses as $cityClassesObj) {
					if ($cityClassesObj['webClass'] == $classesObj['classId']) {
						$addArrTemp = array();
						$addArrTemp['adName'] = $classesObj['className'];
						$addArrTemp['startCityCode'] = $cityClassesObj['startCityCode'];
						$addArrTemp['floorPrice'] = $classesObj['floorPrice'];
						$addArrTemp['adProductCount'] = $classesObj['adProductCount'];
						$addArrTemp['couponUsePercent'] = $classesObj['couponUsePercent'];
						$addArrTemp['isMajor'] = $cityClassesObj['isMajor'];
						$addArrTemp['adKeyType'] = 21;
						$addArrTemp['webClass'] = $cityClassesObj['webClass'];
						array_push($insArr, $addArrTemp);
					}
				}
			}
			// 第四步，生成SQL
			$column = array('ad_name', 'start_city_code', 'floor_price', 'ad_product_count', 'coupon_use_percent', 
							'is_major', 'ad_key_type', 'web_class', 'ad_key', 'type_id', 'show_date_id', 'add_uid', 'add_time', 
							'update_uid', 'update_time');
			$columnValue = array('adName', 'startCityCode', 'floorPrice', 'adProductCount', 'couponUsePercent', 
							'isMajor', 'adKeyType', 'webClass');
			$defaultValue = array(self::AD_KEY_COM, $typeId, $param['showDateId'], 4333, date('Y-m-d H:i:s'), 4333, date('Y-m-d H:i:s'));
			$sqlToAdds = $this->_comdbMod->generateComInsert("ba_ad_position", $column, $columnValue, $insArr, $defaultValue);
			// 第五步，执行数据库操作
			$this->clsrecommendDao->executeSql($sqlToAdds, DaoModule::SALL);
			
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
	 * 保存分类页特殊配置
	 */
	public function saveSpecialConfig($param) {
		// 初始化返回结果
		$result = array();
		
		try {

			// 初始化城市和分类数据集合
			$rows = $param['rows'];
			
			// 初始化城市和SQL集合
			$cityArr = array();
			$insArr = array();
			foreach ($rows as $rowsObj) {
				$addArrTemp = array();
				array_push($cityArr, $rowsObj['startCityCode']);
				$addArrTemp['adName'] = $rowsObj['adName'];
				$addArrTemp['startCityCode'] = $rowsObj['startCityCode'];
				$addArrTemp['webClass'] = $rowsObj['classId'];
				$addArrTemp['isMajor'] = $rowsObj['isMajor'];
				$addArrTemp['floorPrice'] = $param['floorPrice'];
				$addArrTemp['adProductCount'] = $param['adProductCount'];
				$addArrTemp['couponUsePercent'] = $param['couponUsePercent'];
				$addArrTemp['adKeyType'] = 20 + $param['classDepth'];
				array_push($insArr, $addArrTemp);
			}
			$cityStr = implode(',', $cityArr);
			
			// 整合城市维度
			$wd = array();
			foreach ($cityArr as $cityArrObj) {
				$wdTemp = array();
				$wdTemp['startCityCode'] = $cityArrObj;
				$classesStr = "";
				foreach ($rows as $rowsObj) {
					if ($cityArrObj == $rowsObj['startCityCode']) {
						$classesStr = $classesStr.$rowsObj['classId'].',';
					}
				}
				$classesStr = substr($classesStr, 0, strlen($classesStr) - 1);
				$wdTemp['webClasses'] = $classesStr;
				array_push($wd, $wdTemp);
			}
			
			
			// 删除老数据
			$this->clsrecommendDao->deleteSpecialConfig($param, $wd);
			
			// 查询类型ID
			$typeId = $this->clsrecommendDao->queryClsrecommendTypeId();
			if (!empty($typeId['id'])) {
				$typeId = $typeId['id'];
			} else {
				$typeId = 0;
			}
			
			// 生成SQL
			$column = array('ad_name', 'start_city_code', 'floor_price', 'ad_product_count', 'coupon_use_percent', 
							'is_major', 'ad_key_type', 'web_class', 'ad_key', 'type_id', 'show_date_id', 'add_uid', 'add_time', 
							'update_uid', 'update_time');
			$columnValue = array('adName', 'startCityCode', 'floorPrice', 'adProductCount', 'couponUsePercent', 
							'isMajor', 'adKeyType', 'webClass');
			$defaultValue = array(self::AD_KEY_COM, $typeId, $param['showDateId'], 4333, date('Y-m-d H:i:s'), 4333, date('Y-m-d H:i:s'));
			$sqlToAdds = $this->_comdbMod->generateComInsert("ba_ad_position", $column, $columnValue, $insArr, $defaultValue);
			
			// 第五步，执行数据库操作
			$this->clsrecommendDao->executeSql($sqlToAdds, DaoModule::SALL);
			
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
	 * 查询分类页特殊配置
	 */
	public function getSpecialConfig($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;
		
		try {
			// 查询出发城市
			$beginCityList = $this->_commonMod->getBackCity();
			
			// 查询已添加的位置维度
			$wdDb = $this->clsrecommendDao->queryPositionWd($param);
			
			// 查询同步表信息
			$reDb = array();
			$reDbRows = array();
			if (!empty($wdDb['all']) && is_array($wdDb['all'])) {
				// 整合数据
				$wdDbFin = array();
				foreach($wdDb['cls'] as $clsObj) {
					$wdDbFinTemp = array();
					$wdDbFinTemp['webClass'] = $clsObj['webClass'];
					$clsStr="";
					foreach($wdDb['all'] as $allObj) {
						$clsStr=$clsStr.$allObj['startCityCode'].",";
					}
					if (0 < strlen($clsStr)) {
						$wdDbFinTemp['startCityCodes'] = substr($clsStr, 0, strlen($clsStr) - 1);
						array_push($wdDbFin, $wdDbFinTemp);
					}
				}
				
				
				$reDb = $this->clsrecommendDao->querySyncPositionInfoByClass($param, $wdDbFin);
				$reDbRows = $reDb['rows'];
			}
			
			// 查询报价信息
			if (!empty($reDbRows) && is_array($reDbRows)) {
				
				// 初始化城市维度集合
				$citiesArr = array();
				foreach ($reDbRows as $reDbObj) {
					array_push($citiesArr, $reDbObj['startCityCode']);
				}
				$citiesArr = array_unique($citiesArr);
				$citiesStr = implode(',', $citiesArr);
				
				// 区分是二级分类还是三级分类，如果是二级分类，则进行三级处理
				$wdDbSub = array();
				if (3 == $param['classDepth']) {
					$wdSubParam = array();
					foreach ($citiesArr as $citiesArrObj) {
						$wdSubParamTemp = array();
						$wdSubParamTemp['startCityCode'] = $citiesArrObj;
						$webClasses = "";
						foreach ($reDbRows as $reDbObj) {
							if ($citiesArrObj == $reDbObj['startCityCode']) {
								$webClasses = $webClasses.$reDbObj['classId'].",";
							}
						}
						$wdSubParamTemp['webClasses'] = substr($webClasses, 0, strlen($webClasses) - 1);
						array_push($wdSubParam, $wdSubParamTemp);
					}
					
					$wdDbSub = $this->clsrecommendDao->querySyncPositionInfoByCity($param, $wdSubParam);
				}
				
				// 查询总体价格信息
				$priceParam['showDateId'] = $param['showDateId'];
				$priceParam['startCityCodes'] = $citiesStr;
				$priceDb = $this->clsrecommendDao->queryClassPriceInfo($priceParam);
				
				// 如果价格信息不为空则，整理出一级，二级和三级价格信息并整合数据
				if (!empty($priceDb) && is_array($priceDb)) {
					$oneClass = array();
					$twoClass = array();
					$threeClass = array();
					foreach ($priceDb as $priceDbObj) {
						if (21 == $priceDbObj['adKeyType']) {
							array_push($oneClass, $priceDbObj);
						} else if (22 == $priceDbObj['adKeyType']) {
							array_push($twoClass, $priceDbObj);
						} else if (23 == $priceDbObj['adKeyType']) {
							array_push($threeClass, $priceDbObj);
						}
					}
					$listCities = $beginCityList['list'];
					// 筛选出最新的配置信息
					// 配置一级信息
					foreach ($reDbRows as &$reDbObj) {
						foreach ($oneClass as $oneClassObj) {
							if ($reDbObj['parentClass'] == $oneClassObj['webClass'] && $reDbObj['startCityCode'] == $oneClassObj['startCityCode']) {
								$reDbObj['startCityName'] = $listCities[$reDbObj['startCityCode']];
								$reDbObj['floorPrice'] = $oneClassObj['floorPrice'];
								$reDbObj['adProductCount'] = $oneClassObj['adProductCount'];
								$reDbObj['couponUsePercent'] =  $oneClassObj['couponUsePercent'];
								$reDbObj['updateTime'] = $oneClassObj['updateTime'];
								break;
							}
						}
					}
					
					// 分类配置二，三级信息
					if (2 == $param['classDepth']) {
						// 配置三级的二级信息
						foreach ($reDbRows as &$reDbObj) {
							foreach ($twoClass as $twoClassObj) {
								if ($reDbObj['classId'] == $twoClassObj['webClass'] && $reDbObj['startCityCode'] == $twoClassObj['startCityCode'] && strtotime($reDbObj['updateTime']) < strtotime($twoClassObj['updateTime'])) {
									$reDbObj['startCityName'] = $listCities[$reDbObj['startCityCode']];
									$reDbObj['floorPrice'] = $twoClassObj['floorPrice'];
									$reDbObj['adProductCount'] = $twoClassObj['adProductCount'];
									$reDbObj['couponUsePercent'] =  $twoClassObj['couponUsePercent'];
									$reDbObj['updateTime'] = $twoClassObj['updateTime'];
									break;
								}
							}
						}
					} else if (3 == $param['classDepth']) {
						// 配置二级信息前先绑定二级父分类
						foreach ($reDbRows as &$reDbObj) {
							foreach ($wdDbSub as $wdDbSubObj) {
								if ($reDbObj['classId'] == $wdDbSubObj['classId'] && $reDbObj['startCityCode'] == $wdDbSubObj['startCityCode']) {
									$reDbObj['parentTwoClass'] = $wdDbSubObj['parentClass'];
								}
							}
						}
						
						// 配置三级的二级信息
						foreach ($reDbRows as &$reDbObj) {
							foreach ($twoClass as $twoClassObj) {
								if ($reDbObj['parentTwoClass'] == $twoClassObj['webClass'] && $reDbObj['startCityCode'] == $twoClassObj['startCityCode'] && strtotime($reDbObj['updateTime']) < strtotime($twoClassObj['updateTime'])) {
									$reDbObj['startCityName'] = $listCities[$reDbObj['startCityCode']];
									$reDbObj['floorPrice'] = $twoClassObj['floorPrice'];
									$reDbObj['adProductCount'] = $twoClassObj['adProductCount'];
									$reDbObj['couponUsePercent'] =  $twoClassObj['couponUsePercent'];
									$reDbObj['updateTime'] = $twoClassObj['updateTime'];
									break;
								}
							}
						}
						// 配置三级信息
						foreach ($reDbRows as &$reDbObj) {
							foreach ($threeClass as $threeClassObj) {
								if ($reDbObj['classId'] == $threeClassObj['webClass'] && $reDbObj['startCityCode'] == $threeClassObj['startCityCode'] && strtotime($reDbObj['updateTime']) < strtotime($threeClassObj['updateTime'])) {
									$reDbObj['startCityName'] = $listCities[$reDbObj['startCityCode']];
									$reDbObj['floorPrice'] = $threeClassObj['floorPrice'];
									$reDbObj['adProductCount'] = $threeClassObj['adProductCount'];
									$reDbObj['couponUsePercent'] =  $threeClassObj['couponUsePercent'];
									$reDbObj['updateTime'] = $threeClassObj['updateTime'];
									break;
								}
							}
						}
					}
				}  
				
				$data['rows'] = $reDbRows;
				$data['count'] = $reDb['count'];
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
	 * 同步分类和城市数据到memcache
	 */
	public function syncWebClassAndCity() {
		// 查询出发城市
		$beginCityList = array();
		$memcacheKey = md5('CommonController.doRestGetStartCityBackground');
	    $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
	    if(!empty($finalBeginCityResult) && !empty($finalBeginCityResult['all']) && !empty($finalBeginCityResult['major']) 
	    	&& !empty($finalBeginCityResult['minor']) && !empty($finalBeginCityResult['list']) && !empty($finalBeginCityResult['isMajor']) 
	    	&& !empty($finalBeginCityResult['isMinor'])){
	        $beginCityList = $finalBeginCityResult;
	    } else {
	        $beginCityList = $this->_iaoProductMod->getMultiCityInfo();
	        $cityAll = $beginCityList['all'];
	        foreach ($cityAll as $cityAllObj) {
	         	$beginCityList['list'][$cityAllObj['code']] = $cityAllObj['name'];
	        }
	        $cityMajor = $beginCityList['major'];
	        $isMajor = array();
	        foreach ($cityMajor as $cityMajorObj) {
	         	array_push($isMajor, $cityMajorObj['code']);
	        }
	        $beginCityList['isMajor'] = $isMajor;
	        $cityMinor = $beginCityList['minor'];
	        $isMinor = array();
	        foreach ($cityMinor as $cityMinorObj) {
	         	array_push($isMinor, $cityMinorObj['code']);
	        }
	        $beginCityList['isMinor'] = $isMinor;
	        // 缓存24h
	        Yii::app()->memcache->set($memcacheKey, $beginCityList, 86400); 		
	    }
	    
	    $all = $beginCityList['major'];
	    foreach ($all as $allObj) {
	    	// 从memcache获取区块
			$memKey = md5('ClsrecommendMod::clsrecommendMod_' . $allObj['code']);
       		$clsrecomResult = Yii::app()->memcache->get($memKey);
        	// 如果memcache结果为空，则调用搜索接口获取区块
			if(empty($clsrecomResult)) {
				$rorParam = array();
				$rorParam['classBrandTypes'] = array(1);
				$rorParam['startCityCode'] = $allObj['code'];
				$clsrecomResult = $this->_rorProductIao->queryWebCategoryList($rorParam);
				// 判断是否调用成功
				if (!empty($clsrecomResult) && !empty($clsrecomResult['filters'][0]['cateValues'])) {
					$clsrecomResult = $clsrecomResult['filters'][0]['cateValues'];
					// 缓存9h
            		Yii::app()->memcache->set($memKey, $clsrecomResult, 32400); 				
				} else {
					// 继续下一层循环
					continue;
				}
			}
	    }
	}
	
	/**
	 * 同步分类页位置数据
	 */
	public function syncWebClassPosition($param) {
		
		try {
			// 第一步，查询出发城市
			$beginCityList = $this->_commonMod->getBackCity();
		    // 若是全部同步，则添加所有城市
		    if (-1 == $param['startCityCode']) {
		    	$paramCity = array();
		    	$paramCity = array_merge($paramCity, $beginCityList['isMajor']);
		    	$paramCity = array_merge($paramCity, $beginCityList['isMinor']);
		    	$param['startCityCode'] = $paramCity;
		    } else {
		    	$param['startCityCode'] = explode(',', $param['startCityCode']);
		    }
		    // 获取分类页对应的类型ID
			$typeId = $this->clsrecommendDao->queryClsrecommendTypeId();
			if (!empty($typeId['id'])) {
				$typeId = $typeId['id'];
			} else {
				$typeId = 0;
			}
		    // 第二步，循环初始化数据
		    $addSqlDb = array();
		    $cities = $param['startCityCode'];
			foreach($cities as $citiesObj) {
				// 初始化自营和非自营标记
				$isMajor = 0;
				if (in_array($citiesObj, $beginCityList['isMajor'])) {
					$isMajor = 1;
				} else if (in_array($citiesObj, $beginCityList['isMinor'])) {
					$isMajor = 0;
				}
				// 从memcache获取区块
				$memKey = md5('ClsrecommendMod::clsrecommendMod_' . $citiesObj['startCityCode']);
	       		$clsrecomResult = Yii::app()->memcache->get($memKey);
	        	// 如果memcache结果为空，则调用搜索接口获取区块
				if(empty($clsrecomResult)) {
					$rorParam = array();
					$rorParam['classBrandTypes'] = array(1);
					$rorParam['startCityCode'] = $citiesObj;
					$clsrecomResult = $this->_rorProductIao->queryWebCategoryList($rorParam);
					// 判断是否调用成功
					if (!empty($clsrecomResult) && !empty($clsrecomResult['filters'][0]['cateValues'])) {
						$clsrecomResult = $clsrecomResult['filters'][0]['cateValues'];
						// 缓存9h
	            		Yii::app()->memcache->set($memKey, $clsrecomResult, 32400); 				
					} else {
						// 继续下一层循环
						continue;
					}
				}
				
				// 循环初始化SQL
				foreach($clsrecomResult as $clsrecomResultObj) {
					$rootName = str_replace('目的地', '', $clsrecomResultObj['name']);
					$addAllSqlDbObj = array();
					$addAllSqlDbObj['adName'] = $rootName;
					$addAllSqlDbObj['classDepth'] = 1;
					$addAllSqlDbObj['isMajor'] = $isMajor;
					$addAllSqlDbObj['startCityCode'] = $citiesObj;
					$addAllSqlDbObj['webClass'] = $clsrecomResultObj['id'];
					$addAllSqlDbObj['parentClass'] = 0;
					$addAllSqlDbObj['parentDepth'] = 0;
					
					array_push($addSqlDb, $addAllSqlDbObj);
					$fchildren = $clsrecomResultObj['children'];
					if (!empty($fchildren) && is_array($fchildren)) {
						foreach($fchildren as $fchildrenObj) {
							$fname = $rootName.self::AD_NAME_HYPHEN.$fchildrenObj['name'];
							$addAllSqlDbObj = array();
							$addAllSqlDbObj['adName'] = $fname;
							$addAllSqlDbObj['classDepth'] = 2;
							$addAllSqlDbObj['isMajor'] = $isMajor;
							$addAllSqlDbObj['startCityCode'] = $citiesObj;
							$addAllSqlDbObj['webClass'] = $fchildrenObj['id'];
							$addAllSqlDbObj['parentClass'] = $clsrecomResultObj['id'];
							$addAllSqlDbObj['parentDepth'] = 1;
							array_push($addSqlDb, $addAllSqlDbObj);
							$schildren = $fchildrenObj['children'];
							if (!empty($schildren) && is_array($schildren)) {
								foreach($schildren as $schildrenObj) {
									$addAllSqlDbObj = array();
									$addAllSqlDbObj['adName'] = $fname.self::AD_NAME_HYPHEN.$schildrenObj['name'];
									$addAllSqlDbObj['classDepth'] = 3;
									$addAllSqlDbObj['isMajor'] = $isMajor;
									$addAllSqlDbObj['startCityCode'] = $citiesObj;
									$addAllSqlDbObj['webClass'] = $schildrenObj['id'];
									$addAllSqlDbObj['parentClass'] = $fchildrenObj['id'];
									$addAllSqlDbObj['parentDepth'] = 2;
									array_push($addSqlDb, $addAllSqlDbObj);
									$addAllSqlDbObj = array();
									$addAllSqlDbObj['adName'] = $fname.self::AD_NAME_HYPHEN.$schildrenObj['name'];
									$addAllSqlDbObj['classDepth'] = 3;
									$addAllSqlDbObj['isMajor'] = $isMajor;
									$addAllSqlDbObj['startCityCode'] = $citiesObj;
									$addAllSqlDbObj['webClass'] = $schildrenObj['id'];
									$addAllSqlDbObj['parentClass'] = $clsrecomResultObj['id'];
									$addAllSqlDbObj['parentDepth'] = 1;
									array_push($addSqlDb, $addAllSqlDbObj);
								}
							}
						}
					}				
				}	
			}
		    // 第三步，初始化新增SQL
			$column = array('ad_name', 'start_city_code', 'is_major', 'class_depth', 'web_class', 'parent_class', 'parent_depth');
			$columnValue = array('adName', 'startCityCode', 'isMajor', 'classDepth', 'webClass', 'parentClass', 'parentDepth');
			$defaultValue = array();
			$sqlToAdds = $this->_comdbMod->generateComInsert("position_sync_class", $column, $columnValue, $addSqlDb, $defaultValue);
	
		    // 第四步，初始化删除SQL
		    // $sqlToDel = "update position_sync_class set del_flag = 1";
		    
		    // 第五步，删除旧有数据
		    // $this->clsrecommendDao->executeSql($sqlToDel, DaoModule::SROW);
		    
		    // 批量执行新增
		    for($i = 0, $m = count($sqlToAdds); $i < $m; $i = $i+50) {
		    	$this->clsrecommendDao->executeSql(array_slice($sqlToAdds, $i, 50), DaoModule::SALL);
		    }
			
	    } catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 抛异常
            throw $e;
		}
	    	    
	}
	
}
?>
