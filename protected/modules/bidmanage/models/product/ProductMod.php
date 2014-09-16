<?php

Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');
Yii::import('application.modules.bidmanage.dal.iao.ProductIao');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.dal.iao.ReleaseIao');
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao'); 
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import("application.models.CurlUploadModel");
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');
Yii::import('application.modules.bidmanage.dal.dao.product.ClsrecommendDao');
Yii::import('application.modules.bidmanage.models.common.ComdbMod');
Yii::import('application.modules.bidmanage.models.user.ConfigManageMod');
//product
class ProductMod {

    private $productDao;
    private $commonDao;
    private $bidProductDao;
    private $_bidProductIao;
    private $_financeIao;
    private $_productIao;
    private $userManageDao;
    private $_rorProductIao;
    private $_iaoProductMod;
    private $packageDateDao;
    private $count = 0;
    private $clsrecommendDao;
    private $_bbLog;
    private $_comdbMod;
    private $_configManageMod;
    
    public $filterArray = array(1845);
	function __construct() {
        
		$this->productDao = new ProductDao();
        $this->commonDao = new CommonDao();
        $this->bidProductDao = new BidProductDao();
        $this->_bidProductIao = new BidProductIao;
        $this->_financeIao = new FinanceIao;
        $this->_productIao = new ProductIao;
        $this->userManageDao = new UserManageDao();
        $this->_rorProductIao = new RorProductIao;
        $this->_iaoProductMod = new IaoProductMod();
        $this->packageDateDao = new PackageDateDao();
        $this->clsrecommendDao = new ClsrecommendDao();
        $this->_bbLog = new BBLog();
        $this->_comdbMod = new ComdbMod();
        $this->_configManageMod = new ConfigManageMod();
	}

    /**
     * 招客宝改版-新增竞价产品
     *
     * @author chenjinlong 20131113
     * @param $params
     * Contains Keys:
     * account_id
     * product_id
     * vendor_id
     * @return mixed
     */
    public function insertBidProductRecords($params)
    {
        $accountId = $params['account_id'];
        $vendorId = $params['vendor_id'];
        $productId = $params['product_id'];
        $productType = $params['product_type'];
        $productInfo = array(
            array(
                'productId' => $productId,
                'productType' => $productType,
            ),
        );
        $productList = $this->readProduct($productInfo, $vendorId);
        if (empty($productList)) {
            // 没有合法的产品信息
            return false;
        }
        $idArray = array();
        //待添加（删除）产品ID数组
        foreach ($productList as $product) {
            if($product['productType'] == $params['product_type'] && '33' != $product['productType']){
                $idArray[] = 'p_'.$product['productId'];
            } elseif ($product['productType'] == $params['product_type'] && '33' == $product['productType']) {
                $idArray[] = 't_'.$product['productId'];
            }
        }
        $productList = $this->filterMaldivesCategory($productList);
        $result = $this->createBidProduct($idArray, $accountId, $productList);
        $successCount = count($result['success']);
        $failedCount = count($result['failed']);
        /**
         * "0 < $successCount && 0 == $failedCount" : 操作正常
         * "0 < $successCount && 0 < $failedCount" : 存在重复添加的产品(230004)
         * "0 == $successCount && 0 < $failedCount" : 产品被重复添加(230005)
         * "其他" : 产品添加失败(230006)
         */
        if (0 < $successCount && 0 == $failedCount) {
            return true;
        } elseif (0 < $successCount && 0 < $failedCount) {
            return true;
        } elseif (0 == $successCount && 0 < $failedCount) {
            return true;
        } else {
            return false;
        }
    }
    
	public function getIsAddedProduct($productIdArr,$userInfo,$productType = null) {
		if($productType && $productType == 33){
			$info = $this->productDao->getAddedProduct($productIdArr,$userInfo,$productType);
		}else{
            $info = $this->productDao->getAddedProduct($productIdArr,$userInfo);
		}
		
        return !empty($info) ? $info : array();
    }

    /**
     * [product]查询、排序供应商产品列表
     * @param array $params
     */
    public function readAllProduct($params) {
        //跟团游产品 && 自助游1.0
        if($params['productType'] == "1"||$params['productType'] == "3_1"){
        	if(($params['productType'] == "3_1")){
        		$params['productType'] = 3;
        	}else{
        		$params['productType'] = 1;
        	}
        	$productList = $productList  = BidProductIao::getVendorProductList($params);
        }
        //3-自助游3.0, 33-门票, 5-邮轮
        elseif($params['productType'] == "3_3" || $params['productType'] == "33" || $params['productType'] == "5"){
            if(($params['productType'] == "3_3")){
                $params['productType'] = 3;
            }elseif($params['productType'] == 5){
                $params['productType'] = 5;
            }else{
                $params['productType'] = 33;
            }
            $productList = BidProductIao::getTicketAndDiyProductListByParam($params);
            if($params['productType'] == 33){
            	foreach($productList['rows'] as &$product){
            		if(!$product['productId']){
            			continue;
            		}
            		$holiday = BidProductIao::getSpotHolidayId($product['productId']);
            		$product['holidayId'] = $holiday[0]['holiday_id'];
            	}
            }
        }else if($params['productType'] == "4"){
        	// 签证产品
        	$iaoParam['vendorId'] = $params['vendorId'];
        	$iaoParam['productId'] = $params['productId'];
			$iaoParam['productType'] = $params['productType'];
			$iaoParam['productNameType'] = $params['searchType'];
			$iaoParam['productNameKeyword'] = $params['searchKey']; 
			$iaoParam['checkFlag'] = $params['checkerFlag'];
			$iaoParam['start'] = $params['start'];
			$iaoParam['limit'] = $params['limit'];
        	$productList = ProductIao::getVisaProducts($iaoParam);
        }
        return $productList;
    }

    
    /**
     * [product]查询已经添加的产品列表
     * @param unknown_type $params
     */
    public function readAddedProduct($condParams) {
    	if($condParams['products']){
    		$productIds = '';
    		$tktIds = '';
    		foreach($condParams['products'] as $product){
    			if($product['productType'] == 33){
    				if($productIds){
    					$productIds .= ',';
    				}
    				$productIds .= $product['productId']; 
    			}else{
    				if($tktIds){
                        $tktIds .= ',';
                    }
                    $tktIds .= $product['productId']; 
    			}
    		}
    		
    	}
    	$queryRowsPdt = array();
    	$queryRowsTkt = array();
    	if($tktIds){
    		$condParams['productIds'] = $tktIds;
    		$condParams['productType'] = 33;
    		$queryRowsTkt = $this->productDao->readAddedProduct($condParams);
    	}
    	if($productIds){
    		$condParams['productIds'] = $productIds;
            $queryRowsPdt = $this->productDao->readAddedProduct($condParams);
    	}
    	$queryRows = array_merge($queryRowsTkt,$queryRowsPdt);
        if(!$tktIds && !$productIds){
            $queryRows = $this->productDao->readAddedProduct($condParams);
        }
//        $queryRows = $this->productDao->readAddedProduct($condParams);
        $productList = $this->getWholeProductInfo($queryRows);
        return !empty($productList) ? $productList : array();
    }

    /**
     * [product]查询已经添加的产品总数
     * @param int $params
     */
    public function readAddedProductCount($params) {
    	
        if($params['products']){
            $productIds = '';
            $tktIds = '';
            foreach($params['products'] as $product){
                if($product['productType'] == 33){
                    if($productIds){
                        $productIds .= ',';
                    }
                    $productIds .= $product['productId']; 
                }else{
                    if($tktIds){
                        $tktIds .= ',';
                    }
                    $tktIds .= $product['productId']; 
                }
            }
        }
        $productCountPdt = 0;
        $productCountTkt = 0;
        if($tktIds){
            $condParams['productIds'] = $tktIds;
            $condParams['productType'] = 33;
            $productCountTkt = $this->productDao->readAddedProductCount($params);
        }
        if($productIds){
            $condParams['productIds'] = $productIds;
            $productCountPdt = $this->productDao->readAddedProductCount($params);
        }
        $productCount = $productCountPdt + $productCountTkt;
        if(!$tktIds && !$productIds){
            $productCount = $this->productDao->readAddedProductCount($params);
        }
        
        return $productCount;
    }

    /**
     * [product]根据产品id数组获取产品信息
     * @param unknown_type $productArr
     */
    public function getWholeProductInfo($productArr) {
        for ($i = 0; $i < count($productArr); $i++) {
            // 审核状态，名称
            $productId = $productArr[$i]['productId'];
            $productArr[$i]['checkerFlag'] = $productArr[$i]['checkerFlag'];
            $productArr[$i]['productName'] = $productArr[$i]['productName'];
            $productArr[$i]['agencyProductName'] = $productArr[$i]['agencyProductName'];
            $productArr[$i]['startCityCode'] = $productArr[$i]['startCityCode'];
            $info = $this->commonDao->getDepartCityInfo($productArr[$i]['startCityCode']);
            $productArr[$i]['startCity'] = $info['name'];
//              // 均价
//             $averagePrice = $this->bidProductDao->getBidProductAveragePrice(array('product_id' => $productId));
//             $productArr[$i]['price'] = $averagePrice;
//             // 最后一次添加时间
//             $lastAddedTime = $this->bidProductDao->getBidProductLastAddedTime(array('product_id' => $productId));
//             // 投放时间
//             $putInTime = $this->bidProductDao->getBidProductPutInTime(array('product_id' => $productId,
//                 'last_add_time' => $lastAddedTime));
//             $productArr[$i]['putInTime'] = $putInTime;
//             // 过期时间
//             $deadline = $this->bidProductDao->getBidProductDeadline(array('product_id' => $productId));
//             $productArr[$i]['deadline'] = $deadline;
//             // 天数
//             $bidDays = $this->bidProductDao->getBidProductDays(array('product_id' => $productId));
//             $productArr[$i]['days'] = $bidDays;
            // 排名是否发生变动
            if($productArr[$i]['productType'] == 33){
                $holiday = BidProductIao::getSpotHolidayId($productId);
                $productArr[$i]['holidayId'] = $holiday[0]['holiday_id'];
            }
            $rankChange = $this->bidProductDao->getRankIsChange($productId);
            $productArr[$i]['rankChange'] = $rankChange ? 1 : 0;
        }
        return $productArr;
    }

    public function getWebClassListByProductId($productId,$productType,$startCityCode) {
        $webClassList = $this->productDao->getWebClassByProductId($productId,$productType,$startCityCode);
        return !empty($webClassList) ? $webClassList : array();
    }

    /**
     * [product]删除已添加产品
     * @param array $updateParams
     * @param array $productList
     * @return boolean
     */
    public function update($updateParams, $productList) {
    	$condParams = array();
    	foreach($productList as $product){
    		if($product['productType'] == 33){
    			$condParams['ticketIds'][] = $product['productId'];
    		} else {
    			$condParams['productIds'][] = $product['productId'];
    		}
    	}
        // 已有出价记录的产品不可用删除
        if($condParams['ticketIds'])
            $existTktProducts = $this->productDao->checkBidBidProduct($condParams['ticketIds'],33);
        if($condParams['productIds']){  
            $existBidProducts = $this->productDao->checkBidBidProduct($condParams['productIds']);
        }
        $noExis = array();
        foreach($productList as $product){
        	$isTickExist = 0;
        	$isPdtExist = 0;
        	
        	if($product['productType'] == 33){
	            foreach($existTktProducts as $tktProduct){
	            	if($product['productId'] == $tktProduct['product_id'] && $product['productType'] == $tktProduct['product_type']){
	            		$isTickExist = 1;
	            		break; 
	            	}
	            }
	            if($isTickExist == 0)
	               $noExistTktIds[] = $product['productId'];
        	}else{
	        	foreach($existBidProducts as $bidProduct){
	                if($product['productId'] == $bidProduct['product_id'] && $product['productType'] == $bidProduct['product_type']){
	                    $isPdtExist = 1; 
	                }
	            }
	            if($isPdtExist == 0){
                   $noExistPdtids[] = $product['productId'];
	            }
        	}
        }
        if(count($noExistTktIds) == 0 && count($noExistPdtids) == 0){
        	return FALSE;
        }	
        
        if (count($noExistPdtids) > 0) {
            $resultPdt = $this->productDao->deleteBidProduct($updateParams, $noExistPdtids);
            if(!$resultPdt)
                return $resultPdt;
        }
        if (count($noExistTktIds) > 0) {
            $resultTkt = $this->productDao->deleteBidProduct($updateParams, $noExistTktIds,33);
            if(!$resultTkt)
                return $resultTkt;
        }
        $result = true;
        return $result;
    }

    /**
     * [product]根据产品ids查询供应商产品信息
     * @param array $products
     * @param int $vendorId
     * @return array
     */
    public function readProduct($products, $vendorId) {
        $productList = BidProductIao::getProductClassification($products);
        $params['vendorId'] = $vendorId;
        $isAdmin = AdminTool::isAdmin($params);
		
        foreach ($productList as $key => &$value) {
            /*if (!$isAdmin) {
                if ($vendorId != $value['vendorId']) {    //排除不属于此供应商的的产品
                    unset($productList[$key]);
                    continue;
                } elseif (2 != intval($value['checkerFlag'])) {    //排除未审核的产品
                    unset($productList[$key]);
                    continue;
                }
            }*/
            $value['catType'] = $this->getCatToChn($value['catType']);
            if(empty($value['category'])){
                $value['category'] = array();
            }
        }

        return $productList;
    }

    /**
     * [product]查询产品是否存在于已添加的产品中
     * @param array $productIds
     */
    public function createBidProduct($productIds, $accountId, $productList) {
        $success = array();
        $failed = array();
        foreach ($productIds as $id) {
            $product = $productList[$id];
            //把添加到招客宝中所有产品名称中的'&lt;'、'&gt;'转译符变成 <>
            $product['productName'] = str_replace("&lt;","<",$product['productName']);
            $product['productName'] = str_replace("&gt;",">",$product['productName']);
            $product['agencyProductName'] = str_replace("&lt;","<",$product['agencyProductName']);
            $product['agencyProductName'] = str_replace("&gt;",">",$product['agencyProductName']);
            $existProduct = $this->productDao->readBidProduct($product['productId'], $accountId,$product['productType']);

            $class = $this->formatWebClassArray($product['category']);
            //产品添加过
            if ($existProduct) {
                if (1 == $existProduct['delFlag']) {    //产品添加过并且已删除
                    $product['delFlag'] = 0;
                    $product['lastAddUid'] = $accountId;
                    $product['lastAddTime'] = date('Y-m-d H:i:s');
                    $condParams = array(
                        'productId' => $product['productId'],
                        'productType' => $product['productType']
                    );

                    $success[] = $this->productDao->updateBidProduct($product, $condParams, $class);
                } else {    //产品添加过并且未删除
                    $failed[] = $id;
                }
            } else {    //产品未添加过
                $success[] = $this->productDao->createBidProduct($product, $accountId, $class);
            }
        }
        $result = array(
            'success' => $success,
            'failed' => $failed,
        );
        return $result;
    }

    function formatWebClassArray($classification) {
        $class = array();
        if(is_array($classification)){
        	foreach ($classification as $cls) {
	            // 招客宝改造-剔除一、二级网站分类页分类 mdf by chenjinlong 20131115
	            if(!in_array($cls['classification_depth'], array(1,))){
	                $class[] = array(
	                    'id' => $cls['id'],
	                    'name' => $cls['classification_name'],
	                    'begin_city_code' => $cls['begin_city_code']
	                );
	            }
	            /*if ($cls['parent_id'] != 0) {
	                $class[] = array(
	                    'id' => $cls['id'],
	                    'name' => $cls['classification_name'],
	                    'begin_city_code' => $cls['begin_city_code']
	                );
	            }*/
	        }
	        return $class;
        }else{
        	array();
        }
    }
    /**
     * [product]转换产品线顶级分类为频道号
     * @param type $productCatType
     * 1:  around              周边
     * 2:  domestic            国内长线
     * 3:  abroad_s            出境短线
     * 4:  abroad_l            出境长线
     * 5:  domestic_local      国内当地参团
     * 6:  abroad_local        出境当地参团
     * 7:  around_drive        周边自驾游
     * 8:  domestic_drive      国内自驾游
     * 9:  special_around      牛人专线-周边
     * 10: special_l           牛人专线-国内长线
     * 11: special_abroad_s    牛人专线-出境短线
     * 12: special_abroad_l    牛人专线-出境长线
     * 13: tuniu_around        途牛自组-周边
     * 14: tuniu_around_l      途牛自组-国内长线
     * 15: tuniu_abroad_l      途牛自组-出境长线
     * 16: tuniu_abroad_s      途牛自组-出境短线
     * @return int
     */
    function getCatToChn($productCatType) {
        $catToChn = array(
            1 => 1,
            7 => 1,
            9 => 1,
            13 => 1,
            2 => 2,
            5 => 2,
            8 => 2,
            10 => 2,
            14 => 2,
            3 => 3,
            4 => 3,
            6 => 3,
            11 => 3,
            12 => 3,
            15 => 3,
            16 => 3,
        );
        return $catToChn[$productCatType];
    }

    /**
     * [product]转换频道号为产品线顶级分类
     * @param type $productChn
     * 1: 周边
     * 2: 国内
     * 3：出境
     * @return array
     */
    function getChnToCatArray($productChn) {
        $chnToCat = array(
            1 => array(1, 7, 9, 13),
            2 => array(2, 5, 8, 10, 14),
            3 => array(3, 4, 6, 11, 12, 15, 16),
        );
        return $chnToCat[$productChn];
    }

    public function updateProductProcess() {
        $productCount = $this->getProductCount();
        $limit = 10;

        for ($i = 0; $i < $productCount; $i = $i + 10) {
            $start = $i;
            $productIdType = $this->getProductIdTypeByPage($limit, $start);
            $listUpdateFlag = $this->updateProductList($productIdType);
            if ($listUpdateFlag == false) {
                return false;
            }
        }
        return true;
    }

    public function getProductCount() {
        $count = $this->productDao->getProductCount();
        return !empty($count) ? $count : array();
    }

    public function getProductIdTypeByPage($limit, $start) {
        $productList = $this->productDao->getProductListByPage($limit, $start);

        return $productList;
    }

	// alter by wenrui 2014.01.14
	// alter by wenrui 2014.05.24
    public function updateProductList($productIdType) {
        $res = ProductIao::getAgencyProductList($productIdType);
    	if ($res && $res['success'] && $res['data']) {
        	$agencySysProductList = $res['data'];
    		foreach ($agencySysProductList as $product) {
    			// 剔除跟团产品
    			if($product['productId']>=5000000 || $product['productId']<=2000000){
    				$result = $this->updateProduct($product);
    				if (!$result) {
		                return false;
		            }
    			}
            }
        }
        return true;
    }

    public function updateProduct($product) {
        $webClass = $this->formatWebClassArray($product['category']);
        $product['webClass'] = json_encode($webClass);;
        $result = $this->productDao->updateProduct($product);
        return $result;
    }

    public function getProductInfo($productId,$productType,$accountId) {
        $info = $this->productDao->selectProductInfo($productId,$productType,$accountId);
        return !empty($info) ? $info : array();
    }

    /**
     * 批量查询产品基本信息和各产品的新网站分类信息
     *
     * @author chenjinlong 20121209
     * @param $productIdArr
     * @return array
     */
    public function getProductInfoArr($productIdArr) {
        if (!empty($productIdArr) && is_array($productIdArr)) {
            $rows = $this->_bidProductIao->getProductClassification($productIdArr);
            if (!empty($rows) && is_array($rows)) {
                return $rows;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    /**
     * 批量查询一天前竞价信息
     *
     * @author wanglongsheng 20130125
     * @param $productIdArr
     * @return array
     */
    public function getOneDayBidProduct() {
        /**
         * 招客宝改版-修改原一天机制，转变成打包日期形式
         * mdf by chenjinlong 20131118
         */
        $condParams = array(
//            'show_start_date' => $date,
            'is_pass_bid' => 1,
        );
        $showDateIdArr = $this->productDao->getOneDayAgoTillNowIdArr($condParams);
        $queryCondPartams = array(
            'bid_mark' => 2,
        );
        $productArray = $this->productDao->getOneDayBidProduct($showDateIdArr, $queryCondPartams);
        $productIds = array();
        for ($loopindex = 0; $loopindex < count($productArray); $loopindex++) {
            // 广告位数据拼接
            $adKeyInfo = $this->getAdKeyInfo($productArray[$loopindex]['adKey'],0);
            if ($adKeyInfo[0]) {
                $productArray[$loopindex]['adKeyValue'] = $adKeyInfo[0]['adName'];
            } else {
                //如果不是其中任意一种    直接赋key值
                $productArray[$loopindex]['adKeyValue'] = $productArray[$loopindex]['adKey'];
            }
            //获取供应商信息
            $agencyInfo = $this->userManageDao->getVendorInfo($productArray[$loopindex]['accountId']);
            $productArray[$loopindex]['agencyId'] = $agencyInfo['vendorId'];
            $productArray[$loopindex]['accountName'] = $agencyInfo['accountName'];
            //获取产品名称
            $productInfo = $this->productDao->getProductById($productArray[$loopindex]['productId'],$productArray[$loopindex]['productType']);
            $productArray[$loopindex]['productName'] = $productInfo['productName'];
            //获取出发城市名称
            $cityInfo = $this->commonDao->getDepartCityInfo($productArray[$loopindex]['startCityCode']);
            $productArray[$loopindex]['startCityName'] = $cityInfo['name'];
            if (0 != $productArray[$loopindex]['productId']) {
                // 设置产品ID数组
                $productIds[$loopindex] = $productArray[$loopindex]['productId'];
            }
            // 获取审核状态
            $productArray[$loopindex]['reviewState'] = DictionaryTools::getCheckStateTool($productArray[$loopindex]['checkerFlag']);
        }

        //获取产品线
        $res = ProductIao::getProductAllInfoById($productIds);
        for ($loopindex = 0; $loopindex < count($productArray); $loopindex++) {
            if (!empty($res['returnMessage'])) {
                foreach ($res['returnMessage'] as $key => $value) {
                    if ($productArray[$loopindex]['productId'] == $key && 0 != $productArray[$loopindex]['productId']) {
                        $productArray[$loopindex]['line_fullname'] = $value['product_line_fullname'];
                        break;
                    }
                }
            } else {
                $productArray[$loopindex]['line_fullname'] = '';
            }
        }

        //获取分类信息
        $classRes = $this->_bidProductIao->getProductClassification($productArray);
        for ($loopindex = 0; $loopindex < count($productArray); $loopindex++) {
            if ($productArray[$loopindex]['adKey'] == 'class_recommend') {
                foreach ($classRes as $key => $value) {
                    if ($productArray[$loopindex]['productId'] == $key) {
                        if (count($value['category']) > 0) {
                            foreach ($value['category'] as $cKey => $cValue) {
                                if ($cValue['id'] == $productArray[$loopindex]['webClass']) {
                                    $productArray[$loopindex]['classification_name'] = $cValue['classification_name'];
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
        return $productArray;
    }
    
    /**
     * 过滤马代分类
     * @param $productList
     */
    public function filterMaldivesCategory($productList){
    	foreach($productList as &$product){
    		if($product['category']){
    			for($i=0;$i<count($product['category']);$i++){
    				if(in_array($product['category'][$i]['id'],$this->filterArray)){
    					unset($product['category'][$i]);
    				}
    			}
    		}
    	}
    	return $productList;
    }
    /**
     * 增加截图的url到bid_show_product表中
     * @param $product
     */
    public function updateScreenshotUrl($product){
        $result = $this->productDao->updateScreenshotUrl($product);
        return $result;
    }

    /**
     * 替换产品
     * @param $data
     */
    public function replaceProduct($data){
        // 判断是否存在指定竞价产品: 若不存在，新增竞价产品；若存在，则跳过
        $productInfo = $this->productDao->selectProductInfo($data['tgtProductId'],$data['product_type'],$data['account_id']);
        if(empty($productInfo)){
            $accountInfo = $this->userManageDao->readUser(array('id'=>$data['account_id']));
            $productMod = new ProductMod();
            $insertBidPrdParams = array(
                'account_id' => $data['account_id'],
                'product_id' => $data['tgtProductId'],
                'vendor_id' => intval($accountInfo['vendorId']),
                'product_type' => $data['product_type'],
            );
            $result = $productMod->insertBidProductRecords($insertBidPrdParams);
            if (!$result) {
                return -2;
            }
        }
        // add by huangxun 2013-12-10 判断当前替换的目标产品是否已经在当前操作的广告位中竞价成功
        $params['bid_id'] = $data['bidId'];
        $temParams = $this->bidProductDao->getBidRecords($params);
        $aldBidSuc = $this->bidProductDao->getAldBidSuc($temParams['0']);
        if (!empty($aldBidSuc)) {
            foreach($aldBidSuc as $productArr) {
                if ($data['tgtProductId'] == $productArr['product_id']) {
                    $result = -1;
                    return $result;
                }
            }
        }
        $result = $this->bidProductDao->replaceProduct($data);
        return $result;
    }

    /**
     * [product]查询推广产品列表
     * @param unknown_type $params
     */
    public function getBidProductList($condParams) {
        if($condParams['products']){
            $productIds = '';
            foreach($condParams['products'] as $product){
                if($productIds){
                    $productIds .= ',';
                }
                $productIds .= $product['productId'];
            }
        }
        $condParams['productIds'] = $productIds;
        $queryRows = $this->productDao->getBidProductList($condParams);
        $stateMark = array(
            array('id' => 1, 'state' => '推广成功'),
            array('id' => 2, 'state' => '竞价成功'),
            array('id' => -1, 'state' => '产品未审核'),
            array('id' => -2, 'state' => '推广失败'),
            array('id' => -3, 'state' => '竞价失败'),
            array('id' => -100, 'state' => '系统故障'),
        );
        // 获取所有出发城市
        $startCityList = $this->_iaoProductMod->getMultiCityInfo();
        $rows = array();
        // 循环拼接数据
        foreach($queryRows as $val){
            $temp = array();
            $temp['bidId'] = intval($val['bidId']);
            $temp['showDateId'] = intval($val['showDateId']);
            $temp['webClassId'] = intval($val['webClass']);
            $temp['startCityCode'] = intval($val['startCityCode']);
            $temp['productId'] = intval($val['productId']);
            $temp['productType'] = intval($val['productType']);
            $temp['adKey'] = $val['adKey'];
            // 根据出发城市code获取name
            if ($startCityList['all']) {
                foreach ($startCityList['all'] as $tempArr) {
                    if ($tempArr['code'] == $temp['startCityCode']) {
                        $temp['startCityName'] = $tempArr['name'];
                        break;
                    }
                }
            } else {
                $temp['startCityName'] = '';
            }
            // 广告位数据拼接
            if ($val['adKey'] == "index_chosen") {
                $temp['adKeyName'] = $temp['startCityName'] . "出发-首页";
            } elseif ($val['adKey'] == "class_recommend") {
                $webClassStr = json_decode($val['webClassStr'],true);
                foreach($webClassStr as $tempStr){
                    if (intval($tempStr['id']) == intval($val['webClass'])) {
                        $temp['adKeyName'] = $temp['startCityName'] . "出发". "-" . $tempStr['name'];
                    }
                }
            } elseif ($val['adKey'] == "search_complex") {
            	// 修改搜索页字段
                $temp['adKeyName'] = $val['searchName'] . "-搜索页";
            } else {
                $temp['adKeyName'] ='';
            }
            $temp['searchKeyword'] = $val['searchName'];
            $temp['bidPrice'] = intval($val['bidPrice']);
            $temp['ranking'] = intval($val['ranking']);
            $temp['bidMark'] = intval($val['bidMark']);
            $temp['maxLimitPrice'] = $val['maxLimitPrice'];
            // 循环取出对应的状态
            foreach($stateMark as $tempMark) {
                if (intval($val['bidMark']) == intval($tempMark['id'])) {
                    $temp['bidState'] = $tempMark['state'];
                }
            }
            if (empty($temp['bidState'])) {
                $temp['bidState'] = '';
            }
            $temp['productName'] = $val['productName'];
            $temp['agencyProductName'] = $val['agencyProductName'];
            $temp['checkFlag'] = intval($val['checkerFlag']);
            $temp['showStartDate'] = $val['showStartDate'];
            $temp['showEndDate'] = $val['showEndDate'];
            // 提供是否可替换的参数给前端进行是否可替换操作
            if (date("Y-m-d H:i:s") > $val['replaceEndTime']) {
                $temp['enableReplace'] = 0;
            } else {
                $temp['enableReplace'] = 1;
            }
            // 根据accountId获取vendorId
            $manageMod = new UserManageMod();
            $temp['vendorId'] = $manageMod->getVendorIdByAccountId($val['accountId']);
            $rows[] = $temp;
        }
        return !empty($rows) ? $rows : array();
    }

    /**
     * [product]查询推广产品列表总数
     * @param int $condParams
     */
    public function getBidProductListCount($condParams) {
        if($condParams['products']){
            $productIds = '';
            foreach($condParams['products'] as $product){
                if($productIds){
                    $productIds .= ',';
                }
                $productIds .= $product['productId'];
            }
        }
        $condParams['productIds'] = $productIds;
        $queryCount = $this->productDao->getBidProductListCount($condParams);
        return $queryCount;
    }

    /**
     * [product]hg-查询推广产品列表
     * @param unknown_type $params
     */
    public function getHgProductList($condParams) {
        // 根据adName模糊搜索网站接口获取webClassId
        if (!empty($condParams['adName']) && $condParams['adKey'] == 'class_recommend') {
            $WebClassInfo = $this->queryWebClassInfo(array('webClassName' => $condParams['adName']));
            if ($WebClassInfo) {
                foreach ($WebClassInfo as $temp) {
                    $condParams['webClassId'][] = $temp['id'];
                }
            }
        }
        $queryRows = $this->productDao->getHgProductList($condParams);
        $rows = array();
        // 设置参数获取分类页信息
        $webClassId = array();
        $webClassData = array();
        foreach($queryRows as $val){
            if ($val['webClass']) {
                array_push($webClassId,$val['webClass']);
            }
        }
        if ($webClassId) {
            // 使用循环每次10条来获取数据
            for ($i = 0; $i < count($queryRows); $i = $i + 10) {
                // 调用网站接口获取分类信息
                $paramWebClassId = array_slice($webClassId, $i, 10);
                $webClassArr = array('webClassId' => $paramWebClassId);
                $webClassInfo = $this->_productIao->getWebClassInfo($webClassArr);
                $webClassData[] = $webClassInfo['data'];
            }
        }

        // 增加上级分类的查找 added by chenjinlong 20140331
        $webClassParentIdArr = array();
        foreach($webClassData as $subItem) {
            foreach($subItem as $iaoObj){
                $webClassParentIdArr['webClassId'][] = $iaoObj['parentId'];
            }
        }
        $parentWebClassInfoRows = ProductIao::getWebClassInfo($webClassParentIdArr);

        // 获取所有出发城市
        $startCityList = $this->_iaoProductMod->getMultiCityInfo();
        // 循环拼接数据
        foreach($queryRows as $val){
            $temp = array();
            $temp['bidId'] = intval($val['bidId']);
            $temp['bidDate'] =  $val['bidStartDate'].' '.$val['bidStartTime'].'点～'.$val['bidEndDate'].' '.$val['bidEndTime'].'点';
            $temp['showDateId'] = intval($val['showDateId']);
            $temp['webClassId'] = intval($val['webClass']);
            $temp['startCityCode'] = intval($val['startCityCode']);
            // 根据出发城市code获取name
            if ($startCityList['all']) {
                foreach ($startCityList['all'] as $tempArr) {
                    if ($tempArr['code'] == $temp['startCityCode']) {
                        $temp['startCityName'] = $tempArr['name'];
                        break;
                    }
                }
            } else {
                $temp['startCityName'] = '';
            }
            $temp['productId'] = ($val['productId']) ? $val['productId'] : '';
            $temp['productType'] = intval($val['productType']);
            $temp['adKey'] = $val['adKey'];
            // 广告位数据拼接
            $temp['adKeyDetail'] = '';
            $adKeyInfo = $this->getAdKeyAllInfo($val['adKey'], $val['startCityCode']);
            if ($adKeyInfo[0]) {
                $temp['adKeyName'] = $adKeyInfo[0]['adName'];
                if ("class_recommend" == $adKeyInfo[0]['adKey']) {
                    if ($webClassData) {
                        foreach($webClassData as $tempStr){
                            if (intval($tempStr[$temp['webClassId']]['id']) == intval($val['webClass'])) {
                                $parentWebClassStr = str_replace('目的地', '', strval($parentWebClassInfoRows['data'][$tempStr[$temp['webClassId']]['parentId']]['classificationName']));
                                $temp['adKeyDetail'] = $parentWebClassStr . '-' . $tempStr[$temp['webClassId']]['classificationName'];
                            }
                        }
                    }
                }
            } else {
                $temp['adKeyName'] ='';
            }
            // 首页老数据呈现处理
            if ("index_chosen" == $temp['adKey']) {
                $indexAdInfo = $this->productDao->queryIndexInfo("index_chosen");
                $temp['adKeyName'] = $indexAdInfo['ad_name'];
            }
            $temp['searchKeyword'] = $val['searchName'];
            $temp['bidPrice'] = intval($val['bidPrice']);
            $temp['ranking'] = intval($val['ranking']);
            $temp['bidMark'] = intval($val['bidMark']);
            $temp['maxLimitPrice'] = $val['maxLimitPrice'];
            $temp['bidPriceNiu'] = $val['bidPriceNiu'];
            $temp['maxLimitPriceNiu'] = $val['maxLimitPriceNiu'];
            $temp['bidPriceCoupon'] = $val['bidPriceCoupon'];
            $temp['maxLimitPriceCoupon'] = $val['maxLimitPriceCoupon'];
            // 获取竞拍状态
            $temp['bidState'] = DictionaryTools::getBidStateTool($val['bidMark']);
            $temp['productName'] = $val['productName'];
            $temp['agencyProductName'] = $val['agencyProductName'];
            $temp['showDate'] = $val['showStartDate'].'～'.$val['showEndDate'];
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userManageDao->getVendorInfoAll($val['accountId']);
            $temp['vendorId'] = $verdorInfo[0]['vendorId'];
            $temp['vendorName'] = $verdorInfo[0]['brandName'];// 供应商品牌名
            // 根据产品经理Id获取产品经理名字
            $temp['managerName'] = $this->getManagerNameById($val['managerId']);
            $rows[] = $temp;
        }
        return !empty($rows) ? $rows : array();
    }

    /**
     * [product]hg-查询推广产品列表总数
     * @param array $condParams
     */
    public function getHgProductListCount($condParams) {
        // 根据adName模糊搜索网站接口获取webClassId
        if (!empty($condParams['adName']) && $condParams['adKey'] == 'class_recommend') {
            $WebClassInfo = $this->queryWebClassInfo(array('webClassName' => $condParams['adName']));
            if ($WebClassInfo) {
                foreach ($WebClassInfo as $temp) {
                    $condParams['webClassId'][] = $temp['id'];
                }
            }
        }
        $changeParams = $this->getProductLineIdStr($condParams);
        $queryCount = $this->productDao->getHgProductListCount($changeParams);
        return $queryCount;
    }

    /**
     * [product]查询产品线ID串数据
     * @param array $params
     */
    public function getProductLineIdStr($params) {
        if ($params['productLine']['productType'] || $params['productLine']['destinationClass']) {
            // 调用hg接口得到产品线ID串数据
            $tempParams = array(
                'productType' => $params['productLine']['productType'],
                'destinationClass' => $params['productLine']['destinationClass'],
            );
            $productLineIdStr = $this->_productIao->readProductLineIdStr($tempParams);
            if ($productLineIdStr['success'] && $productLineIdStr['data']) {
                $params['productLineIdStr'] = $productLineIdStr['data'];
            }
        }
        return $params;
    }

    /**
     * 上传文件函数
     * @author huangxun 20131226
     * @param $params
     * @return array
     */
    public function uploadFile($file) {
        if (!empty($file)) {
            if (isset($file['size']) && $file['size'] > 0) {
                //处理文件名
                $result = json_decode(CurlUploadModel::save($file), true);
                if ($result['success']) {
                    //save to database
                    $url = $result['data'][0]['url'];
                    return $url;
                } else {
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * 查询所有的产品经理信息
     * @author huangxun 20131227
     * @param $params
     * @return array
     */
    public function getManager($param) {
        //设置缓存
        $key = md5(Sundry::GET_PRODUCT_MANAGER);
        $data = Yii::app()->memcache->get($key);
        if (!empty($data)) {
            $result = $data;
        } else {
            $result = $this->_productIao->getManager();
            if ($result) {
                Yii::app()->memcache->set($key, $result, 86400);
            }
        }
        return $result;
    }

    /**
     * 查询所匹配的产品经理名字
     * @author huangxun 20131227
     * @param $params
     * @return array
     */
    public function getManagerName($param) {
        $allManagerInfo = $this->getManager($param);
        $allManagerArray = $allManagerInfo['data'];
        unset($allManagerInfo);
        $result = array();
        $managerIds = array();
        foreach ($allManagerArray as $tempArray) {
            $managerId = $tempArray['tuniuManagerId'];
            $managerName = $tempArray['tuniuManagerNickname'];
            if ($managerName) {
                // 判断入参是否能匹配产品经理名字
                $index = strpos($managerName,$param['managerName']);
                if (false !== $index && $index >= 0 && !in_array($managerId, $managerIds)) {
                    $temp = array('managerId' => $managerId,'managerName' => $managerName);
                    array_push($managerIds,$managerId);
                    array_push($result,$temp);
                }
            }
        }
        return $result;
    }

    /**
     * 根据产品经理ID查询产品经理名字
     * @author huangxun 20131227
     * @param $params
     * @return array
     */
    public function getManagerNameById($param) {
        $allManagerInfo = $this->getManager($param);
        $allManagerArray = $allManagerInfo['data'];
        $result = "";
        foreach ($allManagerArray as $tempArray) {
            $managerId = $tempArray['tuniuManagerId'];
            $managerName = $tempArray['tuniuManagerNickname'];
            if ($managerId) {
                // 判断入参是否等于产品经理ID
                if ($managerId == $param) {
                    $result = $managerName;
                }
            }
        }
        return $result;
    }

    /**
     * [product]查询列表打包时间参数-3.0
     * @param unknown_type $params
     */
    public function getDateParam($params) {
        return $this->productDao->getDateParam($params);
    }

    /**
     * [product]查询推广位置列表-3.0
     * @param unknown_type $params
     */
    public function getNewProductList($condParams) {
        $queryRows = $this->productDao->getNewProductList($condParams);
        // 设置参数获取分类页信息
        $webClassId = array();
        $webClassData = array();
        foreach($queryRows as $val){
            if ($val['webClass']) {
                array_push($webClassId,$val['webClass']);
            }
        }
        if ($webClassId) {
            // 使用循环每次10条来获取数据
            for ($i = 0; $i < count($queryRows); $i = $i + 10) {
                // 调用网站接口获取分类信息
                $paramWebClassId = array_slice($webClassId, $i, 10);
                $webClassArr = array('webClassId' => $paramWebClassId);
                $webClassInfo = $this->_productIao->getWebClassInfo($webClassArr);
                $webClassData[] = $webClassInfo['data'];
            }
        }

        // 增加上级分类的查找 added by chenjinlong 20140331
        $webClassParentIdArr = array();
        foreach($webClassData as $subItem) {
            foreach($subItem as $iaoObj){
                $webClassParentIdArr['webClassId'][] = $iaoObj['parentId'];
            }
        }
        $parentWebClassInfoRows = ProductIao::getWebClassInfo($webClassParentIdArr);

        // 获取所有出发城市
        $startCityList = $this->_iaoProductMod->getMultiCityInfo();
        // 循环拼接数据
        $rows = array();
        foreach($queryRows as $val){
        	
            $temp = array();
            $temp['bidId'] = intval($val['bidId']);
            $temp['bidDate'] =  $val['bidStartDate'].' '.$val['bidStartTime'].'点～'.$val['bidEndDate'].' '.$val['bidEndTime'].'点';
            $temp['showDateId'] = intval($val['showDateId']);
            $temp['showStartDate'] =  $val['showStartDate'];// 投放时间
            $temp['showEndDate'] =  $val['showEndDate'];// 截止时间
            $temp['webClassId'] = intval($val['webClass']);
            $temp['isBuyout'] = $val['isBuyout'];// 是否包场
            $temp['startCityCode'] = intval($val['startCityCode']);
            // 根据出发城市code获取name
            if ($startCityList['all']) {
                foreach ($startCityList['all'] as $tempArr) {
                    if ($tempArr['code'] == $temp['startCityCode']) {
                        $temp['startCityName'] = $tempArr['name'];
                        break;
                    }
                }
            } else {
                $temp['startCityName'] = '';
            }
            $temp['adKey'] = $val['adKey'];
            // 广告位数据拼接
            $adKeyInfo = $this->getAdKeyAllInfo($val['adKey'], $temp['startCityCode']);
            if ($adKeyInfo[0]) {
                $temp['adKeyName'] = $temp['startCityName'] . '-' . $adKeyInfo[0]['adName'];
                if ("class_recommend" == $adKeyInfo[0]['adKey']) {
                    if ($webClassData) {
                        foreach($webClassData as $tempStr){
                            if (intval($tempStr[$temp['webClassId']]['id']) == intval($val['webClass'])) {
                                $parentWebClassStr = str_replace('目的地', '', strval($parentWebClassInfoRows['data'][$tempStr[$temp['webClassId']]['parentId']]['classificationName']));
                                $temp['adKeyName'] = $temp['startCityName'] . "-" . $parentWebClassStr . '-' . $tempStr[$temp['webClassId']]['classificationName']. "-分类页";
                            }
                        }
                    }
                } elseif ("search_complex" == $adKeyInfo[0]['adKey']) {
                    if (!empty($temp['startCityName']) && '' != $temp['startCityName']) {
                        // 新版本
                        $temp['adKeyName'] = $temp['startCityName'].'-'.$val['searchName'] . '-' . $adKeyInfo[0]['adName'];
                    } else {
                        // 旧版本
                        $temp['adKeyName'] = $val['searchName'] . '-' . $adKeyInfo[0]['adName'];
                    }
                }
            } else {
                $temp['adKeyName'] ='';
            }
            // 首页老数据呈现处理
            if ("index_chosen" == $temp['adKey']) {
                $indexAdInfo = $this->productDao->queryIndexInfo("index_chosen");
                $temp['adKeyName'] = $temp['startCityName'] . '-' . $indexAdInfo['ad_name'];
            }
            $temp['searchKeyword'] = $val['searchName'];
            if ($condParams['bidState'] == 1 || $condParams['bidState'] == 2 || $condParams['bidState'] == -1) {
                $temp['bidMark'] = intval($val['bidMark']);
                // 获取竞拍状态
                $temp['bidState'] = DictionaryTools::getBidStateTool($val['bidMark']);
            } elseif ($condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
                $temp['bidMark'] = 1;
                $temp['bidState'] =  '推广成功';
            }
            if (empty($temp['bidState'])) {
                $temp['bidState'] = '';
            }
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userManageDao->getVendorInfoAll($val['accountId']);
            $temp['vendorId'] = $verdorInfo[0]['vendorId'];
            $temp['vendorName'] = $verdorInfo[0]['brandName'];// 供应商品牌名
            // 查询竞价产品数量和排名信息
            $param = array(
                'adKey' => $temp['adKey'],
                'accountId' => $val['accountId'],
                'showDateId' => $temp['showDateId'],
                'startCityCode' => $temp['startCityCode'],
                'webClass' => $temp['webClassId'],
                'searchKeyword' => $temp['searchKeyword'],
                'bidState' => $condParams['bidState'],
                'bidMark' => $temp['bidMark']
            );
            // 查询位置信息
            $posParam['ad_key'] = $temp['adKey'];
            $posParam['web_class'] = $temp['webClassId'];
            $posParam['start_city_code'] = $temp['startCityCode'];
            $posParam['show_date_id'] = $temp['showDateId'];
            $positionInfo = $this->productDao->getPositionInfo($posParam);
            // 初始化位置ID
            $temp['positionId'] = 0;
            if ('class_recommend' != $temp['adKey']) {
            	$temp['positionId'] = $positionInfo['id'];
            }
            // 初始化位置数量
            if ($positionInfo['ad_product_count']) {
                $temp['adCount'] = $positionInfo['ad_product_count'];
                // ConstDictionary::getAdPositionCountLimit($positionInfo['adProductCount'], $temp['startCityCode'], $temp['adKey']);
            } else {
                $temp['adCount'] = 0;
            }
            $bidAdInfo = $this->productDao->getBidAdInfo($param,1);
            // 获取所有bid_id
            $bidDetailInfo = $this->productDao->getBidDetailInfo($param);
            if ($bidAdInfo) {
                $bidSuccessCount=0;
                $temp['ranking'] = array();
                $bidId = array();
                foreach ($bidAdInfo as $tempInfo) {
                    // 竞价中列表只取竞价成功的数据条数和排名，竞价失败的则不显示排名，其他tab页都为成功，所以全显示
                    if ($condParams['bidState'] == 1 && $tempInfo['bidMark'] == 2) {
                        array_push($temp['ranking'],$tempInfo['ranking']);
                        $bidSuccessCount++;
                    } elseif ($condParams['bidState'] == 2 || $condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
                        array_push($temp['ranking'],$tempInfo['ranking']);
                    }
                    // 取出所有竞价成功的竞价id
                    array_push($bidId,$tempInfo['id']);
                }
                if ($condParams['bidState'] == 1) {
                    $temp['bidAdCount'] = $bidSuccessCount;
                } else {
                    $temp['bidAdCount'] = count($bidAdInfo);
                }
                // 判断竞价成功和推广中产品信息是否编辑完整
                if ($condParams['bidState'] == 2 || $condParams['bidState'] == 3) {
                    $productParam = array(
                        'bidId' => $bidId,
                        'bidState' => $condParams['bidState'],
                        'accountId' => $val['accountId']
                    );
                    $bidProductInfo = $this->productDao->bidProductInfo($productParam);
                    $bidProductCount=0;
                    foreach ($bidProductInfo as $tempBidProductInfo) {
                        if (!empty($tempBidProductInfo['contentType']) && !empty($tempBidProductInfo['contentId'])) {
                            $bidProductCount++;
                        }
                    }
                    if ($bidProductCount >= 0 && $bidProductCount == $temp['bidAdCount']) {
                        $temp['productIsFull'] = 1;// 已完整
                    } else {
                        $temp['productIsFull'] = 0;// 未完整
                    }
                }
            } else {
                $temp['bidAdCount'] = 0;
            }
            // 设置bidIdNew数组
            $temp['bidIds'] = array();
            foreach ($bidDetailInfo as $bidDetailInfoObj) {
            	array_push($temp['bidIds'], $bidDetailInfoObj['id']);
            }
            $rows[] = $temp;
        }
        return !empty($rows) ? $rows : array();
    }

    /**
     * [product]查询推广位置列表总数-3.0
     * @param array $condParams
     */
    public function getNewProductListCount($condParams) {
        $queryCount = $this->productDao->getNewProductListCount($condParams);
        return $queryCount;
    }

    /**
     * [product]查询参与竞价的广告信息-3.0
     * @param array $param
     */
    public function getBidAdInfo($param) {
        $bidAdInfo = $this->productDao->getBidAdInfo($param,2);
        $rows = array();
        foreach($bidAdInfo as $val){
            $temp = array();
            $temp['id'] = $val['id'];
            $temp['ranking'] = $val['ranking'];
            $temp['bidPrice'] = ($val['bidPrice']) ? $val['bidPrice'] : 0;
            $temp['maxLimitPrice'] = ($val['maxLimitPrice']) ? $val['maxLimitPrice'] : 0;
            $temp['bidMark'] = $val['bidMark'];
            $temp['loginName'] = $val['loginName'];
            // 查询推广结束的show_id
            $show_id = '';
            if ($param['bidState'] == 4) {
                $showIdParam = array('accountId' => $param['accountId'],'bidId' => $val['id']);
                $show_id = $this->productDao->getShowId($showIdParam);
            }
            $tempParam = array('bidState' => $param['bidState'],'accountId' => $param['accountId'],'id' => ($param['bidState'] == 4) ? $show_id['showId']:$val['id']);
            // 查询广告内容信息
            $adContent = $this->productDao->getAdContent($tempParam);
            $temp['contentId'] = $adContent['contentId'];
            $temp['contentType'] = $adContent['contentType'];
            // 查询参与竞价的产品信息
            $productParam = array('accountId' => $param['accountId'],'productId' => intval($temp['contentId']),'productType' => intval($temp['contentType']));
            $productInfo = $this->productDao->getBidProductInfo($productParam);
            $temp['productName'] = $productInfo['productName'];
            $temp['agencyProductName'] = $productInfo['agencyProductName'];
            $temp['checkerFlag'] = $productInfo['checkerFlag'];
            // 查询广告附加信息
            $AdAddition = $this->productDao->getAdAddition($tempParam);
            if ($AdAddition) {
                $additionArr = array();
                foreach ($AdAddition as $tempInfo) {
                    $tempInfoArr = array();
                    $tempInfoArr['vasKey'] = $tempInfo['vasKey'];
                    // 查询附加信息名称
                    $vasName = $this->productDao->getVasName($tempInfo);
                    $tempInfoArr['vasName'] = $vasName['vasName'];
                    $tempInfoArr['additionPrice'] = $tempInfo['bidPrice'];
                    $additionArr[] = $tempInfoArr;
                }
                $temp['additionInfo'] = $additionArr;
            } else {
                $temp['additionInfo'] = array();
            }
            $rows[] = $temp;
        }
        $result = array('rows' => $rows, 'count' => count($bidAdInfo));
        return !empty($result) ? $result : array();
    }

	/**
	 * 查询首页产品和分类页产品
	 * @param array $param
	 * @return array $result
	 */
	public function queryClassifyPro($param) {
		switch ($param['type']) {
			// 首页查询
			case 0 :
			// 查询拼接首页
			$result = $this->generateTotalPro($param);
			break;
			// 分类页查询
			case 1 :
			// 查询拼接分类页
			$result = $this->generateClsfyPo($param);
			break;
			// 搜索页查询
			case 2 :
			// 查询拼接分类页
			$result = $this->generateSearchPro($param);
			break;
			// 没有满足条件返回空
			default:
			// 返回空结果
			$result = array();
			break;
		}
			
		// 返回结果
		return $result;
	}

	/**
	 * 生成搜索页结果
	 * @param productType	 productId	vendorId	start	limit
	 * @return array count  rows{   id	  keyword}
	 */
	public function generateSearchPro($param) {
		// 若产品编号为空，则直接返回空数组
		if (empty($param['productId']) || null == $param['productId'] || '' == $param['productId']) {
			return array();
		}
		// 查询数据库		
		$tempRe = $this->productDao->queryKeyWord($param);
		// 初始化最终结果
		$result=array();
		// 若结果不为空且有数据，则处理结果
		if (!empty($tempRe) && is_array($tempRe)) {
			// 循环拼接信息
			foreach ($tempRe as $temObj) {
                // 初始化临时对象
                $tempBObj = array();
                // 拼接搜索页
                $tempBObj['viewName'] = $temObj['keyword'].'-搜索页';
                // 拼接其他条件
                $tempBObj['productId']=$param['productId'];
                $tempBObj['id']=$temObj['id'];
                // 拼接产品类型    新增 自助游  门票
                $tempBObj['productType']=$param['productType'];
                // 添加结果集
                array_push($result, $tempBObj);
			}
		}
		
		// 返回结果
		return $result;
	}

	/**
	 * 生成首页结果
	 * @param productType	 productId	vendorId	start	limit
	 * @return array count  rows{   productId	  startCityCode	 startCityName	productName	 agencyProductName	productType	 price	checkerFlag	 updateTime	viewName 显示的名称}
	 */
	public function generateTotalPro($param) {
		// 若产品编号为空，则直接返回空数组
		if (empty($param['productId']) || null == $param['productId'] || '' == $param['productId']) {
			return array();
		}
		$manage = new UserManageMod;
        $params = array('id' => $param['accountId']);
        $user = $manage->read($params);
		// 分类初始化参数
		if ('33' == $param['productType']) {
			// 门票
			$params = array('vendorId' => $user['vendorId'], 'spotCode' => $param['productId'], 'productType' => $param['productType'], 'accountId' => $param['accountId'], 'start' => 0, 'limit' => 50);
		} else {
			// 跟团游和自助游
			$params = array('vendorId' => $user['vendorId'], 'productId' => $param['productId'], 'productType' => $param['productType'], 'productIds'=>array(0=>$param['productId']), 'accountId' => $param['accountId'], 'start' => 0, 'limit' => 50);
		}
		// 获取首页产品信息   新增 自助游  门票
		// $tempRe = BidProductIao::getVendorProductList($params);
		$tempRe = $this -> readAllProduct($params);
		// 初始化最终结果
		$result=array();
		$tempBObj = array();
		// 若结果不为空且有数据，则处理结果
		if (!empty($tempRe['rows']) && is_array($tempRe['rows']) && 0 != $tempRe['count'] && '1' == $param['productType']) {
			// 跟团游首页
			// 查询产品所支持的所有预订城市 added by chenjinlong 20131118
        	$queryStartCityArrParams = array(
            	'productId' => $param['productId'],
        	);
        	$startCityArr = BidProductIao::getAllProductStartCityArr($queryStartCityArrParams);
			// 循环拼接信息
			foreach ($tempRe['rows'] as $temObj) {
				foreach($startCityArr as $startCityInfo) {
                    // 初始化临时对象
                    $tempBObj = array();
                    // 拼接其他条件
                    $tempBObj['productId']=$temObj['productId'];
                    $tempBObj['productName']=$temObj['productName'];
                   	// 拼接首页
                   	$tempBObj['viewName'] = $startCityInfo['name']. '出发-首页';
                   	$tempBObj['startCityCode']=$startCityInfo['code'];
                   	$tempBObj['startCityName']=$startCityInfo['name'];
                    
                    $tempBObj['price']=$temObj['price'];
                    $tempBObj['checkerFlag']=$temObj['checkerFlag'];
                    // 拼接产品类型    新增 自助游  门票
                    $tempBObj['productType']=$param['productType'];
                    // 添加结果集
                    array_push($result, $tempBObj);
                }
			}
		} else if (!empty($tempRe['rows']) && is_array($tempRe['rows']) && 0 != $tempRe['count'] && '3_3' == $param['productType']) {
			// 自助游首页
			// 循环拼接信息
			foreach ($tempRe['rows'] as $temObj) {
				// 初始化临时对象
    	        $tempBObj = array();
        	    // 拼接其他条件
            	$tempBObj['productId']=$temObj['productId'];
            	$tempBObj['productName']=$temObj['productName'];
           		// 初始化城市名称
           		$cityName = $this->productDao->getCityName($temObj['startCityCode']);
           		// 拼接自助游首页
   	            $tempBObj['viewName'] = $cityName['name']. '出发-首页';
       	        $tempBObj['startCityCode']=$temObj['startCityCode'];
				$tempBObj['startCityName']=$cityName;
                $tempBObj['price']=$temObj['price'];
                $tempBObj['checkerFlag']=$temObj['checkerFlag'];
                // 拼接产品类型    新增 自助游  门票
                $tempBObj['productType']=$param['productType'];
                // 添加结果集
                array_push($result, $tempBObj);
			}
		} else if (!empty($tempRe['rows']) && is_array($tempRe['rows']) && 0 != $tempRe['count'] && '33' == $param['productType']) {
			// 门票首页
			// 初始化获取所有城市的参数
			$paramCity['cityCode'] = -1;
			// 获取所有城市
			$allCity = $this->productDao->getAllDepartureCityInfo($paramCity);
			// 循环拼接信息
			foreach ($tempRe['rows'] as $temObj) {
				// 循环设置每个出发城市的门票信息
				foreach($allCity as $ciObj) {
					// 初始化临时对象
	    	        $tempBObj = array();
    	    	    // 拼接其他条件
        	    	$tempBObj['productId']=$temObj['productId'];
            		$tempBObj['productName']=$temObj['productName'];
            		// 拼接门票首页
    		        $tempBObj['viewName'] = $ciObj['name'].'-'.$temObj['spotName']. '门票-首页';
        	    	$tempBObj['startCityCode']=$ciObj['code'];
					$tempBObj['startCityName']=$ciObj['name'];
                	$tempBObj['price']=$temObj['price'];
                	$tempBObj['checkerFlag']=$temObj['checkerFlag'];
	                // 拼接产品类型    新增 自助游  门票
	                $tempBObj['productType']=$param['productType'];
	                // 添加结果集
	                array_push($result, $tempBObj);	
				}
			}
		}
		// 返回结果
		return $result;
	}
	
	/**
	 * 生成分类页信息
	 * @param array $param
	 * @return array $result
	 */
	public function generateClsfyPo($param) {
		// 若产品编号为空，则直接返回空数组
		if (empty($param['productId']) || null == $param['productId'] || '' == $param['productId']) {
			return array();
		}
		// 初始化参数
		$paramsClient = array("0" => array('productId' => $param['productId'], 'productType' => $param['productType']));
		try {
			// 获取一级分类
			$tempRe = BidProductIao::getProductClassification($paramsClient);
			// 若没数据，直接返回空
			if (empty($tempRe)) {
				return array();
			}
			
			// 预初始化处理结果
			$reArr = array();
			// 分类初始化处理结果，p_为自助游和跟团游   t_为门票
			if ('33' != $param['productType']) {
				$reArr = $tempRe['p_'.$param['productId']]['category'];
			} else if ('33' == $param['productType']) {
				$reArr = $tempRe['t_'.$param['productId']]['category'];
			}
			
			// 初始化最终结果
			$result=array();
			$tempObj = array();
			// 分类整合跟团游，自助游和门票数据
			if (!empty($reArr) && is_array($reArr) && '33' != $param['productType']) {
            // 查询产品所支持的所有预订城市 added by chenjinlong 20131118
            $queryStartCityArrParams = array(
                'productId' => $param['productId'],
            );
            $startCityArr = BidProductIao::getAllProductStartCityArr($queryStartCityArrParams);
            $startCities = array();
            	foreach($startCityArr as $item) {
                $startCities[] = $item['code'];
            }
				// 循环处理category
                foreach ($reArr as $reObj) {
                    // 如果分类级别为1，则过滤掉
                    if (1 != $reObj['classification_depth']) {
                        // 初始化临时对象
                        $tempObj = array();
                        $idArr = '';

                        // 分类设置自助游和门票
                        if ('1' == $param['productType']) {
                            // 取预订城市交集 added by chenjinlong 20131118
                            $beginCityCodeArr = array_intersect($startCities, $reObj['begin_city_code']);
                        } else {
                            $beginCityCodeArr = $reObj['begin_city_code'];
                        }

                        if (!$beginCityCodeArr) {
                            continue;
                        }

                        // 循环拼接id
                        foreach ($beginCityCodeArr as $tempID) {
                            $idArr .= $tempID;
                            $idArr .= ',';
                        }
                        // 过滤id串
                        $idArr = substr($idArr, 0, strlen($idArr) - 1);
                        foreach ($reArr as $obj) {
                            if ($reObj['parent_id'] == $obj['id']) {
                                $tempObj['classification_name'] = $reObj['classification_name'] . '（' . $obj['classification_name'] . '）';
                            }
                        }
                        $tempObj['id'] = $reObj['id'];
                        $tempObj['parent_id'] = $reObj['parent_id'];
                        $tempObj['classification_depth'] = $reObj['classification_depth'];
                        // 初始化查询参数
                        $queryObj['code'] = $idArr;
                        // 设置拼接用的名称
                        $queryObj['name'] = '-' . $tempObj['classification_name'] . '-分类页';
                        // 重新设置城市名称
                        $tempObj['begin_city_code'] = $this->productDao->queryCityName($queryObj);
                        // 拼接产品类型    新增 自助游  门票
                        $tempObj['productType'] = $param['productType'];
                        array_push($result, $tempObj);
                    }
                }
			} else if (!empty($reArr) && is_array($reArr) && '33' == $param['productType']) {
				// 循环处理category
				foreach ($reArr as $reObj) {
					// 如果分类级别为1，则过滤掉
					if (1 != $reObj['classification_depth']) {
						// 初始化临时对象
						$tempObj = array();
						foreach ($reArr as $obj) {
		    	            if ($reObj['parent_id'] == $obj['id']) {
		        	            $tempObj['classification_name'] = $reObj['classification_name'].'（'.$obj['classification_name'].'）';
		            	    }
			            }
						$tempObj['id'] = $reObj['id'];
						$tempObj['parent_id'] = $reObj['parent_id'];
						$tempObj['classification_depth'] = $reObj['classification_depth'];
						// 初始化查询参数，设置拼接用的名称
			            $queryObj['name'] = '-'.$tempObj['classification_name'].'门票-分类页';
						// 重新设置城市名称
						$tempObj['begin_city_code'] = $this->productDao->getAllDepartureCityInfoForCls($queryObj);
		                // 拼接产品类型    新增 自助游  门票
		                $tempObj['productType']=$param['productType'];
						array_push($result, $tempObj);
					}
				}
			}
		} catch(Exception $e) {
			// 初始化错误结果
			$result = array();
		}
		// 返回处理结果
		return $result;
	}

	/**
	 * 查询九宫格综合信息
	 * @param array $param
	 * @return array $return
	 */
	public function queryNineCell($param) {
		// 初始化返回结果
		$return = array();
		// 初始化综合查询参数
		$allParam = $this->generateNineCellParams($param);
		// 查询九宫格综合信息
		$allRe = $this->productDao->queryBidInfo($allParam);
		// 查询九宫格维度信息
		$colRe = $this->productDao->queryBidColumn($allParam);
		
		// 预初始化城市维度结果集
		$citRe = array();
		// 分类初始化城市数组
		if ('nonono' != $allParam['code']) {
			// 查询九宫格城市维度信息
			$citRe = $this->productDao->queryBidCity($allParam);
		}
		// 查询搜索竞价信息
		$searchRe = $this->productDao->queryKeyWordBid($allParam);
		
		// 初始化webClsID集合
		$webRe = '';
		// 判断是否需要查询webClsID
		if ('nonono' != $allParam['webId']) {
			// 查询webClsID
			$webRe = $this->productDao->queryBidWebClass($allParam);			
		}
		
		// 整合结果
		$return['rows'] = $this->generateNineCellResult($allParam, $allRe, $webRe, $colRe, $citRe, $searchRe);
		// 整合title结果
		$return['title'] =  $this->generateNineCellTitle($colRe);
		// 整合count
		$return['count'] = $this->count;
		
		// 返回结果
		return $return;
	}
	
	/**
	 * 生成综合查询参数
	 * @param array $param
	 * @return array $queryParam
	 */
	public function generateNineCellParams($param) {
		// 设置查询参数产品ID
		$queryParam['productId'] = $param['productId'];
		// 设置查询参数账户ID
		$queryParam['accountId'] = $param['accountId'];
		// 初始化首页code字符串和数组
		$indexArr = '';
		$indexList = $param['index_chosen'];
		// 初始化分类页code字符串
		$classifyArr = '';
		$classifyList = $param['class_recommend'];
		// 初始化webclass字符串
		$webClasArr = '';
		
		// 若参数不为空，则循环拼接首页code串
		if (!empty($indexList) && is_array($indexList)) {
			foreach ($indexList as $indexObj) {
				$indexArr .= $indexObj['code'];
				$indexArr .= ',';
			}
			// 过滤code串
			$indexArr = substr($indexArr, 0, strlen($indexArr) - 1);
		} else {
			// 设置SQL不添加条件标记
			$indexArr = 'nonono';
		}
		// 若参数不为空，则循环拼接分类页code串
		if (!empty($classifyList) && is_array($classifyList)) {
			foreach ($classifyList as $classifyObj) {
				$classifyArr .= $classifyObj['code'];
				$classifyArr .= ',';
				$webClasArr .= $classifyObj['webClassId'];
				$webClasArr .= ',';
			}
			// 过滤code串
			$classifyArr = substr($classifyArr, 0, strlen($classifyArr) - 1);
			// 过滤webclass串
			$webClasArr = substr($webClasArr, 0, strlen($webClasArr) - 1);
		} else {
			// 设置SQL不添加条件标记
			$classifyArr = 'nonono';
			$webClasArr = 'nonono';
		}

		// 分类设定最终code串
		$queryParam['code'] = 'nonono';
		if ('nonono' != $indexArr) {
			$queryParam['code'] = $indexArr;
			if ('nonono' != $classifyArr) {
				$queryParam['code'].=',';
				$queryParam['code'].=$classifyArr;
			}
		} 
		if ('nonono' == $indexArr && 'nonono' != $classifyArr) {
			$queryParam['code'] = $classifyArr;	
		}

		// 设置首页code
		$queryParam['codeIndex'] = $indexArr;
		// 设置分类页code
		$queryParam['codeClsy'] = $classifyArr;
		// 设置首页和分类页类型参数
		$queryParam['typeA'] = 'index_chosen';
		$queryParam['typeB'] = 'class_recommend';
		// 设置webclass串参数
		$queryParam['webId'] = $webClasArr;
		// 设置原始参数
		$queryParam['index_chosen'] = $param['index_chosen'];
		$queryParam['class_recommend'] = $param['class_recommend'];
		$queryParam['search_complex'] = $param['search_complex'];
		// 设置产品类型  新增自助游和门票
		$queryParam['productType'] = $param['productType'];
		
		// 返回参数
		return $queryParam;
	}
	
	/**
	 * 生成九宫格结果  处理跟团游和自助游
	 * @param array $allRe
	 * @param array $webRe
	 * @param array $citRe
	 * @return array $rows
	 */ 
	public function generateNineCellResult($allParam, $allRe, $webRe, $colRe, $citRe, $searchRe) {

		// 初始化webclassstr对象
		$webArr = array();
		if (!empty($webRe) && is_array($webRe)) {
			$webArr = json_decode($webRe['webClassStr'], true);
		}
		$rowsArr = array();
        // 初始化count
        $this->count = 0;
		// 循环整合首页数据
		foreach($citRe as $citObj) {
			// 判断是否需要继续循环
			if ('nonono' == $allParam['codeIndex']) {
				// 首页没参数数据，中断循环
				break;
			}
			// 以出发城市为基础维度进行循环
			// 初始化临时行对象
			$tempRow = array();
			// 初始化rows基本信息
			foreach($allParam['index_chosen'] as $paramObj) {
				if ($paramObj['code'] == $citObj['startCityCode']) {
					// 设置出发城市code
					$tempRow['startCityCode'] = $citObj['startCityCode'];
					// 设置名称
					$tempRow['routeName'] = $paramObj['viewName'];
			// 展开日期循环
			foreach($colRe as $colObj) {
				// 初始化判断标记
				$pdFlag = false;
			// 开启综合数据循环
			foreach($allRe as $allObj) {
				// 分类处理首页和分类页数据
					if($citObj['startCityCode'] == $allObj['startCityCode'] && 'index_chosen' == $allObj['adKey'] && $allObj['dateId'] == $colObj['dateId']) {
						// 设置判断标记为true
						$pdFlag = true;
					// 设置日期包含的对象内容
						$tempRow[$allObj['showDate']] = array('id'=>$allObj['id'], 'bidPrice'=>$allObj['bidPrice'], 'bidRanking'=>$allObj['ranking'], 'adKey'=>'index_chosen',
                            'showDateId'=>$allObj['dateId'], 'productId'=>$allParam['productId'], 'startCityCode'=>$citObj['startCityCode'], 'webClassId'=>0,
                            'maxLimitPrice'=>$allObj['maxLimitPrice'], 'bidMark'=>$allObj['bidMark'], 'productType'=>$allParam['productType']);
					} 
				}
				// 若tempRow为空，则添加原始空数据
				if (!$pdFlag) {
					$tempRow[$colObj['showDate']] = array('showDateId'=>$colObj['dateId'], 'productId'=>$allParam['productId'], 'adKey'=>'index_chosen', 'startCityCode'=>$citObj['startCityCode'], 'webClassId'=>0, 'productType'=>$allParam['productType']);
				} 
			}
				}
			}
			// 若结果不为空，添加数据			
			if (!empty($tempRow) && is_array($tempRow)) {
				// 添加行数据
				array_push($rowsArr, $tempRow);
				// 计数器加1
				$this->count = $this->count + 1;
			}
			
		}
		// 循环整合分类页数据
		foreach($citRe as $citObj) {
			// 判断是否需要继续循环
			if ('nonono' == $allParam['codeClsy']) {
				// 分类页页没参数数据，中断循环
				break;
			}
			// 以出发城市为基础维度进行循环
			// 初始化去重标记
			$singFlag = 0;
			while(true) {
				// 初始化临时行对象
				$tempRow = array();
				if ($singFlag == count($allParam['class_recommend'])) {
					break;
				}
				$singFlag++;
			// 初始化rows基本信息
			foreach($allParam['class_recommend'] as $paramObj) {
				// 初始化循环跳出标记
				$xhOut = true;
				foreach($rowsArr as $rowsArrObj) {
					if (strcmp($rowsArrObj['routeName'], $paramObj['viewName']) == 0) {
						$xhOut = false;
					}
				}
				if (!$xhOut) {
					continue;
				}
				if ($paramObj['code'] == $citObj['startCityCode']) {
					// 设置出发城市code
					$tempRow['startCityCode'] = $citObj['startCityCode'];
					// 设置城市名称
					$tempRow['routeName']=$paramObj['viewName'];
			// 展开日期循环
			foreach($colRe as $colObj) {
				// 初始化判断标记
				$pdFlag = false;
				// 开启综合数据循环
				foreach($allRe as $allObj) {
					// 分类处理首页和分类页数据
					if($citObj['startCityCode'] == $allObj['startCityCode'] && 'class_recommend' == $allObj['adKey'] && $allObj['dateId'] == $colObj['dateId']) {
						// 循环查找景点名称
						foreach($webArr as $webObj) {
							// 如果webObj中的id（分类ID）等于webclsid，则取出景点名称
							if ($webObj['id'] == $allObj['webClass']) {
								// 设置判断标记为true
								$pdFlag = true;
								// 设置日期包含的对象内容
								$tempRow[$allObj['showDate']] = array('id'=>$allObj['id'], 'bidPrice'=>$allObj['bidPrice'], 'bidRanking'=>$allObj['ranking'], 'adKey'=>'class_recommend', 'productId'=>$allParam['productId'], 'showDateId'=>$colObj['dateId'], 'startCityCode'=>$citObj['startCityCode'], 'webClassId'=>$webObj['id'], 'maxLimitPrice'=>$allObj['maxLimitPrice'], 'bidMark'=>$allObj['bidMark'], 'productType'=>$allParam['productType']);				
							}
						}
					}	
				}
				// 若tempRow为空，则添加原始空数据
				if (!$pdFlag) {
							$tempRow[$colObj['showDate']] = array('showDateId'=>$colObj['dateId'], 'productId'=>$allParam['productId'], 'adKey'=>'class_recommend', 'startCityCode'=>$citObj['startCityCode'], 'webClassId'=>$paramObj['webClassId'], 'productType'=>$allParam['productType']);
						}	
				}	
			}
			}

			// 若结果不为空，添加数据					
			if (!empty($tempRow) && is_array($tempRow)) {
				// 添加行数据
				array_push($rowsArr, $tempRow);
				// 计数器加1
				$this->count = $this->count + 1;
			}
			}
			
		}

		// 初始化搜索页维度数组
		$searchArr = $allParam['search_complex'];
		// 若搜索页查询参数不为空则循环初始化搜索页结果
		if (!empty($searchArr) && is_array($searchArr)) {
			foreach($searchArr as $searchObj) {
				// 初始化临时行对象
				$tempRow = array();
				// 设置搜索ID
				$tempRow['startCityCode'] = $searchObj['id'];
				// 设置搜索关键词
				$tempRow['routeName']=$searchObj['viewName'];
				// 初始化出价分割数组
				$searchWordArr = explode('-',$searchObj['viewName']);
				
				// 展开日期循环
				foreach($colRe as $colObj) {
					// 初始化判断标记
					$pdFlag = false;
					// 循环匹配已出价的结果	
					foreach($searchRe as $seaObj) {
						// 若关键词匹配，则添加结果对象
						if($searchWordArr[0] == $seaObj['keyword'] && $seaObj['dateId'] == $colObj['dateId']) {
							// 设置判断标记为true
							$pdFlag = true;
							// 设置日期包含的对象内容
							$tempRow[$colObj['showDate']] = array('id'=>$seaObj['id'], 'bidPrice'=>$seaObj['bidPrice'], 'bidRanking'=>$seaObj['ranking'], 'adKey'=>'search_complex', 'productId'=>$allParam['productId'], 'showDateId'=>$colObj['dateId'], 'startCityCode'=>0, 'webClassId'=>0, 'maxLimitPrice'=>$seaObj['maxLimitPrice'], 'bidMark'=>$seaObj['bidMark'], 'searchKeyword'=>$seaObj['keyword'], 'productType'=>$allParam['productType']);				
						}
					}
						
					// 若tempRow为空，则添加原始空数据
					if (!$pdFlag) {
							$tempRow[$colObj['showDate']] = array('showDateId'=>$colObj['dateId'], 'productId'=>$allParam['productId'], 'adKey'=>'search_complex', 'startCityCode'=>0, 'webClassId'=>0, 'searchKeyword'=>$searchWordArr[0], 'productType'=>$allParam['productType']);
					}	
					
				}
				// 若结果不为空，添加数据					
				if (!empty($tempRow) && is_array($tempRow)) {
					// 添加行数据
					array_push($rowsArr, $tempRow);
					// 计数器加1
					$this->count = $this->count + 1;
				}					
			}
		}
		
		// 返回rows数据
		return $rowsArr;
	} 

	/**
	 * 生成九宫格结果
	 * @param array $colRe
	 * @return array $rows
	 */ 
	public function generateNineCellTitle($colRe) {
		// 初始化返回title合集
		$title = array();
		// 循环添加时间title
		foreach($colRe as $colObj) {
			// 添加时间段
			array_push($title, $colObj['showDate']);
		}
		// 返回结果
		return $title;
	}


    /**
     * 招客宝改版-查询所有出发城市数组
     *
     * @author chenjinlong 20131121
     * @return array
     */
    public function getAllDepartureInfo($params)
    {
        return $this->productDao->getAllDepartureCityInfo($params);
    }

    /**
     * 查询即将上线的产品的邮件信息
     *
     * @author wanglongsheng 20130125
     * @param $productIdArr
     * @return array
     */
    public function getMailLineProduct() {
    	// 查询即将上线的产品的原始信息
        $productArray = $this->productDao->queryMailLineProduct();
        // 初始化产品ID数组
        $productIds = array();
        // 循环修改产品相关信息
        for ($loopindex = 0, $m = count($productArray); $loopindex < $m; $loopindex++) {
            // 广告位数据拼接
            $adKeyInfo = $this->getAdKeyInfo($productArray[$loopindex]['adKey'], $productArray[$loopindex]['startCityCode']);
            if ($adKeyInfo[0]) {
                $productArray[$loopindex]['adKeyValue'] = $adKeyInfo[0]['adName'];
            } else {
                //如果不是其中任意一种    直接赋key值
                $productArray[$loopindex]['adKeyValue'] = $productArray[$loopindex]['adKey'];
            }
			// 如果添加了产品，则设置产品线和审核状态
            if (0 != $productArray[$loopindex]['productId']) {
            	// 设置产品ID数组
            	$productIds[$loopindex] = $productArray[$loopindex]['productId'];
                // 获取审核状态
                $productArray[$loopindex]['reviewState'] = DictionaryTools::getCheckStateTool($productArray[$loopindex]['checkerFlag']);
            } else {
            	// 未知状态
	            $productArray[$loopindex]['reviewState'] = '';
            }
        }
        //获取产品线
        $res = ProductIao::getProductAllInfoById($productIds);
        for ($loopindex = 0, $m = count($productArray); $loopindex < $m; $loopindex++) {
        	if(!empty($res['returnMessage'])) {
	            foreach ($res['returnMessage'] as $key => $value) {
    	            if ($productArray[$loopindex]['productId'] == $key && 0 != $productArray[$loopindex]['productId']) {
        	            $productArray[$loopindex]['line_fullname'] = $value['product_line_fullname'];
            	        break;
                	}
            	}
        	} else {
        		$productArray[$loopindex]['line_fullname'] = '';
        	}
        }
        //获取分类信息
        $classRes = $this->_bidProductIao->getProductClassification($productArray);
        for ($loopindex = 0, $m = count($productArray); $loopindex < $m; $loopindex++) {
            if ('class_recommend' == $productArray[$loopindex]['adKey']) {
                foreach ($classRes as $key => $value) {
                    if ($productArray[$loopindex]['productId'] == $key) {
                        if (count($value['category']) > 0) {
                            foreach ($value['category'] as $cKey => $cValue) {
                                if ($cValue['id'] == $productArray[$loopindex]['webClass']) {
                                    $productArray[$loopindex]['classification_name'] = $cValue['classification_name'];
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            // 处理productId为0的情况
        	if (0 == $productArray[$loopindex]['productId']) {
        		$productArray[$loopindex]['productId'] = '';
        	}
        }
        // 返回结果
        return $productArray;
    }
    
    /**
     * 搜索页关键词改版-刷新关键词表
     * 
     * @author p-sunhao 20131231
     * @param array() $param
     * @param int $flag
     * @return boolean 
     */
    public function refreshKeywordTable($param, $flag, $keywordData) {
    	
    	// 初始化需要更新的关键词结果集
    	$updateKeyword = array();
    	// 初始化需要插入的关键词结果集
    	$insertKeyword = array();
		// 初始化计数标记
		$countFlag = 0;
		// 初始化数据库操作标记
		$dbFlag = true;
		
		// 嵌套插入或更新关键词表
		foreach($param as $paramObj) {
			// 判断标记是否正确，若不正确，返回false
			if (!$dbFlag) {
				// 操作数据库失败，返回false
				return false;
			}
			// 还原计数标记
			$countFlag = 0;
			foreach($keywordData as $keywordObj) {
				// 若关键词相等，则更新关键词
				if (0 == strcmp($paramObj['key_word'], $keywordObj['keyword'])) {
					// 设置ID参数
					$paramObj['id'] = $keywordObj['id'];
					// 更新关键词
					$dbFlag = $this->productDao->updateKeyword($paramObj);
					// 中断循环
					break;
				} else {
					// 没找到匹配项，计数标记加1
					$countFlag++;
				}
			}
			// 若计数标记等于表中数据，则添加数据
			if ($countFlag === count($keywordData)) {
				// 插入数据库
				$dbFlag = $this->productDao->insertKeyword($paramObj);
			}
		}
    	
    	// 一切操作顺利，返回true，告知脚本可以执行下一分组
    	return true;
    }
    
    /**
	 * 获取搜索页关键词数据
	 */
	public function getKeywordData($param) {
		// 返回查询结果
		return $this->productDao->queryKeyWord($param);
	}
    
    /**
	 * 获取竞拍时间
	 */
	public function getBidDate() {
		// 返回查询结果
		return $this->productDao->queryBidColumn(0);
	}
	
	/**
	 * 获取广告位类型
	 */
	public function getPositionType() {
		// 初始化首个元素
    	$head = array('adKey'=>'all', 'adName'=>'全部');
    	// 查询数据库
    	$rows = $this->productDao->queryPositionType();
        // 是否存在首页广告位标记
        $indexFlag = 0;
        // 是否存在频道页广告位标记
        $channelFlag = 0;
        // 如果存在首页广告位时则把所有广告位去除
        foreach ($rows as $k => $temp) {
            if (strpos($temp['adKey'],'index_chosen') !== false) {
                unset($rows[$k]);
                $indexFlag = 1;
            }
        }
        // 首页广告位统一处理
        if ($indexFlag == 1) {
            $indexAdKey = array('adKey' => 'index_chosen_all', 'adName' => '首页-全部');
            array_push($rows,$indexAdKey);
        }
        // 如果存在频道页广告位时则把所有广告位去除
        foreach ($rows as $k => $temp) {
            if (strpos($temp['adKey'],'channel_chosen') !== false) {
                unset($rows[$k]);
                $channelFlag = 1;
            }
        }
        // 频道页广告位统一处理
        if ($channelFlag == 1) {
            $channelAdKey = array('adKey' => 'channel_chosen_all', 'adName' => '频道页-全部');
            array_push($rows,$channelAdKey);
        }
    	// 初始化最终结果
    	$result = array();
    	// 添加首个元素
    	array_push($result, $head);
    	// 循环添加数据元素
    	foreach($rows as $rowsObj) {
    		array_push($result, $rowsObj);
    	}
		// 返回结果
		return $result;
	}

    //@TODO-chenjinlong 20140513
    public function generateBidListNew($params)
    {
        // 查询每一个打包日期对应的广告类型和位置数
        $finalResult = array();
        $dateId = '';
		// 判断是否需要将所有可用打包日期查出
		if ('' == $params['dateId']) {
			// 查询数据库获取打包日期集合
			$dateRe = $this->productDao->queryBidColumn(0);
			// 循环初始化打包日期ID和打包时间字符串
			foreach($dateRe as $dateObj) {
				$dateId = $dateId.$dateObj['dateId'].',';
			}
			// 过滤打包相关字符串
			if (0 < strlen($dateId)) {
				$dateId = substr($dateId, 0, strlen($dateId) - 1);
			}
		} else {
			// 初始化打包日期相关参数为前台传过来的参数
			$dateId = $params['dateId'];
		}
		// 没有运营计划，生成并返回空结果
		if (empty($dateId) || '' == $dateId) {
			// 生成格式化结果
			$result['rows'] = array();
			$result['count'] = 0;
			// 返回空结果
			return $result;
		}
		$dateIdArray = explode(",",$dateId);
        $memKeyForPosRe = md5('ProductMod::generateBidList-1_' . $dateId);
        $finalResult = Yii::app()->memcache->get($memKeyForPosRe);
        
        if(empty($finalResult)){
            $positionRe = $this->productDao->queryPositionInfo($dateId);
            foreach($positionRe as $eachPos)
            {
                $key = intval($eachPos['show_date_id']) . '_' . intval($eachPos['web_class']) . '_' . intval($eachPos['start_city_code']) . '_' . $eachPos['ad_key'];
                $finalResult[$key] = $eachPos;
            }
            //缓存6h
            Yii::app()->memcache->set($memKeyForPosRe, $finalResult, 21600);
        }
        // 初始化以出发城市和分类ID为组合的父类集合
        $clsParent = array();
        foreach ($params['rows'] as $posItem) {
          	$clsParent[intval($posItem['webId']) . '_' . intval($posItem['startCityCode'])] = $posItem['parentClass'];
        }
            
        $resultRows = array();
        foreach($dateIdArray as $id){
        	foreach($params['rows'] as $posItem)
	        {
	            if($params['adQueryKey']!='all'&&$params['adQueryKey']!='index_chosen_all'&&$params['adQueryKey']!='channel_chosen_all'&&$params['adQueryKey']!=$posItem['adKey']){
	            	continue;
	            }
	            $mappingKey = '';
	            $mappingKeyCls = '';
	            $viewName = '';
	            switch($posItem['adKey'])
	            {
	                case 'class_recommend':
	                    $viewName = $posItem['startCityName'] . '-' . $posItem['classificationName'] . '-' . $posItem['adName'];
	                    $mappingKey = intval($id) . '_' . intval($clsParent[intval($posItem['webId']) . '_' . intval($posItem['startCityCode'])][0]) . '_' . intval($posItem['startCityCode']) . '_' . $posItem['adKey'];
	                    $mappingKeyCls = intval($id) . '_' . intval($posItem['webId']) . '_' . intval($posItem['startCityCode']) . '_' . $posItem['adKey'];
	                    break;
	                case 'search_complex':
	                    $viewName = $posItem['startCityName'] . '-' . $posItem['searchKeyWord'] . '-' . $posItem['adName'];
	                    $mappingKey = intval($id) . '_0_0' . '_' . $posItem['adKey'];
	                    break;
	                case 'special_subject':
	                    $viewName = $posItem['startCityName'] . '-' . $posItem['adName'];
	                    $mappingKey = intval($id) . '_0_0' . '_' . $posItem['adKey'];
	                    break;
	                case 'brand_zone':
	                    $viewName = $posItem['startCityName'] . '-' . $posItem['adName'];
	                    $mappingKey = intval($id) . '_0_0' . '_' . $posItem['adKey'];
	                    break;
	                default:
	                    $viewName = $posItem['startCityName'] . '-' . $posItem['adName'];
	                    $mappingKey = intval($id) . '_0_' . intval($posItem['startCityCode']) . '_' . $posItem['adKey'];
	                    break;
	            }

                // 过滤没有广告位的数据
                if (!$finalResult[$mappingKey]) {
                    continue;
                }

	            $listParam = array(
	                'start_city_code' => $posItem['startCityCode'],
	                'search_keyword' => $posItem['adKey']=='search_complex'?"'".$posItem['searchKeyWord']."'":'',
	                'web_id' => $posItem['webId'],
	                'account_id' => $params['account_id'],
	                'show_date_id' => $id,
	                'ad_key' => $posItem['adKey'],
	            );
	            $bidSuccessList = $this->productDao->queryBidListRank($listParam);
	            $bidIdArr = array();
	            foreach($bidSuccessList as $eachBidSucItem)
	            {
	                $bidIdArr[] = $eachBidSucItem['id'];
	            }
	
				$adProductCount = $finalResult[$mappingKey]['ad_product_count'];
				if ('class_recommend' == $posItem['adKey'] && 2 == $posItem['classDepth'] && strtotime($finalResult[$mappingKeyCls]['update_time']) > strtotime($finalResult[$mappingKey]['update_time'])) {
					$adProductCount = $finalResult[$mappingKeyCls]['ad_product_count'];
				} else if ('class_recommend' == $posItem['adKey'] && 3 == $posItem['classDepth']) {
					$mappingKeyMid = intval($id) . '_' . intval($clsParent[intval($posItem['webId']) . '_' . intval($posItem['startCityCode'])][1]) . '_' . intval($posItem['startCityCode']) . '_' . $posItem['adKey'];
					$updateKeys = array();
					$updateKeys[$mappingKey] = strtotime($finalResult[$mappingKey]['update_time']);
					$updateKeys[$mappingKeyCls] = strtotime($finalResult[$mappingKeyCls]['update_time']);
					$updateKeys[$mappingKeyMid] = strtotime($finalResult[$mappingKeyMid]['update_time']);
					$adProductCount = $finalResult[array_search(max($updateKeys),$updateKeys)]['ad_product_count'];
				}
				
	            $resultRows['rows'][] = array(
	                'viewName' => $viewName,
	                'positionId' => $finalResult[$mappingKey]['id'],
	                'dateId' => $id,
	                'showDate' => $finalResult[$mappingKey]['showDate'],
	                'startCityCode' => $posItem['startCityCode'],
	                'startCityName' => $posItem['startCityName'],
	                'searchKeyword' => $posItem['adKey']=='search_complex'?$posItem['searchKeyWord']:'',
	                'adKey' => $posItem['adKey'],
	                'adName' => $posItem['adName'],
	                'webClassId' => $posItem['webId'],
	                'classificationName' => $posItem['classificationName'],
	                'totalAd' => $adProductCount,
	                'bidAd' => count($bidSuccessList),
	                //'viewAd' => '',
	                //'bidRank' => '',
	                'bidId' => implode(',', $bidIdArr),
	            );
	        }
        }
        $resultRows['count'] = count($resultRows['rows']);
        return $resultRows;
    }
	
	/**
	 * 生成竞价列表
	 */
	public function generateBidList($param) {
		// 预初始化打包日期ID和打包时间字符串
		$dateIdArr = '';
		// 判断是否需要将所有可用打包日期查出
		if ('' == $param['dateId']) {
			// 查询数据库获取打包日期集合
			$dateRe = $this->productDao->queryBidColumn(0);
			// 循环初始化打包日期ID和打包时间字符串
			foreach($dateRe as $dateObj) {
				$dateIdArr = $dateIdArr.$dateObj['dateId'].',';
			}
			// 过滤打包相关字符串
			if (0 < strlen($dateIdArr)) {
				$dateIdArr = substr($dateIdArr, 0, strlen($dateIdArr) - 1);
			}
		} else {
			// 初始化打包日期相关参数为前台传过来的参数
			$dateIdArr = $param['dateId'];
		}
		// 没有运营计划，生成并返回空结果
		if (empty($dateIdArr) || '' == $dateIdArr) {
			// 生成格式化结果
			$result['rows'] = array();
			$result['count'] = 0;
			// 返回空结果
			return $result;
		}
		// 查询每一个打包日期对应的广告类型和位置数
        $memKeyForPosRe = md5('ProductMod::generateBidList_' . $dateIdArr);
        $positionRe = Yii::app()->memcache->get($memKeyForPosRe);
        if(empty($positionRe)){
            $positionRe = $this->productDao->queryPositionInfo($dateIdArr);
            //缓存6h
            Yii::app()->memcache->set($memKeyForPosRe, $positionRe, 21600);
        }

		// 初始化列表竞价数据查询参数
		$listParam = $this->generateBisListParams($param, $dateIdArr);
		// 查询竞价成功的排名
		$succBid = $this->productDao->queryBidListRank($listParam);
     	// 查询竞价成功的数量
		$countBid = $this->productDao->queryBidListCount($listParam);
		// 生成最终结果
		$rows = $this->generateBisListResult($param, $positionRe, $succBid, $countBid);
		// 生成格式化结果
		$result['rows'] = $rows;
		$result['count'] = count($rows);
		
		// 返回结果
		return $result;
	}
	
	/**
	 * 生成出价查询列表参数
	 */
	public function generateBisListParams($param, $dateIdArr) {
		// 预初始化整合后的参数
		$returnParam = array();
		// 预初始化城市编码参数
	    $cityCodeParam = "";
	    // 预初始化ad_key参数
	    $adKey = "";
	    // 预初始化关键词参数
	    $serachKeyword = "";
	    // 预初始化分类ID参数
	    $webClassParam = "";

        // 全部广告位数据
        $adKeyInfo = $this->getAdKeyInfo("all",0);
        // 如果查询类型为全部，则初始化ad_key为全部类型
        if ("all" == $param['adQueryKey']) {
            // 初始化ad_key为全部类型
            foreach ($adKeyInfo as $adKeyTemp) {
                $adKey .= "'".$adKeyTemp['adKey']."',";
            }
            // 去掉最后一个字符","
            $adKey = substr($adKey,0,strlen($adKey)-1);
	    } else {
	    	// 初始化ad_key为指定类型
	    	$adKey = "'".$param['adQueryKey']."'";
	    }
	    // 分类初始化首页，专题页和搜索页参数
	    foreach ($param['rows'] as $rowsObj) {
            foreach ($adKeyInfo as $adKeyTemp) {
                if ($adKeyTemp['adKey'] == $rowsObj['adKey'] && ("all" == $param['adQueryKey'] || $adKeyTemp['adKey'] == $param['adQueryKey'])) {
                    if(!empty($rowsObj['startCityCode'])){
                    	// 累加cityCode参数
                    	$cityCodeParam = $cityCodeParam.$rowsObj['startCityCode'].",";
                    }
                    if ("search_complex" == $adKeyTemp['adKey']) {
                        // 累加关键词参数
                        $serachKeyword = $serachKeyword."'".$rowsObj['searchKeyWord']."',";
                    }
                    if ("class_recommend" == $adKeyTemp['adKey']) {
                        // 累加分类ID参数
                        $webClassParam = $webClassParam.$rowsObj['webId'].",";
                    }
                }
            }
	    }

	    // 过滤城市编码参数和关键词参数
	    if (0 < strlen($cityCodeParam)) {
	    	$cityCodeParam = substr($cityCodeParam, 0, strlen($cityCodeParam) - 1);
	    }
	    if (0 < strlen($serachKeyword)) {
	    	$serachKeyword = substr($serachKeyword, 0, strlen($serachKeyword) - 1);
	    }
	    
	    // 整合参数
	    $returnParam['start_city_code'] = $cityCodeParam;
	    $returnParam['search_keyword'] = $serachKeyword;
	    $returnParam['web_id'] = $webClassParam;
	    $returnParam['show_date_id'] = $dateIdArr;
	    $returnParam['ad_key'] = $adKey;
	    $returnParam['account_id'] = $param['account_id'];
	    
	    // 返回参数
	    return $returnParam;
	}

	/**
	 * 生成出价列表结果
	 */
	public function generateBisListResult($param, $positionRe, $succBid, $countBid) {
		// 预初始化返回结果
		$result = array();
		// 预初始化返回结果临时对象
		$resultTemp = array();
        // 全部广告位数据
        $adKeyInfo = $this->getAdKeyInfo("all",0);
		// 开启最外层广告位循环
		foreach($param['rows'] as $rowsObj) {
			// 开启打包时间维度循环
			foreach($positionRe as $positionObj) {
				// 初始化返回结果临时对象
				$resultTemp = array();
                // 分类初始化返回结果的viewName
                foreach ($adKeyInfo as $adKeyTemp) {
                    if (("all" == $param['adQueryKey'] || $positionObj['ad_key'] == $param['adQueryKey']) && ("all" == $param['adQueryKey'] || $adKeyTemp['adKey'] == $param['adQueryKey']) && $adKeyTemp['adKey'] == $rowsObj['adKey'] && $positionObj['ad_key'] == $rowsObj['adKey']) {
                        // 设置viewName
                        $resultTemp['viewName'] = $rowsObj['startCityName'].'-'.$rowsObj['adName'];
                        // 设置位置ID
                        $resultTemp['positionId'] = $positionObj['id'];
                        // 设置date_id
                        $resultTemp['dateId'] = $positionObj['show_date_id'];
                        // 设置日期范围
                        $resultTemp['showDate'] = $positionObj['showDate'];
                        // 设置出发城市code
                        $resultTemp['startCityCode'] = $rowsObj['startCityCode'];
                        // 设置出发城市名称
                        $resultTemp['startCityName'] = $rowsObj['startCityName'];
                        // 设置关键词
                        $resultTemp['searchKeyword'] = "";
                        // 设置广告类型
                        $resultTemp['adKey'] = $rowsObj['adKey'];
                        // 设置广告名称
                        $resultTemp['adName'] = $rowsObj['adName'];
                        // 设置分类ID
                        $resultTemp['webClassId'] = 0;
                        // 设置分类名称
                        $resultTemp['classificationName'] = '';
                        // 设置总位置数
                        $resultTemp['totalAd'] = $positionObj['ad_product_count'];
                        // 预设竞拍位置为空串
                        $resultTemp['bidAd'] = "";
                        // 预设展现位置为空串
                        $resultTemp['viewAd'] = "";
                        // 循环匹配并设置竞拍位置
                        foreach($countBid as $countBidObj) {
                            // 若广告位类型，出发城市名称和竞拍时间ID都匹配，则设置竞拍成功位置，展现位置
                            if ($countBidObj['ad_key'] == $rowsObj['adKey'] && $countBidObj['start_city_code'] == $rowsObj['startCityCode'] && $countBidObj['show_date_id'] == $positionObj['show_date_id']) {
                                // 设置竞拍成功位置
                                $resultTemp['bidAd'] = $countBidObj['count_ad'];
                                // 设置展现位置
                                $resultTemp['viewAd'] = $countBidObj['count_ad'].'/'.$positionObj['ad_product_count'];
                                // 中断循环
                                break;
                            }
                        }
                        // 预设排名为空串
                        $resultTemp['bidRank'] = "";
                        // 预设竞价ID为空串
                        $resultTemp['bidId'] = "";
                        foreach($succBid as $succBidObj) {
                            // 若广告位类型，出发城市名称和竞拍时间ID都匹配，则设置竞拍成功位置
                            if ($succBidObj['ad_key'] == $rowsObj['adKey'] && $succBidObj['start_city_code'] == $rowsObj['startCityCode'] && $succBidObj['show_date_id'] == $positionObj['show_date_id']) {
                                // 累加排名
                                $resultTemp['bidRank'] = $resultTemp['bidRank'].$succBidObj['bid_ranking'].',';
                                // 累加竞价ID
                                $resultTemp['bidId'] = $resultTemp['bidId'].$succBidObj['id'].',';
                            }
                        }
                        // 过滤排名和竞价ID
                        if (0 < strlen($resultTemp['bidRank'])) {
                            $resultTemp['bidRank'] = substr($resultTemp['bidRank'], 0, strlen($resultTemp['bidRank']) - 1);
                        }
                        if (0 < strlen($resultTemp['bidId'])) {
                            $resultTemp['bidId'] = substr($resultTemp['bidId'], 0, strlen($resultTemp['bidId']) - 1);
                        }
                    }
                    if (("all" == $param['adQueryKey'] || $positionObj['ad_key'] == $param['adQueryKey']) && ("all" == $param['adQueryKey'] || "search_complex" == $param['adQueryKey']) && "search_complex" == $rowsObj['adKey'] && $positionObj['ad_key'] == $rowsObj['adKey'] && "search_complex" == $adKeyTemp['adKey']) {
                        // 设置搜索页
                        // 设置viewName
                        $resultTemp['viewName'] = $rowsObj['startCityName'].'-'.$rowsObj['searchKeyWord'].'-'.$rowsObj['adName'];
                        // 设置位置ID
                        $resultTemp['positionId'] = $positionObj['id'];
                        // 设置date_id
                        $resultTemp['dateId'] = $positionObj['show_date_id'];
                        // 设置日期范围
                        $resultTemp['showDate'] = $positionObj['showDate'];
                        // 设置出发城市code
                        $resultTemp['startCityCode'] = $rowsObj['startCityCode'];
                        // 设置出发城市名称
                        $resultTemp['startCityName'] = $rowsObj['startCityName'];
                        // 设置关键词
                        $resultTemp['searchKeyword'] = $rowsObj['searchKeyWord'];
                        // 设置广告类型
                        $resultTemp['adKey'] = $rowsObj['adKey'];
                        // 设置广告名称
                        $resultTemp['adName'] = $rowsObj['adName'];
                        // 设置分类ID
                        $resultTemp['webClassId'] = 0;
                        // 设置分类名称
                        $resultTemp['classificationName'] = '';
                        // 设置总位置数
                        $resultTemp['totalAd'] = $positionObj['ad_product_count'];
                        // 预设竞拍位置为空串
                        $resultTemp['bidAd'] = "";
                        // 预设展现位置为空串
                        $resultTemp['viewAd'] = "";
                        // 循环匹配并设置竞拍位置
                        foreach($countBid as $countBidObj) {
                            // 若广告位类型，出发城市名称和竞拍时间ID都匹配，则设置竞拍成功位置，展现位置
                            if ($countBidObj['ad_key'] == $rowsObj['adKey'] && $countBidObj['search_keyword'] == $rowsObj['searchKeyWord'] && $countBidObj['show_date_id'] == $positionObj['show_date_id'] && $countBidObj['start_city_code'] == $rowsObj['startCityCode']) {
                                // 设置竞拍成功位置
                                $resultTemp['bidAd'] = $countBidObj['count_ad'];
                                // 设置展现位置
                                $resultTemp['viewAd'] = $countBidObj['count_ad'].'/'.$positionObj['ad_product_count'];
                                // 中断循环
                                break;
                            }
                        }
                        // 预设排名为空串
                        $resultTemp['bidRank'] = "";
                        // 预设竞价ID为空串
                        $resultTemp['bidId'] = "";
                        foreach($succBid as $succBidObj) {
                            // 若广告位类型，出发城市名称和竞拍时间ID都匹配，则设置竞拍成功位置
                            if ($succBidObj['ad_key'] == $rowsObj['adKey'] && $succBidObj['search_keyword'] == $rowsObj['searchKeyWord'] && $succBidObj['show_date_id'] == $positionObj['show_date_id'] && $succBidObj['start_city_code'] == $rowsObj['startCityCode']) {
                                // 累加排名
                                $resultTemp['bidRank'] = $resultTemp['bidRank'].$succBidObj['bid_ranking'].',';
                                // 累加竞价ID
                                $resultTemp['bidId'] = $resultTemp['bidId'].$succBidObj['id'].',';
                            }
                        }
                        // 过滤排名和竞价ID
                        if (0 < strlen($resultTemp['bidRank'])) {
                            $resultTemp['bidRank'] = substr($resultTemp['bidRank'], 0, strlen($resultTemp['bidRank']) - 1);
                        }
                        if (0 < strlen($resultTemp['bidId'])) {
                            $resultTemp['bidId'] = substr($resultTemp['bidId'], 0, strlen($resultTemp['bidId']) - 1);
                        }
                    }
                    if (("all" == $param['adQueryKey'] || $positionObj['ad_key'] == $param['adQueryKey']) && ("all" == $param['adQueryKey'] || "class_recommend" == $param['adQueryKey']) && "class_recommend" == $rowsObj['adKey'] && $positionObj['ad_key'] == $rowsObj['adKey'] && "class_recommend" == $adKeyTemp['adKey']) {
                        // 设置分类页
                        // 设置viewName
                        $resultTemp['viewName'] = $rowsObj['startCityName'].'-'.$rowsObj['classificationName'].'-'.$rowsObj['adName'];
                        // 设置位置ID
                        $resultTemp['positionId'] = $positionObj['id'];
                        // 设置date_id
                        $resultTemp['dateId'] = $positionObj['show_date_id'];
                        // 设置日期范围
                        $resultTemp['showDate'] = $positionObj['showDate'];
                        // 设置出发城市code
                        $resultTemp['startCityCode'] = $rowsObj['startCityCode'];
                        // 设置出发城市名称
                        $resultTemp['startCityName'] = $rowsObj['startCityName'];
                        // 设置关键词
                        $resultTemp['searchKeyword'] = "";
                        // 设置广告类型
                        $resultTemp['adKey'] = $rowsObj['adKey'];
                        // 设置广告名称
                        $resultTemp['adName'] = $rowsObj['adName'];
                        // 设置分类ID
                        $resultTemp['webClassId'] = $rowsObj['webId'];
                        // 设置分类名称
                        $resultTemp['classificationName'] = $rowsObj['classificationName'];
                        // 设置总位置数
					$resultTemp['totalAd'] = ConstDictionary::getAdPositionCountLimit($positionObj['ad_product_count'], $rowsObj['startCityCode'], $rowsObj['adKey']);
					// 预设竞拍位置为空串
					$resultTemp['bidAd'] = "";
					// 预设展现位置为空串
					$resultTemp['viewAd'] = "";
					// 循环匹配并设置竞拍位置
					foreach($countBid as $countBidObj) {
						// 若广告位类型，出发城市名称和竞拍时间ID都匹配，则设置竞拍成功位置，展现位置
						if ($countBidObj['ad_key'] == $rowsObj['adKey'] && $countBidObj['web_class'] == $rowsObj['webId'] && $countBidObj['show_date_id'] == $positionObj['show_date_id'] && $countBidObj['start_city_code'] == $rowsObj['startCityCode']) {
							// 设置竞拍成功位置
							$resultTemp['bidAd'] = $countBidObj['count_ad'];
							// 设置展现位置
							$resultTemp['viewAd'] = $countBidObj['count_ad'].'/'.$positionObj['ad_product_count'];
							// 中断循环
							break;
						}
					}
					// 预设排名为空串
					$resultTemp['bidRank'] = "";
					// 预设竞价ID为空串
					$resultTemp['bidId'] = "";
					foreach($succBid as $succBidObj) {
						// 若广告位类型，出发城市名称和竞拍时间ID都匹配，则设置竞拍成功位置
						if ($succBidObj['ad_key'] == $rowsObj['adKey'] && $succBidObj['web_class'] == $rowsObj['webId'] && $succBidObj['show_date_id'] == $positionObj['show_date_id'] && $succBidObj['start_city_code'] == $rowsObj['startCityCode']) {
							// 累加排名
							$resultTemp['bidRank'] = $resultTemp['bidRank'].$succBidObj['bid_ranking'].',';
							// 累加竞价ID
							$resultTemp['bidId'] = $resultTemp['bidId'].$succBidObj['id'].',';
						}
					}
					// 过滤排名和竞价ID
					if (0 < strlen($resultTemp['bidRank'])) {
						$resultTemp['bidRank'] = substr($resultTemp['bidRank'], 0, strlen($resultTemp['bidRank']) - 1);
					}
					if (0 < strlen($resultTemp['bidId'])) {
						$resultTemp['bidId'] = substr($resultTemp['bidId'], 0, strlen($resultTemp['bidId']) - 1);
					}
				}
                }
				// 若临时结果不为空，则插入临时结果
				if (!empty($resultTemp) && is_array($resultTemp)) {
					// 填充结果集
					array_push($result, $resultTemp);
				}
			}
		}
		
		// 返回结果
		return $result;
	}
	
	/**
     * 获取查看和出价页面头
     */
	public function getHeadcommon($param) {
		// 初始化返回结果
		$result = array();
		// 初始化一些其他必须条件结果
		$result['adKey'] = $param['adKey'];
        $result['startCityCode'] = $param['startCityCode'];
		$result['searchKeyword'] = $param['searchKeyword'];
		$result['showDateId'] = $param['showDateId'];
		$result['showDate'] = $param['showDate'];
		$result['succCount'] = 0;
		$result['allPrice'] = 0;
		$result['limitPrice'] = 0;
		$result['allPriceNiu'] = 0;
		$result['limitPriceNiu'] = 0;
		$result['allPriceCoupon'] = 0;
		$result['limitPriceCoupon'] = 0;
		$result['webId'] = 0;
		$result['vasPrice'] = 0;
        // 判断是否需要查询数据库拼接显示名称
        if ("" != $param['viewName']) {
            $result['viewName'] = $param['viewName'];
        } else {
            // 全部广告位数据
            $adKeyInfo = $this->getAdKeyInfo('all',0);

            //兼容旧数据问题 mdf by chenjinlong 20140512
            $adKeyInfo[] = array(
                'adKey' => 'index_chosen',
                'adName' => '首页',
            );

            foreach ($adKeyInfo as $adKeyTemp) {
                if ($adKeyTemp['adKey'] == $param['adKey']) {
                    // 获取临时结果
                    $temp = $this->productDao->queryViewName($param);
                    // 设置显示名称
                    $result['viewName'] = $temp['name'].'-'.$temp['ad_name'];
                }
                if ('search_complex' == $adKeyTemp['adKey'] && 'search_complex' == $param['adKey']) {
                    // 获取搜索页临时结果
                    $temp = $this->productDao->queryViewName($param);
                    // 兼容旧版本，分类设置显示名称
                    if (!empty($param['startCityCode']) && 0 != $param['startCityCode']) {
                        // 新版本
                        $result['viewName'] = $temp['name'].'-'.$param['searchKeyword'].'-'.$temp['ad_name'];
                    } else {
                        // 旧版本
                        $result['viewName'] = $param['searchKeyword'].'-'.$temp['ad_name'];
                    }
                }
                if ('class_recommend' == $adKeyTemp['adKey'] && 'class_recommend' == $param['adKey']) {
                    // 分类初始化接口调用参数
                    if (!empty($param['webId']) && 0 !=$param['webId']) {
                        $iaoParam['webClassId'] = array($param['webId']);
                    } else if (!empty($param['webClassId']) && 0 !=$param['webClassId']) {
                        $iaoParam['webClassId'] = array($param['webClassId']);
                    } else {
                        $iaoParam['webClassId'][0] = 0;
                    }
                    // 调用接口获取分类名称
                    $re = ProductIao::getWebClassInfo($iaoParam);

            //查询上一级分类信息 added by chenjinlong 20140331
            $parentIdInfoQueryParams = array();
            foreach($re['data'] as $iaoObj) {
                $parentIdInfoQueryParams['webClassId'][] = $iaoObj['parentId'];
            }
            $parentWebClassInfoRows = ProductIao::getWebClassInfo($parentIdInfoQueryParams);

			// 如果有数据，则初始化显示名称
			if (!empty($re) && $re['success'] && !empty($re['data'][$iaoParam['webClassId'][0]])) {
				// 获取分类页临时结果
			    $temp = $this->productDao->queryViewName($param);
			    // 设置显示名称
                $parentWebClassStr = str_replace('目的地', '', strval($parentWebClassInfoRows['data'][$re['data'][$iaoParam['webClassId'][0]]['parentId']]['classificationName']));
				$result['viewName'] = $temp['name']. '-' . $parentWebClassStr . '-'.$re['data'][$iaoParam['webClassId'][0]]['classificationName'].'-'.$temp['ad_name'];
			} else {
				// 设置默认显示名称
				$result['viewName'] = '分类页'; 
			}
			// 充值webId参数
			$param['webId'] = $iaoParam['webClassId'][0];
		}
            }
        }
		
		// 查询总位置数
		$tolCount = $this->productDao->queryPositionCount($param);
		// 若有位置数，则设置总位置，否则返回0
		if (!empty($tolCount) && is_array($tolCount)) {
			$result['allowCount'] = ConstDictionary::getAdPositionCountLimit($tolCount['ad_product_count'], $param['startCityCode'], $param['adKey']);
			$result['floorPrice'] = $tolCount['floor_price'];
		} else {
			$result['allowCount'] = 0;
			$result['floorPrice'] = 0;
		}
        // 广告位为品牌专区时只能竞拍一个位置
        if ('brand_zone' == $param['adKey']) {
            $result['allowCount'] = 1;
        }
		// 查询竞价成功的信息
		$succBid = $this->productDao->queryBidTotalInfo($param);
		// 查询附加信息出价
		$vasPrice = $this->productDao->queryVasTotalPrice($param);
		// 设置附加信息出价
		if (!empty($vasPrice) && is_array($vasPrice)) {
			$result['vasPrice'] = $vasPrice['floor_price'];
		}
		// 若有竞价成功的信息，则设置竞价成功的信息，否则返回0
		if (!empty($succBid) && is_array($succBid)) {
			$result['succCount'] = $succBid['count_ad'];
			$result['allPrice'] = intval($succBid['bid_price']);
			$result['limitPrice'] = intval($succBid['max_limit_price']);
			$result['allPriceNiu'] = intval($succBid['bid_price_niu']);
			$result['limitPriceNiu'] = intval($succBid['max_limit_price_niu']);
			$result['allPriceCoupon'] = intval($succBid['bid_price_coupon']);
			$result['limitPriceCoupon'] = intval($succBid['max_limit_price_coupon']);
			$result['showDate'] = $succBid['showDate'];
			$result['webId'] = $succBid['web_class'];
		} else if (empty($param['showDate']) || '' == $param['showDate']) {
			// 查询打包时间
			$dateTime = $this->productDao->queryDateByid($param['showDateId']);
			// 若时间集合不为空，则设置时间
			if (!empty($dateTime) && is_array($dateTime)) {
				// 设置打包时间
				$result['showDate'] = $dateTime['show_date'];
			}
		}
		// 返回结果
		return $result;
	}
	
	/**
	 * 获取编辑列表
	 */
	public function getEditList($param) {
		// 查询编辑列表
		$rows = $this->productDao->queryEditList($param);
		// 查询编辑列表数量
		$count = $this->productDao->queryEditListCount($param);
		// 整合返回结果
		$result['rows'] = $rows;
		$result['count'] = intval($count['list_count']);
		$result['start'] = $param['start'];
		$result['limit'] = $param['limit'];
		// 返回结果
		return $result;
	}
	
	/**
	 * 获取编辑列表个性化
	 */
	public function getEditVas($param) {
		// 初始化最终结果
		$result = array();
		// 查询登录名
		$loginName = $this->productDao->queryLoginNameByBidId($param);
		// 判断是否需要显示个性化
		if (!$param['isFather'] && 0 != strcmp($param['subAgency'], $loginName['login_name'])) {
			// 不需要个性化，返回空
			return $result;
		}
		// 查询附加信息维度
		$vasWd = $this->productDao->queryVasByAdkeyDateid($param);
		// 查询竞价附加信息
		$bidVas = $this->productDao->queryBidVas($param);
		// 循环整合结果
		foreach ($vasWd as $vasWdObj) {
			// 初始化排名数组
			$rankArr = explode(',', $vasWdObj['vas_position']);
			// 初始化排名标记
			$rankFlag = false;
			// 判断该竞价记录的排名是否需要展示个性化
			foreach($rankArr as $rankObj) {
				if ($rankObj == $vasWdObj['ranking']) {
					// 将标记设为true，继续以后的操作
					$rankFlag = true;
					// 中断循环
					break;
				}
			}
			// 若标记为false，则不需要执行后续操作
			if (!$rankFlag) {
				// 继续下一层循环
				continue;
			}
			// 初始化临时结果
			$temp = array();
			// 设置附加信息ID
			$temp['vasId'] = $vasWdObj['id'];
			// 设置竞价附加信息ID为默认值
			$temp['vasBidId'] = -1;
			// 设置附加信息位置信息
			$temp['vasPosition'] = $vasWdObj['vas_position'];
			// 设置底价
			$temp['floorPrice'] = $vasWdObj['floor_price'];
			// 设置附加信息名称
			$temp['vasName'] = $vasWdObj['vas_name'];
			// 设置附加信息key
			$temp['vasKey'] = $vasWdObj['vas_key'];
			// 设置单元底价
			$temp['unitFloorPrice'] = $vasWdObj['unit_floor_price'];
			// 开启二层循环匹配竞价附加信息
			foreach ($bidVas as $bidVasObj) {
				// 若附加信息vas_key匹配，则设置竞价附加信息
				if ($vasWdObj['vas_key'] == $bidVasObj['vas_key']) {
					// 设置竞价附加信息ID
					$temp['vasBidId'] = $bidVasObj['id'];
					// 设置竞价ID
					$temp['bidId'] = $bidVasObj['bid_id'];
					// 设置竞价
					$temp['bidPrice'] = $bidVasObj['bid_price'];
					// 设置竞价标记
					$temp['bidMark'] = $bidVasObj['bid_mark'];
					// 设置财务标记
					$temp['fmisMark'] = $bidVasObj['fmis_mark'];
					// 中断循环
					break;
				}
			}
			// 填充结果
			array_push($result, $temp); 
		}
		// 返回结果
		return $result;
	} 
	
	/**
	 * 保存编辑列表
	 */
	public function saveEditList($param) {
		// 初始化需要操作的数据参数
		$dataParam = $param['rows'];
		// 初始化重复判断数组
		$douArr = array();
		// 判断是否有重复的产品
		foreach($dataParam as $dataParamObj) {
			// 只校验productId不为0的数据
			if (0 != $dataParamObj['productId']) {
				// 填充需要判断的产品ID
				array_push($douArr, $dataParamObj['productId']);
			}
		}
		// 判断是否有产品重复
		if (count($douArr) > count(array_unique($douArr))) {
			// 有产品重复，向前台报错
			return array('success'=>false, 'msg'=>'保存失败，产品不能重复！');
		}
		// 初始化财务标记
		$fmisState = 2;
		// 初始化需要删除的附加信息ID参数
		$delVasId = '';
		// 初始化需要更新财务状态的附加信息ID参数
		$fmisVasId = '';
		// 初始化竞价ID字符串参数
		$bidIdArr = '';
		// 初始化需要冻结的金额
		$amt = 0;
		
		// 获取agencyID
		$agencyId = $this->userManageDao->getVendorInfo($param['accountId']);
		
		// 设置竞价ID array参数
		foreach($dataParam as $dataParamObj) {
			// 拼接竞价ID参数
			$bidIdArr = $bidIdArr.$dataParamObj['bidId'].',';
		}
		// 过滤竞价ID参数
		$param['bidIdArr'] = substr($bidIdArr, 0, strlen($bidIdArr) - 1);
		
		// 查询该广告位的推广开始时间和推广结束时间
		$showDate = $this->productDao->queryStartEndDateByBidId($param['bidIdArr']);
		// 初始化当前日期
		$nowDay = strval(date('Y-m-d',time()));
		// 判断当天是否是推广结束日期，若是，则返回错误，提示今天已是推广时间段的最后一天，不可替换产品，否则，继续保存产品
		if ($nowDay === strval($showDate['show_end_date'])) {
			// 当天是推广结束日期，返回错误
			return array('success'=>false, 'msg'=>'今天已是推广时间段的最后一天，不可替换产品！');
		}
		
		// 初始化子账户个性化扣费集合
		$subVasFreeze = array();
		// 查询上一批次已经冻结的金额
		$oldAmt = $this->productDao->queryVasFmis($param);
		// 查询上一批次已经冻结的金额明细
		$oldAmtDetail = $this->productDao->queryVasFmisDetail($param);
		// 删除竞价附加信息表
		$this->productDao->deleteBidVasInfo($param);
		try {
			// 展开最外层循环，对每条数据进行增删改操作
			foreach($dataParam as $dataParamObj) {
				// 设置accountId参数
				$dataParamObj['accountId'] = $param['accountId'];
				// 设置loginName参数
				$dataParamObj['subAgency'] = $param['subAgency'];
				// 设置viewName参数
				$dataParamObj['viewName'] = $param['viewName'];

                // 品牌专区时产品编号插入供应商编号，产品类型插入500
                if('brand_zone' == $dataParamObj['adKey']) {
                    $manageMod = new UserManageMod();
                    $accountInfo = $manageMod->read(array('id'=>$dataParamObj['accountId']));
                    $dataParamObj['productId'] = $accountInfo['vendorId'];
                    $dataParamObj['productType'] = '500';
                }

				// 插入日志
                if ("brand_zone" != $dataParamObj['adKey']) {
                    $this->productDao->insertReplaceLog($dataParamObj);
                }
				// 更新竞价表替换的产品
				// $this->productDao->updateBidBidProduct($dataParamObj);
				// 删除竞价内容表替换的产品
				// $this->productDao->updateBidBidContent($dataParamObj);
				// 插入竞价内容表替换的产品
				// $this->productDao->insertBidBidContent($dataParamObj);
				// 获取该产品在竞价产品表的数量，判断是否有必要将该产品插入竞价产品表
				// $count = $this->productDao->queryBidProductCount($dataParamObj);
				// 更新竞价相关表，并获取该产品在竞价产品表的数量，判断是否有必要将该产品插入竞价产品表
				$count = $this->productDao->saveBidBidAndQueryProCount($dataParamObj);
				// 若竞价产品表没有改产品，则添加该产品
				if (0 == $count['count_pro']) {
					/*$insertBidPrdParams = array(
        	        	'account_id' => $dataParamObj['accountId'],
            	    	'product_id' => $dataParamObj['productId'],
                		'vendor_id' => $agencyId['vendorId'],
                		'product_type' => $dataParamObj['productType'],
	            	);*/

                    $productIdArr = array(
                        $dataParamObj['productId'],
                    );
                    $productList[$dataParamObj['productId']] = array(
                        'productName' => $dataParamObj['productName'],
                        'agencyProductName' => '',
                        'productType' => $dataParamObj['productType'],
                        'productId' => $dataParamObj['productId'],
                        'checkerFlag' => 2,
                        'category' => array(),
                    );
    	    		// 插入竞价产品信息
                    $this->createBidProduct($productIdArr, $dataParamObj['accountId'], $productList);
        	    	//$this->insertBidProductRecords($insertBidPrdParams);
				}
				// 拼接需要删除的附加信息ID参数
				if (!empty($dataParamObj['delVasId']) && "" != $dataParamObj['delVasId']) {
					$delVasId = $delVasId.$dataParamObj['delVasId'].',';
				}				
				// 判断是否需要插入附加信息
				if (!empty($dataParamObj['vas']) && is_array($dataParamObj['vas'])) {
					// 初始化临时冻结金额变量
					$tempFreVas = 0;
					// 循环插入附加信息
					foreach ($dataParamObj['vas'] as $vasObj) {
						// 累加冻结金额
						$amt = $amt + $vasObj['price'];
						$tempFreVas = $tempFreVas + $vasObj['price'];
						// 初始化共通参数
						$vasObj['accountId'] = $dataParamObj['accountId'];
						$vasObj['bidId'] = $dataParamObj['bidId'];
						$vasObj['bidMark'] = 2;
						$vasObj['fmisMark'] = 2;
						$vasObj['subAgency'] = $dataParamObj['subAgency'];
						// 执行插入操作
						$vasTempId = $this->productDao->insertBidVasInfo($vasObj);
						// 累加财务状态更新ID参数
						$fmisVasId = $fmisVasId.$vasTempId.',';
					}
					// 初始化子账户冻结参数变量
					$subFreVaas['accountId'] = $dataParamObj['accountId'];
					$subFreVaas['bidId'] = $dataParamObj['bidId'];
					$subFreVaas['price'] = $tempFreVas;
					$subFreVaas['ranking'] = $dataParamObj['ranking'];
					// 添加子账户冻结参数
					array_push($subVasFreeze, $subFreVaas);
				}
			}
			// 调用财务接口冻结金额
			$res = FinanceIao::bidCutFinance($agencyId['vendorId'], $amt, $oldAmt['bid_price']);
			// 判断是否需要冻结子供应商金额
			if ($res['success']) {
				// 冻结供应商子账户个性化金额
				$this->userManageDao->freezeSubAgencyVas($subVasFreeze, $param, $amt, $oldAmt['bid_price'], $oldAmtDetail);
			}
			// 过滤需要删除的附加信息ID参数并删除相关附加信息
			if (0 < strlen($delVasId)) {
				// 过滤参数
				$delVasId = substr($delVasId, 0, strlen($delVasId) - 1);
				// 删除附加信息
				$this->productDao->deleteBidBidVas($delVasId);
				// 判断是否冻结成功
				if ($res['success']) {
					// 先退款
					$this->productDao->updateVasFmisState($delVasId, -1);
					// 再冻结
					if (0 < strlen($fmisVasId)) {
						// 过滤参数
						$fmisVasId = substr($fmisVasId, 0, strlen($fmisVasId) - 1);
						// 更新附加信息财务参数
						$this->productDao->updateVasFmisState($fmisVasId, 0);
					}
				} else {
					// 删除竞价附加信息
					$this->productDao->deleteVasFmisState($fmisVasId);
					// 扣款失败
					return array('success'=>false, 'msg'=>'财务扣款失败！');
				}
			} else if ($res['success']) {
					// 直接冻结
					if (0 < strlen($fmisVasId)) {
						// 过滤参数
						$fmisVasId = substr($fmisVasId, 0, strlen($fmisVasId) - 1);
						// 更新附加信息财务参数
						$this->productDao->updateVasFmisState($fmisVasId, 0);
					}
			} else {
				// 删除竞价附加信息
				$this->productDao->deleteVasFmisState($fmisVasId);
				// 扣款失败
				return array('success'=>false, 'msg'=>'财务扣款失败！');
			}
			// 判断是否是推广中产品替换
			if (strtotime(date('Y-m-d H:i:s', time())) >= strtotime($showDate['show_start_date'].' 00:00:00') && strtotime(date('Y-m-d H:i:s', time())) < strtotime($showDate['show_end_date'].' 24:00:00')) {
				// 查询show_id
				$showArr = $this->productDao->queryEditShow($param['bidIdArr']);
				// 初始化需要进行事务操作的数据集合
				$transArr = array();
				// 初始化需要删除的数据集合
				$delArr = array();
				// 初始化需要更新show表的数据集合
				$showUpdArr = array();
				// 初始化需要插入content表的数据集合
				$contentAddArr = array();
				// 整合需要更新的数据
				foreach ($dataParam as $dataParamObj) {
					foreach ($showArr as $showArrObj) {
						// 若bid_id匹配，则对比产品类型和产品ID
						if ($dataParamObj['bidId'] == $showArrObj['bid_id'] && ($dataParamObj['productId'] != $showArrObj['product_id'] || $dataParamObj['productType'] != $showArrObj['product_type'])) {
							// 添加需要更新的数据
							array_push($delArr, $showArrObj['show_id']);
							// 添加需要更新show表的数据
							array_push($showUpdArr, array('bid_id' => $showArrObj['bid_id'], 'product_id' => $dataParamObj['productId'], 'product_type' => $dataParamObj['productType']));
							// 添加需要插入content表的数据
							array_push($contentAddArr, array('account_id' => $param['accountId'], 'content_id' => $dataParamObj['productId'], 'content_type' => $dataParamObj['productType'], 'show_id' => $showArrObj['show_id']));
							// 中断里层循环
							break;
						}
					}
				}
				
				// 判断是否有需要更新的数据，若没有，则不操作数据库
				if (!empty($delArr) && is_array($delArr)) {
					// 整合show_id字符串
					$showIDArr = implode(',', $delArr);
					// 删除，更新，添加推广数据
					$this->productDao->saveEditShowContent($showIDArr, $showUpdArr, $contentAddArr);
				}
				
				// 初始化需要推广的数据结果集
				$pushProductArr = array();
				/*// 初始化当前日期的后一天
				$tomorrow = date('Y-m-d', mktime(0, 0, 0, date('m', time()), date('d', time()) + 1, date('Y', time())));*/
                // 初始化当前日期 mdf by chenjinlong 20140520
                $tomorrow = date('Y-m-d');
				// 循环生成需要推广的数据
				foreach($dataParam as $dataParamObj) {
					// 初始化开始日期
					$i = $tomorrow;
					// 循环添加每一天的数据
					while ($i <= $showDate['show_end_date']) {
						// 初始化临时推送数据对象
						$tempPushObj = array();
						// 设置竞价日期
						$tempPushObj['showDate'] = $i;
						// 设置产品ID
						$tempPushObj['productId'] = $dataParamObj['productId'];
						// 设置产品类型
						$tempPushObj['productType'] = $dataParamObj['productType'];
						// 设置广告位类型
						$tempPushObj['adKey'] = $dataParamObj['adKey'];
						// 设置
						$tempPushObj['catType'] = 0;
						// 设置分类编号
						$tempPushObj['webClass'] = $dataParamObj['webId'];
						// 设置出发城市编码
						$tempPushObj['startCityCode'] = $dataParamObj['startCityCode'];
						// 设置排名
						$tempPushObj['ranking'] = $dataParamObj['ranking'];
						// 设置关键词
						$tempPushObj['search_keyword'] = $dataParamObj['searchKeyword'];
						// 将临时结果添加进需要推广的数据结果集
						array_push($pushProductArr, $tempPushObj);
						// 将需要相加的时间字符串转换为时间
						$bidDate = strtotime($i);
						// 将时间加1天
						$i = date('Y-m-d', mktime(0, 0, 0, date('m', $bidDate), date('d', $bidDate) + 1, date('Y', $bidDate)));
					}
				}
				// 初始化网站接口调用对象
				$releaseIao = new ReleaseIao;
				// 调用接口
				$reIaoRe = $releaseIao->releaseToChannelAndClsRoutesNew($pushProductArr);
				// 判断接口调用是否成功
				if (empty($reIaoRe) || !$reIaoRe['success']) {
					return array('success'=>false, 'msg'=>'数据推送至网站失败！');
				}
			}
		} catch (Exception $e) {
            Yii::log($e);
            // 发生错误，返回false
            return array('success'=>false, 'msg'=>'数据错误！');
        }
        
        // 操作成功，返回true
        return array('success'=>true, 'msg'=>'保存成功！');
	} 
	
	/**
	 * 获取分类页广告位
	 */
	public function getClassad($param) {
		// 初始化返回结果
		$result = array();
		// 初始化接口调用参数
		$iaoParam = array();
		// 设置明细参数
		$iaoParam['productId'] = $param['productId'];
		$iaoParam['productType'] = $param['productType'];
		$iaoParam['startCityCode'] = $param['startCityCode'];
		$iaoParam['classificationName'] = $param['classificationName'];
		$iaoParam['start'] = $param['start'];
		$iaoParam['limit'] = $param['limit'];
		$iaoParam['classificationDepth'] = array(2, 3);
		// 调用接口，获取分类页数据
		$iaoRe = ProductIao::getClassificationList($iaoParam);
		// 判断是否有分类页数据
		if (!empty($iaoRe) && $iaoRe['success'] && empty($iaoRe['data']['rows'])) {
			// 接口调用成功，但没数据
			$result['data'] = array();
			$result['success'] = true;
			$result['msg'] = '没有相关分类页数据！';
			// 返回结果
			return $result;
		} else if (empty($iaoRe) || !$iaoRe['success']) {
			// 接口调用失败
			$result['data'] = array();
			$result['success'] = false;
			$result['msg'] = '接口调用失败！';
			// 返回结果
			return $result;
		}
		// 调用接口成功，并且有数据，查询城市名称
		$cityParam['code'] = $param['startCityCode'];
		$cityName = $this->productDao->queryStartCity($cityParam);

        //查询上一级分类信息
        $parentIdInfoQueryParams = array();
        foreach($iaoRe['data']['rows'] as $iaoObj) {
            $parentIdInfoQueryParams['webClassId'][] = $iaoObj['parentId'];
        }
        $parentWebClassInfoRows = ProductIao::getWebClassInfo($parentIdInfoQueryParams);

		// 初始化rows结果集
		$rows = array();
		// 整合结果
		foreach($iaoRe['data']['rows'] as $iaoObj) {
			// 初始化临时结果对象
			$tempObj = array();
			// 设置父类ID
			$tempObj['parentId'] = $iaoObj['parentId'];
			// 设置分类编号
			$tempObj['webId'] = $iaoObj['id'];
			// 设置产品ID
			$tempObj['productId'] = $param['productId'];
			// 设置产品类型
			$tempObj['productType'] = $param['productType'];
			// 设置分类名称
            $parentWebClassStr = str_replace('目的地', '', strval($parentWebClassInfoRows['data'][$iaoObj['parentId']]['classificationName']));
			$tempObj['classificationName'] = $parentWebClassStr . '-' .$iaoObj['classificationName'];
			// 设置分类层级
			$tempObj['classificationDepth'] = $iaoObj['classificationDepth'];
			// 设置出发城市编码
			$tempObj['startCityCode'] = $param['startCityCode'];
			// 设置出发城市名称
			$tempObj['startCityName'] = $cityName[0]['name'];
			// 设置显示名称
			$tempObj['viewName'] = $cityName[0]['name'] . '-' . $parentWebClassStr . '-' . $iaoObj['classificationName'];
			// 设置目的地ID
			$tempObj['destinationId'] = $iaoObj['destinationId'];
			// 设置目的地类型
			$tempObj['destinationType'] = $iaoObj['destinationType'];
			// 填充结果
			array_push($rows, $tempObj);
		}
		
		// 整合结果
		$result['data']['rows'] = $rows;
		$result['data']['count'] = $iaoRe['data']['count'];
		$result['success'] = true;
		$result['msg'] = '查询数据成功！';
		
		// 返回结果
		return $result;
	}
	
	/**
	 * 获取出发城市
	 */
	public function getStartCity($param) {
		// 查询出发城市
		$result['rows'] = $this->productDao->queryStartCity($param);
		// 返回结果
		return $result;
	}
	
	/**
	 * 查询出哪些打包计划在广告位置表里没有对应的数据
	 */
	public function queryNotSyncShowDateIds(){
		return $this->productDao->queryNotSyncShowDateIds();
	}
	
	/**
	 * 添加广告位置信息
	 */
	public function addAdPosition($data,$id){
		return $this->productDao->addAdPosition($data,$id);
	}
	
	/**
     * 招客宝3.0获取相似产品替换
     */
	public function getSimilarProduct($param) {
		// 初始化返回结果
		$result = array();
		// 获取agencyId
		$manage = new UserManageMod;
        $params = array('id' => $param['accountId']);
        $user = $manage->read($params);
        // 初始化审核标记参数
        $checkFlag = array();
        if (2 == $param['checkerFlag']) {
        	array_push($checkFlag, $param['checkerFlag']);
        }
        // 同质化首页和专题页
        if ('special_subject' == $param['adKey']) {
        	$param['adKey'] = 'index_chosen';
        }
		// 初始化接口调用参数
		$iaoParam['productId'] = $param['productId'];
		$iaoParam['productType'] = $param['productType'];
		$iaoParam['startCityCode'] = $param['startCityCode'];
		$iaoParam['webClassId'] = $param['webId'];
		$iaoParam['adKey'] = $param['adKey'];
		$iaoParam['productNameType'] = $param['searchType'];
		$iaoParam['productNameKeyword'] = $param['searchKey']; 
		$iaoParam['checkFlag'] = $checkFlag;
		$iaoParam['start'] = $param['start'];
		$iaoParam['limit'] = $param['limit'];
		$iaoParam['vendorId'] = $user['vendorId'];
        // 如果为包含index_chosen的首页广告位时，获取其相似产品种类
        if (strpos($param['adKey'],'index_chosen') !== false || strpos($param['adKey'],'channel_chosen') !== false) {
            $adCategory = $this->packageDateDao->getAdCategory($param);
            if ($adCategory) {
            	//categoryId和classBrandTypes 都为int类型的数组
                $iaoParam['categoryId'] = json_decode(str_replace("\"","",$adCategory['categoryId']),true);
                $iaoParam['classBrandTypes'] = json_decode(str_replace("\"","",$adCategory['classBrandTypes']),true);
                $iaoParam['catType'] = json_decode($adCategory['catType'],true);
            }
        }
        // 如果为分类页，则设置catType
        if ('class_recommend' == $param['adKey']) {
        	$rorParam = array();
			$rorParam['classBrandTypes'] = array(ConstDictionary::$bbRorProductMapping[$param['productType']]);
			$rorParam['startCityCode'] = $param['startCityCode'];
			$rorParam['categoryId'] = $param['webId'];
			$clsrecomResult = $this->_rorProductIao->queryWebCategoryList($rorParam);
			$cateValues = $clsrecomResult['filters'][0]['cateValues'];
			if (!empty($clsrecomResult) && !empty($cateValues) && is_array($cateValues)) {
				$catId = 0;
				// 查找父类ID
				foreach($cateValues as $cateValuesObj) {
					$fchildren = $cateValuesObj['children'];
					foreach($fchildren as $fchildrenObj) {
						if ($fchildrenObj['id'] == $param['webId']) {
							$catId = $cateValuesObj['id'];
							break;
						} else {
							$schildren = $fchildrenObj['children'];
							foreach($schildren as $schildrenObj) {
								if ($schildrenObj['id'] == $param['webId']) {
									$catId = $cateValuesObj['id'];
									break;
								}
							}
						}
					}
				}
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
				$iaoParam['catType'] = $clsCatType;
			} else {
				// 接口调用失败
				$result['data'] = array();
				$result['success'] = false;
				$result['msg'] = '接口调用失败！';
				// 返回结果
				return $result;
			}
        }
        
        // 调用接口，获取相似产品
        $iaoRe = array();
        // 计算categoryId数据量，若大于200则分批查询搜索接口
        $categoryIdCount = sizeof($iaoParam['categoryId']);
        if ($categoryIdCount > 200) {
            // 临时变量
            $start = $iaoParam['start'];
            $limit = $iaoParam['limit'];
            $categoryId = $iaoParam['categoryId'];
            // 从缓存获取数据
            $memKey = 'querySimilarProductList_' . md5(json_encode(array('adKey' => $iaoParam['adKey'],'startCityCode' => $iaoParam['startCityCode'])));
            $resultRows = Yii::app()->memcache->get($memKey);
            if (!empty($resultRows['data']['rows'])) {
                $iaoRe = $resultRows;
            } else {
                // 使用循环每次200条来获取数据
                for ($i = 0; $i < $categoryIdCount; $i = $i + 200) {
                    // 调用搜索接口获取相似产品
                    $iaoParam['start'] = 0;
                    $iaoParam['limit'] = 200;
                    $iaoParam['categoryId'] = array_slice($categoryId, $i, 200);
                    $iaoReTemp = $this->_rorProductIao->querySimilarProductList($iaoParam);
                    if (!empty($iaoReTemp) && $iaoReTemp['success'] && !empty($iaoReTemp['data']['rows'])) {
                        foreach ($iaoReTemp['data']['rows'] as $temp) {
                            $iaoRe['data']['rows'][] = $temp;
                        }
                        $iaoRe['data']['count'] += $iaoReTemp['data']['count'];
                    }
                }
                // 数据缓存1小时
                Yii::app()->memcache->set($memKey, $iaoRe, 3600);
            }
            // 整理返回值
            $iaoRe['success'] = true;
            if ($iaoRe['data']['count'] > 0 && !empty($iaoRe['data']['rows'])) {
                // 用start，limit对数据进行拆分
                $iaoRe['data']['rows'] = array_slice($iaoRe['data']['rows'],$start,$limit);
            }
        } else {
            // 直接调用接口，获取相似产品
            $iaoRe = $this->_rorProductIao->querySimilarProductList($iaoParam);
        }
		// 判断是否有相似产品
		if (!empty($iaoRe) && $iaoRe['success'] && empty($iaoRe['data']['rows'])) {
			// 接口调用成功，但没数据
			$result['data'] = array();
			$result['success'] = true;
			$result['msg'] = '没有相关分类页数据！';
			// 返回结果
			return $result;
		} else if (empty($iaoRe) || !$iaoRe['success']) {
			// 接口调用失败
			$result['data'] = array();
			$result['success'] = false;
			$result['msg'] = '接口调用失败！';
			// 返回结果
			return $result;
		}
		
		// 初始化rows结果
		$rows = array();
		// 整合rows结果
		foreach($iaoRe['data']['rows'] as $iaoObj) {
			// 初始化临时结果对象
			$tempObj = array();
			// 设置分类编号
			$tempObj['webId'] = $param['webId'];
			// 设置产品ID
			$tempObj['productId'] = $iaoObj['productId'];
			// 设置产品类型
			$tempObj['productType'] = $iaoObj['productType'];
			// 设置分类名称
			$tempObj['productName'] = $iaoObj['productName'];
			// 设置分类层级
			$tempObj['agencyProductName'] = $iaoObj['agencyProductName'];
			// 设置出发城市编码
			$tempObj['startCityCode'] = $iaoParam['startCityCode'];
            // 查询出发城市
            $cityParam['code'] = $iaoParam['startCityCode'];
            $cityRe = $this->productDao->queryStartCity($cityParam);
            // 设置出发城市
            $tempObj['startCityName'] = $cityRe[0]['name'];
			// 设置出发城市名称
			$tempObj['checkerFlag'] = $iaoObj['checkFlag'];
			// 设置显示名称
			$tempObj['adKey'] = $param['adKey'];
			// 设置途牛价
			$tempObj['tuniuPrice'] = $iaoObj['tuniuPrice'];
			// 如果产品类型为门票则设置门票产品编号
			if ('33' == $iaoObj['productType']) {
				// 设置门票产品编号
				$tempObj['ticketProductId'] = $iaoObj['ticketProductId'];
			} else {
				// 设置门票产品编号为0
				$tempObj['ticketProductId'] = 0;
			}
			// 填充结果
			array_push($rows, $tempObj);
		}
	
		// 整合最终结果
		$result['data']['rows'] = $rows;
		$result['data']['count'] = $iaoRe['data']['count'];
		$result['success'] = true;
		$result['msg'] = '查询成功！';		
		
		// 返回结果
		return $result;
	}
	
	/**
	 * 过滤出当前支持的广告位类型
	 */
	public function getAvailableType(){
		return $this->productDao->getAvailableType();
	}
	
	/**
	 * 获得供应商日志
	 */
	public function getAgencyLog($param) {
		// 预初始化返回结果
    	$result = array();
    	try {
    		// 初始化竞价ID数组参数
    		$param['bidIdArr'] = implode(',', $param['bidId']);
    		// 查询数据库
    		$result['data']['rows'] = $this->productDao->queryAgencyLog($param);
    		$result['data']['count'] = $this->productDao->queryAgencyLogCount($param);
    		// 整合数据
    		foreach ($result['data']['rows'] as $k=>$rowsObj) {
    			// 解析临时JSON变量
    			$json = json_decode($rowsObj['content'], true);
    			// 重新整合日志显示内容
    			$result['data']['rows'][$k]['content'] = $json['content'];
    			// 若为扣款日志和解冻日志，则需要做特殊处理
    			if (5 == $rowsObj['type'] || (7 == $rowsObj['type'] && !empty($json['flag']) && 1 == $json['flag'])) {
    				// 初始化广告位名称查询参数
        			$adParam = array(
			        	'showDateId' => 0,
						'adKey' => $json['ad_key'],
			            'startCityCode' => $json['start_city_code'],
						'searchKeyword' => $json['search_keyword'],
						'positionId' => 0,
						'webId' => 0,
						'webClassId' => $json['web_class'],
						'viewName' => '',
						'showDate' => '',
						'account_id' => $rowsObj['accountId']
			        );
			        // 查询广告位名称
			        $adName = $this->getHeadcommon($adParam);
			        // 替换名称
			        $result['data']['rows'][$k]['content'] = str_replace("@@@", $adName['viewName'], $result['data']['rows'][$k]['content']);
    			}
    		}
    		// 设置成功编码
    		$result['errorCode']=230000;
    		// 设置成功状态
    		$result['success'] = true;
    		// 设置成功信息
    		$result['msg'] = '查询成功！';
    	} catch(Exception $e) {
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
	 * 获取招客宝推广成功的产品
	 * 
	 * @author wenrui 2014-03-21
	 */
	public function getShowProduct($data){
		// 推广位置
		$adKey = $data["adKey"];
		$condition = " AND c.ad_key = '$adKey'".$this->startCityCondition($data);
		$dateCondition = "";
        if ('class_recommend' == $adKey) {
            $condition .= $this->webClassCondition($data);
        }
        if ('search_complex' == $adKey) {
            $condition .= $this->searchKeywordCondition($data);
        }
		// 如果为传入推广时间，默认默认获取当前日期的推广数据
		if (!empty($data["showDate"])) {
			// 日期参数转化为"2014-03-21"的样式
			$dateCondition = date('Y-m-d', strtotime($data["showDate"]));
		} else {
			$dateCondition = date('Y-m-d');
		}
		return $this->productDao->getShowProduct($condition,$dateCondition);
	}
	
	/**
	 * 出发城市过滤条件
	 * 
	 * @author wenrui 2014-03-21
	 */
	public function startCityCondition($data){
		if(!empty($data["startCityCode"])){
			return " AND c.start_city_code = " . $data["startCityCode"];
		}
		return "";
	}
	
	/**
	 * 分类id过滤条件
	 * 
	 * @author wenrui 2014-03-21
	 */
	public function webClassCondition($data){
		if(!empty($data["webClass"])){
			return " AND c.web_class = " . $data["webClass"];
		}
		return "";
	}
	
	/**
	 * 搜索条件过滤条件
	 * 
	 * @author wenrui 2014-03-21
	 */
	public function searchKeywordCondition($data){
		if(!empty($data["searchKeyword"])){
			return " AND c.search_keyword = '" . $data["searchKeyword"] . "'";
		}
		return "";
	}
	
	/**
	 * 获得海格广告位产品记录
	 */
	public function getProductHis($param) {
		// 初始化返回结果
		$result = array();
		$data['rows'] = array();
		
		try {
			// 查询数据
			$dataDb = $this->productDao->queryProductHis($param);
			// 查询数量
			$dataCount = $this->productDao->queryProductHisCount($param);
			
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据，否则抛异常，进行错误处理
			if (!empty($dataDb) && is_array($dataDb)) {
				
				// 循环整合数据
				foreach ($dataDb as $dataObj) {
					// 设置操作内容
					if (0  == $dataObj['contentType']) {
						// 设置空内容
						$dataObj['content'] = '--';
					} else {
						// 设置前台显示的产品类型和ID
						$dataObj['content'] = ConstDictionary::getBbProductTypeNameByKey($dataObj['contentType']).'：'.$dataObj['contentId'];
					}
					// 设置操作人
					$dataObj['uidName'] = $this -> getOperationName($dataDb[0]['addUid']);
					// 填充结果
					array_push($data['rows'], $dataObj);
				}
				
			}
			
			// 插入最后一条数据的标识，便于前台控制
			if ($param['start'] + $param['limit'] >= $dataCount) {
				$data['rows'][count($data['rows']) - 1]['orange'] = true;
			}
			
			// 整合最终返回的正确结果
			$data['count'] = $dataCount;
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
	 * 获取操作人名称
	 */
	public function getOperationName($uid) {
		// uid大于0为供应商，否则为系统
		if (0 < $uid) {
			// 供应商
			return '供应商';
		} else {
			// 系统
			return '系统';
		}
	}
	
	/**
	 * 获取赠币使用占比
	 * 
	 * @author wenrui 2014-04-10
	 */
	public function getCouponUsePercent($param){
        // 初始化结果变量
        $result = '';
        // 获取供应商赠币配置列表
        $couponConfigInfo = $this->_configManageMod->getCouponConfigList(array());
        if ($couponConfigInfo['rows'] && is_array($couponConfigInfo['rows'])) {
            foreach ($couponConfigInfo['rows'] as $tempConfigInfo) {
                if ($param['accountId'] == $tempConfigInfo['accountId']) {
                    $result = $tempConfigInfo['couponUsePercent'];
                    break;
                }
            }
        }
        // 如果获取到配置的供应商则直接返回
        if (!empty($result)) {
            return $result['couponUsePercent'];
        // 否则再去进行查询
        } else {
            $result = $this->productDao->getCouponUsePercent($param);
            if(!empty($result)){
                return $result['couponUsePercent'];
            }else{
                return 0.00;
            }
        }
	}
	
	/**
	 * 获取当前也推广的广告位
	 * 
	 * @author wenrui 2014-04-25
	 * 
	 */
	public function getReleaseAdKeyInfo(){
		$adKeyArr = array();
		$result = $this->productDao->getReleaseAdKeyInfo();
		if(!empty($result)){
			foreach($result as $value){
				array_push($adKeyArr,$value['ad_key']);
			}
		}
		return $adKeyArr;
	}

    /*
     * 根据adKey获取广告位配置信息
     */
    public function getAdKeyInfo($adKey, $startCityCode) {
    	$memKey = "";
    	if (empty($startCityCode)) {
        $memKey = md5('ProductMod::getAdKeyInfo_' . $adKey);
    	} else {
    		$memKey = md5('ProductMod::getAdKeyInfo_' . $adKey.'_'.$startCityCode);
    	}
        
        $resultRows = Yii::app()->memcache->get($memKey);
        if(!empty($resultRows)){
            return $resultRows;
        }

        $result = $this->productDao->queryAdKeyInfo($adKey, $startCityCode);
        $data = array();
        if (!empty($result)) {
            foreach ($result as $a) {
                $temp = array();
                $temp['adKey'] = $a['ad_key'];
                $temp['adName'] = $a['ad_name'];
                $data[] = $temp;
            }
        }
        //缓存6h
        Yii::app()->memcache->set($memKey, $data, 21600);

        return $data;
    }

    /*
     * 根据adKey获取广告位配置信息,包括被删除的广告位
     */
    public function getAdKeyAllInfo($adKey, $startCityCode) {
        $memKey = "";
        if (empty($startCityCode)) {
            $memKey = md5('ProductMod::getAdKeyAllInfo_' . $adKey);
        } else {
            $memKey = md5('ProductMod::getAdKeyAllInfo_' . $adKey.'_'.$startCityCode);
        }

        $resultRows = Yii::app()->memcache->get($memKey);
        if(!empty($resultRows)){
            return $resultRows;
        }

        $result = $this->productDao->queryAdKeyAllInfo($adKey, $startCityCode);
        $data = array();
        if (!empty($result)) {
            foreach ($result as $a) {
                $temp = array();
                $temp['adKey'] = $a['ad_key'];
                $temp['adName'] = $a['ad_name'];
                $data[] = $temp;
            }
        }
        //缓存6h
        Yii::app()->memcache->set($memKey, $data, 21600);

        return $data;
    }

    /*
     * 查询当前可参与竞拍的首页的广告位
     */
    public function getIndexAdKey($param) {
        $param = array(
            'accountId' => intval($param['accountId']),
            'startCityCode' => intval($param['startCityCode']),
            'start' => intval($param['start']),
            'limit' => intval($param['limit']),
        );
        $memKey = 'getIndexAdKey_' . md5(json_encode($param));
        $resultRows = Yii::app()->memcache->get($memKey);
        if(!empty($resultRows)){
            return $resultRows;
        }

        $result = $this->productDao->queryIndexAdKey($param);
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
            // 过滤掉当前供应商没有产品的首页广告位
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
        Yii::app()->memcache->set($memKey, $rows, 900);

        return $rows;
    }

    /**
     * 保存/编辑包场记录
     */
    public function saveBuyout($param) {
    	// 初始化返回结果
    	$result = array();
    	$result['flag'] = Symbol::CONS_TRUE;
    	$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231501)];
    	
    	// 添加方法开始日志
    	if ($this->_bbLog->isInfo()) {
            $this->_bbLog->logMethod($param, $param['nickname'].$param['uid']."修改了包场数据", __METHOD__.'::'.__LINE__, chr(50));
        }
    	
    	// 分类处理，新增和编辑
    	if (Sundry::INSERT == $param['saveFlag']) {
    		// 查询城市维度
    		$cityArr = $this->productDao->queryIndexKeyByCity(implode(chr(44), $param['startCityCodes']), $param['adKeyType'], $param['webClass']);
    		
    		// 获取adKey维度
    		$adKeyWd = array();
    		foreach($cityArr as $cityArrObj) {
    			array_push($adKeyWd, chr(39).$cityArrObj['adKey'].chr(39));
    		}
    		$adKeyWd = array_unique($adKeyWd);
    		
    		// 获取所有排名
    		$rows = $param['rows'];
    		$rankings = array();
    		$products = array();
    		foreach($rows as $rowsObj) {
    			array_push($rankings, intval($rowsObj['ranking']));
    			array_push($products, intval($rowsObj['productId']));
    		}
    		$rankings = array_unique($rankings);
    		
    		// 校验保存的产品是否重复
    		$productsCheck = array_unique($products);
    		if (count($productsCheck) < count($products)) {
    			$result['flag'] = Symbol::CONS_FALSE;
	    		$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231615)];
	    		return $result;
    		}
    		
    		// 删除旧的包场
    		$delParam['accountId'] = $param['accountId'];
    		$delParam['startCityCodes'] = implode(chr(44), $param['startCityCodes']);
    		$delParam['webClasses'] = $param['webClass'];
    		$delParam['adKey'] = implode(chr(44), $adKeyWd);
    		$delParam['showDateId'] = $param['showDateId'];
    		$delParam['rankings'] = implode(chr(44), $rankings);;
    		$this->productDao->deleteBuyoutPosConfig($delParam);
    		
    		// 查询位置数量的限制
    		$limitParam = array();
    		$limitParam['adKeyType'] = $param['adKeyType'];
    		$limitParam['startCityCodes'] = $delParam['startCityCodes'];
    		$limitParam['webClass'] = $param['webClass'];
    		$limitDb = $this->productDao->queryBuyoutPosLimit($limitParam);
    		$limitKv = array();
    		foreach ($limitDb as $limitDbObj) {
    			$limitKv[$limitDbObj['start_city_code'].chr(45).$limitDbObj['web_class']] = $limitDbObj['ad_product_count'];
    		}
    		
    		
    		// 查询包场重复的产品
    		$douParam['startCityCodes'] = $delParam['startCityCodes'];
    		$douParam['webClass'] = $param['webClass'];
    		$douParam['adKeys'] = $delParam['adKey'];
    		$douParam['showDateId'] = $param['showDateId'];
    		$douProduct = $this->productDao->queryBuyoutDouProduct($douParam);
    		$douProductCheck = array();
    		foreach ($douProduct as $douProductObj) {
    			$douProductCheck[$douProductObj['ad_key'].chr(45).$douProductObj['web_class'].chr(45).$douProductObj['start_city_code']][] = intval($douProductObj['product_id']);
    		}
    		
    		unset($limitParam);
    		unset($delParam);
    		unset($douParam);
    		unset($douProductObj);
    		
    		// 匹配数据集合
    		$sqlData = array();
    		foreach ($cityArr as $cityArrObj) {
    			foreach ($rows as $rowsObj) {
    				if (in_array(intval($rowsObj['productId']), $douProductCheck[$cityArrObj['adKey'].chr(45).$param['webClass'].chr(45).$cityArrObj['startCityCode']])) {
    					$result['flag'] = Symbol::CONS_FALSE;
	    				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231615)];
	    				return $result;
    				} else if (intval($rowsObj['ranking']) <= intval($limitKv[$cityArrObj['startCityCode'].chr(45).$param['webClass']])) {
    					$sqlDataTemp = array();
    					$sqlDataTemp['productId'] = $rowsObj['productId'];
    					$sqlDataTemp['productType'] = $rowsObj['productType'];
    					$sqlDataTemp['startCityCode'] = $cityArrObj['startCityCode'];
    					$sqlDataTemp['adKey'] = $cityArrObj['adKey'];
    					$sqlDataTemp['ranking'] = $rowsObj['ranking'];
    					$sqlDataTemp['bidRanking'] = $rowsObj['ranking'];
    					array_push($sqlData, $sqlDataTemp);
    					unset($sqlDataTemp);
    				}	
    			}
    		}
    		
    		
    		
    		// 生成SQL，并批量插入
			$column = array('product_id', 'product_type', 'start_city_code', 'ranking', 'bid_ranking', 
							'ad_key', 'web_class', 'account_id', 'show_date_id', 'fmis_mark', 'bid_mark', 'add_uid', 'add_time', 
							'update_uid', 'update_time', 'login_name', 'is_buyout');
			$columnValue = array('productId', 'productType', 'startCityCode', 'ranking', 'bidRanking','adKey');
			$defaultValue = array($param['webClass'], $param['accountId'], $param['showDateId'], chr(48), chr(50), 
									$param['agencyId'], date('Y-m-d H:i:s'), $param['agencyId'], date('Y-m-d H:i:s'), $param['agencyId'], chr(49));
			$sqlToAdds = $this->_comdbMod->generateComInsert("bid_bid_product", $column, $columnValue, $sqlData, $defaultValue);
			unset($sqlData);
			$this->productDao->executeSql($sqlToAdds, DaoModule::SALL);
    		
    	} else if (Sundry::UPDATE == $param['saveFlag']) {
    		// 查询位置数量的限制
    		$limitParam = array();
    		$limitParam['adKeyType'] = $param['adKeyType'];
    		$limitParam['startCityCodes'] = $param['startCityCode'];
    		$limitParam['webClass'] = $param['webClass'];
    		$limitDb = $this->productDao->queryBuyoutPosLimit($limitParam);
			unset($limitParam);
			// 对比数量
			foreach ($limitDb as $limitDbObj) {
				if (intval($param['ranking']) > intval($limitDbObj['ad_product_count'])) {
					$result['flag'] = Symbol::CONS_FALSE;
    				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231613)];
    				return $result;
				}
			}
			
			// 查询重复的ID
			$recordRank = $this->productDao->queryBuyoutRecordRank($param);
			
			// 对比重复记录
			foreach ($recordRank as $recordRankObj) {
				if (intval($param['bidId']) != intval($recordRankObj['id'])) {
					$result['flag'] = Symbol::CONS_FALSE;
    				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231614)];
    				return $result;
				}
			}
			
			// 查询包场重复的产品
    		$douParam['startCityCodes'] = $param['startCityCode'];
    		$douParam['webClass'] = $param['webClass'];
    		$douParam['adKeys'] = chr(39).$param['adKey'].chr(39);
    		$douParam['showDateId'] = $param['showDateId'];
    		$douParam['bidId'] = $param['bidId'];
    		$douProduct = $this->productDao->queryBuyoutDouProduct($douParam);
    		$douProductCheck = array();
    		foreach ($douProduct as $douProductObj) {
    			if (intval($douProductObj['product_id']) == intval($param['productId'])) {
    				$result['flag'] = Symbol::CONS_FALSE;
	    			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231615)];
	    			return $result;
    			}
    		}
			
			// 更新数据
			$this->productDao->updateBuyoutRecord($param);
    	}
    	// 返回结果
    	return $result;
    }

    /**
     * 删除包场记录
     */
    public function delBuyout($param) {
        return $this->productDao->delBuyout($param);
    }

    /**
     * 获得包场信息
     */
    public function queryBuyout($param) {
        $queryRows =  $this->productDao->queryBuyout($param);
        $rows = array();
        // 设置参数获取分类页信息
        $webClassId = array();
        $webClassData = array();
        foreach($queryRows as $val){
            if ($val['webClass']) {
                array_push($webClassId,$val['webClass']);
            }
        }
        if ($webClassId) {
            // 使用循环每次10条来获取数据
            for ($i = 0; $i < count($queryRows); $i = $i + 10) {
                // 调用网站接口获取分类信息
                $paramWebClassId = array_slice($webClassId, $i, 10);
                $webClassArr = array('webClassId' => $paramWebClassId);
                $webClassInfo = $this->_productIao->getWebClassInfo($webClassArr);
                $webClassData[] = $webClassInfo['data'];
            }
        }

        // 增加上级分类的查找 added by chenjinlong 20140331
        $webClassParentIdArr = array();
        foreach($webClassData as $subItem) {
            foreach($subItem as $iaoObj){
                $webClassParentIdArr['webClassId'][] = $iaoObj['parentId'];
            }
        }
        $parentWebClassInfoRows = ProductIao::getWebClassInfo($webClassParentIdArr);

        // 获取所有出发城市
        $startCityList = $this->_iaoProductMod->getMultiCityInfo();
        // 获取所有产品类型
        $productTypeArr = ConstDictionary::$bbProductTypeList;
        // 循环拼接数据
        foreach($queryRows as $val){
            $temp = array();
            if ($param['bidState'] == 1) {
                $temp['isModify'] = 1;
            } else {
                $temp['isModify'] = 0;
            }
            $temp['bidId'] = intval($val['bidId']);
            $temp['bidDate'] =  $val['bidStartDate'].' '.$val['bidStartTime'].'点～'.$val['bidEndDate'].' '.$val['bidEndTime'].'点';
            $temp['showDateId'] = intval($val['showDateId']);
            $temp['webClassId'] = intval($val['webClass']);
            $temp['startCityCode'] = intval($val['startCityCode']);
            // 根据出发城市code获取name
            if ($startCityList['all']) {
                foreach ($startCityList['all'] as $tempArr) {
                    if ($tempArr['code'] == $temp['startCityCode']) {
                        $temp['startCityName'] = $tempArr['name'];
                        break;
                    }
                }
            } else {
                $temp['startCityName'] = '';
            }
            $temp['productId'] = ($val['productId']) ? $val['productId'] : '';
            $temp['productType'] = intval($val['productType']);
            foreach ($productTypeArr as $k => $tempArr) {
                if ($temp['productType'] == $k) {
                    $temp['productTypeName'] = $tempArr;
                    break;
                }
            }
            // 品牌专区产品类型显示处理
            if ('500' == $temp['productType']) {
                $temp['productTypeName'] = '供应商编号';
            }
            if (empty($temp['productType'])) {
                $temp['productTypeName'] = '';
            }
            $temp['adKey'] = $val['adKey'];
            // 广告位数据拼接
            $adKeyInfo = $this->getAdKeyInfo($val['adKey'], $val['startCityCode']);
            if ($adKeyInfo[0]) {
                $temp['adKeyName'] = $adKeyInfo[0]['adName'];
                if ("class_recommend" == $adKeyInfo[0]['adKey']) {
                    if ($webClassData) {
                        foreach($webClassData as $tempStr){
                            if (intval($tempStr[$temp['webClassId']]['id']) == intval($val['webClass'])) {
                                $parentWebClassStr = str_replace('目的地', '', strval($parentWebClassInfoRows['data'][$tempStr[$temp['webClassId']]['parentId']]['classificationName']));
                                $temp['webClass'] = $tempStr[$temp['webClassId']]['classificationName'];
                                $temp['adKeyName'] = $parentWebClassStr . '-' . $tempStr[$temp['webClassId']]['classificationName'] . '-' .$adKeyInfo[0]['adName'];
                            }
                        }
                    }
                }
            } else {
                $temp['adKeyName'] ='';
            }
            // 首页老数据呈现处理
            if ("index_chosen" == $temp['adKey']) {
                $indexAdInfo = $this->productDao->queryIndexInfo("index_chosen");
                $temp['adKeyName'] = $indexAdInfo['ad_name'];
            }
            // 首页老数据呈现处理
//            if ("index_chosen" == $temp['adKey']) {
//                $indexAdInfo = $this->productDao->queryIndexInfo("index_chosen");
//                $temp['adKeyName'] = $temp['startCityName'].'-'.$indexAdInfo['ad_name'];
//            }
            $temp['searchKeyword'] = $val['searchName'];
            $temp['ranking'] = intval($val['ranking']);
            // 获取竞拍状态
            $temp['bidState'] = $param['bidState'];
            $temp['bidStateName'] = DictionaryTools::getBuyoutStateTool($param['bidState']);
            $temp['showDate'] = $val['showStartDate'].'～'.$val['showEndDate'];
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userManageDao->getVendorInfoAll($val['accountId']);
            $temp['vendorId'] = $verdorInfo[0]['vendorId'];
            $temp['vendorName'] = $verdorInfo[0]['brandName'];// 供应商品牌名
            $rows[] = $temp;
        }
        return !empty($rows) ? $rows : array();
    }

    /**
     * 获得包场信息总数
     * @param array $condParams
     */
    public function queryBuyoutCount($condParams) {
        $queryCount = $this->productDao->queryBuyoutCount($condParams);
        return $queryCount;
    }

    /**
     * 过滤出当前支持的包场广告位类型
     */
    public function getAllBuyoutType($condParams){
        // 先从缓存取数据
        $memKey = 'getBuyoutType_' . md5(json_encode($condParams));
        $resultRows = Yii::app()->memcache->get($memKey);
        if(!empty($resultRows)){
            // 返回结果
            return $resultRows;
        }
        $result = $this->productDao->getBuyoutType($condParams);
        // 数据缓存
        Yii::app()->memcache->set($memKey, $result, 43200);
        return $result;
    }

    /**
     * 查询所匹配的包场广告位类型
     * @param $param
     * @return array
     */
    public function getBuyoutTypeName($param) {
    	return $this->productDao->queryBuyoutPosInfo($param);
    }

    /**
     * 获得包场分类页信息
     * @param array $condParams
     */
    public function queryWebClassInfo($condParams) {
        // 先从缓存取数据
        $memKey = 'queryWebClassInfo_' . md5(json_encode($condParams));
        $resultRows = Yii::app()->memcache->get($memKey);
        if(!empty($resultRows)){
            // 返回结果
            return $resultRows;
        }
        // 默认传webClassId为空数组
        $condParams['webClassId'] = array();
        $webClassInfo = $this->_productIao->getWebClassInfo($condParams);
        $result = array();
        // 返回数据处理
        if ($webClassInfo['data']) {
            foreach ($webClassInfo['data'] as $temp) {
                // 取非1级分类
                if (1 != $temp['classificationDepth']) {
                    array_push($result,$temp);
                }
            }
        }
        // 数据缓存
        Yii::app()->memcache->set($memKey, $result, 43200);
        return $result;
    }

    /**
     * 删除招客宝广告位
     * 
     * @author wenrui 2014-05-28
     */
    public function delAdPosition($data){
    	// 待处理数据
    	$returnData = array();
    	$returnData['flag'] = true;
    	$returnData['data']['showEndDate'] = '';
    	
    	// 判断招客宝是否有竞拍中或者即将竞拍的打包计划包含此广告位
    	$packages = $this->productDao->queryBidPackageExist($data);
    	if(!empty($packages) && '0000-00-00' != strval($packages['show_end_date'])){
    		// $returnData['flag'] = false;
			$returnData['msg'] = '删除失败：已存在打包计划包含此广告位';
			$returnData['data']['showEndDate'] = $packages['show_end_date'];
			return $returnData;
    	}
    	// 判断当前时间此广告位是否已在招客宝被竞拍
    	$bidProducts = $this->productDao->queryBidProductExist($data);
    	if(!empty($bidProducts) && '0000-00-00' != strval($bidProducts['show_end_date'])){
    		// $returnData['flag'] = false;
			$returnData['msg'] = '删除失败：该位置已在招客宝被竞拍';
			$returnData['data']['showEndDate'] = $bidProducts['show_end_date'];
			return $returnData;
    	}
    	// 删除招客宝的广告位
    	$this->productDao->delBBPosition($data);
		$returnData['msg'] = '删除成功';
    	return $returnData;
    } 
    
    /**
	 * 获得广告位信息完整性
	 */
	public function getAdWholeness($param) {
		// 初始化返回结果
		$result = array();
		$data['count'] = 0;
		
		try {
			// 查询信息
			$dataDb = $this->productDao->queryAdWholeness($param);
			// 判断是否有数据，若有数据，则返回整合数据返回正确的数据
			if (!empty($dataDb) && is_array($dataDb)) {
				$data['count'] = $dataDb['countRe'];
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
	 * 查询竞价结束时间
	 */
	public function getBidEndTime($showDateId) {
		// 初始化返回结果
		$result = array();
		
		// 查询竞价结束时间
		$result = $this->productDao->queryBidEndTime($showDateId);
        
        // 返回结果
        return $result; 
	}
    
    /**
     * 同步包场位置数据
     */
    public function syncBuyout($showDateId) {
    	// 添加方法开始日志
    	if ($this->_bbLog->isInfo()) {
            $this->_bbLog->logMethod("运营计划ID：".$showDateId, "同步包场位置数据", __METHOD__.'::'.__LINE__, chr(50));
        }
        try {
        	// 清理数据
        	$this->productDao->clearBuyoutPosData();
        	// 查询包场位置维度
        	$wd = $this->productDao->queryBuyoutPosWd();
        	
        	// 整合首页和分类页参数
        	$cityArr = array();
        	$classStrArr = array();
        	$indexName = Symbol::EMPTY_STRING;
        	$indexArr = array();
        	$classArr = array();
        	foreach($wd as $wdObj) {
        		array_push($cityArr, $wdObj['start_city_code']);
        		array_push($classStrArr, $wdObj['web_class']);
        		if (intval(chr(49)) == $wdObj['ad_key_type']) {
        			$indexName = $wdObj['ad_name'];
        			array_push($indexArr, $wdObj);
        		} else if (Symbol::TWENTY_TWO == $wdObj['ad_key_type'] || Symbol::TWENTY_THREE == $wdObj['ad_key_type']) {
        			array_push($classArr, $wdObj);
        		}
        	}
        	
        	// 清理内存
        	unset($wd);
        	
        	// 初始化共通参数
        	$paramData = array();
        	$paramData['adName'] = $indexName;
        	$paramData['showDateId'] = $showDateId;
        	$paramData['startCityCodes'] = implode(chr(44), $cityArr);
        	
        	// 查询首页信息
        	$indexData = $this->productDao->queryBuyoutIndexCount($paramData);
        	
        	// 查询分类页分类信息
        	$paramData['webClasses'] = implode(chr(44), $classStrArr);
			$classData = $this->productDao->queryBuyoutClassInfo($paramData);
			// 生成SQL，并批量更新
			$this->updateBuyout($indexArr, $classArr, $indexData, $classData);
			
		} catch (BBException $e) {
			throw $e;
        } catch (Exception $e) {
			throw new BBException(ErrorCode::ERR_231602, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231602)], $classData, $e);
        }
	}
     
    /**
     * 更新包场位置数据
     */
    public function updateBuyout($indexArr, $classArr, $indexData, $classData) {
        
        try {
        	$data = array();
        	
        	// 生成首页数据集合
        	$updIndex = array();
        	foreach ($indexArr as $indexArrObj) {
        		foreach ($indexData as $indexDataObj) {
        			if ($indexArrObj['start_city_code'] == $indexDataObj['start_city_code']) {
        				$updIndexTemp = array();
        				$updIndexTemp['id'] = $indexArrObj['id'];
        				$updIndexTemp['adKey'] = $indexDataObj['ad_key'];
        				$updIndexTemp['adProductCount'] = $indexDataObj['ad_product_count'];
        				array_push($updIndex, $updIndexTemp);
        				unset($updIndexTemp);
        				break;
        			}
        		}
        	}
        	
        	// 生成分类页数据集合
        	$updClass = array();
        	$clsCom = array();
        	// 筛选一级和二级
        	foreach ($classData['one'] as $oneObj) {
        		$tempObj = array();
        		$tempObj = array_merge($tempObj, $oneObj);
        		foreach ($classData['two'] as $twoObj) {
        			if ($oneObj['startCityCode'] == $twoObj['startCityCode'] && strtotime($oneObj['updateTime']) < strtotime($twoObj['updateTime'])) {
        				$tempObj['adProductCount'] = $twoObj['adProductCount'];
        				$tempObj['updateTime'] = $twoObj['updateTime'];
        				break;
        			}
        			
        		}
        		array_push($clsCom, $tempObj);
        		unset($tempObj);
        	}
        	// 生成三级数据
        	foreach ($classArr as $classArrObj) {
        		$updClassTemp = array();
        		$updClassTemp['id'] = $classArrObj['id'];
        		$updClassTemp['adKey'] = BusinessType::CLASS_RECOMMEND;
        		foreach ($clsCom as $clsComObj) {
        			if ($classArrObj['start_city_code'] == $clsComObj['startCityCode']) {
        				$updClassTemp['adProductCount'] = $clsComObj['adProductCount'];
        				foreach($classData['three'] as $threeObj) {
        					if ($classArrObj['web_class'] == $threeObj['webClass'] && $classArrObj['start_city_code'] == $threeObj['startCityCode']
        						&& strtotime($clsComObj['updateTime']) < strtotime($threeObj['updateTime'])) {
        						$updClassTemp['adProductCount'] = $threeObj['adProductCount'];	
        						break;
        					}
        				}
        				break;
        			}
        		}
        		array_push($updClass, $updClassTemp);
        		unset($updClassTemp);
        	}
        	
        	// 综合集合
        	$data = array_merge($data, $updIndex);
        	$data = array_merge($data, $updClass);
        	
			// 生成SQL，并批量更新
			$column = array('id', 'ad_key', 'ad_product_count', 'update_time');
			$columnValue = array('id', 'adKey', 'adProductCount');
			$updateColumn = array('ad_key', 'ad_product_count', 'update_time');
			$defaultValue = array(date('Y-m-d H:i:s'));
			$sqlToUpds = $this->_comdbMod->generateComUpdate("buyout_position_config", $column, $columnValue, $data, $defaultValue, $updateColumn);
			$this->productDao->executeSql($sqlToUpds, DaoModule::SALL);
			
		} catch (Exception $e) {
			// 抛异常
			throw new BBException(ErrorCode::ERR_231602, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231602)], $sqlToUpds, $e);
        }
	}
     
}
?>