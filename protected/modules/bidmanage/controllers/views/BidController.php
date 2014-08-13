<?php
Yii::import('application.modules.bidmanage.models.bid.BidProduct');
Yii::import('application.modules.bidmanage.models.product.ProductMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.models.fmis.FmisBidInfo');
Yii::import('application.modules.bidmanage.models.bid.BidProductEmployeeImpl');
Yii::import('application.modules.bidmanage.factory.bid.BidModFactory');
Yii::import('application.modules.bidmanage.models.date.PackageDateMod');
Yii::import('application.modules.bidmanage.models.date.AdPositionMod');
Yii::import('application.modules.bidmanage.models.product.BidContentMod');
Yii::import('application.modules.bidmanage.models.iao.IaoRorProductMod');
/**
 * bid process
 * @author chuhui@2012-01-04
 * @version 1.0
 * @func doRestPostRecord
 * @func doRestGetRank
 */
class BidController extends restUIServer {
	private $bidProduct;
	private $product;
	private $manage;
	private $fmisBidInfo;
    private $packageDateMod;
    private $_adPositionMod;
    private $_bidContentMod;
    private $_iaoRorProductMod;
	
	function __construct() {
		$this->bidProduct = new BidProduct();
		$this->product = new ProductMod();
		$this->manage = new UserManageMod();
		$this->fmisBidInfo = new FmisBidInfo();
        $this->packageDateMod = new PackageDateMod();
        $this->_adPositionMod = new AdPositionMod();
        $this->_bidContentMod = new BidContentMod();
        $this->_iaoRorProductMod = new IaoRorProductMod();
	}


    /**
     * 招客宝改版，新版竞价
     *
     * @author chenjinlong 20131113
     * @param mixed
     * @return mixed
     */
    public function doRestPostRun($data)
    {
    	// 校验出价是否过了时间
    	$memcacheKey = md5('BidController.doRestPostRun_showDate'.$data['showDateId']);
	    // $bidTime = Yii::app()->memcache->get($memcacheKey);
	    if(empty($bidTime)) {
	    	// 查询出价时间
	        $bidTimeDb = $this->product->getBidEndTime($data['showDateId']);
	        $bidTime = $bidTimeDb['bidEndTime'];
	        // 缓存24h
	        Yii::app()->memcache->set($memcacheKey, $bidTime, 86400);
	    }
    	if (intval(date('H')) >= intval($bidTime)) {
    		$this->returnRest(array(), false, 230001, '出价时间已过，无法出价');
    		return;
    	}
    	
        $data['accountId'] = $this->getAccountId();
        // 检查执行竞价流程的前置条件
        /*$returnData = $this->checkBidSendInParams($data);
        if (!$returnData['success']) {
            $this->returnRest($returnData['data'],false,$returnData['errorCode'],$returnData['msg']);
            return;
        }*/
        if(intval($data['bidPrice']) <= 0 || intval($data['maxLimitPrice']) <= 0 ||
            intval($data['bidPrice']) > intval($data['maxLimitPrice']) ||
            intval($data['accountId']) <= 0){
            $this->returnRest(array(), false, 230015, '输入参数错误');
            return;
        }
        // 预初始化关键词参数
        $keyWordParam = '';
        // 若为搜索页，则初始化关键词参数为关键词
        if ('search_complex' == strval($data['adKey'])) {
        	$keyWordParam = strval($data['searchKeyword']);
        }
        
        $accountInfo = $this->manage->read(array('id'=>$data['accountId']));
        
        
        // 初始化广告位名称查询参数
        $adParam = array(
        	'showDateId' => 0,
			'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
			'searchKeyword' => $keyWordParam,
			'positionId' => 0,
			'webId' => 0,
			'webClassId' => $data['webClassId'],
			'viewName' => '',
			'showDate' => '',
			'account_id' => $data['accountId']
        );
        
        // 查询广告位名称
        $adName = $data['viewName'];
        // $adName = $this->product->getHeadcommon($adParam);
        // 获取赠币使用占比
        $couponUsePercent = $this->product->getCouponUsePercent($data);
        
        $params = array(
            'account_id' => $data['accountId'],
            'product_id' => $data['productId'],
            'ad_key' => $data['adKey'],
            'show_date_id' => $data['showDateId'],
            'start_city_code' => $data['startCityCode'],
            'web_class' => $data['webClassId'],
            'bid_price' => $data['bidPrice'],
            'latest_bid_price' => $data['latestBidPrice'],
            'max_limit_price' => $data['maxLimitPrice'],
            'product_type' => ($data['productType']=='3_3') ? 3:$data['productType'], // 1：跟团游  3_3：自助游  33：门票
            'vendor_id' => $accountInfo['vendorId'],
            'search_keyword' => $keyWordParam,
            'bid_id' => intval($data['bidId']),
            'login_name' => $this->getLoginName(),
            'is_father' => $this->getAdminFlag(),
            'ad_name' => $adName['viewName'],
            'coupon_use_percent' => $couponUsePercent
        );
        
        try{
             $checkResult = $this->checkBidSendInParamsNew($params);
            if($checkResult['state'] == 0){
            	$this->returnRest(array(), false, 230001, $checkResult['msg']);
            }else if($checkResult['state'] == 2){
            	$params['bid_price_niu'] = $checkResult['bidPriceNiu'];
            	$params['bid_price_coupon'] = $checkResult['bidPriceCoupon'];
            	$params['max_limit_price_niu'] = $checkResult['maxLimitPriceNiu'];
            	$params['max_limit_price_coupon'] = $checkResult['maxLimitPriceCoupon'];
            	$biddingJobResult = $this->bidProduct->runBiddingProcess($params);
                if (!empty($biddingJobResult['sub_flag'])) {
                	$this->returnRest(array(), false, 230001, $biddingJobResult['msg']);
                } else if (!$biddingJobResult) {
                    $this->returnRest(array(), false, 230001, '未查询到该产品信息导致无法出价');
                } else {
                    $result = array(
                        'isBidSuccess' => $biddingJobResult['state'],
                        'ranking' => $biddingJobResult['final_ranking'],
                    );
                    $this->returnRest($result);
                }
            }
        }catch (Exception $e){
            $this->returnRest(array(), false, 230099, '未知原因失败');
        }
    }

    /**
     * 招客宝改版-检查竞价前置条件
     *
     * @author chenjinlong 20131115
     * @param $params
     * @return mixed
     */
    public function checkBidSendInParamsNew($params)
    {
        $checkResult = array(
            'state' => 1,
            'msg' => '',
        );
        // 检查当前出价是否高于之前成功价格
        if(intval($params['bid_id']) > 0){
            $queryParams = array(
                'bid_id' => $params['bid_id'],
            );
            $bidRecords = $this->bidProduct->queryBidRecordByCondition($queryParams);
            if(!empty($bidRecords) &&
                $bidRecords[0]['bid_mark'] == 2 &&
                $bidRecords[0]['bid_price'] >= $params['bid_price']){

                $checkResult['state'] = 0;
                $checkResult['msg'] = '您的本次出价需要高于上一次出价牛币';
                return $checkResult;
            }
        }
		// alter by wenrui 2014-04-11 start
		// 赠币占比
		$couponUsePercent = $params['coupon_use_percent'];
		// 本次出价
		$bidPrice = $params['bid_price'];
		// 本次最高出价
		$maxLimitPrice = $params['max_limit_price'];
		// 本次出价理论赠币值
		$bidPriceCoupon = floor($bidPrice * $couponUsePercent);
		// 本次出价理论牛币值
		$bidPriceNiu = $bidPrice - $bidPriceCoupon;
		// 本次最高出价理论赠币值
		$maxLimitPriceCoupon = floor($maxLimitPrice * $couponUsePercent);
		// 本次最高出价理论牛币值
		$maxLimitPriceNiu = $maxLimitPrice - $maxLimitPriceCoupon;
        // 检查最高出价是否符合
        $properMaxLimitPrice = $this->bidProduct->toGetProperMaxLimitPrice($params);
        // 当前账户可用赠币不够扣除赠币时
        if($maxLimitPriceCoupon>$properMaxLimitPrice['coupon']){
        	// 且牛币也不够弥补赠币的不足时
        	if(($maxLimitPriceCoupon-$properMaxLimitPrice['coupon'])+$maxLimitPriceNiu-$properMaxLimitPrice['niu']>0){
        		$checkResult['state'] = 0;
	            $checkResult['msg'] = '最高出价已经超过上限，请核对充值记录，您的部分充值金额可能即将过期';
	            return $checkResult;
        	}else{
        		$checkResult['state'] = 2;
        		// 本次最高出价最终赠币值
        		$checkResult['maxLimitPriceCoupon'] = $properMaxLimitPrice['coupon'];
        		// 本次最高出价最终牛币值
        		$checkResult['maxLimitPriceNiu'] = $maxLimitPrice - $properMaxLimitPrice['coupon'];
        	}
        }else{
        	// 牛币金额不够
        	if($maxLimitPriceNiu>$properMaxLimitPrice['niu']){
        		$checkResult['state'] = 0;
	            $checkResult['msg'] = '最高出价已经超过上限，请核对充值记录，您的部分充值金额可能即将过期';
	            return $checkResult;
        	}else{
        		$checkResult['state'] = 2;
        		// 本次最高出价最终数值不变
        		$checkResult['maxLimitPriceCoupon'] = $maxLimitPriceCoupon;
        		$checkResult['maxLimitPriceNiu'] = $maxLimitPriceNiu;
        	}
        }
        if($bidPriceCoupon>$checkResult['maxLimitPriceCoupon']){
        	$checkResult['bidPriceCoupon'] = $checkResult['maxLimitPriceCoupon'];
        	$checkResult['bidPriceNiu'] = ($bidPriceCoupon-$checkResult['maxLimitPriceCoupon']) + $bidPriceNiu;
        }else{
        	$checkResult['bidPriceCoupon'] = $bidPriceCoupon;
			$checkResult['bidPriceNiu'] = $bidPriceNiu;
        }

        /*if($params['max_limit_price'] > $properMaxLimitPrice){
            $checkResult['state'] = 0;
            $checkResult['msg'] = '最高出价已经超过上限【'.$properMaxLimitPrice.'牛币】，请核对充值记录，您的部分充值金额可能即将过期';
            return $checkResult;
        }*/
        // alter by wenrui 2014-04-11 end

        // 查询呈现天数
        $showDateId = intval($params['show_date_id']);
        $showDaysCount = $this->packageDateMod->getBidShowDays($showDateId);
        if($showDaysCount == 0){
            $checkResult['state'] = 0;
            $checkResult['msg'] = '您所选择的广告日期是无效的，请重新选择';
            return $checkResult;
        }else{
            // 检查当前出价是否高于广告位底价
            $adPositionRow = $this->_adPositionMod->queryAdPositionSpecific($params);
            if($params['bid_price'] < round($adPositionRow['floor_price'])){
                $checkResult['state'] = 0;
                $checkResult['msg'] = '您所填写的出价低于广告位的底价';
                return $checkResult;
            }
        }
        return $checkResult;
    }

    /**
     * 招客宝改版-查询底价
     *
     * @author chenjinlong 20131118
     * @param $url
     * @param $data
     */
    public function doRestGetBaseprice($url, $data)
    {
        $adKey = strval($data['adKey']);
        $showDateId = intval($data['showDateId']);
        if(empty($adKey) || empty($showDateId) || intval($showDateId) <= 0){
            $this->returnRest(array(), false, 230015, '输入参数错误');
        }else{
            $adPositionRow = $this->_adPositionMod->queryAdPositionSpecific($showDateId, $adKey);
            if(!empty($adPositionRow) && is_array($adPositionRow)){
                $floorPriceOfPlan = $adPositionRow['floor_price'];
                $returnResult = array(
                    'floorPrice' => round($floorPriceOfPlan),
                );
                $this->returnRest($returnResult);
            }else{
                $this->returnRest(array(), false, 230015, '打包日期无效');
            }
        }
    }

    /**
     * 3.0版：查询所有出价记录(分页)
     *
     * @author chenjinlong 20140118
     * @param $url
     * @param $data
     */
    public function doRestGetBidlist($url, $data)
    {
        $adKey = strval($data['adKey']);
        $showDateId = intval($data['showDateId']);
        $accountId = $this->getAccountId();
        if(empty($adKey) || empty($showDateId) || intval($showDateId) <= 0 ||
            intval($data['start']) < 0 || intval($data['limit']) <= 0 || $accountId <= 0){
            $this->returnRest(array(), false, 230015, '输入参数错误');
        }else{
            $listParams = array(
                'account_id' => $accountId,
                'show_date_id' => $data['showDateId'],
                'ad_key' => $data['adKey'],
                'start_city_code' => $data['startCityCode'],
                'search_keyword' => $data['searchKeyword'],
                'web_class' => $data['webClassId'],
                'need_pager' => 1,
                'start' => intval($data['start']),
                'limit' => intval($data['limit']),
            );
            $bidRows = $this->bidProduct->queryBidRecordByCondition($listParams);
            $countParams = array(
                'account_id' => $accountId,
                'show_date_id' => $data['showDateId'],
                'ad_key' => $data['adKey'],
                'start_city_code' => $data['startCityCode'],
                'search_keyword' => $data['searchKeyword'],
                'web_class' => $data['webClassId'],
            );
            $bidRowsCount = $this->bidProduct->queryBidRecordCountByCondition($countParams);
            $finalRows = array(
                'count' => $bidRowsCount,
                'rows' => array(),
            );
            if(!empty($bidRows)){
                foreach($bidRows as $row)
                {
                    $bidContentRow = $this->_bidContentMod->queryBidContentRow($row['bid_id']);
                    $finalRows['rows'][] = array(
                        'bidId' => $row['bid_id'],
                        'bidMark' => $row['bid_mark'],
                        'ranking' => $row['ranking'],
                        'bidPrice' => round($row['bid_price']),
                        'maxLimitPrice' => round($row['max_limit_price']),
                        'bidPriceNiu' => round($row['bid_price_niu']),
                        'maxLimitPriceNiu' => round($row['max_limit_price_niu']),
                        'bidPriceCoupon' => round($row['bid_price_coupon']),
                        'maxLimitPriceCoupon' => round($row['max_limit_price_coupon']),
                        'loginName' => $row['login_name'],
                        'contentType' => $bidContentRow['content_type'],
                        'contentId' => $bidContentRow['content_id'],
                    );
                }
            }
            $this->returnRest($finalRows);
        }
    }

    /**
     * 查询网站分类页广告位列表
     *
     * @author chenjinlong 20140418
     * @param $url
     * @param $data
     */
    public function doRestGetCategorytree($url, $data)
    {
        $data['accountId'] = $this->getAccountId();
        $accountInfo = $this->manage->read(array('id'=>$data['accountId']));
        $apiParams = array(
        	'showDateIds' => $data['showDateIds'],
            'startCityCode' => $data['startCityCode'],
            'agencyId' => $accountInfo['vendorId'],
            'classBrandTypes' => ConstDictionary::getGlobalRorProductTypeIdList(),
        );
        $rows = $this->_iaoRorProductMod->getWebCategoryTree($apiParams);

        $this->_returnData ['success'] = true;
        $this->_returnData ['msg'] = __CLASS__ . '.' . __FUNCTION__;
        $this->_returnData['data'] = $rows;
        $this->renderJson();
    }
}

