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
	function getCpsProduct($param) {
		$param['agencyId'] = 1169;
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, $param['accountId']."获取需要编辑的CPS产品", __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
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
    
}
?>