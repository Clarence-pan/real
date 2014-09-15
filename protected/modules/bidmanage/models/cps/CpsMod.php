<?php

Yii::import('application.modules.bidmanage.dal.dao.cps.CpsDao');
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');
Yii::import('application.modules.bidmanage.dal.iao.TuniuIao');
Yii::import('application.modules.bidmanage.dal.iao.CpsIao');
Yii::import('application.modules.bidmanage.models.common.ComdbMod');

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
     * 数据共通类
     */
    private $_comdbMod;
    
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
		// 初始化数据共通类
		$this->_comdbMod = new ComdbMod();
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
		$result['blocksInfo'] = array();
		$result['count'] = 0;
		$rows = array();

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 添加监控示例
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			
			// 从memcache获取导航树
			$memKey = md5('CpsMod::getCpsProduct' . $param['startCityCode']);
	   		$clsrecomResult = Yii::app()->memcache->get($memKey);
	       	// 如果memcache结果为空，则调用搜索接口获取区块
			if(empty($clsrecomResult)) {
				$tuniuParam = array();
				$tuniuParam['leftFlag'] = 1;
				$tuniuParam['cityCode'] = $param['startCityCode'];
				$clsrecomResult = TuniuIao::getTuniuLeftHeaderMenuInfo($tuniuParam);
				unset($tuniuParam);
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
						unset($f_default_cat);
						foreach($f_recommend_cat as $f_recommend_catObj) {
							array_push($webClassIds, $f_recommend_catObj['id']);
						}
					}
					$webClassIds = array_unique($webClassIds);
					$clsrecomResult = $webClassIds;
					// 缓存3h
		         	Yii::app()->memcache->set($memKey, $webClassIds, 10800); 	
		         	unset($webClassIds);
		         	unset($f_recommend_cat);			
				}
			}
			
			// 查询供应商拥有的分类
			$cateIds = array();  
			$webParam['agencyId'] = $param['agencyId'];
			$webParam['startCityCode'] = $param['startCityCode'];
			$webParam['classBrandTypes'] = array($param['productType']);
			$webRows = $this->rorProductIao->queryWebCategoryList($webParam);
			unset($webParam);
			$cateValues = $webRows['filters'][0]['cateValues'];
			
			// 初始化cateValues  ID集合
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
			
			// 如果供应商没有分类信息，则返回空结果
			if (empty($cateIds) || !is_array($cateIds)) {
				// 结束监控
				BPMoniter::endMoniter($posTry, Symbol::ONE_THOUSAND, __LINE__);
				// 返回结果
        		return $result; 
			}
			
			// 需要筛选分类
			// 查询分类信息
			$webClasses = $this->cpsDao->getWebClassByName($param);
			
			// 如果没有分类信息，则返回空结果
			if (empty($webClasses) || empty($webClasses) || !in_array($webClasses['web_class'], $cateIds)) {
				// 返回结果
	        	return $result; 
			}
			unset($cateIds);
			
			// 获取需要查询的分类ID
		    $webClassesOther = $webClasses['web_class'];
		    
			// 调用网站区块，如果没有区块直接返回空
			$tuniuParam = array();
			$tuniuParam['webClass'] = $webClassesOther;
			$tuniuParam['startCityCode'] = $param['startCityCode'];
			$tuniuParam['productType'] = $param['productType'];
			$tuniuBlock = CpsIao::queryTuniuCpsBlocks($tuniuParam);
			
			// $tuniuBlock = array(array('blockName' => 'aaaa', 'webClass' => 426),array('blockName' => 'bbb', 'webClass' => 428));
			if (empty($tuniuBlock) && !is_array($tuniuBlock)) {
				// 结束监控
				BPMoniter::endMoniter($posTry, Symbol::ONE_THOUSAND, __LINE__);
				// 返回结果
        		return $result; 
			}
			// 循环调用搜索接口，捞出所有产品
			$allProduct = array();
			$mainProduct = array();
			$rorParam = array();
			$rorParam['productType'] = $param['productType'];
			$rorParam['vendorId'] = $param['agencyId'];
			$rorParam['startCityCode'] = $param['startCityCode'];
			$rorParam['start'] = chr(48);
			$rorParam['limit'] = chr(49);
			$rorParam['webClassId'] = $webClassesOther;
			$temp = $this->rorProductIao->querySimilarProductList($rorParam);
			$tempCount = $temp['data']['count'];
			if (Symbol::ONE_THOUSAND < $tempCount) {
				$subCount = intval($tempCount / Symbol::ONE_THOUSAND) + 1;
				$rorParam['limit'] = Symbol::ONE_THOUSAND;
				for ($j = 0; $j < $subCount; $j++) {
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
			}
			unset($temp);
			unset($rorParam);		
			
			// 获取打包产品
			$packProduct = $this->cpsDao->getPackProduct($param);
			$packProducts = array();
			foreach($packProduct as $packProductObj) {
				$packProducts[] = $packProductObj['product_id'];
			}
			unset($packProduct);
			
			// 整合数据
			$singlePro = array();
			$mainKeyPro = array();
			foreach($mainProduct as $mainProductObj) {
				$mainKeyPro[$mainProductObj['productId']] = chr(49);
			}
			unset($mainProduct);
			
			// 获取区块信息
			$blocksInfo = $this->getCpsBlock($param, $tuniuBlock, $webClassesOther, $allProduct);
			$allProduct = $blocksInfo['rows'];
			unset($blocksInfo['rows']);
			foreach($allProduct as $allProductObj) {
				if (!in_array($allProductObj['productId'], $packProducts) && !in_array($allProductObj['productId'], $singlePro)) {
					$tempRows['isPrinciple'] = CommonTools::getIsOrNot($mainKeyPro[$allProductObj['productId']]);
					$tempRows['productId'] = $allProductObj['productId'];
					$tempRows['productType'] = $allProductObj['productType'];
					$tempRows['productName'] = $allProductObj['productName'];
					$tempRows['checkerFlag'] = $allProductObj['checkFlag'];
					$tempRows['tuniuPrice'] = $allProductObj['tuniuPrice'];
					$tempRows['isInBlock'] = $allProductObj['isInBlock'];
					$singlePro[] = $allProductObj['productId'];
					$rows[] = $tempRows;
				}
			}
			
			// 整合最终结果
			$result['rows'] = $rows;
			$result['count'] = count($rows);
			$result['blocksInfo'] = $blocksInfo['blocksInfo'];
			
			// 结束监控示例
			BPMoniter::endMoniter($posTry, Symbol::TWO_THOUSAND, __LINE__);
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
	public function getCpsBlock($param, $tuniuBlock, $webClassesOther, $rows) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."获取CPS区块", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}
		
		// 初始化返回结果
		$result = array();
		$result['blocksInfo'] = array();
		$result['rows'] = array();

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 初始化相关返回结果
			$rorRows = array();
			$blocksInfoTrue = array();
			
			// 查询现有区块
			$param['webClass'] = $webClassesOther;
			$existsInfo = $this->cpsDao->getExistsBlockProduct($param);
			
			// 整合区块维度
			$blocksInfo = array();
			foreach($tuniuBlock as $tuniuBlockObj) {
				if (!empty($tuniuBlockObj) && '' != $tuniuBlockObj) {
					$blocksTemp = array();
					$blocksTemp['blockName'] = $tuniuBlockObj;
					$blocksTemp['webClass'] = $param['webClass'];
					$blocksTemp['products'] = array();
					$blocksInfo[$tuniuBlockObj] = $blocksTemp;
				}
			}
			
			// 整合已存在区块数据
			$productsDb = $existsInfo['product'];
			unset($existsInfo);
			
			$productsDbIds = array();
			foreach($productsDb as $productsDbObj) {
				$productsDbIds[] = $productsDbObj['product_id'];
			}
			
			// 重整搜索数据
			$rorRowProductIds = array();
			foreach($rows as $rowsObj) {
				// 提取已经存储在本地的产品
				if (in_array($rowsObj['productId'], $productsDbIds)) {
					$rowsObj['isInBlock'] = chr(49);
				} else {
					$rowsObj['isInBlock'] = chr(48);
				}
				$rorRows[$rowsObj['productId']] = $rowsObj;
				$rorRowProductIds[] = $rowsObj['productId'];
			}
			
			$proIdNamesKv = array();
			if (!empty($productsDb) && is_array($productsDb)) {
				
				// 获取已下线的产品ID
				$productsDbIds = array_unique($productsDbIds);
				$productsDbIds = array_diff($productsDbIds, $rorRowProductIds);
				var_dump(11);die;
				// 查询产品名称
				$proIdNames = array();
				if (!empty($productsDbIds)) {
					$proIdNames = $this->cpsDao->getExistsProductNameId($productsDbIds, chr(49));
				}
				
				var_dump(11);die;
				foreach($proIdNames as $proIdNamesObj) {
					$proIdNamesKv[$proIdNamesObj['product_id']] = $proIdNamesObj['product_name'];
				}
				
				// 整合详细信息
				$now = date(Sundry::TIME_Y_M_D);
				foreach($productsDb as $productsDbObj) {
					$productsTemp = array();
					$productsTemp['dbId'] = $productsDbObj['id'];
					$productsTemp['productId'] = $productsDbObj['product_id'];
					$productsTemp['productType'] = $productsDbObj['product_type'];
					$productsTemp['productName'] = empty($rorRows[$productsDbObj['product_id']]) ? $proIdNamesKv[$productsDbObj['product_id']] : $rorRows[$productsDbObj['productName']];
					$productsTemp['checkerFlag'] = empty($rorRows[$productsDbObj['product_id']]) ? chr(49) : chr(50);
					$productsTemp['tuniuPrice'] = empty($rorRows[$productsDbObj['product_id']]) ? $productsDbObj['tuniu_price'] : intval($rorRows[$productsDbObj['product_id']]['tuniuPrice']);
					$productsTemp['isPrinciple'] = $productsDbObj['is_principle'];
	 				$productsTemp['delEnable'] = ($now == $productsDbObj['add_time'] ? chr(48) : chr(49));
					$blocksInfo[$productsDbObj['block_name']]['products'][] = $productsTemp; 
				}
			}
			
			// 整合区块
			foreach ($blocksInfo as $key => $val) {
				$blocksInfoTrue[] = $val;
			}
			
			// 整合最终结果
			$result['blocksInfo'] = $blocksInfoTrue;
			$result['rows'] = $rorRows;
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), "获取供应商区块信息异常", $e);
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

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			
			// 初始化当前时间
			$now = date(Sundry::TIME_Y_M_D_H_I_S);
			
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			$flag = Symbol::BPM_EIGHT_HUNDRED;
			
			// 初始化基础维度
			$blocks = $param['blocks'];
			$blockNames = array();
			foreach ($blocks as $blocksObj) {
				$blockNames[] = $blocksObj['blockName'];
			}
			
			// 初始化需要批量执行的SQL
			$sqlData = array();
			
			// 读取网站区块，并且给出提示
			$tuniuParam = array();
			$tuniuParam['webClass'] = $param['webClass'];
			$tuniuParam['startCityCode'] = $param['startCityCode'];
			$tuniuParam['productType'] = $param['productType'];
			$tuniuBlock = CpsIao::queryTuniuCpsBlocks($tuniuParam);
			
			// 该出发城市，该分类下区块已全部删除
			if (empty($tuniuBlock) && !is_array($tuniuBlock)) {
				// 全部删除
				$endParam = array();
				$endParam['agencyId'] = $param['agencyId'];
				$endParam['startCityCode'] = $param['startCityCode'];
				$endParam['webClass'] = $param['webClass'];
				$endParam['productType'] = $param['productType'];
				$this->cpsDao->endCpsBlock($endParam);
				// 设置返回消息
				$result['rows'] = '该出发城市下的该分类所拥有的区块已被全部删除，所以无法保存区块信息，正在推广中的产品将自动下线。';
				// 结束监控
				BPMoniter::endMoniter($posTry, Symbol::ONE_THOUSAND, __LINE__);
				// 返回结果
        		return $result; 
			}
			
			// 查询现有区块
			$existsInfo = $this->cpsDao->getExistsBlockProduct($param);
			
			// 分析区块修改状态
			$blockDbNames = array();
			$existsInfoBlock = $existsInfo['block'];
			if (!empty($existsInfoBlock) && is_array($existsInfoBlock)) {
				foreach ($existsInfoBlock as $existsInfoBlockObj) {
					$blockDbNames[] = $existsInfoBlockObj['block_name'];
				}
				unset($existsInfoBlock);
			}
			
			// 该出发城市，该分类下部分区块删除
			$tuniuBlock = array_unique($tuniuBlock);
			$tuniuDel = array_diff($blockNames, $tuniuBlock);
			$tuniuDelDb = array_diff($blockDbNames, $tuniuBlock);
			if (!empty($tuniuDel) && is_array($tuniuDel)
				&& !empty($tuniuDelDb) && is_array($tuniuDelDb)) {
				$syncParam = array();
				$syncParam['agencyId'] = $param['agencyId'];
				$syncParam['startCityCode'] = $param['startCityCode'];
				$syncParam['webClass'] = $param['webClass'];
				$syncParam['productType'] = $param['productType'];
				$syncParam['blockNames'] = $tuniuDelDb;
				$this->cpsDao->syncCpsBlockProduct($syncParam);
				// 设置返回消息
				$result['rows'] = '保存的区块中有部分区块已被删除，所以无法保存区块信息，请重新读取区块信息并保存，正在推广中的产品将自动移至其他产品推荐区块。';
				// 结束监控
				BPMoniter::endMoniter($posTry, Symbol::ONE_THOUSAND, __LINE__);
				// 返回结果
        		return $result; 
			}
			
			// 需要新增的区块
			$blockAdd = array_diff($blockNames, $blockDbNames);
			// 需要删除的区块
			$blockDel = array_diff($blockDbNames, $blockNames);
			// 需要修改的区块
			$blockUpd = array_intersect($blockNames, $blockDbNames);
				
			// 生成删除区块的SQL
			if (!empty($blockDel)) {
				$sqlData[] = "update cps_product set cps_flag = 2, uid = ".$param['agencyId'].", show_end_date = '".$now."' where vendor_id = ".$param['agencyId']." and web_class = ".$param['webClass']." and start_city_code = ".$param['startCityCode']." and block_name in (".implode(chr(44), $blockDel).")";
			}
			
			// 生成新增区块和新增产品的SQL
			$blockProductAdd = array();
			$frontId = array();
			$productNameIds = array();
			$productNameIdKv = array();
			foreach ($blocks as $blocksObj) {
				if (in_array($blocksObj['blockName'], $blockAdd)) {
					// 新增区块
					$proArr = $blocksObj['products'];
					foreach ($proArr as $proArrObj) {
						$blockProductAddTemp['blockName'] = $blocksObj['blockName'];
						$blockProductAddTemp['productId'] = $proArrObj['productId'];
						$blockProductAddTemp['isPrincipal'] = $proArrObj['isPrinciple'];
						$blockProductAddTemp['tuniuPrice'] = $proArrObj['tuniuPrice'];
						$blockProductAdd[] = $blockProductAddTemp;
						$productNameIds[] = $proArrObj['productId'];
						$productNameIdKv[$proArrObj['productId']] = $proArrObj['productName'];
					}
				} else if (in_array($blocksObj['blockName'], $blockUpd)) {
					// 新增产品
					$proArr = $blocksObj['products'];
					foreach ($proArr as $proArrObj) {
						if (intval(chr(48)) > $proArrObj['dbId']) {
							$blockProductAddTemp['blockName'] = $blocksObj['blockName'];
							$blockProductAddTemp['productId'] = $proArrObj['productId'];
							$blockProductAddTemp['isPrincipal'] = $proArrObj['isPrinciple'];
							$blockProductAddTemp['tuniuPrice'] = $proArrObj['tuniuPrice'];
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
			$column = array('block_name', 'product_id', 'is_principal', 'tuniu_price', 'cps_flag', 'show_start_time', 'web_class', 'product_type', 
							'vendor_id', 'start_city_code', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('blockName', 'productId', 'isPrincipal', 'tuniuPrice');
			$defaultValue = array(chr(49), $now, $param['webClass'], $param['productType'], $param['agencyId'], $param['startCityCode'], $param['agencyId'], $now, $param['agencyId']);
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_product", $column, $columnValue, $blockProductAdd, $defaultValue);
			$sqlData = array_merge($sqlData, $sqlAdd);
			unset($blockProductAdd);
			
			// 查询产品表，刷新名称
			$productNameIds = array_unique($productNameIds);
			$productNameIdsDb = $this->cpsDao->getExistsProductNameId($productNameIds, chr(48));
			$proNameUpdSql = array();
			$productNameIdsDbArr = array();
			foreach($productNameIdsDb as $productNameIdsDbObj) {
				$productNameIdsDbArr[] = $productNameIdsDbObj['product_id'];
				$proNameUpdSqlTemp = array();
				$proNameUpdSqlTemp['id'] = $productNameIdsDbObj['id'];
				$proNameUpdSqlTemp['productName'] = $productNameIdKv[$productNameIdsDbObj['product_id']];
				$proNameUpdSql[] = $proNameUpdSqlTemp;
			}
			
			// 生成SQL，并批量更新产品名称表
			if (!empty($proNameUpdSql)) {
				$column = array('id', 'product_name', 'update_uid');
				$columnValue = array('id', 'productName');
				$updateColumn = array('product_name', 'update_uid');
				$defaultValue = array($param['agencyId']);
				$sqlToUpds = $this->_comdbMod->generateComUpdate("cps_product_name", $column, $columnValue, $proNameUpdSql, $defaultValue, $updateColumn);
				$this->cpsDao->executeSql($sqlToUpds, DaoModule::SALL);
				unset($proNameUpdSql);
				unset($sqlToUpds);
			}
			
			$proNameGap = array_diff($productNameIds, $productNameIdsDbArr);
			// 生成SQL数据
			$proNameGapSql = array();
			foreach($proNameGap as $proNameGapObj) {
				$proNameGapSqlTemp['productId'] = $proNameGapObj;
				$proNameGapSqlTemp['productName'] = htmlspecialchars($productNameIdKv[$proNameGapObj]);
				$proNameGapSql[] = $proNameGapSqlTemp;
			}
			
			// 生成产品表插表SQL
			$column = array('product_id', 'product_name', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('productId', 'productName');
			$defaultValue = array($param['agencyId'], $now, $param['agencyId']);
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_product_name", $column, $columnValue, $proNameGapSql, $defaultValue);
			$sqlData = array_merge($sqlData, $sqlAdd);
			unset($proNameGapSql);
			
			// 生成修改区块删除的SQL
			$dbId = array();
			
			$existsProduct = $existsInfo['product'];
			if (!empty($existsProduct) && is_array($existsProduct)) {
				foreach ($existsProduct as $existsProductObj) {
					if (in_array($existsProductObj['block_name'], $blockUpd)) {
						$dbId[] = $existsProductObj['id'];
					}
				}
				// 需要删除的产品
				$productDelId = array_diff($dbId, $frontId);
				// 生成需要删除的产品
				$sqlData[] = "update cps_product set cps_flag = 2, uid = ".$param['agencyId'].", show_end_date = '".$now."' where id in (".implode(chr(44), $productDelId).")";
			}
			
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
	 * 获取费率
	 */
	public function getExpenseRatio($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['nickname']."获取CPS费率".$param['expenseRatio'], __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		// 初始化返回结果
		$result = array();

		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			
			// 配置费率
			$result = $this->cpsDao->queryExpenseRatio();
			
			// 结束监控
			BPMoniter::endMoniter($posTry, Symbol::FOUR_HUNDRED, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON.$param['nickname']."获取CPS费率发生异常", $e);
        } 
        
        return $result;
	}
	
	/**
	 * 配置费率
	 */
	public function configExpenseRatio($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['nickname']."配置CPS费率".$param['expenseRatio'], __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			
			// 配置费率
			$this->cpsDao->configExpenseRatio($param);
			
			// 结束监控
			BPMoniter::endMoniter($posTry, Symbol::FOUR_HUNDRED, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON.$param['nickname']."配置CPS费率发生异常", $e);
        } 
	}
	
	/**
	 * 获取网站显示的产品
	 */
	public function getShowCpsProduct($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, "获取网站显示的产品", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		// 初始化返回结果
		$result = array();

		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			
			// 查询网站显示的产品
			$resultBb = $this->cpsDao->queryShowCpsProduct($param);
			
			// 整合结果
			if (!empty($resultBb) && is_array($resultBb)) {
				foreach ($resultBb as $resultBbObj) {
					$result[$resultBbObj['blockName']][] = $resultBbObj['productId'];
				}
			}
			
			// 结束监控
			BPMoniter::endMoniter($posTry, Symbol::TWO_HUNDRED, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."获取网站显示的产品", $e);
        } 
        
        // 返回结果
        return $result;
	}
	
	/**
	 * 网站删除区块，同步数据
	 */
	public function delCpsBlock($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, "网站删除区块，同步数据", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			// 查询已存在区块
			$existsBlocks = $this->cpsDao->getExistsBlocks($param);
			$existsBlocksName = array();
			foreach ($existsBlocks as $existsBlocksObj) {
				$existsBlocksName[] = $existsBlocksObj['block_name'];
			}
			
			// 过滤需要操作的区块名称
			$param['blockNames'] = array_diff($existsBlocksName, $param['blockNames']);
			
			// 过滤删除区块，分两种情况，1 区块全部删除  2 区块部分删除
			if (!empty($param['blockNames'])) {
				// 部分删除			
				if (!empty($param['blockNames']) && is_array($param['blockNames'])) {
					// 同步网站数据
					foreach ($param['blockNames'] as &$blockNmaesObj) {
						$blockNmaesObj = chr(39).$blockNmaesObj.chr(39);
					}
					$this->cpsDao->syncCpsBlockProduct($param);
				}
			} else {
				// 全部删除
				$this->cpsDao->endCpsBlock($param);
			}
			
			// 结束监控
			BPMoniter::endMoniter($posTry, Symbol::TWO_HUNDRED, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."网站删除区块，同步数据", $e);
        }
	}
	
	/**
	 * 添加CPS供应商
	 */
	public function addCpsVendor($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."添加CPS供应商", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			
			// 添加CPS供应商
			$this->cpsDao->addCpsVendor($param);
			
			// 结束监控
			BPMoniter::endMoniter($posTry, Symbol::TWO_HUNDRED, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."添加CPS供应商", $e);
        }
	}
	
	/**
	 * 查询CPS供应商
	 */
	public function getCpsVendor($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."查询CPS供应商", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		// 初始化返回结果
		$result = array();
		$result['flag'] = Symbol::CONS_FALSE;

		try {
			// 添加监控
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			
			// 查询网站显示的产品
			$resultDb = $this->cpsDao->queryCpsVendor($param);
			
			// 判断是否开通了协议
			if (!empty($resultDb) && is_array($resultDb)) {
				$result['flag'] = Symbol::CONS_TRUE;
				$result['showEndDate'] = $resultDb['showEndDate'];
			}
			
			// 结束监控
			BPMoniter::endMoniter($posTry, Symbol::TWO_HUNDRED, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."查询CPS供应商", $e);
        } 
        
        // 返回结果
        return $result;
	}
	
	/**
	 * 同步CPS订单
	 */
	public function syncCpsOrders() {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod("脚本执行无参数", "系统同步CPS订单", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		try {
			// 初始化共通日期
			$now = date(Sundry::TIME_Y_M_D);
			$yesterday = date(Sundry::TIME_Y_M_D, time() - 12*60*60);
			
			// 过期推广期结束的CPS
			$this->cpsDao->overdueCps($now);
			
			// 同步主从产品数据
			$this->syncCpsProductGroups();
			
			// 刷新订单数据
			
			// 调用BOSS订单接口，获取订单信息
			$orders = CpsIao::getOrders();
			
			// 获取订单产品集合
			$orderProducts = array();
			foreach($orders as $ordersObj) {
				$orderProducts[] = $ordersObj['route_id'];
			}
			$orderProducts = array_unique($orderProducts);
			
			$productRe = $this->getOrderProducts($orderProducts);
			unset($orderProducts);
			$existsCpsProKv = $productRe['existsCpsProKv'];
			$otherExistsGroupKvGroups = $productRe['otherExistsGroupKvGroups'];
			$otherExistsGroupVkGroups = $productRe['otherExistsGroupVkGroups'];
			unset($productRe);
			
			// 初始化需要处理的订单
			$needOrders = array();
			$needOrderIds = array();
			$productVendorIds = array();
			// 获取对应的订单
			foreach($orders as $ordersObj) {
				// 获取数据库中直接存在的产品的订单
				if (!empty($existsCpsProKv[$ordersObj['route_id']])) {
					$existsCpsProKvTemp = $existsCpsProKv[$ordersObj['route_id']];
					foreach($existsCpsProKvTemp as $existsCpsProKvTempObj) {
						if ((Sundry::RELEASETIME == $existsCpsProKvTempObj['show_end_time'] 
							&& $existsCpsProKvTempObj['show_start_time'] < $ordersObj['added_time'])
							||($existsCpsProKvTempObj['show_start_time'] < $ordersObj['added_time']
							&& $existsCpsProKvTempObj['show_end_time'] > $ordersObj['added_time'])) {
							$needOrdersTemp = array();
							$needOrdersTemp = $ordersObj;
							$needOrdersTemp['vendor_id'] = $existsCpsProKvTempObj['vendor_id'];
							$needOrdersTemp['fee_product_id'] = $existsCpsProKvTempObj['product_id'];
							$needOrdersTemp['cps_id'] = $existsCpsProKvTempObj['id'];
							$needOrders[$ordersObj['id']]['product_info'][] = $needOrdersTemp;
							$needOrderIds[] = $ordersObj['id'];
							$productVendorIds[] = $existsCpsProKvTempObj['vendor_id'];
						}
					}
				} else if (!empty($otherExistsGroupKvGroups[$otherExistsGroupVkGroups[$ordersObj['route_id']]])) {
					// 获取主从产品关联的产品的订单
					$otherExistsGroupKvGroupsTemp = $otherExistsGroupKvGroups[$otherExistsGroupVkGroups[$ordersObj['route_id']]];
					foreach($otherExistsGroupKvGroupsTemp as $otherExistsGroupKvGroupsTempObj) {
						if ((Sundry::RELEASETIME == $otherExistsGroupKvGroupsTempObj['show_end_time'] 
							&& $otherExistsGroupKvGroupsTempObj['show_start_time'] < $ordersObj['added_time'])
							||($otherExistsGroupKvGroupsTempObj['show_start_time'] < $ordersObj['added_time']
							&& $otherExistsGroupKvGroupsTempObj['show_end_time'] > $ordersObj['added_time'])) {
							$needOrdersTemp = array();
							$needOrdersTemp = $ordersObj;
							$needOrdersTemp['vendor_id'] = $otherExistsGroupKvGroupsTempObj['vendor_id'];
							$needOrdersTemp['fee_product_id'] = $otherExistsGroupKvGroupsTempObj['product_id'];
							$needOrdersTemp['cps_id'] = $otherExistsGroupKvGroupsTempObj['id'];
							$needOrders[$ordersObj['id']]['product_info'][] = $needOrdersTemp;
							$needOrderIds[] = $ordersObj['id'];
							$productVendorIds[] = $otherExistsGroupKvGroupsTempObj['vendor_id'];
						}
					}
				}	
			}
			unset($existsCpsProKv);
			unset($otherExistsGroupKvGroups);
			unset($otherExistsGroupVkGroups);
			unset($orders);
			$productVendorIds = array_unique($productVendorIds);
			$needOrderIds = array_unique($needOrderIds);
			
			// 初始化日志类
			$bbLog = new BBLog();
			
			// 循环获取财务采购单
			$fmisVendorIds = array();
			foreach($needOrderIds as $needOrderIdsObj) {
				$fmisOrder = CpsIao::queryCpsFmisOrder($needOrderIdsObj, $bbLog);
				foreach($fmisOrder as $fmisOrderObj) {
					// 只获取地接和打包
					if (6 == $fmisOrderObj['product_type'] || 23 == $fmisOrderObj['product_type']) {
						$needOrdersTemp = array();
						$needOrdersTemp['fmis_id'] = $fmisOrderObj['id'];
						$needOrdersTemp['total_price'] = $fmisOrderObj['total_price'];
						$needOrdersTemp['vendor_id'] = $fmisOrderObj['agency_id'];
						$needOrdersTemp['purchase_order_type'] = $fmisOrderObj['purchase_order_type'];
						$needOrders[$needOrderIdsObj]['fmis_info'][] = $needOrdersTemp;
						if (in_array($fmisOrderObj['agency_id'], $productVendorIds)) {
							$fmisVendorIds[] = $fmisOrderObj['agency_id'];
						}
					}
				}
				$fmisOrder = null;
			}
			unset($needOrderIds);
			$fmisVendorIds = array_unique($fmisVendorIds);
			
			// 获取供应商结算方式
			$fmisVendorWay = array();
			foreach($fmisVendorIds as $fmisVendorIdsObj) {
				$fmisWay = CpsIao::queryCpsFmisWay($fmisVendorIdsObj, $bbLog);
				if (!empty($fmisWay)) {
					$fmisVendorWay[$fmisVendorIdsObj] = $fmisWay['agencyAccountType'];
				}
				$fmisWay = null;
			}
			unset($fmisVendorIds);
			unset($bbLog);
			
			$expenseRe = $this->getExpenseOrderRatio($now);
			$expenseRatioKvFin = $expenseRe['expenseRatioKvFin'];
			$startDate = $expenseRe['start'];
			unset($expenseRe);
			
			// 生成订单和采购单数据插入数据集合
			$orderAddData = array();
			$purchaseOrderAddData = array();
			foreach($needOrders as $key => $val) {
				// 生成临时cpsId映射
				$cpsIdTemp = array();
				// 生成临时签约时间
				$signDateTemp = array();
				
				// 插入订单数据
				$productInfo = $val['product_info'];
				foreach($productInfo as $productInfoObj) {
					$orderAddDataTemp = array();
					$orderAddDataTemp['vendorId'] = $productInfoObj['vendor_id'];
					$orderAddDataTemp['orderId'] = $key;
					$orderAddDataTemp['placeOrderTime'] = $productInfoObj['added_time'];
					$orderAddDataTemp['signContractTime'] = $productInfoObj['sign_date'];
					$orderAddDataTemp['returnTime'] = $yesterday;
					$orderAddDataTemp['cpsId'] = $productInfoObj['cps_id'];
					$orderAddDataTemp['productId'] = $productInfoObj['route_id'];
					$orderAddDataTemp['feeProductId'] = $productInfoObj['fee_product_id'];
					$orderAddData[] = $orderAddDataTemp;
					$cpsIdTemp[$orderAddDataTemp['vendorId']] = $orderAddDataTemp['cpsId'];
					$signDateTemp = $productInfoObj['sign_date'];
				}
				
				// 插入采购单数据
				$fmisInfo = $val['fmis_info'];
				foreach($fmisInfo as $fmisInfoObj) {
					$purchaseOrderAddDataTemp = array();
					$purchaseOrderAddDataTemp['vendorId'] = $fmisInfoObj['vendor_id'];
					$purchaseOrderAddDataTemp['orderId'] = $key;
					$purchaseOrderAddDataTemp['purchaseOrderId'] = $fmisInfoObj['fmis_id'];
					$purchaseOrderAddDataTemp['purchaseOrderType'] = $fmisInfoObj['purchase_order_type'];
					$purchaseOrderAddDataTemp['purchaseCost'] = $fmisInfoObj['total_price'];
					$purchaseOrderAddDataTemp['purchaseType'] = $fmisVendorWay[$purchaseOrderAddDataTemp['vendorId']];
					$purchaseOrderAddDataTemp['cpsId'] = $cpsIdTemp[$purchaseOrderAddDataTemp['vendorId']];
					if (empty($expenseRatioKvFin[$signDateTemp])) {
						$purchaseOrderAddDataTemp['expenseRatio'] = $expenseRatioKvFin[$startDate];
					} else {
						$purchaseOrderAddDataTemp['expenseRatio'] = $expenseRatioKvFin[$signDateTemp];
					}
					$purchaseOrderAddDataTemp['expense'] = $purchaseOrderAddDataTemp['purchaseCost']*$purchaseOrderAddDataTemp['expenseRatio'];
					$purchaseOrderAddData[] = $purchaseOrderAddDataTemp;
				}
				
			}
			unset($needOrders);
			
			// 生成新增的订单SQL，并插入
			$column = array('vendor_id', 'order_id', 'place_order_time', 'sign_contract_time', 'return_time', 'cps_id',
							'product_id', 'fee_product_id', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('vendorId', 'orderId', 'placeOrderTime', 'signContractTime', 'returnTime', 'cpsId', 'productId', 'feeProductId');
			$defaultValue = array('4333', $now, '4333');
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_order", $column, $columnValue, $orderAddData, $defaultValue);
			// 执行数据库操作
			$this->cpsDao->executeSql($sqlAdd, DaoModule::SALL);
			unset($orderAddData);
			unset($sqlAdd);
			
			// 生成新增的采购单SQL，并插入
			$column = array('vendor_id', 'order_id', 'purchase_order_id', 'purchase_order_type', 'purchase_cost', 'purchase_type', 'cps_id', 
							'expense_ratio', 'expense', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('productId', 'productType', 'principalProduct', 'isPrincipal');
			$defaultValue = array('vendorId', 'orderId', 'purchaseOrderId', 'purchaseOrderType', 'purchaseCost', 'purchaseType', 'cpsId', 'expenseRatio', 'expense');
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_purchase_order", $column, $columnValue, $purchaseOrderAddData, $defaultValue);
			// 执行数据库操作
			$this->cpsDao->executeSql($sqlAdd, DaoModule::SALL);
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), "系统同步CPS订单异常", $e);
        }
	}
	
	/**
	 * 同步cps产品组
	 */
	public function syncCpsProductGroups() {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod("方法执行无参数", "系统同步CPS产品组", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		try {
			
			// 同步主从产品数据
			
			// 初始化主从产品数据同步数组
			$mmProArr = array();
			$now = date(Sundry::TIME_Y_M_D);
			$yesterday = date(Sundry::TIME_Y_M_D, time() - 12*60*60);
			
			// 查询当日的主产品
			$priProducts = $this->cpsDao->queryCpsProductPriOrNot(chr(49));
			$priProductIds = array();
			foreach($priProducts as $priProductsObj) {
				$priProductIds[] = $priProductsObj['product_id'];
			}
			unset($priProducts);
			
			// 查询当日的从产品
			$priNotProducts = $this->cpsDao->queryCpsProductPriOrNot(chr(48));
			$priNotProductIds = array();
			foreach($priNotProducts as $priNotProductsObj) {
				$priNotProductIds[] = $priNotProductsObj['product_id'];
			}
			unset($priNotProducts);
			
			// 整合所有产品ID
			$allProductIds = array();
			$allProductIds = array_merge($allProductIds, $priProductIds);
			$allProductIds = array_merge($allProductIds, $priNotProductIds);
			
			// 查询已经归档的产品组
			$existsGroup = $this->cpsDao->queryExistsProductGroup($allProductIds);
			unset($allProductIds);
			$existsGroupIds = array();
			foreach($existsGroup as $existsGroupObj) {
				$existsGroupIds[] = $existsGroupObj['product_id'];
			}
			unset($existsGroup);
			
			// 过滤主线产品
			$priProductIds = array_unique($priProductIds);
			$priProductIds = array_diff($priProductIds, $existsGroupIds);
			// 过滤从线产品
			$priNotProductIds = array_diff($priNotProductIds, $existsGroupIds);
			
			// 查询主线的名称
			$priProductIdNames = $this->cpsDao->getExistsProductNameId($priProductIds, chr(49));
			
			// 刷新主线路的线路组ID
			$sqlSyncPri = "UPDATE cps_product SET principal_product = product_id WHERE del_flag = 0 AND is_principal = 1 AND date_format(show_start_time, '%Y-%m-%d') = '".$yesterday."'";
			$this->cpsDao->dbRW->createCommand($sqlSyncPri)->execute();
			unset($sqlSyncPri);
			
			// 循环初始化主线路集合
			foreach($priProductIdNames as $priProductIdNamesObj) {
				$rorParam['classBrandType'] = chr(49);
				$rorParam['key'] = $priProductIdNamesObj['product_name'];
				$rorParam['currentPage'] = chr(49);
				$rorParam['limit'] = Symbol::ONE_THOUSAND;
				$rorData = CpsIao::queryCpsRorProduct($rorParam);
				foreach($rorData as $rorDataObj) {
					$mmProArrObj['productId'] = $rorDataObj['productId'];
					$mmProArrObj['productType'] = $rorDataObj['classBrandType'];
					$mmProArrObj['principalProduct'] = $priProductIdNamesObj['product_id'];
					if ($priProductIdNamesObj['product_id'] == $rorDataObj['productId']) {
						$mmProArrObj['isPrincipal'] = chr(49);
					} else {
						$mmProArrObj['isPrincipal'] = chr(48);
					}
					$mmProArr[] = $mmProArrObj;
				}
			}
			unset($priProductIdNames);
			
			// 生成新增的主线路SQL，并插入
			$column = array('product_id', 'product_type', 'principal_product', 'is_principal', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('productId', 'productType', 'principalProduct', 'isPrincipal');
			$defaultValue = array('4333', $now, '4333');
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_product_group", $column, $columnValue, $mmProArr, $defaultValue);
			// 执行数据库操作
			$this->cpsDao->executeSql($sqlAdd, DaoModule::SALL);
			$mmProArr = array();
			unset($sqlAdd);
			
			// 再次查询已经归档的从线产品组
			$existsNorPriGroup = $this->cpsDao->queryExistsProductGroup($priNotProductIds);
			$existsNorPriGroupIds = array();
			foreach($existsNorPriGroup as $existsNorPriGroupObj) {
				$existsNorPriGroupIds[] = $existsNorPriGroupObj['product_id'];
			}
			
			// 过滤最后的从线产品
			$priNotProductIds = array_diff($priNotProductIds, $existsNorPriGroupIds);
			unset($existsNorPriGroupIds);
			
			// 查询主线的名称
			$priNotProductIdNames = $this->cpsDao->queryProductNameById($priNotProductIds);
			
			// 循环初始化从线路集合
			$priNotTempProductIds = array();
			foreach($priNotProductIdNames as $priNotProductIdNamesObj) {
				if(in_array($priNotProductIdNamesObj['product_id'], $priNotTempProductIds)) {
					continue;
				}
				$rorParam['classBrandType'] = chr(49);
				$rorParam['key'] = $priNotProductIdNamesObj['product_name'];
				$rorParam['currentPage'] = chr(49);
				$rorParam['limit'] = Symbol::ONE_THOUSAND;
				$rorData = CpsIao::queryCpsRorProduct($rorParam);
				$rorParam['limit'] = chr(49);
				$rorParam['showFlag'] = chr(48);
				$rorMainData = CpsIao::queryCpsRorProduct($rorParam);
				unset($rorParam);
				if (!empty($rorMainData) && is_array($rorMainData)) {
					foreach($rorData as $rorDataObj) {
						$priNotTempProductIds[] = $rorDataObj['productId'];
						$mmProArrObj['productId'] = $rorDataObj['productId'];
						$mmProArrObj['productType'] = $rorDataObj['classBrandType'];
						$mmProArrObj['principalProduct'] = $rorMainData[0]['productId'];
						if ($rorMainData[0]['productId'] == $rorDataObj['productId']) {
							$mmProArrObj['isPrincipal'] = chr(49);
						} else {
							$mmProArrObj['isPrincipal'] = chr(48);
						}
						$mmProArr[] = $mmProArrObj;
					}
				}
			}
			unset($priNotProductIdNames);
			
			// 生成新增的从线路SQL，并插入
			$column = array('product_id', 'product_type', 'principal_product', 'is_principal', 'add_uid', 'add_time', 'update_uid');
			$columnValue = array('productId', 'productType', 'principalProduct', 'isPrincipal');
			$defaultValue = array('4333', $now, '4333');
			$sqlAdd = $this->_comdbMod->generateComInsert("cps_product_group", $column, $columnValue, $mmProArr, $defaultValue);
			// 执行数据库操作
			$this->cpsDao->executeSql($sqlAdd, DaoModule::SALL);
			unset($mmProArr);
			unset($sqlAdd);
			
			// 获取从线路的产品组线路信息
			$existsNorPriGroup = $this->cpsDao->queryExistsProductGroup($priNotProductIds);
			
			// 生成从线路更新的数据集合
			$updCpsPro = array();
			foreach($existsNorPriGroup as $existsNorPriGroupObj) {
				$updCpsPro[$existsNorPriGroupObj['principal_product']][] = $existsNorPriGroupObj['product_id'];
			}
			unset($existsNorPriGroup);
			
			// 生成从线路更新的SQL
			$sqlUpd = array();
			foreach($updCpsPro as $key=>$val) {
				$sqlUpd[] = "UPDATE cps_product SET principal_product = ".$key." WHERE del_flag = 0 AND product_id IN (".implode(chr(44), $val).") AND is_principal = 0 AND date_format(show_start_time, '%Y-%m-%d') = '".$yesterday."'";
			}
			
			// 执行更新
			$this->cpsDao->dbRW->createCommand($sqlUpd)->execute();
			
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), "系统同步CPS产品组数据异常", $e);
        }
	}
	
	/**
	 * 获取订单费率
	 */
	function getExpenseOrderRatio($now) {
		
		try {
			// 查询并生成数据库费率数组
			$expenseRatioKv = array();
			$expenseRatio = $this->cpsDao->queryExpenseRatioAll();
			$startDate = current($expenseRatio);
			$expenseRatioKv[$startDate['add_time']] = $startDate['expense_ratio'];
			$startDate = $startDate['add_time'];
			foreach($expenseRatio as $expenseRatioObj) {
				$expenseRatioKv[date(Sundry::TIME_Y_M_D, strtotime($expenseRatioObj['add_time']) + Symbol::ONE_DAY_SECOND)] = $expenseRatioObj['expense_ratio'];
			}
			unset($expenseRatio);
			
			// 获取从开始配置日期到当前日期的所有日期
			$everyDay = array();
			$everyDay[] = $startDate;
			$everyDay = array_merge($everyDay, CommonTools::intervalDate($startDate, $now));
			$everyDay[] = $now;
			
			// 生成最终费率数组
			$expenseRatioKvFin = array();
			foreach($everyDay as $everyDayObj) {
				$expenseRatioTemp = chr(48);
				if (!empty($expenseRatioKv[$everyDayObj])) {
					$expenseRatioTemp = $expenseRatioKv[$everyDayObj];
				}
				$expenseRatioKvFin[$everyDayObj] = $expenseRatioTemp;
			}
			
			// 整合并返回结果
			$result['expenseRatioKvFin'] = $expenseRatioKvFin;
			$result['start'] = $startDate;
			return $result;
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), "系统获取订单费率异常", $e);
        }
	}
	
	/**
	 * 获取订单产品
	 */
	function getOrderProducts($orderProducts) {
		try {
			// 获取订单所有产品的产品组信息
			$existsProGroup = $this->cpsDao->queryExistsProductGroup($orderProducts);
			
			$existsProGroupKv = array();
			$orderExistsOneProducts = array();
			foreach($existsProGroup as $existsProGroupObj) {
				$existsProGroupKv[$existsProGroupObj['principal_product']][] = $existsProGroupObj['product_id'];
				$orderExistsOneProducts[] = $existsProGroupObj['product_id'];
			}
			unset($existsProGroup);
			
			// 查询推广表中已经存在的产品
			$existsCpsPro = $this->cpsDao->queryCpsProductShowTime($orderExistsOneProducts, chr(49));
			$existsCpsProIds = array();
			$existsCpsProKv = array();
			foreach($existsCpsPro as $existsCpsProObj) {
				$existsCpsProIds[] = $existsCpsProObj['product_id'];
				$existsCpsProKv[$existsCpsProObj['product_id']][] = $existsCpsProObj;
			}
			unset($existsCpsPro);
			
			// 过滤线路组的其他产品
			$otherGroupProIds = array_diff($orderExistsOneProducts, $existsCpsProIds);
			unset($existsCpsProIds);
			
			// 过滤需要扣费的其他产品
			$otherGroupKvGroups = array();
			foreach($otherGroupProIds as $otherGroupProIdsObj) {
				foreach($existsProGroupKv as $key => $val) {
					if (in_array($otherGroupProIdsObj, $val)) {
						$otherGroupKvGroups[] = $key;
						break;
					}
				}
			}
			unset($otherGroupProIds);
			
			// 查询推广表中存在的剩余产品组
			$otherExistsCpsPro = $this->cpsDao->queryCpsProductShowTime($otherGroupKvGroups, chr(50));
			$otherExistsGroupKvGroups = array();
			$otherExistsGroupVkGroups = array();
			foreach($otherExistsCpsPro as $otherExistsCpsProObj) {
				$otherExistsGroupKvGroups[$otherExistsCpsProObj['principal_product']][] = $otherExistsCpsProObj;
				$otherExistsGroupVkGroups[$otherExistsCpsProObj['proudtc_id']] = $otherExistsCpsProObj['principal_product'];
			}
			
			// 初始化结果并返回
			$result['existsCpsProKv'] = $existsCpsProKv;
			$result['otherExistsGroupKvGroups'] = $otherExistsGroupKvGroups;
			$result['otherExistsGroupVkGroups'] = $otherExistsGroupVkGroups;
			return $result;
		} catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), "系统获取订单产品异常", $e);
        }
	}
	
	/**
     * 查询推广报表
     * */
    public function getShowReport($param) {
        // 填充日志
        if ($this->bbLog->isInfo()) {
            $this->bbLog->logMethod($param, $param['loginName'] . chr(124) . $param['nickname'] . '(ID:'. $param['agencyId'] . chr(124) . $param['uid'] . ")查询推广报表", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
        }

        try {
            $result = $this->cpsDao->getShowReport($param);
            foreach ($result['rows'] as &$row) {
                $row['problem'] = '未提出';
                $row['expenseRatio'] = strval($row['expenseRatio'] * 100) . chr(37); // 添加%
                $row['purchaseType'] = DictionaryTools::getPurchaseType($row['purchaseType']);
                if ($row['showEndTime'] == "0000-00-00 00:00:00") {
                    $row['showEndTime'] = "未结束";
                }
            }
            return $result;
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage(), "系统同步CPS订单异常", $e);
        }
    }
    
    /**
	 * 查询CPS协议
	 */
	public function getCpsAgreement($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['agencyId']."查询CPS协议", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}

		// 初始化返回结果
		$result = array();
			
		// 查询CPS协议
		$result = "<div style=\"text-indent:16em;font-size:24;font-weight:bold\">2014年途牛旅游网CPS推广协议</div><br/>".
				    "<div style=\"text-indent:2em;font-size:16;width:660px\">本协议缔约方为途牛旅游网CPS用户（以下简称为“用户”）、北京途牛国际旅行社有限公司南京分公司（下称为“途牛”）。" .
				    "途牛公司在此特别提醒用户认真阅读、充分理解本协议各条款，特别是涉及服务费用条款，免除或者限制途牛公司责任的条款，".
				    "对用户权利限制条款，争议解决和法律适用等。请您审慎阅读并选择接受或不接受本协议。除非您接受本协议所有条款，否则您" .
				    "无权使用途牛公司提供的相关服务。您的使用服务行为将视为对本协议的接受，并同意接受本协议各项条款的约束。 </div>".
					"<div style=\"font-size:16;width:660px\">一、如无特别说明，本协议下列重要术语的含义如下：<br/>".
					"1、途牛网，是指由途牛提供技术支持和服务的网站，其网址或域名为“www.tuniu.com”。<br/>". 
					"2、途牛网CPS服务，指途牛向用户提供的，将用户在途牛网上所出售旅游产品进行人工推荐以及站外推广的一种有偿服务。该" .
					"服务的具体内容和方式，将在本协议相关条款规则、途牛网发布的公告、提示中进行规定，用户应予以遵循。<br/>".
					"3、途牛网CPS用户，是指符合本协议条件，使用途牛网CPS服务，并向途牛支付约定费用的单位或自然人。</div>".
					"<div style=\"font-size:16;width:660px\">二、用户的权利和义务 <br/>".
					"1、用户有权依本协议约定条件，获得途牛提供的途牛网CPS服务。<br/>".
					"2、用户同意了本协议或使用途牛网CPS服务，即表示用户已接受途牛网CPS服务的现有功能和规则，也同意途牛有权无须经用" .
					"户同意或提前做出告示而直接对途牛网CPS服务的功能、规则进行变更、升级、改进、修改，用户也愿意为此支付相关服务费用。<br/>".
					"3、用户在任何情况下，包括但不限于通过与途牛网的沟通、诉讼或其它方式，获知途牛网未在途牛网公布的信息，未经途牛书面同" .
					"意情况下，用户不得将该信息告知其他任何第三人。</div>".
					"<div style=\"font-size:16;width:660px\">三、本协议的确认、接受、修订<br/>".
					"1、本协议内容包括协议正文及所有与途牛网CPS服务相关的已经发布的或将来可能发布的各类规则。上述内容为本协议不可分割的" .
					"一部分，与本协议正文具有同等法律效力。用户在使用途牛网CPS服务时，应予遵守。<br/>".
					"2、无论用户称事实上是否阅读了本协议，只要用户点击接受本协议并开启CPS服务流程，则仍表示用户同意接受本协议约束。<br/>".
					"3、鉴于互联网相关技术、市场、业务模式等等的发展迅速，用户同意，途牛有权随时根据中华人民共和国有关法律、法规的变化、互" .
					"联网的发展以及公司经营状况和经营策略的调整等不定时地制定、修改本协议及各类规则，如有修订，途牛将在用户NB系统、".
					"邮箱、途牛网公告等渠道通知于用户。经修订的协议、规则一经公布，则立即自动生效，且成为本服务协议不可分割的一部分。如用户" .
					"不同意途牛的上述修订，则用户必须立即停止使用本协议下各项服务。用户登录或继续使用途牛网CPS服务，则表示用户接受经修订的".
					"协议、规则。<br/>".
					"4、如途牛另行明确对“途牛网CPS服务”中某项服务范围扩大、功能增强作出新的调整规定，则以新调整规定为准。</div>".
					"<div style=\"font-size:16;width:660px\">四、途牛网CPS服务条款<br/>".
					"1、用户在向途牛支付相关的服务费并符合本协议的其他约定条件后，将享受以下服务：<br/>".
					"<div style=\"text-indent:2em;font-size:16;width:660px\">（1）在用户认可的情况下，用户所售出的旅游产品将出现在途牛旗下网站以及其他联盟网站；</div>".
					"<div style=\"text-indent:2em;font-size:16;width:660px\">（2）在服务期内，用户可以根据用户名和密码，登录NB系统CPS推广入口。用户申请参加CPS推广，获得途牛网通过后，根据系统" .
					"设定的广告推广佣金比例，在参与途牛站内外推广活动中按成交效果支付广告服务费用。</div>". 
					"2、用户应妥善保管好自身的用户名及其密码，并对该用户名下的一切操作行为负责。若发现任何非法使用用户名及其密码的，用户应立" .
					"即以有效方式通知途牛。<br/>".
					"3、用户自愿使用途牛网CPS服务，途牛不对其使用该服务后的访问量、商品销量等作任何承诺。<br/>".
					"4、途牛有权根据业务发展的需要对途牛网CPS服务的形式和服务内容进行更新、变更。<br/>".
					"5、如途牛免费向用户提供或作为促销手段赠送的其他服务，途牛有权对其进行随时调整、变更及撤销。</div>".
					"<div style=\"font-size:16;width:660px\">五、协议最终解释权归途牛旅游网所有，途牛有权在法律规定的框架内予以解释。</div>";
        // 返回结果
        return $result;
	}
	
}

?>