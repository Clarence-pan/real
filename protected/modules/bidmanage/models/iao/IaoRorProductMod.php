<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 14-4-18
 * @time 下午7:38
 * @description IaoRorProductMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');
Yii::import('application.modules.bidmanage.dal.iao.TuniuIao');
Yii::import('application.modules.bidmanage.dal.dao.product.ClsrecommendDao');

class IaoRorProductMod 
{
    private $_rorProductIao;

	private $clsrecommendDao;

    function __construct()
    {
        $this->_rorProductIao = new RorProductIao;
        $this->clsrecommendDao = new ClsrecommendDao();
    }

    public function getWebCategoryTree($params)
    {
    	// 从memcache获取区块
		$memKey = md5('IaoRorProductMod::clsrecommendad_' . $params['startCityCode']);
   		$clsrecomResult = Yii::app()->memcache->get($memKey);
       	// 如果memcache结果为空，则调用搜索接口获取区块
		if(empty($clsrecomResult)) {
			$tuniuParam = array();
			$tuniuParam['leftFlag'] = 1;
			$tuniuParam['cityCode'] = $params['startCityCode'];
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
		
    	// 从已配置的分类ID
		$memIdKey = md5('IaoRorProductMod::clsrecommendad_ids'. $params['showDateIds'].'_'. $params['startCityCode']);
   		$existIds = Yii::app()->memcache->get($memIdKey);
   		if (empty($existIds)) {
   			$existIds = $this->clsrecommendDao->queryBidWebClass($params);
   			// 缓存12h
	        Yii::app()->memcache->set($memIdKey, $existIds, 43200); 		
   		}
        $rows = $this->_rorProductIao->queryWebCategoryList($params);
        $cateValues = $rows['filters'][0]['cateValues'];
        
        if(!empty($cateValues) && !empty($existIds)){
        	// 初始化cateValues  ID集合
	      	$cateIds = array();
	      	$cateIdsRoot = array();    
	        foreach($cateValues as $key => $val){
	        	array_push($cateIdsRoot, $val["id"]);  
	        	foreach($val['children'] as $fkey => $fchildrenObj) {
	        		array_push($cateIds, $fchildrenObj["id"]);
	        		foreach($fchildrenObj['children'] as $skey => $schildrenObj) {
	        			array_push($cateIds, $schildrenObj["id"]);
	        		}
	        	}
	        }
	        
	        // 取和导航树的交集
	        $cateIds = array_intersect($cateIds, $clsrecomResult);
	        // 取和运营计划的交集
	        $cateIds = array_merge($cateIds, $cateIdsRoot);
	        $cateIds = array_intersect($cateIds, $existIds);
        	if (!empty($cateIds)) {
        		$cateValuesResult = array();
	        	// 剔除指定要过滤掉的分类
	        	foreach($cateValues as $key => $val){
	        		if(!in_array($val["id"],ConstDictionary::$classRecommendFilterArray) && in_array($val["id"], $cateIds)){
	        			$cateValues[$key]['classDepth'] = 1;
	        			$cateValues[$key]['parentClass'] = array(0);
	        			foreach($val['children'] as $fkey => $fchildrenObj) {
		        				if (!in_array($fchildrenObj["id"],ConstDictionary::$classRecommendFilterArray) && in_array($fchildrenObj["id"], $cateIds) 
		        					&& $this->queryProductFilter($params, $val["id"], $fchildrenObj["id"])){
		        					$cateValues[$key]['children'][$fkey]['classDepth'] = 2;
		        					$cateValues[$key]['children'][$fkey]['parentClass'] = array($val["id"]);
	        					foreach($fchildrenObj['children'] as $skey => $schildrenObj) {
				        				if (in_array($schildrenObj["id"],ConstDictionary::$classRecommendFilterArray) || !in_array($schildrenObj["id"], $cateIds) 
				        					|| !$this->queryProductFilter($params, $val["id"], $schildrenObj["id"])){
				        						$cateValues[$key]['children'][$fkey]['children'][$skey] = array();
			        					// unset($cateValues[$key]['children'][$fkey]['children'][$skey]);
				        				} else {
				        					$cateValues[$key]['children'][$fkey]['children'][$skey]['classDepth'] = 3;
		        							$cateValues[$key]['children'][$fkey]['children'][$skey]['parentClass'] = array($val["id"], $fchildrenObj["id"]);
			        				}
			        			}
	        				} else {
	        					// unset($cateValues[$key]['children'][$fkey]);
	        					$cateValues[$key]['children'][$fkey] = array();
	        				}
	        			}
	        		} else {
	        			// unset($cateValues[$key]);
	        			$cateValues[$key] = array();
	        		}
        		}
	        	foreach($cateValues as $key => $val){
	        		if (empty($val['children'])) {
	        			// unset($cateValues[$key]);
	        			$cateValues[$key] = array();
	        		}
	        	}
	            $cateValuesResult = array();
        		// 剔除指定要过滤掉的分类
	        	foreach($cateValues as $cateValuesObj){
	        		if (!empty($cateValuesObj)) {
	        			$caRe = array();
	        			$caRe['id'] = $cateValuesObj['id'];
						$caRe['name'] = $cateValuesObj['name'];
						$caRe['classDepth'] = $cateValuesObj['classDepth'];
						$caRe['parentClass'] = $cateValuesObj['parentClass'];
						$caRe['children'] = array();
		        		foreach($cateValuesObj['children'] as $fchildrenObj) {
		        			if (!empty($fchildrenObj)) {
								$fre = array();
								$fre['id'] = $fchildrenObj['id'];
								$fre['name'] = $fchildrenObj['name'];
								$fre['classDepth'] = $fchildrenObj['classDepth'];
								$fre['parentClass'] = $fchildrenObj['parentClass'];
								$fre['children'] = array();
			        			foreach($fchildrenObj['children'] as $schildrenObj) {
			        				if (!empty($schildrenObj)) {
			        					$sre = $schildrenObj;
			        					array_push($fre['children'], $schildrenObj);
			        				}
			        				
			        			}	     
			        			if (!empty($fre)) {
			        				array_push($caRe['children'], $fre);
			        			}
		        			}	
		        		}
		        		if (!empty($caRe['children'])) {
		        			array_push($cateValuesResult, $caRe);
		        		}
	        		}
        		}
	            return $cateValuesResult;
        	}   
        }
        return array();
    }

	/**
	 * 查询产品过滤
	 */ 
	public function queryProductFilter($param, $catId, $webClass) {
		// 获得产品类型
		$clsCatType = array();
			switch ($catId) {
		       	case 26:
		       	    $clsCatType = array(1,7,9,13);
		       	    break;
		       	case 27:
		       	    $clsCatType = array(2,5,10,14);
		       	    break;
		       	case 28:
		       	    $clsCatType = array(3,11,16,4,12,15,6);
		       	    break;
		       	default:
		       	    break;
        }
        // 查询搜索产品
        $inputParams = array(
            'vendorId' => $param['agencyId'],
            'startCityCode' => $param['startCityCode'],
            'categoryId' => $webClass,
            'classBrandTypes' => array(1),
            'catType' => $clsCatType,
            'currentPage' => 1,
            'limit' => 1
        );
        $similarProduct = $this->_rorProductIao->querySimilarProductList($inputParams);
        
        // 返回判断结果
        if ($similarProduct['success'] && $similarProduct['data']['count'] > 0) {
             return true;
        } else {
             return false;
		}
	}               

}