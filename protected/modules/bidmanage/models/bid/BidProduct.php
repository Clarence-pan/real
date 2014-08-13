<?php

Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao');
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.dao.fmis.FmisManageDao');
Yii::import('application.modules.bidmanage.models.bid.BidLog');
Yii::import('application.modules.bidmanage.models.product.ProductMod');
Yii::import('application.modules.bidmanage.models.fmis.FmisBidInfo');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');
Yii::import('application.modules.bidmanage.models.date.AdPositionMod');
Yii::import('application.modules.bidmanage.models.product.BidContentMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');

/**
 * 
 * @author chuhui@2013-01-06
 * @version 1.0
 * @func __construct
 * @func bidProcess
 * @func insertBidRecord
 * @func delBidRecord
 * @func readBidRank
 * @func getBidRankInfo
 * @func hasBid
 * @func getBidFinanceInfo
 * @func cutBidFinance
 * @func rollbackFinance
 * @func getBidRecordInfoById
 * @func updateBidRecord
 * @func getRankIsChangeBidList
 * @func updateProduct
 */
class BidProduct {

    // 每个广告位自动出价梯级数额
    const priceEveryUnitAd = 1;

    private $bidProductDao;
    private $manageDao;
    private $fmismManageDao;
    private $departCityDao;
    private $productDao;
    private $bidLog;
    private $_financeIao;
    private $packageDateDao;
    private $_adPositionMod;
    private $_bidContentMod;

    function __construct() {
        $this->bidProductDao = new BidProductDao();
        $this->manageDao = new UserManageDao();
        $this->fmismManageDao = new FmisManageDao();
        $this->departCityDao = new CommonDao();
        $this->productDao = new ProductDao();
        $this->bidLog = new BidLog();
        $this->_financeIao = new FinanceIao();
        $this->packageDateDao = new PackageDateDao();
        $this->_adPositionMod = new AdPositionMod();
        $this->_bidContentMod = new BidContentMod();
    }


    /**
     * 招客宝改版，新版竞价流程
     *
     * @author chenjinlong 20131113
     * @param mixed
     *  'account_id' => $data['accountId'],
     *  'product_id' => $data['productId'],
     *  'ad_key' => $data['adKey'],
     *  'show_date_id' => $data['showDateId'],
     *  'start_city_code' => $data['startCityCode'],
     *  'web_class' => $data['webClassId'],
     *  'bid_price' => $data['bidPrice'],
     *  'latest_bid_price' => $data['latestBidPrice'],
     *  'max_limit_price' => $data['maxLimitPrice'],
     * @return mixed
     */
    public function runBiddingProcess($params)
    {
        $curProductId = $params['product_id'];
        $curBidId = $params['bid_id'];
        // 判断是否存在指定竞价产品: 若不存在，新增竞价产品；若存在，则跳过
        $productInfo = $this->productDao->selectProductInfo($params['product_id'],$params['product_type'],$params['account_id']);
        if(empty($productInfo) && $curProductId > 0){
            $productMod = new ProductMod();
            $insertBidPrdParams = array(
                'account_id' => $params['account_id'],
                'product_id' => $params['product_id'],
                'vendor_id' => $params['vendor_id'],
                'product_type' => $params['product_type'],
            );
            $result = $productMod->insertBidProductRecords($insertBidPrdParams);
            if (!$result) {
                return $result;
            }
        }
        // 查询当前指定竞价的广告位（startCityCode+adKey+webClass+showDateId）中，所有竞价成功的出价信息
        $sameAdsBidRowsParams = array(
            'start_city_code' => $params['start_city_code'],
            'ad_key' => $params['ad_key'],
            'web_class' => $params['web_class'],
            'show_date_id' => $params['show_date_id'],
            'bid_mark' => 2, // 2-竞价成功
            'search_keyword' => $params['search_keyword'], // 搜索关键词
            'is_buyout' => 2, // 包场状态
//            'product_type' => $params['product_type'], // 1：跟团游  3_3：自助游  33：门票
        );
        $sameAdsBidRows = $this->bidProductDao->getBidRecords($sameAdsBidRowsParams);
        // 执行招客宝排名计算方法，得出三维数组结果
        $afterRankingJobResult = $this->executeRankingJob($params, $sameAdsBidRows);
        // 执行竞价失败出价记录的财务款项解冻操作
        $bidState = 0; //0-失败，1-成功
        $finalBidRanking = -1; //-1无排名
        if(!empty($afterRankingJobResult['fail'])){
            $failItem = $afterRankingJobResult['fail'][0];
            if(isset($afterRankingJobResult['fail'][0]['bid_id']) && $afterRankingJobResult['fail'][0]['bid_id'] > 0){
                $bidId = $failItem['bid_id'];
                // 初始化获取财务解冻信息的参数
                $infoParam = array('id' => $bidId, 'bid_mark' => 2);
                // 财务解冻
                $infoRows = $this->bidProductDao->getBidBidProductRows($infoParam);
                if (!empty($infoRows) && is_array($infoRows)) {
	                $info = $infoRows[0];
	                // $lastBidPrice = $info['max_limit_price'];//src_max_limit_price
	                // $curBidPrice = 0;
	                // 调财务接口
	                $flag = $this->refundBidFinance($failItem['account_id'], $info['max_limit_price_niu'], $info['max_limit_price_coupon']);
	                $failItem['ad_name'] = $params['ad_name'];
	                $failItem['is_father'] = $params['is_father'];
					// 执行供应商子账户解冻
	            	$this->manageDao->unfreezeSubAgency($info['login_name'], $info['max_limit_price_niu'], $info['max_limit_price_coupon'], $failItem, $bidId);
	            	// 日志记录
	                if($flag){
	                    $refundFlagStr = 'SUCCESS';
	                }else{
	                    $refundFlagStr = 'FAIL';
	                }
	                CommonSysLogMod::log('RunBiddingProcess-' . $failItem['show_date_id'], '竞价失败-解冻', 1, 'chenjinlong', $params['account_id'], $params['vendor_id'],
	                    'Refund:'.$info['max_limit_price_niu'].'牛币，'.$info['max_limit_price_coupon'].'赠币', $refundFlagStr . '-' . $info['product_id'], '促发变更源:'.$curProductId);
                }
                $failItem['bid_mark'] = -3;
                $failItem['fmis_mark'] = -1; //-1金额已退还
            }else{
                $failItem['bid_mark'] = -3;
                $failItem['fmis_mark'] = 2; //2财务方面无操作
            }
            $failItem['bid_ranking'] = $this->toGetFinalBidRanking($curProductId, $curBidId, $failItem);
            // 添加日志参数
//            $failItem['login_name'] = $params['login_name'];
            //被淘汰出价记录的操作，若不存在，则新增；若存在，则更新
            $this->saveBidBidProductRecord($failItem, 0);

            // 附属流程：竞价日志新增
            $this->bidLog->insertBidLogRecord($failItem);
        }
        // 初始化日志计数标记
        $logCount = $curBidId;
        foreach($afterRankingJobResult['success'] as $sucItem)
        {
        	// 添加日志参数
//        	if (empty($sucItem['login_name']) || '' == $sucItem['login_name']) {
//            	$sucItem['login_name'] = $params['login_name'];
//        	}
            $sucItem['is_father'] = $params['is_father'];
            $sucItem['ad_name'] = $params['ad_name'];
			
            // 执行竞价成功出价记录的财务款项冻结操作(部分冻结)
            $financeCutFlag = $this->cutBidFinanceNew($sucItem, $logCount);
            // 预初始化返回结果
            $biddingJobResult = array();
            // 分类返回错误结果
            if (!empty($financeCutFlag['sub_flag']) && 1 == $financeCutFlag['sub_flag']) {
            	// 父供应商余额不足
            	$biddingJobResult['sub_flag'] = 1;
            	$biddingJobResult['msg'] = '该账号余额不足导致无法出价';
            	// 返回错误信息
            	return $biddingJobResult;
            } else if (!empty($financeCutFlag['sub_flag']) && 2 == $financeCutFlag['sub_flag']) {
            	// 父供应商因为分配了子账号导致余额不足
            	$biddingJobResult['sub_flag'] = 2;
            	$biddingJobResult['msg'] = '该账号分配了子账号导致余额不足无法出价';
            	// 返回错误信息
            	return $biddingJobResult;            	
            } else if (!empty($financeCutFlag['sub_flag']) && 3 == $financeCutFlag['sub_flag']) {
            	// 子账号余额不足
            	$biddingJobResult['sub_flag'] = 3;
            	$biddingJobResult['msg'] = '该子账号余额不足导致无法出价';
            	// 返回错误信息
            	return $biddingJobResult;            	
            }
            
            // 当前操作的出价记录，若不存在，则新增；若存在，则更新
            $sucItem['bid_mark'] = 2;
            $sucItem['fmis_mark'] = 0;
            $sucItem['bid_ranking'] = $this->toGetFinalBidRanking($curProductId, $curBidId, $sucItem);
            $sucItem['log_id'] = $financeCutFlag['log_id'];
            $financeCutFlag = $financeCutFlag['success'];
            
            $this->saveBidBidProductRecord($sucItem, $logCount);
            // 附属流程：竞价日志新增
            $this->bidLog->insertBidLogRecord($sucItem);

            // 整理输出字段
            if($sucItem['product_id'] == $params['product_id']){
                $bidState = 1;
                $finalBidRanking = $sucItem['ranking'];
            }
        }

        $biddingJobResult = array(
            'state' => $bidState,
            'final_ranking' => $finalBidRanking,
        );
        return $biddingJobResult;
    }

    /**
     * 招客宝改版-根据是否被动变化导致的排名变化关系，
     * 查询bidRanking数据
     *
     * @author chenjinlong 20131115
     * @param $curProductId
     * @param $checkBidBidProductRow
     * @return int
     */
    public function toGetFinalBidRanking($curProductId, $curBidId, $checkBidBidProductRow)
    {
        $bidRankingVal = 0;
        if($curProductId == $checkBidBidProductRow['product_id'] || $curBidId == $checkBidBidProductRow['bid_id']){
            $bidRankingVal = intval($checkBidBidProductRow['ranking']);
        }else{
            $bidRankingVal = intval($checkBidBidProductRow['src_ranking']);
        }
        return $bidRankingVal;
    }

    /**
     * 招客宝改版-保存竞价记录
     *
     * @author chenjinlong 20131113
     * @param $params
     *  'account_id' => $data['accountId'],
     *  'bid_id' => $data['bidId'],
     *  'product_id' => $data['productId'],
     *  'ad_key' => $data['adKey'],
     *  'show_date_id' => $data['showDateId'],
     *  'start_city_code' => $data['startCityCode'],
     *  'web_class' => $data['webClassId'],
     *  'bid_price' => $data['bidPrice'],
     *  'latest_bid_price' => $data['latestBidPrice'],
     *  'max_limit_price' => $data['maxLimitPrice'],
     *  'ranking' => $data['ranking'],
     * @return mixed
     */
    public function saveBidBidProductRecord($params, $logCount)
    {
        $bidId = intval($params['bid_id']);
        //查询是否存在出价记录
        $bidRecordInfo = $this->getBidRankInfo($params);

        // 品牌专区时产品编号插入供应商编号，产品类型插入500
        if('brand_zone' == $params['ad_key']) {
            $manageMod = new UserManageMod();
            $accountInfo = $manageMod->read(array('id'=>$params['account_id']));
            $params['product_id'] = $accountInfo['vendorId'];
            $params['product_type'] = '500';
        }

        if(!empty($bidRecordInfo) && $bidId > 0){
            //若存在，则更新
            $updateParams = array(
                'bid_price' => $params['bid_price'],
                'max_limit_price' => $params['max_limit_price'],
                'bid_price_niu' => $params['bid_price_niu'],
                'max_limit_price_niu' => $params['max_limit_price_niu'],
                'bid_price_coupon' => $params['bid_price_coupon'],
                'max_limit_price_coupon' => $params['max_limit_price_coupon'],
                'bid_mark' => $params['bid_mark'],
                'fmis_mark' => $params['fmis_mark'],
                'ranking' => $params['ranking'],
                'bid_ranking' => $params['bid_ranking'],
                'search_keyword' => $params['search_keyword'],
                'login_name' => $params['login_name'],
            );
            $conditionParams = array(
                'bid_id' => $bidId,
                'account_id' => $params['account_id'],
                'product_id' => $params['product_id'],
                'product_type' => $params['product_type'],
                'ad_key' => $params['ad_key'],
                'show_date_id' => $params['show_date_id'],
                'start_city_code' => $params['start_city_code'],
                'web_class' => $params['web_class'],
                'search_keyword' => strval($params['search_keyword']),
            );
            $this->bidProductDao->updateBidRecordNew($updateParams, $conditionParams);
            // 插入日志
            $params['bidId'] = $bidId;
            // 如果不是本出价记录，则不更新
            if (0 == strcmp($logCount, $bidId)) {
            	$this->productDao->insertAgencyUpdateBidLog($params);
            }
        }else{
            //若不存在，则新增
            $insertParams = array(
                'account_id' => $params['account_id'],
                'product_type' => intval($params['product_type']),
                'product_id' => intval($params['product_id']),
                'show_date_id' => $params['show_date_id'],
                'bid_date' => '0000-00-00', //字段废弃
                'ad_key' => $params['ad_key'],
                'cat_type' => 0,
                'web_class' => $params['web_class'],
                'start_city_code' => $params['start_city_code'],
                'bid_price' => $params['bid_price'],
                'max_limit_price' => $params['max_limit_price'],
                'bid_price_niu' => $params['bid_price_niu'],
                'max_limit_price_niu' => $params['max_limit_price_niu'],
                'bid_price_coupon' => $params['bid_price_coupon'],
                'max_limit_price_coupon' => $params['max_limit_price_coupon'],
                'newBidRecordRanking' => $params['ranking'],
                'bid_mark' => $params['bid_mark'],
                'fmis_mark' => $params['fmis_mark'],
                'search_keyword' => strval($params['search_keyword']),
                'login_name' => $params['login_name'],
                // 'bid_ranking' => $params['bid_ranking'],
            );
            $bidId = $this->bidProductDao->insertBidRecord($insertParams);
            // 插入日志
            $params['bidId'] = $bidId;
            $this->productDao->insertAgencyInitBidLog($params);
        }

        //新增bid_bid_content(先逻辑删除，后插入)
        $bidContent = array(
            'account_id' => $params['account_id'],
            'content_type' => intval($params['product_type']),
            'content_id' => intval($params['product_id']),
        );
        $this->_bidContentMod->saveBidContent($bidId, $bidContent);
    }

    /**
     * 招客宝改版-出价冻结招客宝账户
     *
     * @author chenjinlong 20131114
     * @param $params
     * @return bool
     */
    public function cutBidFinanceNew($params, $logCount)
    {
        $lastBidSumMoney = $this->bidProductDao->getLastBidSumMoneyNew($params);
        $lastBidSumMoneyBid = floatval($lastBidSumMoney['bid']);
        $lastBidSumMoneyNiu = floatval($lastBidSumMoney['niu']);
        $lastBidSumMoneyCoupon = floatval($lastBidSumMoney['coupon']);
        $curBidSumMoney = floatval($params['max_limit_price']);
        $curBidSumMoneyNiu = floatval($params['max_limit_price_niu']);
        $curBidSumMoneyCoupon = floatval($params['max_limit_price_coupon']);
		// 根据account_id获取供应商id
        $params['id'] = $params['account_id'];
        $accountInfo = $this->manageDao->readUser($params);
        $agencyId = $accountInfo['vendorId'];
        
        // 调财务接口
        $amtNiu = $curBidSumMoneyNiu;
        $oldAmtNiu = $lastBidSumMoneyNiu;
        $amtCoupon = $curBidSumMoneyCoupon;
        $oldAmtCoupon = $lastBidSumMoneyCoupon;
		// 预初始化返回结果
        $response = array();

        if ($curBidSumMoney == $lastBidSumMoneyBid && $params['del_flag'] == 0) {
        	$response['log_id'] = 0;
			// 判断是否需要冻结子账户
	        if (!$params['is_father'] && 0 == strcmp($logCount, $params['bid_id'])) {
	            // 初始化供应商子账户冻结参数
		        $subParam['login_name'] = $params['login_name'];
	   		    $subParam['account_id'] = $params['account_id'];
	   	    	$subParam['bid_id'] = $params['bid_id'];
	          	$subParam['agency_id'] = $agencyId;
	            $subParam['amt_niu'] = $amtNiu;
			    $subParam['old_amt_niu'] = $oldAmtNiu;
			    $subParam['amt_coupon'] = $amtCoupon;
			    $subParam['old_amt_coupon'] = $oldAmtCoupon;
	   	    	$subParam['ad_name'] = $params['ad_name'];
	   	        $subParam['ranking'] = $params['ranking'];
	    	    // 执行供应商子账户冻结
	   	        $lastLogId = $this->manageDao->freezeSubAgency($subParam);
	       	    // 设置最后插入的lodId
	           	$response['log_id'] = $lastLogId;
	         } else {
	          	// 若为当前竞价ID，则进行父子账号排斥处理以及子账号扣费
		        if (0 == strcmp($logCount, $params['bid_id'])) {
		           	// 若不是第一次竞价，则清空其他账户
		           	if (!empty($logCount) && 0 != $logCount) {
		           		// 清理同一账号下的其他账户
		           		$this->manageDao->handlefreezeSubAgency($params, $logCount);
		           	}
		        }
	        }
	        $response['success'] = true;
            return $response;
        }
        // 判断父账号和子账号是否有足够的钱供竞价
        if ($params['is_father']) {
        	// 判断父账号是否有足够的钱供竞价
        	// 获取父供应商可用金额
        	$financeIaoInfo = FinanceIao::getAccountAvailableBalance($agencyId);
        	$controlMoney = $financeIaoInfo['controlMoney'];
        	$controlMoneyCoupon = $financeIaoInfo['couponControlMoney'];
        	// 获取父供应商的子供应商的可用金额总和
    		$subTotalBudget = $this->manageDao->queryAgencyTotalBudget($agencyId);
    		// 如果供应商真正可用余额小于最高出价金额，则返回错误
    		if (floatval($controlMoney + $controlMoneyCoupon) < floatval($curBidSumMoney - $lastBidSumMoneyBid)) {
    			// 设置错误标记为父供应商余额不足，原因不是因为分配了子账号
    			$response['sub_flag'] = 1;
    			// 返回结果
    			return $response;
    		} else if ((0 != $subTotalBudget['niu']+$subTotalBudget['coupon']) && ((floatval($controlMoney - $subTotalBudget['niu']) < floatval($curBidSumMoneyNiu - $lastBidSumMoneyNiu))||(floatval($controlMoneyCoupon - $subTotalBudget['coupon']) < floatval($curBidSumMoneyCoupon - $lastBidSumMoneyCoupon))) && ((floatval($controlMoney) >= floatval($curBidSumMoney - $lastBidSumMoneyBid))||(floatval($controlMoneyCoupon) >= floatval($curBidSumMoneyCoupon - $lastBidSumMoneyCoupon)))) {
    			// 设置错误标记为父供应商余额不足，原因是因为分配了子账号
    			$response['sub_flag'] = 2;
    			// 返回结果
    			return $response;
    		}
        } else if (!$params['is_father']) {
        	// 判断子账号是否有足够的钱供竞价
        	// 获取当前子账号的可用金额
        	$subAccount = $this->manageDao->querySubAgencyTotalBudget($params['login_name']);
        	// 如果是首次竞价
        	if (empty($logCount) || 0 == $logCount) {
        		// 如果子账号没有余额或子账号真正可用余额小于最高出价金额，则返回错误
	        	if ((0 == $subAccount['niu']&&0 == $subAccount['coupon']) || ((0 != $subAccount['niu']||0!=$subAccount['coupon']) && !empty($subAccount) && (floatval($subAccount['niu']) < floatval($curBidSumMoneyNiu - $lastBidSumMoneyNiu)||(floatval($subAccount['coupon']) < floatval($curBidSumMoneyCoupon - $lastBidSumMoneyCoupon))))){
	        		// 设置错误标记为子供应商余额不足
	    			$response['sub_flag'] = 3;
	    			// 返回结果
	    			return $response;
	        	}
        	} else {
        		// 查询供应商竞价信息
        		$bidPriceInfo = $this->manageDao->queryBidLoginName($logCount);
        		// 分类判断上次出价的是不是同一个供应商
        		if (0 == strcmp($bidPriceInfo['account_id'], $params['account_id']) && 0 == strcmp($bidPriceInfo['login_name'], $params['login_name'])
        		    && ((0 == $subAccount['niu']&&0 == $subAccount['coupon']) || ((0 != $subAccount['niu']||0!=$subAccount['coupon']) && !empty($subAccount) && (floatval($subAccount['niu']) < floatval($curBidSumMoneyNiu - $lastBidSumMoneyNiu)||(floatval($subAccount['coupon']) < floatval($curBidSumMoneyCoupon - $lastBidSumMoneyCoupon)))))) {
        			// 同一个子供应商出价的情况
        			// 设置错误标记为子供应商余额不足
	    			$response['sub_flag'] = 3;
	    			// 返回结果
	    			return $response;
        		} else if (0 == strcmp($bidPriceInfo['account_id'], $params['account_id']) && 0 != strcmp($bidPriceInfo['login_name'], $params['login_name'])
        		    && ((0 == $subAccount['niu']&&0 == $subAccount['coupon']) || ((0 != $subAccount['niu']||0!=$subAccount['coupon']) && (floatval($subAccount['niu']) < floatval($curBidSumMoneyNiu)||floatval($subAccount['coupon']) < floatval($curBidSumMoneyCoupon))))) {
        			// 不同一个子供应商出价的情况
        			// 设置错误标记为子供应商余额不足
	    			$response['sub_flag'] = 3;
	    			// 返回结果
	    			return $response;
        		}
        	}
        }
        CommonSysLogMod::log('RunBiddingProcess-' . $params['show_date_id'], '竞价成功-准备进行冻结操作', 1, 'wenrui', $params['account_id'], $agencyId,
            'Deduct:' . ($amtNiu-$oldAmtNiu).'牛币,'.($amtCoupon-$oldAmtCoupon).'赠币', '广告位' . $params['ad_name'] . '竞拍记录id:' . $params['bid_id'], '上次冻结:' . $lastBidSumMoneyNiu.'牛币,'.$lastBidSumMoneyCoupon.'赠币', '本次冻结:' . $curBidSumMoneyNiu.'牛币,'.$curBidSumMoneyCoupon.'赠币');
        // 调用财务接口
        $response = FinanceIao::bidCutFinanceNew($agencyId, $amtNiu, $oldAmtNiu, $amtCoupon, $oldAmtCoupon);
        CommonSysLogMod::log('RunBiddingProcess-' . $params['show_date_id'], '竞价成功-财务冻结操作后', 1, 'wenrui', $params['account_id'], $agencyId, json_encode($response), json_encode($params), $logCount);
		$response['log_id'] = 0;
		// 判断是否需要冻结子账户
        if (!$params['is_father'] && 0 == strcmp($logCount, $params['bid_id'])) {
            // 初始化供应商子账户冻结参数
	        $subParam['login_name'] = $params['login_name'];
   		    $subParam['account_id'] = $params['account_id'];
   	    	$subParam['bid_id'] = $params['bid_id'];
          	$subParam['agency_id'] = $agencyId;
		    $subParam['amt_niu'] = $amtNiu;
		    $subParam['old_amt_niu'] = $oldAmtNiu;
		    $subParam['amt_coupon'] = $amtCoupon;
		    $subParam['old_amt_coupon'] = $oldAmtCoupon;
   	    	$subParam['ad_name'] = $params['ad_name'];
   	        $subParam['ranking'] = $params['ranking'];
    	    // 执行供应商子账户冻结
   	        $lastLogId = $this->manageDao->freezeSubAgency($subParam);
       	    // 设置最后插入的lodId
           	$response['log_id'] = $lastLogId;
         } else {
          	// 若为当前竞价ID，则进行父子账号排斥处理以及子账号扣费
	        if (0 == strcmp($logCount, $params['bid_id'])) {
	           	// 若不是第一次竞价，则清空其他账户
	           	if (!empty($logCount) && 0 != $logCount) {
	           		// 清理同一账号下的其他账户
	           		$this->manageDao->handlefreezeSubAgency($params, $logCount);
	           	}
	        }
        }
        // 日志记录
        if($response['success']){
            $deductFlagStr = 'SUCCESS';
        }else{
            $deductFlagStr = 'FAIL';
        }
        CommonSysLogMod::log('RunBiddingProcess-' . $params['show_date_id'], '竞价成功-冻结', 1, 'chenjinlong', $params['account_id'], $agencyId,
            'Deduct:' . ($amtNiu-$oldAmtNiu).'牛币,'.($amtCoupon-$oldAmtCoupon).'赠币', $deductFlagStr . '-' . $params['product_id'], '上次冻结:' . $lastBidSumMoneyNiu.'牛币,'.$lastBidSumMoneyCoupon.'赠币', '本次冻结:' . $curBidSumMoneyNiu.'牛币,'.$curBidSumMoneyCoupon.'赠币');
            
        return $response;
    }


    /**
     * 判断查询最大出价设定值的上限
     *
     * @author chenjinlong 20131115
     * @param $params
     * @return int
     */
    public function toGetProperMaxLimitPrice($params)
    {
        $curAccountId = $params['account_id'];
        $curProductId = $params['product_id'];
        $curBidPrice = $params['bid_price'];
        $curMaxLimitPrice = $params['max_limit_price'];
        $curBidId = $params['bid_id'];
        // 查询当前指定竞价的广告位（startCityCode+adKey+webClass+showDateId）中，所有竞价成功的出价信息
        $sameAdsBidRowsParams = array(
            'start_city_code' => $params['start_city_code'],
            'ad_key' => $params['ad_key'],
            'web_class' => $params['web_class'],
            'show_date_id' => $params['show_date_id'],
            'bid_mark' => 2, // 2-竞价成功
            'product_type' => $params['product_type'], // 1：跟团游  3_3：自助游  33：门票
            'search_keyword'=>$params['search_keyword'],
        );
        $sameAdsBidRows = $this->bidProductDao->getBidRecords($sameAdsBidRowsParams);

        $curFreezePriceNiu = 0;
        $curFreezePriceCoupon = 0;
        //查询各个账户的非冻结余额
        // $isCurBidRecExist = 0;
        $accountIds = array($curAccountId);
        foreach($sameAdsBidRows as $key => $row)
        {
            $accountIds[] = $row['account_id'];
            if($row['account_id'] == $curAccountId && ($row['product_id'] == $curProductId || $row['bid_id'] == $curBidId)){
                $curFreezePriceNiu = $row['max_limit_price_niu'];
                $curFreezePriceCoupon = $row['max_limit_price_coupon'];
                // $isCurBidRecExist = 1;
            }
        }
        $fmisBidInfo = new FmisBidInfo();
        $accountFmisInfo = $fmisBidInfo->batchGetAccountFmisInfo($accountIds);

        /**
         * modified by wuke 2013-12-18
         * 招客宝财务充值优化
         */
        //查询该产品推广开始日期
        $showDateInfo = $this->packageDateDao->getShowDateInfoById($params['show_date_id']);
        $showStartDate = $showDateInfo['show_start_date'];
        //获得供应商id
        $agencyParams = array(
            'id' => intval($params['account_id']),
        );
        $accountInfo = $this->manageDao->readUser($agencyParams);
        $chargeParams = array(
            'agency_id' => intval($accountInfo['vendorId']),
            'show_start_date' => $showStartDate,
        );
        $chargeResult = $this->_financeIao->getValidCharge($chargeParams);
        if ($chargeResult){
            if($chargeResult['success']){
                //查询供应商出价列表
                $agencyBidParams = array(
                    'account_id'=>$params['account_id'],
                    'del_flag'=>0,
                    'bid_mark'=>2,
                );
                $agencyBidRows = $this->bidProductDao->getBidRecords($agencyBidParams);
                //本次出价产品的推广日期之前的所有产品的最高出价总额
                $frozenAmtBeforeNiu = 0;
                $frozenAmtBeforeCoupon = 0;
                //本次出价产品的推广日期之后的所有产品的最高出价总额
                $frozenAmtAfterNiu = 0;
                $frozenAmtAfterCoupon = 0;

                foreach($agencyBidRows as $agencyBidProduct){

                    $agencyBidProductShowInfo = $this->packageDateDao->getShowDateInfoById($agencyBidProduct['show_date_id']);
                    if($agencyBidProductShowInfo['show_start_date'] < $showStartDate) {
                        $frozenAmtBeforeNiu += $agencyBidProduct['max_limit_price_niu'];
                        $frozenAmtBeforeCoupon += $agencyBidProduct['max_limit_price_coupon'];
                    }else{
                        $frozenAmtAfterNiu += $agencyBidProduct['max_limit_price_niu'];
                        $frozenAmtAfterCoupon += $agencyBidProduct['max_limit_price_coupon'];
                    }
                }
                if($frozenAmtBeforeNiu > $chargeResult['data']['overdue_amt_niu']){
                    $finalLimitNiu = $accountFmisInfo[$curAccountId]['controlMoney'];
                }else{
                    $finalLimitNiu = $chargeResult['data']['valid_amt_niu'] - $frozenAmtAfterNiu;
                }
                if($frozenAmtBeforeCoupon > $chargeResult['data']['overdue_amt_coupon']){
                    $finalLimitCoupon = $accountFmisInfo[$curAccountId]['couponControlMoney'];
                }else{
                    $finalLimitCoupon = $chargeResult['data']['valid_amt_coupon'] - $frozenAmtAfterCoupon;
                }
            }
        }





        /*$willMaxLimitPriceForCur = 0;
        if($isCurBidRecExist == 1){
            $willMaxLimitPriceForCur = $accountFmisInfo[$curAccountId]['controlMoney'] + $curFreezePrice;
        }else{
            $willMaxLimitPriceForCur = $accountFmisInfo[$curAccountId]['controlMoney'];
        }*/
        $willMaxLimitPriceForCurNiu = $finalLimitNiu + $curFreezePriceNiu;
        $willMaxLimitPriceForCurCoupon = $finalLimitCoupon + $curFreezePriceCoupon;

        return array("niu"=>$willMaxLimitPriceForCurNiu,"coupon"=>$willMaxLimitPriceForCurCoupon);
    }

    /**
     * 招客宝改版-排名计算方法
     *
     * @author chenjinlong 20131113
     * @param $curSameAdsBidRowParams
     * @param $oldSameAdsBidRowsParams
     * Contains Keys:
     * account_id,
     * product_id,
     * show_date_id,
     * ad_key,
     * web_class,
     * start_city_code,
     * bid_price,
     * max_limit_price,
     * ranking[sec-param],
     * bid_ranking[sec-param],
     * bid_mark[sec-param],
     * fmis_mark[sec-param],
     * add_time[sec-param],
     * @return mixed
     */
    public function executeRankingJob($curSameAdsBidRowParams, $oldSameAdsBidRowsParams)
    {
        //返回array('suc'=>array(),'fail'=>array())
        $returnRows = array(
            'success'=>array(),
            'fail'=>array()
        );
        $curSameAdsBidRowParams['add_time'] = date('Y-m-d H:i:s');
        $curAccountId = $curSameAdsBidRowParams['account_id'];
        $curProductId = $curSameAdsBidRowParams['product_id'];
        $curBidId = $curSameAdsBidRowParams['bid_id'];
        // $isCurBidRecExist = 0;

        //查询广告位置数量上限值
        $adPositionRow = $this->_adPositionMod->queryAdPositionSpecific($curSameAdsBidRowParams);
        $adPositionLimitCount = ConstDictionary::getAdPositionCountLimit($adPositionRow['ad_product_count'], $curSameAdsBidRowParams['start_city_code'], $curSameAdsBidRowParams['ad_key']);
        $adLimitNumber = intval($adPositionLimitCount);

        $curFreezePrice = 0;
        $curBidPriceBefore = 0;
        $curRankingBefore = 0;
        //查询各个账户的非冻结余额
        $accountIds = array($curAccountId);
        foreach($oldSameAdsBidRowsParams as $key => $row)
        {
            $accountIds[] = $row['account_id'];
            if($row['account_id'] == $curAccountId && ($row['product_id'] == $curProductId || $row['bid_id'] == $curBidId)){
                // $isCurBidRecExist = 1;
                $curBidPriceBefore = $row['bid_price'];
                $curFreezePrice = $row['max_limit_price'];
                $curBidPriceBeforeNiu = $row['bid_price_niu'];
                $curFreezePriceNiu = $row['max_limit_price_niu'];
                $curBidPriceBeforeCoupon = $row['bid_price_coupon'];
                $curFreezePriceCoupon = $row['max_limit_price_coupon'];
                $curRankingBefore = $row['ranking'];
                $curSameAdsBidRowParams['add_time'] = $row['add_time'];
                unset($oldSameAdsBidRowsParams[$key]);
            }
        }

        $fmisBidInfo = new FmisBidInfo();
        $accountFmisInfo = $fmisBidInfo->batchGetAccountFmisInfo($accountIds);
        //按规则，拼凑写入各自的最高出价
        /*$willMaxLimitPriceForCur = 0;
        if($isCurBidRecExist == 1){
            $willMaxLimitPriceForCur = $accountFmisInfo[$curAccountId]['controlMoney'] + $curFreezePrice;
        }else{
            $willMaxLimitPriceForCur = $accountFmisInfo[$curAccountId]['controlMoney'];
        }*/
        $willMaxLimitPriceForCur = intval($accountFmisInfo[$curAccountId]['controlMoney']) + intval($curFreezePriceNiu);
        if($curSameAdsBidRowParams['max_limit_price_niu'] > $willMaxLimitPriceForCur){
            $curSameAdsBidRowParams['max_limit_price_niu'] = $willMaxLimitPriceForCur;
        }
        $willMaxLimitCouponPriceForCur = intval($accountFmisInfo[$curAccountId]['couponControlMoney']) + intval($curFreezePriceCoupon);
        if($curSameAdsBidRowParams['max_limit_price_coupon'] > $willMaxLimitCouponPriceForCur){
            $curSameAdsBidRowParams['max_limit_price_coupon'] = $willMaxLimitCouponPriceForCur;
        }
        /*foreach($oldSameAdsBidRowsParams as &$row)
        {
            $rowAccountId = $row['account_id'];
            $willMaxLimitPriceForRow = $row['max_limit_price'] + $accountFmisInfo[$rowAccountId]['controlMoney'];
            if($row['max_limit_price'] > $willMaxLimitPriceForRow){
                $row['max_limit_price'] = $willMaxLimitPriceForRow;
            }
        }*/

        if(!empty($oldSameAdsBidRowsParams)){
            $usedSameAdsBidRows = array_merge(array($curSameAdsBidRowParams), $oldSameAdsBidRowsParams);
        }else{
            $usedSameAdsBidRows = array($curSameAdsBidRowParams);
        }

        //备份原出价和最高出价数值
        foreach($usedSameAdsBidRows as &$row)
        {
            if($row['account_id'] == $curAccountId && ($row['product_id'] == $curProductId || $row['bid_id'] == $curBidId)){
                $row['src_bid_price'] = $curBidPriceBefore;
                $row['src_max_limit_price'] = $curFreezePrice;
                $row['src_bid_price_niu'] = $curBidPriceBeforeNiu;
                $row['src_max_limit_price_niu'] = $curFreezePriceNiu;
                $row['src_bid_price_coupon'] = $curBidPriceBeforeCoupon;
                $row['src_max_limit_price_coupon'] = $curFreezePriceCoupon;
                $row['src_ranking'] = $curRankingBefore;
            }else{
                $row['src_bid_price'] = $row['bid_price'];
                $row['src_max_limit_price'] = $row['max_limit_price'];
                $row['src_bid_price_niu'] = $row['bid_price_niu'];
                $row['src_max_limit_price_niu'] = $row['max_limit_price_niu'];
                $row['src_bid_price_coupon'] = $row['bid_price_coupon'];
                $row['src_max_limit_price_coupon'] = $row['max_limit_price_coupon'];
                $row['src_ranking'] = $row['ranking'];
            }
        }

		// 初始化包场查询条件
		$buyoutParams = $curSameAdsBidRowParams;
		unset($buyoutParams['bid_id']);
		unset($buyoutParams['account_id']);
		$buyoutParams['is_buyout'] = 1;
		// 查询该广告位的包场记录
		$buyoutRows = $this->bidProductDao->getBidRecords($buyoutParams);
        //若广告位还未达到互相竞价条件，则按一般排序规则
        if(count($usedSameAdsBidRows) <= $adLimitNumber){
            $returnRows['success'] = $this->seqContentsBySpecificField($usedSameAdsBidRows, 'bid_price');
            // 处理包场数据
            $returnRows = $this->seqBuyoutRows($returnRows['success'], array(), $buyoutRows, $adLimitNumber);
            return $returnRows;
        }

        //按最高价进行排序
        $usedSameAdsBidRows = $this->seqContentsBySpecificField($usedSameAdsBidRows, 'max_limit_price');
        $arrayChunkBidRowsResult = array_chunk($usedSameAdsBidRows, $adLimitNumber);
        $thePassBidRows = $arrayChunkBidRowsResult[0];
        $theLastBidRow = $arrayChunkBidRowsResult[1];
        //规避无效产品虚抬价格的可能性
        /*if($theLastBidRow['product_id'] == $curProductId){
            $returnRows['success'] = $thePassBidRows;
            $returnRows['fail'] = $theLastBidRow;
            return $returnRows;
        }*/
        //根据梯度自增约定，对当前出价进行自增
        $afterModifyBidPriceBidRows = $this->seqContentsByPlusRule($thePassBidRows, $theLastBidRow[0]['max_limit_price_niu'], $adLimitNumber);

        //对自增后的数据再次执行当前出价的排序
        $afterModifyBidPriceBidRows = $this->seqContentsBySpecificField($afterModifyBidPriceBidRows, 'bid_price');

        $returnRows['success'] = $afterModifyBidPriceBidRows;
        $returnRows['fail'] = $theLastBidRow;
        
        // 处理包场数据
        $returnRows = $this->seqBuyoutRows($returnRows['success'], $returnRows['fail'], $buyoutRows, $adLimitNumber);
        
        return $returnRows;
    }

    /**
     * 招客宝改版-查询指定广告位限定位置
     *
     * @author chenjinlong 20131113
     * @param $adKey
     * @return int
     */
    public function getBaAdPositionCountByType($adKey)
    {
        $adCount = 0;
        $adPositionInfo = $this->manageDao->readAdPosition('');
        foreach($adPositionInfo as $row)
        {
            if($row['ad_key'] == $adKey){
                $adCount = $row['ad_product_count'];
                break;
            }
        }
        return $adCount;
    }

    /**
     * 招客宝改版-查询所有配置广告位信息
     *
     * @author chenjinlong 20131116
     * @return mixed
     */
    public function getBaAdPositionInfo()
    {
        $finalReturn = array();
        $adPositionInfo = $this->manageDao->readAdPosition('');
        foreach($adPositionInfo as $row)
        {
            $finalReturn[$row['ad_key']] = $row;
        }
        return $finalReturn;
    }

    /**
     * 根据约定自增数额，执行当前出价的自增
     *
     * @author chenjinlong 20131113
     * @param $thePassBidRows
     * @param $theMaxLimitPriceForLastItem
     * @param $adLimitNumber
     * @return mixed
     */
    public function seqContentsByPlusRule($thePassBidRows, $theMaxLimitPriceForLastItem, $adLimitNumber)
    {
        foreach($thePassBidRows as $key => &$row)
        {
            $valOfPlusRule = $theMaxLimitPriceForLastItem + ($adLimitNumber * self::priceEveryUnitAd);
            if($row['max_limit_price_niu'] < $valOfPlusRule){
            	// 出价牛币自增的额度，本次出价也要增加相应的额度
            	$balance = $row['max_limit_price_niu'] - $row['bid_price_niu'];
                $row['bid_price_niu'] = $row['max_limit_price_niu'];
                $row['bid_price'] += $balance;
            }elseif($row['max_limit_price_niu'] >= $valOfPlusRule && $valOfPlusRule > $row['bid_price_niu']){
                $balance = $valOfPlusRule - $row['bid_price_niu'];
                $row['bid_price_niu'] = $valOfPlusRule;
                $row['bid_price'] += $balance;
            }elseif($valOfPlusRule <= $row['bid_price']){

            }else{

            }
            $adLimitNumber--;
        }
        return $thePassBidRows;
    }

    /**
     * 二维数组排序
     *
     * @author chenjinlong 20131113
     * @param $cmpRows
     * Contains Keys:
     * "product_id":required,
     * "add_time":required,
     * @param $specificFieldName
     * @return mixed
     * Contains Keys:
     * "ranking":Modified,
     * "product_id",
     */
    public function seqContentsBySpecificField($cmpRows, $specificFieldName)
    {
        foreach ($cmpRows as $key => $row) {
            $specificFieldCol[$key]  = $row[$specificFieldName];
            $addTimeCol[$key] = $row['add_time'];
        }
        array_multisort($specificFieldCol, SORT_DESC, $addTimeCol, SORT_ASC, $cmpRows);
        $seq = 1;
        foreach($cmpRows as &$elem)
        {
            $elem['ranking'] = $seq++;
        }
        return $cmpRows;
    }

    /**
     * 根据条件查询竞价记录
     *
     * @author chenjinlong 20140118
     * @param $queryParams
     * @return array|mixed
     */
    public function queryBidRecordByCondition($queryParams)
    {
        $rows = $this->bidProductDao->getBidRecords($queryParams);
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 根据条件查询竞价记录条数
     *
     * @author chenjinlong 20140118
     * @param $queryParams
     * @return array|mixed
     */
    public function queryBidRecordCountByCondition($queryParams)
    {
        $queryParams['only_count'] = 1;
        $countRows = $this->bidProductDao->getBidRecords($queryParams);
        return intval($countRows);
    }

    /**
     * 查询竞价记录
     * @param unknown_type $inParams
     */
    public function getBidRankInfo($inParams) {
        $execResult = $this->bidProductDao->getBidRankInfo($inParams);
        return $execResult;
    }

    /**
     * 判断该产品（包括产品下的分类）是否有出价记录
     * @param unknown_type $params
     */
    public function hasBid($params) {
        $execResult = $this->bidProductDao->hasBid($params);
        return $execResult;
    }

    /**
     * [bid]获取账户竞价财务信息
     * @param unknown_type $accountId
     */
    public function getBidFinanceInfo($accountId) {
        $params['id'] = $accountId;
        $accountInfo = $this->manageDao->readUser($params);
        $agencyId = $accountInfo['vendorId'];
        $financeIaoInfo = FinanceIao::getAccountAvailableBalance($agencyId);
        $controlMoney = $financeIaoInfo['controlMoney'];
        $currentMoney = $financeIaoInfo['currentMoney'];
        $hasAssignMoney = $currentMoney - $controlMoney;
        $startDate = date("Y-m-d", strtotime("-1Day"));
        $hasAssignBalance = $this->fmismManageDao->getHasAssignBalance($accountId, $startDate);
        $financeInfo = array('hasAssignBalance' => $hasAssignBalance,
            'controlMoney' => $controlMoney,
            'currentMoney' => $currentMoney,
            'hasAssignMoney' => $hasAssignMoney);
        return $financeInfo;
    }

    /**
     * 解冻招客宝财务账户的冻结金额
     *
     * @author chenjinlong 20131210
     * @param $accountId
     * @param $amt
     * @return bool
     */
    public function refundBidFinance($accountId, $amtNiu, $amtCoupon)
    {
        $params = array(
            'id' => intval($accountId),
        );
        $accountInfo = $this->manageDao->readUser($params);

        $bidFailFinanceParams = array(
            'agency_id' => intval($accountInfo['vendorId']),
            'amtNiu' => intval($amtNiu),
            'amtCoupon' => intval($amtCoupon),
        );
        $response = $this->_financeIao->bidFailFinance($bidFailFinanceParams);
        return $response;
    }
    
    /**
     * @param rankList
     * @param i
     */
     private function getBidRecordSort($rank) {
	     $sort  = 0;
	     if ($rank['ranking'] < $rank['bidRanking']) {
	     	$sort = 1;
	     } else if ($rank['ranking'] > $rank['bidRanking']) {
	     	$sort = 2;
	     } else {
	     	$sort = 3;
	     }
	     return $sort;
    }
    
    /**
     * 排序包场产品
     */
    public function seqBuyoutRows($successRows, $failRows, $buyoutRows, $limit) {
    	// 初始化返回结果
    	$return['success'] = array();
    	$return['fail'] = array();
    	// 初始化排序标记
    	$seqFlag = false;
    	
    	// 排序包场结果
		foreach ($buyoutRows as $key => $row) {
            $rankingBuyoutCol[$key] = $row['ranking'];
        }
        array_multisort($rankingBuyoutCol, SORT_ASC, $buyoutRows);
        
		// 循环修改排序
		for ($i = 0, $m = count($buyoutRows); $i < $m; $i++) {
			// 还原标记
			$seqFlag = false;
			for ($j = 0, $n = count($successRows); $j < $n; $j++) {
				// 如果标记为true，则正常广告位排名加1
				if ($seqFlag && $successRows[$j]['ranking'] >= $buyoutRows[$i]['ranking']) {
					// 正常广告位排名加1
					$successRows[$j]['ranking'] = $successRows[$j]['ranking'] + 1;
				}
				// 如果正常广告位排名和包场排名冲突，则将正常广告位排名顺延1，包场排名插进来
				if (intval($buyoutRows[$i]['ranking']) === intval($successRows[$j]['ranking'])) {
					// 设置标记为true，以后排名全部加1
					$seqFlag = true;
					// 正常广告位排名加1
					$successRows[$j]['ranking'] = $buyoutRows[$i]['ranking'] + 1;
				}
			}
		}
		
		// 整合成功结果
		$totalSuccessRows = array_merge($successRows, $buyoutRows);
		// 排序成功结果
		foreach ($totalSuccessRows as $key => $row) {
            $rankingCol[$key] = $row['ranking'];
            $addTimeCol[$key] = $row['add_time'];
        }
        array_multisort($rankingCol, SORT_ASC, $addTimeCol, SORT_ASC, $totalSuccessRows);
        // 若成功结果集大于广告位限制，则淘汰出失败的竞拍记录
		if (count($totalSuccessRows) > $limit) {
			// 如果失败广告位结果集不为空，则往后顺延名次
			if (!empty($failRows) && is_array($failRows)) {
				// 初始化失败广告位结果集需要往后推延的名次
				$backCount = count($totalSuccessRows) - $limit;
				// 循环顺延名次
				foreach ($failRows as &$elem) {
					$elem['ranking'] = $elem['ranking'] + $backCount;
				}
			}
			
			// 将新淘汰的结果填充进淘汰结果中
			for($i = 0, $m = count($totalSuccessRows) - $limit; $i < $m; $i++) {
				array_push($failRows, $totalSuccessRows[$limit + $i]);
			}
			
			// 将新淘汰的结果从成功结果中淘汰
			for($i = 0, $m = count($totalSuccessRows) - $limit; $i < $m; $i++) {
				unset($totalSuccessRows[$limit + $i]);
			}
		}
		
		// 整合最终返回结果
		$return['success'] = $totalSuccessRows;
    	$return['fail'] = $failRows;
    	
    	// 返回结果
		return $return;
	}
    
}

?>