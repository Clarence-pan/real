<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/12/12
 * Time: 12:23 AM
 * Description: ReleaseSiteClsMod.php
 */
Yii::import('application.modules.bidmanage.models.iao.IaoReleaseMod');
Yii::import('application.modules.bidmanage.models.release.ReleaseCustom');
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');

class ReleaseSiteClsMod extends ReleaseCustom
{
    private $_iaoReleaseMod;
    private $_iaoBidProduct;

    function __construct()
    {
        parent::__construct();
        $this->_iaoReleaseMod = new IaoReleaseMod;
        $this->_iaoBidProduct = new BidProductIao;
    }

    /**
     * 执行发布操作
     *
     * @author chenjinlong 20121210
     * @return bool
     */
    public function runRelease()
    {
        //do release job
        //查询配置的容纳产品最大值
        $maxShowRec = $this->getAdPositionCountByType('class_recommend');
        //查询推广开始日期为当天的出价列表
        $bidProductArray = $this->_releaseProductMod->getProductArr('class_recommend');
        //将需要推广的产品按推广城市位置分组
        $bidProductGroups = $this->groupingReleaseProduct($bidProductArray);
        //用以保存组名
        $groupsArr = array();
        foreach ($bidProductGroups as $key => $bidProductGroup) {

            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '当前组为：' . $key, 11, 'wuke', 11, 0, json_encode($key));
            //避免同一组重复推广
            if (in_array($key, $groupsArr)) {
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '出现错误，该组多次进入循环：' . $key, 11, 'wuke', -11, 0, json_encode($key));
                break;
            } else {
                $groupsArr[] = $key;
            }

            //根据出发城市修改productId为从线路id
            //用以保存修改结果
            $bidProductArr = array();
            foreach ($bidProductGroup as $bidProductItem) {
				// 若为GT，则替换产品ID
	            if (1 == intval($bidProductItem['product_type'])) {
    	    	    $startCityParams = array(
        	            'productId' => $bidProductItem['product_id'],
		               );
        		    $startCityArr = $this->_iaoBidProduct->getAllProductStartCityArr($startCityParams);
					if(!empty($startCityArr)){
						foreach ($startCityArr as $startCityItem) {
	                	    if ($startCityItem['code'] == $bidProductItem['start_city_code']) {
	                    	    $bidProductItem['product_id'] = $startCityItem['productId'];
	                    	}
	                	}
					}
            	}
                $bidProductArr[] = $bidProductItem;
            }

            //过滤出有效的产品
            $preReleaseBidProductArr = $this->_releaseProductMod->filterValidProductArr($bidProductArr, $maxShowRec);

            if (!empty($preReleaseBidProductArr['invalid'])) {
                foreach($preReleaseBidProductArr['invalid'] as $invalidBidProduct){
                    $this->_agencyFailReleaseProductArr[] = $invalidBidProduct;
                }
                //更新出价表推广状态
                $this->runUpdateReleaseState($preReleaseBidProductArr['invalid'], '-2');
            }
            //转化产品列表数据结构，把打包的推广日期拆散
            $beforeFormattingProducts = $preReleaseBidProductArr['valid'];
            $formattedProducts = array();
            // 初始化空广告位的数组
            $imperfect = array();
            foreach ($beforeFormattingProducts as $product) {
				// 3.0的竞价广告位没有添加产品无需进行网站发布
				if(empty($product['product_type'])){
					array_push($imperfect, $product);
				} else {
					$i = $product['show_start_date'];
	                while ($i <= $product['show_end_date']) {
	                    $product['bid_date'] = $i;
	                    array_push($formattedProducts, $product);
	                    $bidDate = strtotime($i);
	                    $i = date('Y-m-d', mktime(0, 0, 0, date('m', $bidDate), date('d', $bidDate) + 1, date('Y', $bidDate)));
	                }
				}
            }

            //转换为接口约定的数据格式
            $releaseProductArr = self::reconstructApiParamsArray($formattedProducts);

            //推送到网站分类页发布
            $feedBackResultArr = $this->_iaoReleaseMod->pushRoutesIntoChannelAndClsSet($releaseProductArr);


            //推送后，新增推广表记录(逐条)
            if (!empty($imperfect)||!empty($feedBackResultArr)) {
                $exeFailReleaseArr = array();
                $exeSuccessReleaseArr = array();
                if (!empty($imperfect)||(!empty($feedBackResultArr['valid']) && is_array($feedBackResultArr['valid']))) {

                    //推送成功的bidId数组--去重
                    $feedBackResultArrRepeated = $feedBackResultArr['valid'];
                    $feedBackResultArrNonRepeated = array_unique($feedBackResultArrRepeated);
                    // 3.0的竞价广告位没有添加产品放入推广成功列表
                    if(!empty($imperfect)){
                    	foreach($imperfect as $im){
                    		$feedBackResultArrNonRepeated[] = $im['id'];
                    	}
                    }

                    foreach ($feedBackResultArrNonRepeated as $bidId) {
                        foreach ($bidProductArr as $relItems) {
                            if ($relItems['id'] == $bidId) {
                                //新增推广表记录
                                $showProductRecArr['account_id'] = $relItems['account_id'];
                                $showProductRecArr['product_id'] = $relItems['product_id'];
                                $showProductRecArr['product_type'] = $relItems['product_type'];
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
                                $showProductId = $this->_releaseProductMod->insertReleaseShowPrdArr($showProductRecArr);

                                //记录成功涉及供应商信息及数额
                                //先判断该产品是否已记录，防止重复
                                $tempArr = $this->_agencySucReleaseProductArr;
                                $successRecorded = 0;
                                foreach ($tempArr as $tempItem) {
                                    if ($tempItem['bid_id'] == $relItems['id']) {
                                        $successRecorded = 1;
                                    }
                                }
                                if ($successRecorded == 0) {
                                	// 计算竞价产品的总出价（包括：竞价产品价格+附加属性价格）
                                	$vasPrice = $this->countBidPrice($relItems['id']);
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
                                        'product_id' => $relItems['product_id'],
                                        'product_type' => $relItems['product_type'],
                                        'start_city_code' => $relItems['start_city_code'],
                                        'ad_key' => $relItems['ad_key'],
                                        'ranking' => $relItems['ranking'],
                                        'is_buyout' => $relItems['is_buyout'],
                                    );
                                    $this->_agencySucReleaseProductArr[] = $tempSuccessProduct;
                                    $exeSuccessReleaseArr[] = $tempSuccessProduct;
                                }
                                break;
                            }
                        }
                    }
                }
                if (!empty($feedBackResultArr['invalid']) && is_array($feedBackResultArr['invalid'])) {

                    //推送失败bidId去重
                    $feedBackResultArrRepeated = $feedBackResultArr['invalid'];
                    $feedBackResultArrNonRepeated = array_unique($feedBackResultArrRepeated);

                    foreach ($feedBackResultArrNonRepeated as $bidId) {
                        foreach ($releaseProductArr as $relItems) {
                            if ($relItems['bidId'] == $bidId) {
                                //记录失败涉及供应商信息及数额
                                //先判断是否已经记录，防止重复
                                $tempFailArr = $this->_agencyFailReleaseProductArr;
                                $failRecorded = 0;
                                foreach ($tempFailArr as $tempFailItem) {
                                    if ($tempFailItem['bid_id'] == $relItems['bidId']) {
                                        $failRecorded = 1;
                                    }
                                }
                                //判断在成功记录中是否存在，防止在成功、失败记录中同时存在
                                $tempArr = $this->_agencySucReleaseProductArr;
                                foreach ($tempArr as $tempItem) {
                                    if ($tempItem['bid_id'] == $relItems['bidId']) {
                                        $failRecorded = 1;
                                    }
                                }
                                if ($failRecorded == 0) {
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
                                    $this->_agencyFailReleaseProductArr[] = $tmpFailRelArr;
                                    $exeFailReleaseArr[] = $tmpFailRelArr;
                                }
                                break;
                            }
                        }
                    }
                }
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '推广-结果', 11, 'wenrui', 0, 0, json_encode($exeFailReleaseArr), json_encode($exeSuccessReleaseArr));
                //更新出价表推广状态-推广失败
                $this->runUpdateReleaseState($exeFailReleaseArr, '-2');
                //更新出价表推广状态-推广成功
                $this->runUpdateReleaseState($exeSuccessReleaseArr, '1');
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '本次分类页推广结束', 11, 'wenrui',0,0,'');
            }
        }
    }

    /**
     * 重新组织拼装数组，适用于调用CMS发布接口
     *
     * @author chenjinlong 20121211
     * @param $inArr
     * @return array()
     */
    protected static function reconstructApiParamsArray($inArr)
    {
        if (!empty($inArr) && is_array($inArr)) {
            $outArr = array();
            $formatRankArr = array();
            foreach ($inArr as $bidProductRow) {
                $startCityCode = $bidProductRow['start_city_code'] . '_' . $bidProductRow['web_class'];
                $productId = $bidProductRow['product_id'];

                $formatRankArr[$startCityCode][$productId] = array(
                    'accountId' => $bidProductRow['account_id'],
                    'productId' => $bidProductRow['product_id'],
                    'productType' => $bidProductRow['product_type'],
                    'showDate' => $bidProductRow['bid_date'],
                    'adKey' => $bidProductRow['ad_key'],
                    'catType' => $bidProductRow['cat_type'],
                    'webClass' => $bidProductRow['web_class'],
                    'startCityCode' => $bidProductRow['start_city_code'],
                    'search_keyword' => $bidProductRow['search_keyword'],
                    'bidId' => $bidProductRow['id'],
                    'bid_price' => $bidProductRow['bid_price'],
                    'max_limit_price' => $bidProductRow['max_limit_price'],
                    'bid_mark' => $bidProductRow['bid_mark'],
                    'ranking' => $bidProductRow['ranking'],
                    'login_name' => $bidProductRow['login_name'],
                    'is_buyout' => $bidProductRow['is_buyout'],
                );
//                $index = count($formatRankArr[$bidProductRow['start_city_code'] . '_' . $bidProductRow['web_class']]);
//                $formatRankArr[$startCityCode][$productId] = array_merge($formatRankArr[$startCityCode][$productId], array('ranking' => $index,));
                $outArr[] = $formatRankArr[$startCityCode][$productId];
            }
            return $outArr;
        } else {
            return array();
        }
    }
}
