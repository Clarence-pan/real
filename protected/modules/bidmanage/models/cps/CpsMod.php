<?php

Yii::import('application.modules.bidmanage.dal.dao.cps.CpsDao');
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');
Yii::import('application.modules.bidmanage.dal.iao.TuniuIao');

/**
 * CPS业务处理类
 */
class CpsMod {
    
    /**
     * 数据库处理类
     */
    private $cpsDao;
    
    /**
     * 日志类
     */
    private $bbLog;
    
    /**
     * 搜索接口调用类
     */
    private $rorProductIao;
    
    /**
     * 默认构造函数
     */
	function __construct() {
		// 初始化日志类
		$this->bbLog = new BBLog();
		// 初始化数据库处理类
		$this->cpsDao = new CpsDao();
		// 初始化搜索接口调用类
		$this->rorProductIao = new RorProductIao();
	}	
	
	/**
	 * 获取需要编辑的CPS产品
	 */
	public function getCpsProduct($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."获取需要编辑的CPS产品", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}
		
		// 初始化返回结果
		$result = array();
		$result['rows'] = array();
		$result['count'] = 0;
		$rows = array();

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 添加监控示例
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			$flag = Symbol::BPM_EIGHT_HUNDRED;
			
			// 从memcache获取导航树
			$memKey = md5('CpsMod::getCpsProduct' . $param['startCityCode']);
	   		$clsrecomResult = Yii::app()->memcache->get($memKey);
	       	// 如果memcache结果为空，则调用搜索接口获取区块
			if(empty($clsrecomResult)) {
				$tuniuParam = array();
				$tuniuParam['leftFlag'] = 1;
				$tuniuParam['cityCode'] = $param['startCityCode'];
				$clsrecomResult = TuniuIao::getTuniuLeftHeaderMenuInfo($tuniuParam);
				// 判断是否调用成功
				if (!empty($clsrecomResult)) {
					$webClassIds = array();
					foreach ($clsrecomResult as $clsrecomResultObj) {
						$f_default_cat = $clsrecomResultObj['f_default_cat'];
						$f_recommend_cat = $clsrecomResultObj['f_recommend_cat'];
						foreach($f_default_cat as $f_default_catObj) {
							array_push($webClassIds, $f_default_catObj['id']);
							array_push($webClassIds, $f_default_catObj['parentId']);
							$children = $f_default_catObj['children'];
							foreach($children as $childrenObj) {
								array_push($webClassIds, $childrenObj['id']);
							}
						}
						foreach($f_recommend_cat as $f_recommend_catObj) {
							array_push($webClassIds, $f_recommend_catObj['id']);
						}
					}
					$webClassIds = array_unique($webClassIds);
					$clsrecomResult = $webClassIds;
					// 缓存3h
		         	Yii::app()->memcache->set($memKey, $webClassIds, 10800); 				
				} else {
					return array();
				}
			}
			
			// 查询供应商拥有的分类
			$webParam['agencyId'] = $param['agencyId'];
			$webParam['startCityCode'] = $param['startCityCode'];
			$webParam['classBrandTypes'] = array($param['productType']);
			$webRows = $this->rorProductIao->queryWebCategoryList($webParam);
			$cateValues = $webRows['filters'][0]['cateValues'];
			// 如果供应商没有分类信息，则返回空结果
			if (empty($cateValues) || !is_array($cateValues)) {
				// 返回结果
        		return $result; 
			}
			
			// 初始化cateValues  ID集合
	      	$cateIds = array();  
	        foreach($cateValues as $key => $val){
	        	array_push($cateIds, $val["id"]);  
	        	foreach($val['children'] as $fkey => $fchildrenObj) {
	        		array_push($cateIds, $fchildrenObj["id"]);
	        		foreach($fchildrenObj['children'] as $skey => $schildrenObj) {
	        			array_push($cateIds, $schildrenObj["id"]);
	        		}
	        	}
	        }
	        
	        // 获取和导航树的交集
	        $cateIds = array_intersect($cateIds, $clsrecomResult);
			
			$webClassesOther = array();
			if (!empty($param['webClassName'])) {
				// 需要筛选分类
				// 查询分类信息
				$webClasses = $this->cpsDao->getWebClassByName($param);
				// 如果没有分类信息，则返回空结果
				if (empty($webClasses) || !is_array($webClasses)) {
					// 返回结果
	        		return $result; 
				}
				
				// 整合并分割分类信息
				foreach ($webClasses as $webClassesObj) {
					array_push($webClassesOther, $webClassesObj['web_class']);
				}
				unset($webClassesObj);
				
				// 获取和本地分类的交集
		        $webClassesOther = array_intersect($webClassesOther, $cateIds);
			} else {
				// 不需要筛选分类
				$webClassesOther = $cateIds;
			}
			
			// 循环调用搜索接口，捞出所有产品
			$allProduct = array();
			$mainProduct = array();
			$allCount = intval(count($webClassesOther) / Symbol::TWO_HUNDRED) + 1;
			$rorParam = array();
			$rorParam['productType'] = $param['productType'];
			$rorParam['vendorId'] = $param['agencyId'];
			$rorParam['startCityCode'] = $param['startCityCode'];
			
			for ($i = 0; $i < $allCount; $i++) {
				$rorParam['start'] = chr(48);
				$rorParam['limit'] = chr(49);
				$rorParam['categoryId'] = array_slice($webClassesOther, $i * Symbol::TWO_HUNDRED, Symbol::TWO_HUNDRED);
				$temp = $this->rorProductIao->querySimilarProductList($rorParam);
				$tempCount = $temp['data']['count'];
				if (Symbol::ONE_THOUSAND < $tempCount) {
					$subCount = intval($tempCount / Symbol::ONE_THOUSAND) + 1;
					$rorParam['limit'] = Symbol::ONE_THOUSAND;
					for ($j = 0; $j < $allCount; $j++) {
						$rorParam['start'] = $j * Symbol::ONE_THOUSAND;
						$temp = $this->rorProductIao->querySimilarProductList($rorParam);
						$allProduct = array_merge($allProduct, $temp['data']['rows']);
						$rorParam['showFlag'] = chr(48);
						$temp = $this->rorProductIao->querySimilarProductList($rorParam);
						$mainProduct = array_merge($mainProduct, $temp['data']['rows']);
						unset($rorParam['showFlag']);
					}
				} else {
					$rorParam['limit'] = $tempCount;
					$temp = $this->rorProductIao->querySimilarProductList($rorParam);
					$allProduct = array_merge($allProduct, $temp['data']['rows']);
					$rorParam['showFlag'] = chr(48);
					$temp = $this->rorProductIao->querySimilarProductList($rorParam);
					$mainProduct = array_merge($mainProduct, $temp['data']['rows']);
					unset($rorParam['showFlag']);
				}
				unset($temp);
			}			
			unset($webClassesOther);
			
			// 获取打包产品
			$packProduct = $this->cpsDao->getPackProduct($param);
			$packProducts = array();
			foreach($packProduct as $packProductObj) {
				$packProducts[] = $packProductObj['product_id'];
			}
			
			// 整合数据
			$singlePro = array();
			$mainKeyPro = array();
			foreach($mainProduct as $mainProductObj) {
				$mainKeyPro[$mainProductObj['productId']] = chr(49);
			}
			foreach($allProduct as $allProductObj) {
				if (!in_array($allProductObj['productId'], $packProducts) && !in_array($allProductObj['productId'], $singlePro)) {
					$tempRows['isPrinciple'] = CommonTools::getIsOrNot($mainKeyPro[$allProductObj['productId']]);
					$tempRows['productId'] = $allProductObj['productId'];
					$tempRows['productType'] = $allProductObj['productType'];
					$tempRows['productName'] = $allProductObj['productName'];
					$singlePro[] = $allProductObj['productId'];
					$rows[] = $tempRows;
				}
			}
			$result['rows'] = $rows;
			$result['count'] = count($rows);
			
			// 结束监控示例
			BPMoniter::endMoniter($posTry, $flag, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."获取供应商消耗信息异常", $e);
        }
        
        // 返回结果
        return $result; 
	}
	
	/**
	 * 获取CPS区块
	 */
	public function getCpsBlock($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."获取CPS区块", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}
		
		// 初始化返回结果
		$result = array();
		$result['blocks'] = array();
		$result['blocksInfo'] = array();

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 添加监控示例
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			$flag = Symbol::BPM_EIGHT_HUNDRED;
			
			// 获取网站区块
			
			
			// 查询现有区块
			$existsInfo = $this->cpsDao->getExistsBlockProduct($param);
			
			// 整合区块维度
			$tuniuBlock = array(array('blockId' => 1, 'blockName' => 'aaaa'),array('blockId' => 2, 'blockName' => 'bbb'));
			$blocks = array();
			$blocksInfo = array();
			foreach($tuniuBlock as $tuniuBlockObj) {
				$blocksTemp = array();
				$blocksTemp['blockId'] = $tuniuBlockObj['blockId'];
				$blocksTemp['blockName'] = $tuniuBlockObj['blockName'];
				$blocks[] = $blocksTemp;
				$blocksTemp['products'] = array();
				$blocksInfo[$blocksTemp['blockId']][] = $blocksTemp;
			}
			
			// 查询产品名称
			$productsDb = $existsInfo['product'];
			$productsDbIds = array();
			foreach($productsDb as $productsDbObj) {
				$productsDbIds[] = $productsDbObj['product_id'];
			}
			$proIdNames = $this->cpsDao->getExistsProductNameId($productsDbIds, chr(49));
			$proIdNamesKv = array();
			foreach($proIdNames as $proIdNamesObj) {
				$proIdNamesKv[$proIdNamesObj['product_id']] = $proIdNamesObj['product_name'];
			}
			
			// 整合详细信息
			$now = date(Sundry::TIME_Y_M_D);
			foreach($productsDb as $productsDbObj) {
				$productsTemp = array();
				$productsTemp['productId'] = $productsDbObj['product_id'];
				$productsTemp['productType'] = $productsDbObj['product_type'];
				$productsTemp['productName'] = $proIdNamesKv[$productsDbObj['product_id']];
				$productsTemp['isPrinciple'] = $productsDbObj['is_principle'];
 				$productsTemp['delEnable'] = ($now == $productsDbObj['add_time'] ?　chr(48) : chr(49));
				$blocksInfo[$productsDbObj['block_id']]['products'][] = $productsTemp; 
			}
			$blocksInfoTrue = array();
			foreach ($blocksInfo as $key => $val) {
				$blocksInfoTrue[] = $val;
			}
			
			// 整合最终结果
			$result['blocks'] = $blocks;
			$result['blocksInfo'] = $blocksInfoTrue;
			
			// 结束监控示例
			BPMoniter::endMoniter($posTry, $flag, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."获取供应商消耗信息异常", $e);
        }
        
        // 返回结果
        return $result; 
	}
	
    /**
	 * 保存CPS产品
	 */
	public function saveCpsProduct($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."保存CPS产品", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}
		
		// 初始化返回结果
		$result = array();
		$result['rows'] = array();
		$rows = array();

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			$flag = Symbol::BPM_EIGHT_HUNDRED;
			
			// 初始化基础维度
			$blocks = $param['blocks'];
			$blockIds = array();
			foreach ($blocks as $blocksObj) {
				$blockIds[] = $blocksObj['blockId'];
			}
			
			// 初始化需要批量执行的SQL
			$sqlData = array();
			
			// 读取网站区块，并且给出提示
			
			
			
			// 查询现有区块
			$existsInfo = $this->cpsDao->getExistsBlockProduct($param);
			
			// 分析区块修改状态
			$blockDbIds = array();
			$existsInfoBlock = $existsInfo['block'];
			foreach ($existsInfoBlock as $existsInfoBlockObj) {
				$blockDbIds[] = $existsInfoBlockObj['block_id'];
			}
			unset($existsInfoBlock);
			// 需要新增的区块
			$blockAdd = array_diff($blockIds, $blockDbIds);
			// 需要删除的区块
			$blockDel = array_diff($blockDbIds, $blockIds);
			// 需要修改的区块
			$blockUpd = array_intersect($blockIds, $blockDbIds);
				
			// 生成删除区块的SQL
			$sqlData[] = "update cps_product set del_flag = 1, uid = ".$param['agencyId']." where vendor_id = ".$param['agencyId']." and start_city_code = ".$param['startCityCode']." and block_id in (".implode(chr(39), $blockDel).")";
			
			// 生成新增区块和新增产品的SQL
			$blockProductAdd = array();
			$frontId = array();
			$productNameIds = array();
			$productNameIdKv = array();
			foreach ($blocks as $blocksObj) {
				if (in_array($blocksObj['blockId'], $blockAdd)) {
					// 新增区块
					$proArr = $blocksObj['products'];
					foreach ($proArr as $proArrObj) {
						$blockProductAddTemp['blockId'] = $blocksObj['blockId'];
						$blockProductAddTemp['productId'] = $proArrObj['productId'];
						$blockProductAddTemp['isPrincipal'] = $proArrObj['isPrincipal'];
						$blockProductAdd[] = $blockProductAddTemp;
						$productNameIds[] = $proArrObj['productId'];
						$productNameIdKv[$proArrObj['productId']] = $proArrObj['productName'];
					}
				} else if (in_array($blocksObj['blockId'], $blockUpd)) {
					// 新增产品
					$proArr = $blocksObj['products'];
					foreach ($proArr as $proArrObj) {
						if (intval(chr(48)) > $proArrObj['dbId']) {
							$blockProductAddTemp['blockId'] = $blocksObj['blockId'];
							$blockProductAddTemp['productId'] = $proArrObj['productId'];
							$blockProductAddTemp['isPrincipal'] = $proArrObj['isPrincipal'];
							$blockProductAdd[] = $blockProductAddTemp;
							$productNameIds[] = $proArrObj['productId'];
							$productNameIdKv[$proArrObj['productId']] = $proArrObj['productName'];
						} else {
							$frontId[] = $proArrObj['dbId'];
						}
					} 
				}
			}
			// 生成需要新增的产品和区块
			$column = array('block_id', 'product_id', 'is_principal', 'product_type', 'vendor_id',  
							'start_city_code', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('blockId', 'productId', 'isPrincipal');
			$defaultValue = array($param['productType'], $param['agencyId'], $param['startCityCode'], $param['agencyId'], date('Y-m-d H:i:s'), $param['agencyId']);
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_product", $column, $columnValue, $blockProductAdd, $defaultValue);
			$sqlData = array_merge($sqlData, $sqlAdd);
			unset($blockProductAdd);
			
			// 查询产品表，刷新名称
			$productNameIdsDb = $this->cpsDao->getExistsProductNameId($productNameIds, chr(48));
			$productNameIdsDbArr = array();
			foreach($productNameIdsDb as $productNameIdsDbObj) {
				$productNameIdsDbArr[] = $productNameIdsDbObj['product_id'];
			}
			$proNameGap = array_diff($productNameIds, $productNameIdsDbArr);
			// 生成SQL数据
			$proNameGapSql = array();
			foreach($proNameGap as $proNameGapObj) {
				$proNameGapSqlTemp['productId'] = $proNameGapObj;
				$proNameGapSqlTemp['productName'] = htmlspecialchars($productNameIdKv[$proNameGapObj]);
				$proNameGapSql[] = $proNameGapSqlTemp;
			}
			// 生成插表SQL
			$column = array('product_id', 'product_name', 'account_id', 'start_city_code', 'product_type', 'add_uid', 'add_time');
			$columnValue = array('productId', 'productName');
			$defaultValue = array($param['accountId'], $param['startCityCode'], $param['productType'], $param['accountId'], date('Y-m-d H:i:s'));
			$sqlAdd = $this->_comdbMod->generateComInsert("bid_product", $column, $columnValue, $proNameGapSql, $defaultValue);
			$sqlData = array_merge($sqlData, $sqlAdd);
			unset($proNameGapSql);
			
			// 生成修改区块删除的SQL
			$dbId = array();
			
			$existsProduct = $existsInfo['product'];
			foreach ($existsProduct as $existsProductObj) {
				if (in_array($existsProductObj['block_id'], $blockUpd)) {
					$dbId[] = $existsProductObj['id'];
				}
			}
			// 需要删除的产品
			$productDelId = array_diff($dbId, $frontId);
			// 生成需要删除的产品
			$sqlData[] = "update cps_product set del_flag = 1, uid = ".$param['agencyId']." where id in (".implode(chr(39), $productDelId).")";
			
			// 向网站推送数据
			
			// 执行数据库操作
			$this->cpsDao->executeSql($sqlData, DaoModule::SALL);
			
			// 结束监控
			BPMoniter::endMoniter($posTry, $flag, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."保存CPS产品异常", $e);
        }
        
        // 返回结果
        return $result; 
	}
	
	/**
	 * 同步区块和产品
	 */
	public function syncCpsBlockProduct($param) {
		
	}
	
//	// 存储主从线路映射关系
//			// 查询已存在的分组线路
//			$productIds = array();
//			$productIdNames = array();
//			foreach($routes as $routesObj) {
//				$productTemp = $routesObj['products'];
//				foreach($productTemp as $productTempObj) {
//					$productIds[] = $productTempObj['productId'];
//					$productIdNames[$productTempObj['productId']] = $productTempObj['productName'];
//				}
//			}
//			$groupParam['startCityCode'] = $param['startCityCode'];
//			$groupParam['productIds'] = implode(chr(39), $productIds);
//			$groupProduct = $this->cpsDao->getGroupProduct($groupParam);
//			
//			// 过滤出需要查询的分组产品
//			$groupProducts = array();
//			foreach ($groupProduct as $groupProductObj) {
//				$groupProducts[] = $groupProductObj['product_id'];
//			}
//			$diffProducts = array_diff($productIds, $groupProducts);
//			
//			// 动态查询搜索分组产品
//			$rorProducts = array();
//			$rorParam['productType'] = $param['productType'];
//			$rorParam['productNameType'] = $param['productType'];
//			$rorParam['vendorId'] = $param['agencyId'];
//			$rorParam['startCityCode'] = $param['startCityCode'];
//			$rorParam['start'] = chr(48);
//			$rorParam['limit'] = Symbol::TWO_HUNDRED;
//			foreach($diffProducts as $diffProductsObj) {
//				$rorParam['productNameKeyword'] = htmlspecialchars($productIdNames[$diffProductsObj]);
//				$rorProduct = $this->rorProductIao->querySimilarProductList($rorParam);
//				$tempCount = $rorProduct['data']['count'];
//				$rorProduct = $rorProduct['data']['rows'];
//				if (1 < $tempCount) {
//					$rorParam['showFlag'] = chr(48);
//					$rorProductMain = $this->rorProductIao->querySimilarProductList($rorParam);
//					$rorProductMain = $rorProductMain['data']['rows'];
//					unset($rorParam['showFlag']);
//				}
//			}
	
    
}
?>