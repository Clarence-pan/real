<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/11/12
 * Time: 2:40 PM
 * Description: ReleaseCustom.php
 */
Yii::import('application.modules.bidmanage.models.release.AbsRelease');
Yii::import('application.modules.bidmanage.models.iao.IaoFinanceMod');
Yii::import('application.modules.bidmanage.models.product.ReleaseProductMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.models.release.ReleaseMessageMod');
Yii::import('application.modules.bidmanage.dal.dao.product.ReleaseProductDao');
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');

class ReleaseCustom extends AbsRelease
{
    private $_releaseProductDao;
    
    //产品推广成功后的标志位
    const BID_MARK_SUC = 1;

    //财务退款之解冻操作流水号
    public $_fmisIdRefundArr;

    //推广成功所涉及供应商信息及数额
    public $_agencySucReleaseProductArr = array();

    //推广成失败所涉及供应商信息及数额
    public $_agencyFailReleaseProductArr = array();

    public $_iaoFinanceMod;

    public $_releaseProductMod;

    public $_manageMod;

    public $_productMod;
    
    public $_iaoBidProduct;

    function __construct()
    {
        $this->_iaoFinanceMod = new IaoFinanceMod;
        $this->_releaseProductMod = new ReleaseProductMod;
        $this->_manageMod = new UserManageMod;
        $this->_releaseProductDao = new ReleaseProductDao;
        $this->_productMod = new ProductMod;
        $this->_iaoBidProduct = new BidProductIao;
    }

    public function runRelease()
    {
        throw new Exception('Invoke is not correct!');
    }

    /**
     * 执行财务扣款操作
     *
     * @author chenjinlong 20121210
     */
    public function runDeduct()
    {
        //do deduct job
        $agencySucRelProductArr = $this->_agencySucReleaseProductArr;
        if (!empty($agencySucRelProductArr) && is_array($agencySucRelProductArr)) {
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '财务扣款-推广成功集合', 11, 'wuke', 19, sizeof($agencySucRelProductArr), $_SERVER['SERVER_ADDR']);
            foreach ($agencySucRelProductArr as $agencyItemArr) {
                //过滤已经推广成功的产品
                if (self::isReleaseSuccess($agencyItemArr['bid_mark'])) {
                    continue;
                }

                $showProductId = $agencyItemArr['serial_id'];

                //根据收客宝账户查询供应商编号
                $agencyId = $this->_manageMod->getVendorIdByAccountId($agencyItemArr['account_id']);
                //执行财务扣款
                $fmisParamsArr = array(
                    'agency_id' => $agencyId,
                    'amt' => floatval($agencyItemArr['amt_niu']+$agencyItemArr['vasPrice']),
                    'amt_coupon' => floatval($agencyItemArr['amt_coupon']),
                    'serial_id' => $showProductId,
                );
                // 财务扣费
                $fmisIdDeduct = $this->_iaoFinanceMod->bidSuccessFinanceDeduct($fmisParamsArr);
                
                if ($fmisIdDeduct) {
                    // 更新出价表和附加属表性推广之财务状态
                    $this->runUpdateFmisState(array($agencyItemArr), '1');

                    //执行财务退款之解冻金额--差额
                    $maxLimitPriceNiu = $agencyItemArr['max_limit_price_niu'];
                    $bidPriceNiu = $agencyItemArr['amt_niu'];
                    $maxLimitPriceCoupon = $agencyItemArr['max_limit_price_coupon'];
                    $bidPriceCoupon = $agencyItemArr['amt_coupon'];
                    $differenceNiu = $maxLimitPriceNiu - $bidPriceNiu;
                    $differenceCoupon = $maxLimitPriceCoupon - $bidPriceCoupon;
                    $fmisRefundParamsArr = array(
                        'agency_id' => $agencyId,
                        'amtNiu' => floatval($differenceNiu),
                        'amtCoupon' => floatval($differenceCoupon),
                    );
                    // 财务退冻结差额
                    $fmisIdRefund = $this->_iaoFinanceMod->bidFailFinanceRefund($fmisRefundParamsArr);
                    $this->_fmisIdRefundArr[] = $fmisIdRefund;
                }
                //更新推广表财务信息
                $udtParamsArr = array(
                    'fmis_id' => $fmisIdDeduct['niu'],
                    'fmis_id_coupon' => $fmisIdDeduct['coupon'],
                );
                $udtCondParamsArr = array(
                    'id' => $showProductId,
                );
                // 初始化管理员账号数组
        		$adArr = explode('@', $agencyItemArr['login_name']);
                // 判断是否需要向子账户扣费
                if (!empty($agencyItemArr['login_name']) && (0 != strcmp('admin', $adArr[0]) && 0 != strcmp($agencyItemArr['login_name'], $agencyId))) {
					// 初始化子账户扣费参数
					$deductParam['limit_amt_niu'] = $agencyItemArr['max_limit_price_niu']+$agencyItemArr['vasPrice'];
					$deductParam['amt_niu'] = $agencyItemArr['amt_niu']+$agencyItemArr['vasPrice'];
					$deductParam['limit_amt_coupon'] = $agencyItemArr['max_limit_price_coupon'];
					$deductParam['amt_coupon'] = $agencyItemArr['amt_coupon'];
					$deductParam['bid_id'] = $agencyItemArr['bid_id'];
					$deductParam['account_id'] = $agencyItemArr['account_id'];
					$deductParam['login_name'] = $agencyItemArr['login_name'];;
					$deductParam['agency_id'] = $agencyId;
					$deductParam['search_keyword'] = $agencyItemArr['search_keyword'];
                    $deductParam['web_class'] = $agencyItemArr['web_class'];
                    $deductParam['product_id'] = $agencyItemArr['product_id'];
                    $deductParam['product_type'] = $agencyItemArr['product_type'];
                    $deductParam['start_city_code'] = $agencyItemArr['start_city_code'];
                    $deductParam['ad_key'] = $agencyItemArr['ad_key'];
                    $deductParam['ranking'] = $agencyItemArr['ranking'];
					// 向子账户扣费和解冻
                	$this->_manageMod->dedcutSubAgency($deductParam);
                }
                $this->_releaseProductMod->updateShowPrdArrayAftDeduct($udtParamsArr, $udtCondParamsArr);
            }
        } else {
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '财务扣款-推广成功集合空', 11, 'wuke', 16, 0,'');
        }
    }

    /**
     * 执行退款解除冻结操作
     *
     * @author chenjinlong 20121210
     */
    public function runRefund()
    {
        //do refund job
        $agencyFailRelProductArr = $this->_agencyFailReleaseProductArr;
        if (!empty($agencyFailRelProductArr) && is_array($agencyFailRelProductArr)) {
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '财务解冻-推广失败集合', 11, 'wuke', -19, sizeof($agencyFailRelProductArr),$_SERVER['SERVER_ADDR'],'',json_encode($agencyFailRelProductArr));
            foreach ($agencyFailRelProductArr as $agencyItemArr) {
                //过滤已经推广成功的产品
                if (self::isReleaseSuccess($agencyItemArr['bid_mark'])) {
                    continue;
                }

                //根据收客宝账户查询供应商编号
                $agencyId = $this->_manageMod->getVendorIdByAccountId($agencyItemArr['account_id']);
                //执行财务退款之解冻金额
                $fmisParamsArr = array(
                    'agency_id' => $agencyId,
                    'amtNiu' => floatval($agencyItemArr['amt_niu']+$agencyItemArr['vasPrice']),
                    'amtCoupon' => floatval($agencyItemArr['amt_coupon']),
                );
                $fmisIdRefund = $this->_iaoFinanceMod->bidFailFinanceRefund($fmisParamsArr);
                // 初始化管理员账号数组
        		$adArr = explode('@', $agencyItemArr['login_name']);
                // 判断是否需要向子账户解冻
                if (!empty($agencyItemArr['login_name']) && (0 != strcmp('admin', $adArr[0]) && 0 != strcmp($agencyItemArr['login_name'], $agencyId))) {
					// 初始化子账户扣费参数
					$unfreezeParam['amt_niu'] = $agencyItemArr['amt_niu']+$agencyItemArr['vasPrice'];
					$unfreezeParam['amt_coupon'] = $agencyItemArr['amt_coupon'];
					$unfreezeParam['bid_id'] = $agencyItemArr['bid_id'];
					$unfreezeParam['account_id'] = $agencyItemArr['account_id'];
					$unfreezeParam['login_name'] = $agencyItemArr['login_name'];
					$unfreezeParam['agency_id'] = $agencyId;
					$unfreezeParam['search_keyword'] = $agencyItemArr['search_keyword'];
                    $unfreezeParam['web_class'] = $agencyItemArr['web_class'];
                    $unfreezeParam['product_id'] = $agencyItemArr['product_id'];
                    $unfreezeParam['product_type'] = $agencyItemArr['product_type'];
                    $unfreezeParam['start_city_code'] = $agencyItemArr['start_city_code'];
                    $unfreezeParam['ad_key'] = $agencyItemArr['ad_key'];
                    $unfreezeParam['ranking'] = $agencyItemArr['ranking']; 
					// 向子账户扣费和解冻
       	        	$this->_manageMod->unfreezeSubAgency($unfreezeParam);
           	    }
                $this->_fmisIdRefundArr[] = $fmisIdRefund;
                if ($fmisIdRefund) {
                    //更新出价表推广之财务状态
                    $this->runUpdateFmisState(array($agencyItemArr), '-1');
                }
            }
        } else {
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '财务解冻-推广失败集合空', 11, 'wuke', 17, 0,'');
        }
    }

    /**
     * 更新出价表之推广状态
     *
     * @author chenjinlong 20121211
     * @param $bidIdInfoArr
     * @param $tgtStateDigit
     */
    public function runUpdateReleaseState($bidIdInfoArr, $tgtStateDigit)
    {
        if (!empty($bidIdInfoArr)) {
            foreach ($bidIdInfoArr as $value) {
                //过滤已经推广成功的产品
                if (self::isReleaseSuccess($value['bid_mark'])) {
                    continue;
                }

                $this->_releaseProductMod->updateRelStateAftRelease($value['bid_id'], $tgtStateDigit);
            }
        }
    }

    /**
     * 更新出价表和附加属表性推广之财务状态
     *
     * @author chenjinlong 20121211
     * @param $bidIdInfoArr
     * @param $tgtStateDigit
     */
    public function runUpdateFmisState($bidIdInfoArr, $tgtStateDigit)
    {
        if (!empty($bidIdInfoArr)) {
            foreach ($bidIdInfoArr as $value) {
                //过滤已经推广成功的产品
                if (self::isReleaseSuccess($value['bid_mark'])) {
                    continue;
                }
                $this->_releaseProductMod->updateFmisStateAftRelease($value['bid_id'], $tgtStateDigit);
            }
        }
    }

    /**
     * 判定该产品是否已经推广成功
     *
     * @author chenjinlong 20121214
     * @param $bidMarkVal
     * @return bool
     */
    public static function isReleaseSuccess($bidMarkVal)
    {
        return ($bidMarkVal == self::BID_MARK_SUC) ? true : false;
    }

    /**
     * 查询广告位容纳的产品数量-控制参数
     *
     * @author chenjinlong 20130109
     * @param $adKeyStr
     * @return int
     */
    public function getAdPositionCountByType($adKeyObj)
    {
        $adPositionCount = $this->_manageMod->getAdPositionCountByType($adKeyObj);
        if (!empty($adPositionCount)) {
            return $adPositionCount;
        } else {
            return 0;
        }
    }

	/**
	 * 推广数据按关键字分组
	 */
    public function groupingReleaseProduct($srcRelProductArr)
    {
        $group = array();
        foreach ($srcRelProductArr as $bid) {
            $groupKey = $this->getGroupKey($bid);
            $group[$groupKey][] = $bid;
        }
        return $group;
    }
	
	/**
	 * 返回分组关键字
	 */ 
    function  getGroupKey($bid)
    {
        $groupKey = '';
        switch($bid['ad_key']){
        	// 分类页
        	case 'class_recommend':
        		$groupKey = $bid['start_city_code'] . '_' . $bid['web_class'];
        		break;
    		// 频道页
        	case 'channel_hot':
        		$groupKey = $bid['start_city_code'] . '_' . $bid['cat_type'];
        		break;
        	// 搜索页
        	case 'search_complex':
        		$groupKey = $bid['start_city_code'] . '_' . $bid['search_keyword'];
        		break;
        	// 其他
        	default:
        		$groupKey = $bid['start_city_code'];
        		break;
        }
        return $groupKey;
    }
    
    /**
	 * 计算附加属性金额
	 * 
	 * @author wenrui
	 */
	public function countBidPrice($id){
		// 查看产品的是否有附加属性
    	$productVas = $this->_releaseProductDao->isExistProductVas($id);
    	// 初始化竞价总额
    	$bidPrice = 0;
    	// 算出附加属性总额
    	if(!empty($productVas)){
    		foreach($productVas as $vas){
        		$bidPrice += $vas['bid_price'];
        	}
    	}
    	return $bidPrice;
	}
	
	/**
	 * 广告位数组
	 * 
	 * @author wenrui 2014-04-25
	 * 
	 */
	public $adKey = array(
		// 首页
		'1'=>'index_chosen',
		// 分类页
		'3'=>'class_recommend',
		// 搜索页
		'4'=>'search_complex',
		// 专题页
		'5'=>'special_subject',
		// 品牌专区
		'6'=>'brand_zone',
		// 
		'7'=>'index_chosen_abroad',
		//
		'8'=>'index_chosen_abroad_long',
		//
		'9'=>'index_chosen_abroad_short',
		//
		'10'=>'index_chosen_around',
		//
		'11'=>'index_chosen_domestic',
		//
		'12'=>'index_chosen_ticket',
	);
	
	/**
	 * 处理推广的位置
	 * 
	 * @author wenrui 2014-04-25
	 * 
	 */
	public function releaseAdKey(){
		$adKeyArr = array();
		// 推广所有位置
		if(RELEASE_TYPE == 0){
			$adKeyArr = $this->_productMod->getReleaseAdKeyInfo();
		}else{
			foreach($this->adKey as $key => $value){
				// 推广指定位置
				if(RELEASE_TYPE == $key){
					array_push($adKeyArr,$value);
					break;
				}
			}
		}
		return $adKeyArr;
	}

	/**
	 * 跟团产品需要替换产品id为网站的线路id作为唯一标识
	 * 
	 * @author wenrui 2014-04-25
	 */
//	public function routeStartCityCode($bidProductGroup){
//		foreach($bidProductGroup as $bidProductItem){
//            // 若为GT，则替换产品ID
//            if (1 == intval($bidProductItem['product_type'])) {
//                $startCityParams = array(
//                    'productId' => $bidProductItem['product_id'],
//                );
//                $startCityArr = $this->_iaoBidProduct->getAllProductStartCityArr($startCityParams);
//                if(!empty($startCityArr)){
//                    foreach($startCityArr as $startCityItem){
//                        if($startCityItem['code'] == $bidProductItem['start_city_code']){
//                            $bidProductItem['product_id'] = $startCityItem['productId'];
//                        }
//                    }
//                }
//            }
//            $bidProductArr[] = $bidProductItem;
//        }
//        return $bidProductArr;
//	}
	
    /**
     * 重新组织拼装数组，适用于调用发布接口
     *
     * @author wenrui 2014-04-25
     */
    public function reconstructApiParamsArray($inArr)
    {
        if (!empty($inArr) && is_array($inArr)) {
            $outArr = array();
            $tmpArr = array();
            //重置推送给网站的排名数值
            foreach ($inArr as $bidProductRow) {
                // 当为分类页时添加web_class
                if ('class_recommend' == $bidProductRow['ad_key']) {
                    $startCityCode = $bidProductRow['start_city_code'] . '_' . $bidProductRow['web_class'];
                } else {
                    $startCityCode = $bidProductRow['start_city_code'];
                }
                $productId = $bidProductRow['product_id'];

                $tmpArr[$startCityCode][$productId] = array(
                    'accountId' => $bidProductRow['account_id'],
                    'productId' => $bidProductRow['product_id'],
                    'productType' => $bidProductRow['product_type'],
                    'showDate' => $bidProductRow['bid_date'],
                    'adKey' => $bidProductRow['ad_key'],
                    'catType' => $bidProductRow['cat_type'],
                    'webClass' => $bidProductRow['web_class'],
                    'startCityCode' => $bidProductRow['start_city_code'],
                    'bidId' => $bidProductRow['id'],
                    'bid_price' => $bidProductRow['bid_price'],
                    'max_limit_price' => $bidProductRow['max_limit_price'],
                    'bid_mark' => $bidProductRow['bid_mark'],
                    'search_keyword' => $bidProductRow['search_keyword'],
                    'ranking' => $bidProductRow['ranking'],
                    'login_name' => $bidProductRow['login_name'],
                    'is_buyout' => $bidProductRow['is_buyout'],
                );
                //结果接口格式结构化
                $outArr[] = $tmpArr[$startCityCode][$productId];
            }
            return $outArr;
        } else {
            return array();
        }
    }
    
    /**
     * 插入推广表的数据封装
     * 
     * @author wenrui 2014-04-25
     */
    public function reconstructShowProductArray($relItems,$adKey){
    	// 新增推广表记录
        // 品牌专区时产品编号插入供应商编号，产品类型插入500
        if ('brand_zone' == $adKey) {
            $manageMod = new UserManageMod();
            $accountInfo = $manageMod->read(array('id'=>$relItems['account_id']));
            $showProductRecArr['product_id'] = $accountInfo['vendorId'];
            $showProductRecArr['product_type'] = '500';
        } else {
            $showProductRecArr['product_id'] = $relItems['product_id'];
            $showProductRecArr['product_type'] = $relItems['product_type'];
        }
        $showProductRecArr['account_id'] = $relItems['account_id'];
        $showProductRecArr['show_date_id'] = $relItems['show_date_id'];
        $showProductRecArr['ad_key'] = $relItems['ad_key'];
        $showProductRecArr['cat_type'] = $relItems['cat_type'];
        $showProductRecArr['web_class'] = $relItems['web_class'];
        $showProductRecArr['start_city_code'] = $relItems['start_city_code'];
        $showProductRecArr['bid_price'] = $relItems['bid_price'];
        $showProductRecArr['ranking'] = $relItems['ranking'];
        $showProductRecArr['bid_id'] = $relItems['id'];
        $showProductRecArr['search_keyword'] = $relItems['search_keyword'];
        $showProductRecArr['bid_price_coupon'] = $relItems['bid_price_coupon'];
        $showProductRecArr['bid_price_niu'] = $relItems['bid_price_niu'];
        $showProductRecArr['is_buyout'] = $relItems['is_buyout'];
        return $showProductRecArr;
    }
    
    /**
     * 放入推广成功数组集合的数据封装
     * 
     * @author wenrui 2014-04-25
     */
    public function reconstructBidSuccessArray($relItems,$adKey,$showProductId){
    	// 计算竞价产品的总出价（包括：竞价产品价格+附加属性价格）
        $vasPrice = $this->countBidPrice($relItems['id']);
        // 品牌专区时产品编号插入供应商编号，产品类型插入500
        $manageMod = new UserManageMod();
        $accountInfo = $manageMod->read(array('id'=>$relItems['account_id']));
        $tempSuccessProduct = array(
            'account_id' => $relItems['account_id'],
            'amt' => $relItems['bid_price']+$vasPrice,
            'max_limit_price' => $relItems['max_limit_price']+$vasPrice,
            'amt_niu' => $relItems['bid_price_niu'],
            'max_limit_price_niu' => $relItems['max_limit_price_niu'],
            'amt_coupon' => $relItems['bid_price_coupon'],
            'max_limit_price_coupon' => $relItems['max_limit_price_coupon'],
            'vasPrice' => $vasPrice,
            'serial_id' => $showProductId,
            'bid_id' => $relItems['id'],
            'bid_mark' => $relItems['bid_mark'],
            'login_name' => $relItems['login_name'],
            'search_keyword' => $relItems['search_keyword'],
            'web_class' => $relItems['web_class'],
            'product_id' => ('brand_zone' == $adKey) ? $accountInfo['vendorId'] : $relItems['product_id'],
            'product_type' => ('brand_zone' == $adKey) ? 500 : $relItems['product_type'],
            'start_city_code' => $relItems['start_city_code'],
            'ad_key' => $relItems['ad_key'],
            'ranking' => $relItems['ranking'],
            'is_buyout' => $relItems['is_buyout'],
        );
        return $tempSuccessProduct;
    }
    
    /**
     * 放入推广失败数组集合的数据封装
     * 
     * @author wenrui 2014-04-25
     */
    public function reconstructionBidFailArray($relItems){
    	// 计算竞价产品的总出价（包括：竞价产品价格+附加属性价格）
        $vasPrice = $this->countBidPrice($relItems['bidId']);
        $tmpFailRelArr = array(
            'account_id' => $relItems['accountId'],
            'amt' => $relItems['max_limit_price']+$vasPrice,
            'amt_niu' => $relItems['max_limit_price_niu'],
            'amt_coupon' => $relItems['max_limit_price_coupon'],
            'vasPrice' => $vasPrice,
            'bid_id' => $relItems['bidId'],
            'bid_mark' => $relItems['bid_mark'],
            'login_name' => $relItems['login_name'],
            'search_keyword' => $relItems['search_keyword'],
            'web_class' => $relItems['web_class'],
            'product_id' => $relItems['product_id'],
            'product_type' => $relItems['product_type'],
            'start_city_code' => $relItems['start_city_code'],
            'ad_key' => $relItems['ad_key'],
            'ranking' => $relItems['ranking'],
            'is_buyout' => $relItems['is_buyout'],
        );
        return $tmpFailRelArr;
    }
    
    /**
     * 把推广数据根据打包计划按日分割
     * 
     * @author wenrui 2014-04-25
     */
    public function splitDate($beforeFormattingProducts){
    	// 初始化推广至网站的数组
    	$formattedProducts = array();
        // 初始化空广告位的数组
        $imperfect = array();
    	foreach($beforeFormattingProducts as $product){
            // 3.0的竞价广告位没有添加产品无需进行网站发布
            if(empty($product['product_type']) || 500 == $product['product_type']){
                array_push($imperfect, $product);
            } else {
                $i = $product['show_start_date'];
                while($i <= $product['show_end_date']){
                    $product['bid_date'] = $i;
                    array_push($formattedProducts,$product);
                    $bidDate = strtotime($i);
                    $i = date('Y-m-d',mktime(0,0,0,date('m',$bidDate),date('d',$bidDate)+1,date('Y',$bidDate)));
                }
            }
        }
        return array('push'=>$formattedProducts,'not_push'=>$imperfect);
    }
}
