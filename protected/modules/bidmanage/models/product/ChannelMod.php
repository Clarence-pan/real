<?php

Yii::import('application.modules.bidmanage.dal.dao.product.ChannelDao');
Yii::import('application.modules.bidmanage.dal.iao.TuniuIao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');

Yii::import('application.modules.bidmanage.models.common.ComdbMod');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');


class ChannelMod {

    private $channelDao;
    
    private $_iaoProductMod;
    
    private $_comdbMod;
	
    private $packageDateDao;
	
    private $_rorProductIao;
    
    const AD_KEY_COM = 'channel_chosen_';
    
    const AD_KEY_UNDERLINE = '_';
    
    const AD_NAME_CHANNEL = '频道页';
    
    const AD_NAME_HYPHEN  = '-';
    
    const COMMA = ",";
	
	const COMMA_UP = "'";
    
	function __construct() {
		$this->channelDao = new ChannelDao();
		$this->_iaoProductMod = new IaoProductMod();
		$this->_comdbMod = new ComdbMod();
		$this->packageDateDao = new PackageDateDao();
		$this->_rorProductIao = new RorProductIao();
	}
    /**
     * 查询当前可参与竞拍的频道页的广告位
     * @param array $param
     * @return array
     */
    public function getChannelChosenAdKey($param) {
        $param = array(
        	'channelId' => intval($param['channelId']),
            'accountId' => intval($param['accountId']),
            'startCityCode' => intval($param['startCityCode']),
            'start' => intval($param['start']),
            'limit' => intval($param['limit']),
        );
//        $memKey = 'getChannelChosenAdKey_' . md5(json_encode($param));
//        $resultRows = Yii::app()->memcache->get($memKey);
//        if(!empty($resultRows)){
//            return $resultRows;
//        }
        $result = $this->channelDao->queryChannelChosenAdKey($param);
        if (!empty($result)) {
            // 获取所有出发城市
            $startCityList = $this->_iaoProductMod->getMultiCityInfo();
            foreach ($result as $k => $temp) {
                if ($temp['startCityCode']) {
                    // 根据出发城市code获取name
                    if ($startCityList['all']) {
                        foreach ($startCityList['all'] as $tempArr) {
                            if ($tempArr['code'] == $temp['startCityCode']) {
                                $result[$k]['startCityName'] = $tempArr['name'];
                                break;
                            }
                        }
                    }
                } else {
                    $result[$k]['startCityName'] = '';
                }
            }
        }

        // 获取agencyId
        $manage = new UserManageMod;
        $params = array('id' => $param['accountId']);
        $user = $manage->read($params);
        if (!in_array($user['vendorId'], Yii::app()->params['ADMINID'])) {
            // 过滤掉当前供应商没有产品的频道页广告位
            foreach ($result as $k => $temp) {
                $adCategory = $this->packageDateDao->getAdCategory(array('adKey' => $temp['adKey'], 'startCityCode' => $temp['startCityCode']));
                if ($adCategory) {
                    $categoryId = json_decode($adCategory['categoryId'], true);
                    $classBrandTypes = json_decode($adCategory['classBrandTypes'], true);
                    $catType = json_decode($adCategory['catType'], true);
                    $inputParams = array(
                        'vendorId' => $user['vendorId'],
                        'startCityCode' => $temp['startCityCode'],
                        'categoryId' => $categoryId,
                        'classBrandTypes' => $classBrandTypes,
                        'catType' => $catType,
                        'currentPage' => 1,
                        'limit' => 1
                    );

                    // 计算categoryId数据量，若大于200则分批查询搜索接口
                    $similarProduct = array();
                    $categoryIdCount = sizeof($inputParams['categoryId']);
                    if ($categoryIdCount > 200) {
                        // 临时变量
                        $categoryId = $inputParams['categoryId'];
                        // 使用循环每次200条来获取数据
                        for ($i = 0; $i < $categoryIdCount; $i = $i + 200) {
                            // 调用搜索接口获取相似产品
                            $inputParams['start'] = 0;
                            $inputParams['limit'] = 1;
                            $inputParams['categoryId'] = array_slice($categoryId, $i, 200);
                            $iaoReTemp = $this->_rorProductIao->querySimilarProductList($inputParams);
                            if (!empty($iaoReTemp) && $iaoReTemp['success'] && !empty($iaoReTemp['data']['rows']) && $iaoReTemp['data']['count'] > 0) {
                                $similarProduct['data']['count'] = $iaoReTemp['data']['count'];
                                $similarProduct['success'] = true;
                                break;
                            }
                        }
                    } else {
                        // 直接调用接口，获取相似产品
                        $similarProduct = $this->_rorProductIao->querySimilarProductList($inputParams);
                    }

                    if ($similarProduct['success'] && $similarProduct['data']['count'] > 0) {
                        continue;
                    } else {
                        unset($result[$k]);
                    }
                }
            }
        }

        //处理结果
        $rows = array();
        foreach ($result as $temp) {
            $rows[] = $temp;
        }

        //数据缓存15mins
//        Yii::app()->memcache->set($memKey, $rows, 900);

        return $rows;
    } 
    /**
	 * 查询招客宝的频道页区块信息
	 */
	public function getChannelChannelForBB($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;
		
		try {
			// 查询信息
			$dataDb = $this->channelDao->queryChannelChannelForBB($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				$data['rows'] = $dataDb;
				$data['count'] = count($dataDb);
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
	 * 查询海格的频道页区块信息
	 */
	public function getChannelChannelForHA($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;
		
		try {
			// 查询信息
			$dataDb = $this->channelDao->queryChannelChannelForHA($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				$data['rows'] = $dataDb;
				$data['count'] = count($dataDb);
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
	 * 同步频道页广告位
	 */
	public function syncChannelPositon($param) {
		// 初始化需要返回的城市编码
		$returnCode = -1;
		
		try {
		
			// 查询出发城市
			$memcacheKey = md5('CommonController.doRestGetStartCity');
	        $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
	        if(!empty($finalBeginCityResult) && !empty($finalBeginCityResult['all']) && !empty($finalBeginCityResult['major']) && !empty($finalBeginCityResult['minor'])){
	            $beginCityList = $finalBeginCityResult;
	        } else {
	            $beginCityList = $this->_iaoProductMod->getMultiCityInfo();	
	        }	
			
			// 初始化需要插入的广告位
			$adKeyArr = array();
			// 如果是所有城市，则遍历城市集合同步数据，否则，有判断有选择地同步数据
			if (-1 == $param['startCityCode']) {
				// 第一步，先同步自营城市
				$major = $beginCityList['major'];
				foreach ($major as $majorObj) {
					// 调用网站接口同步数据
					$tuniuData = TuniuIao::getTuniuChannelAdList($majorObj['code']);
					if (empty($tuniuData)) {
						$returnCode = $returnCode.",".$majorObj['code'];
					} else {
						// 整合网站数据
						foreach ($tuniuData as $tuniuDataObj) {
							if ("自助游" != $tuniuDataObj['header']['title'] && "自驾游" != $tuniuDataObj['header']['title']) {
								$items = $tuniuDataObj['items'];
								foreach ($items as $itemsObj) {
									$adKeyArrObj = array();
									$adKeyArrObj['blockId'] = $itemsObj['id'];
									$adKeyArrObj['blockName'] = $itemsObj['title'];
									$adKeyArrObj['catType'] = json_encode($tuniuDataObj['header']['catType']);
									$adKeyArrObj['categoryId'] = '['.$itemsObj['destination'].']';
									$adKeyArrObj['channelId'] = $tuniuDataObj['header']['id'];
									$adKeyArrObj['channelName'] = $tuniuDataObj['header']['title'];
									$adKeyArrObj['adKey'] = ChannelMod::AD_KEY_COM.$tuniuDataObj['header']['id'].ChannelMod::AD_KEY_UNDERLINE.$itemsObj['id'];
									$adKeyArrObj['adName'] = ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$tuniuDataObj['header']['title'].ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$itemsObj['title'];
									$adKeyArrObj['unitFloorPrice'] = $param['unitFloorPrice'];
									$adKeyArrObj['startCityCode'] = $majorObj['code'];
									$adKeyArrObj['classBrandTypes'] = '['.$itemsObj['classify'].']';
									$adKeyArrObj['isMajor'] = 1; 
									array_push($adKeyArr, $adKeyArrObj);
								}
							}
						}
					}
				}
				// 第二步，再同步非自营城市
				$minor = $beginCityList['minor'];
				foreach ($minor as $minorObj) {
					// 调用网站接口同步数据
					$tuniuData = TuniuIao::getTuniuChannelAdList($minorObj['code']);
					if (empty($tuniuData)) {
						$returnCode = $returnCode.",".$minorObj['code'];
					} else {
						// 整合网站数据
						foreach ($tuniuData as $tuniuDataObj) {
							if ("自助游" != $tuniuDataObj['header']['title'] && "自驾游" != $tuniuDataObj['header']['title']) {
								$items = $tuniuDataObj['items'];
								foreach ($items as $itemsObj) {
									$adKeyArrObj = array();
									$adKeyArrObj['blockId'] = $itemsObj['id'];
									$adKeyArrObj['blockName'] = $itemsObj['title'];
									$adKeyArrObj['catType'] = json_encode($tuniuDataObj['header']['catType']);
									$adKeyArrObj['categoryId'] = '['.$itemsObj['destination'].']';
									$adKeyArrObj['channelId'] = $tuniuDataObj['header']['id'];
									$adKeyArrObj['channelName'] = $tuniuDataObj['header']['title'];
									$adKeyArrObj['adKey'] = ChannelMod::AD_KEY_COM.$tuniuDataObj['header']['id'].ChannelMod::AD_KEY_UNDERLINE.$itemsObj['id'];
									$adKeyArrObj['adName'] = ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$tuniuDataObj['header']['title'].ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$itemsObj['title'];
									$adKeyArrObj['unitFloorPrice'] = $param['unitFloorPrice'];
									$adKeyArrObj['startCityCode'] = $minorObj['code'];
									$adKeyArrObj['classBrandTypes'] = '['.$itemsObj['classify'].']';
									$adKeyArrObj['isMajor'] = 0; 
									array_push($adKeyArr, $adKeyArrObj);
								}
							}
						}
					}
				}
			} else {
				// 第一步，有选择地同步数据
				$cities = explode($param['startCityCode'], ",");
				$citiesMajor = array();
				$citiesMinor = array();
				
				// 第二步，过滤自营城市			
				$major = $beginCityList['major'];
				foreach ($major as $majorObj) {
					foreach ($cities as $citiesObj) {
						if (0 == strcmp($citiesObj, $majorObj['code'])) {
							array_push($citiesMajor, $citiesObj);
							break;
						}
					}
				}
				
				// 第三步，过滤非自营城市			
				$minor = $beginCityList['minor'];
				foreach ($minor as $minorObj) {
					foreach ($cities as $citiesObj) {
						if (0 == strcmp($citiesObj, $minorObj['code'])) {
							array_push($citiesMinor, $citiesObj);
							break;
						}
					}
				}
				
				// 第四步，同步自营城市
				foreach ($citiesMajor as $citiesMajorObj) {
					// 调用网站接口同步数据
					$tuniuData = TuniuIao::getTuniuChannelAdList($citiesMajorObj['code']);
					if (empty($tuniuData)) {
						$returnCode = $returnCode.",".$citiesMajorObj['code'];
					} else {
						// 整合网站数据
						foreach ($tuniuData as $tuniuDataObj) {
							if ("自助游" != $tuniuDataObj['header']['title'] && "自驾游" != $tuniuDataObj['header']['title']) {
								$items = $tuniuDataObj['items'];
								foreach ($items as $itemsObj) {
									$adKeyArrObj = array();
									$adKeyArrObj['blockId'] = $itemsObj['id'];
									$adKeyArrObj['blockName'] = $itemsObj['title'];
									$adKeyArrObj['catType'] = json_encode($tuniuDataObj['header']['catType']);
									$adKeyArrObj['categoryId'] = '['.$itemsObj['destination'].']';
									$adKeyArrObj['channelId'] = $tuniuDataObj['header']['id'];
									$adKeyArrObj['channelName'] = $tuniuDataObj['header']['title'];
									$adKeyArrObj['adKey'] = ChannelMod::AD_KEY_COM.$tuniuDataObj['header']['id'].ChannelMod::AD_KEY_UNDERLINE.$itemsObj['id'];
									$adKeyArrObj['adName'] = ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$tuniuDataObj['header']['title'].ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$itemsObj['title'];
									$adKeyArrObj['unitFloorPrice'] = $param['unitFloorPrice'];
									$adKeyArrObj['startCityCode'] = $citiesMajorObj['code'];
									$adKeyArrObj['classBrandTypes'] = '['.$itemsObj['classify'].']';
									$adKeyArrObj['isMajor'] = 1; 
									array_push($adKeyArr, $adKeyArrObj);
								}
							}
						}
					}
				}
				
				// 第五步，同步非自营城市
				$minor = $beginCityList['minor'];
				foreach ($citiesMinor as $citiesMinorObj) {
					// 调用网站接口同步数据
					$tuniuData = TuniuIao::getTuniuChannelAdList($citiesMinorObj['code']);
					if (empty($tuniuData)) {
						$returnCode = $returnCode.",".$citiesMinorObj['code'];
					} else {
						// 整合网站数据
						foreach ($tuniuData as $tuniuDataObj) {
							if ("自助游" != $tuniuDataObj['header']['title'] && "自驾游" != $tuniuDataObj['header']['title']) {
								$items = $tuniuDataObj['items'];
								foreach ($items as $itemsObj) {
									$adKeyArrObj = array();
									$adKeyArrObj['blockId'] = $itemsObj['id'];
									$adKeyArrObj['blockName'] = $itemsObj['title'];
									$adKeyArrObj['catType'] = json_encode($tuniuDataObj['header']['catType']);
									$adKeyArrObj['categoryId'] = '['.$itemsObj['destination'].']';
									$adKeyArrObj['channelId'] = $tuniuDataObj['header']['id'];
									$adKeyArrObj['channelName'] = $tuniuDataObj['header']['title'];
									$adKeyArrObj['adKey'] = ChannelMod::AD_KEY_COM.$tuniuDataObj['header']['id'].ChannelMod::AD_KEY_UNDERLINE.$itemsObj['id'];
									$adKeyArrObj['adName'] = ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$tuniuDataObj['header']['title'].ChannelMod::AD_NAME_CHANNEL.ChannelMod::AD_NAME_HYPHEN.$itemsObj['title'];
									$adKeyArrObj['unitFloorPrice'] = $param['unitFloorPrice'];
									$adKeyArrObj['startCityCode'] = $citiesMinorObj['code'];
									$adKeyArrObj['classBrandTypes'] = '['.$itemsObj['classify'].']';
									$adKeyArrObj['isMajor'] = 0; 
									array_push($adKeyArr, $adKeyArrObj);
								}
							}
						}
					}
				}
			}
			// 插入数据库
			$column = array('ad_key', 'ad_name', 'start_city_code', 'category_id', 'cat_type', 'class_brand_types', 
							'unit_floor_price', 'is_minor', 'channel_id', 'channel_name', 'block_id', 'block_name', 
							'ad_key_type', 'add_uid', 'add_time', 'update_uid', 'update_time', 'del_flag', 'misc');
			$columnValue = array('adKey', 'adName', 'startCityCode', 'categoryId', 'catType', 'classBrandTypes', 
							'unitFloorPrice', 'isMajor', 'channelId', 'channelName', 'blockId', 'blockName');
			$defaultValue = array(5, 4333, date('Y-m-d H:i:s'), 4333, date('Y-m-d H:i:s'), 0, "");
			$sqlArr = $this->_comdbMod->generateComInsert("ba_ad_position_type", $column, $columnValue, $adKeyArr, $defaultValue);
			$this->channelDao->syncChannelPositon($sqlArr);
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 抛异常
            throw $e;
        }
        
		// 返回结果
		return $returnCode;
	}
	
	/**
	 * 保存全局配置
	 */
	public function saveOverallConfig($param) {
		// 初始化返回结果
		$result = array();
		
		try {
			// 新增集合
			$toAdd = array();
			
			// 删除已配置的城市			
			$cities = $param['startCityCodes'];
			$adKeys = $param['rows'];
			$citiesArr = array();
			$channelsArr = array();
			$adKeyParam = array();
			$adKeyDb = array();
			
			foreach ($cities as $citiesObj) {
				array_push($citiesArr, $citiesObj['startCityCode']);
			}
			
			$adKeyParam['startCityCodes'] = implode($citiesArr, ',');
			
			foreach ($adKeys as $adKeysObj) {
				array_push($channelsArr, $adKeysObj['channelId']);	
			}
			
			$adKeyParam['channelIds'] = implode($channelsArr, ',');
			$adKeyParam['showDateId'] = $param['showDateId'];
			
			$adKeyDb = $this->channelDao->deleteExistAdkey($adKeyParam);
			
			
			
			// 新增广告位信息
			$param['startCityCodes'] = $adKeyParam['startCityCodes'];
			$this->channelDao->saveOverallConfig($param);
			
			// 整合最终返回的正确结果
			$result['data'] = array();
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
	 * 保存特殊非统一配置
	 */
	public function saveSpecialNoConfig($param) {
		// 初始化返回结果
		$result = array();
		
		try {
			
			// 保存特殊非统一配置
			$this->channelDao->saveSpecialNoConfig($param);
			
			// 整合最终返回的正确结果
			$result['data'] = array();
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
	 * 保存特殊统一配置
	 */
	public function saveSpecialYesConfig($param) {
		// 初始化返回结果
		$result = array();
		
		try {
			
			// 保存特殊统一配置
			$this->channelDao->saveSpecialYesConfig($param);
			
			// 整合最终返回的正确结果
			$result['data'] = array();
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
     * 查询招客宝的频道页特殊配置列表信息
     */
	public function getSpecialconfig($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		$data['count'] = 0;
		
		try {
			// 查询信息
			$dataDb = $this->channelDao->querySpecialconfig($param);
			//查询有多少条数据
			$dataCount = $this->channelDao->querySpecialconfigCount($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				$data['rows'] = $dataDb;
				$data['count'] = $dataCount['count'];
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
	
}
?>
