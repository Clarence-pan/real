<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 13-12-10
 * @time 下午4:15
 * @description BidAdminMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');
Yii::import('application.modules.bidmanage.dal.iao.TuniuIao');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');

class BidAdminMod 
{
    private $_userManageDao;

    private $_bidProductDao;

    private $_financeIao;

    private $_tuniuIao;

    private $_packageDateDao;

    private $_productDao;

    private $_iaoProductMod;

    public function __construct()
    {
        $this->_userManageDao = new UserManageDao();
        $this->_bidProductDao = new BidProductDao();
        $this->_financeIao = new FinanceIao();
        $this->_tuniuIao = new TuniuIao();
        $this->_packageDateDao = new PackageDateDao();
        $this->_productDao = new ProductDao();
        $this->_iaoProductMod = new IaoProductMod();
    }

    /**
     * 管理员专用：解决招客宝账户冻结款项异常的问题
     *
     * @author chenjinlong 20131210
     * @param $initAccountId
     * @param $isExec
     * @return mixed
     */
    public function fixFmisFreezeErrorProcess($initAccountId, $isExec = 0)
    {
        $initAccountId = intval($initAccountId);
        /**
         * 是否订正所有的招客宝正常账户的款项
         */
        $accountIdArr = array();
        // 传入id=-100，则修正所有招客宝供应商
        if($initAccountId == -100){
            $inUseAccountIdArr = $this->_userManageDao->getAllInUseAccountIdArr();
            if(!empty($inUseAccountIdArr) && is_array($inUseAccountIdArr)){
                $accountIdArr = $inUseAccountIdArr;
            }else{
                // Skip and do nothing
                return;
            }
        }else{
            $accountIdArr[] = $initAccountId;
        }

        /**
         * 收集函数返回结果
         */
        $result = array(
            'deduct_niu' => array(),
            'refund_niu' => array(),
            'deduct_coupon' => array(),
            'refund_coupon' => array(),
            'fmis_before_exec' => array(),
        );
        foreach($accountIdArr as $accountId)
        {

            /**
             * 查询招客宝帐号关联的供应商编号
             */
            $queryAccountParams = array(
                'id' => $accountId,
            );
            $accountInfo = $this->_userManageDao->readUser($queryAccountParams);
            $agencyId = intval($accountInfo['vendorId']);

            /**
             * 查询当前财务账户款项详情
             * Return Contains Keys:
             * "controlMoney":可支配余额
             * "currentMoney":财务余额（包含冻结款项）
             */
            $currentFmisInfo = FinanceIao::getAccountAvailableBalance($agencyId);
            $currentMoney = round($currentFmisInfo['currentMoney']);
            $controlMoney = round($currentFmisInfo['controlMoney']);
            $couponCurrentMoney = round($currentFmisInfo['couponCurrentMoney']);
            $couponControlMoney = round($currentFmisInfo['couponControlMoney']);

            /**
             * 查询当前帐号竞价正确冻结金额
             */
            $queryBidRowsParams = array(
                'account_id' => $accountId,
                'bid_mark' => 2,
                'fmis_mark' => 0,
                'is_new_bb_version' => 1,
            );
            $bidBidProductRows = $this->_bidProductDao->getBidRecords($queryBidRowsParams);
            // 牛币
            $totalCorrectFreezeNiuVal = 0;
            // 赠币
            $totalCorrectFreezeCouponVal = 0;
            if(!empty($bidBidProductRows)){
                foreach($bidBidProductRows as $bidRow)
                {
                    $totalCorrectFreezeNiuVal += $bidRow['max_limit_price_niu'];
                    $totalCorrectFreezeCouponVal += $bidRow['max_limit_price_coupon'];
                }
            }

            /**
             * 退款 OR 扣款
             */
            $judgeNiuVal = ($currentMoney - $controlMoney) - $totalCorrectFreezeNiuVal;
            $judgeCouponVal = ($couponCurrentMoney - $couponControlMoney) - $totalCorrectFreezeCouponVal;

            /**
             * 收集异常招客宝财务账户数据
             */
            if($judgeNiuVal != 0 || $judgeCouponVal != 0){
                $result['fmis_before_exec'][] = array(
                    'account_id' => $accountId,
                    'agency_id' => $agencyId,
                    'current_money' => $currentMoney,
                    'control_money' => $controlMoney,
                    'total_correct_freeze_niu_money' => $totalCorrectFreezeNiuVal,
                    'coupon_current_money' => $couponCurrentMoney,
                    'coupon_control_money' => $couponControlMoney,
                    'coupon_total_correct_freeze_money' => $totalCorrectFreezeCouponVal,
                );
            }
			// 牛币修正
            if($judgeNiuVal > 0){
                $refundParams = array(
                    'agency_id' => $agencyId,
                    'amtNiu' => $judgeNiuVal,
                    'amtCoupon' => 0,
                );
                if($isExec == 1){
                    $refundApiResult = $this->_financeIao->bidFailFinance($refundParams);
                    if($refundApiResult){
                        $isRefundSuccessStr = 'SUCCESS';
                    }else{
                        $isRefundSuccessStr = 'FAIL';
                    }
                    CommonSysLogMod::log('AdminController', 'FIX-牛币解冻退款', 2, 'chenjinlong', $accountId, $agencyId, $judgeNiuVal, $isRefundSuccessStr);
                }else{
                    $isRefundSuccessStr = 'CHECK';
                }
                $refundParams['flag'] = $isRefundSuccessStr;
                $result['refund_niu'][] = $refundParams;
            }elseif($judgeNiuVal < 0){
                $deductParams = array(
                    'agency_id' => $agencyId,
                    'amtNiu' => -$judgeNiuVal,
                    'amtCoupon' => 0,
                );
                if($isExec == 1){
                    $deductApiResult = FinanceIao::bidCutFinanceNew($agencyId, -$judgeNiuVal, 0, 0, 0);
                    if($deductApiResult['success']){
                        $isDeductSuccessStr = 'SUCCESS';
                    }else{
                        $isDeductSuccessStr = 'FAIL';
                    }
                    CommonSysLogMod::log('AdminController', 'FIX-牛币冻结差额', 2, 'chenjinlong', $accountId, $agencyId, -$judgeNiuVal, $isDeductSuccessStr);
                }else{
                    $isDeductSuccessStr = 'CHECK';
                }
                $deductParams['flag'] = $isDeductSuccessStr;
                $result['deduct_niu'][] = $deductParams;
            }
            if($judgeCouponVal > 0){
            	// 赠币修正
            	$refundParams = array(
                    'agency_id' => $agencyId,
                    'amtNiu' => 0,
                    'amtCoupon' => $judgeCouponVal,
                );
                if($isExec == 1){
                    $refundApiResult = $this->_financeIao->bidFailFinance($refundParams);
                    if($refundApiResult){
                        $isRefundSuccessStr = 'SUCCESS';
                    }else{
                        $isRefundSuccessStr = 'FAIL';
                    }
                    CommonSysLogMod::log('AdminController', 'FIX-赠币解冻退款', 2, 'chenjinlong', $accountId, $agencyId, $judgeCouponVal, $isRefundSuccessStr);
                }else{
                    $isRefundSuccessStr = 'CHECK';
                }
                $refundParams['flag'] = $isRefundSuccessStr;
                $result['refund_coupon'][] = $refundParams;
            }elseif($judgeCouponVal < 0){
            	$deductParams = array(
                    'agency_id' => $agencyId,
                    'amtNiu' => 0,
                    'amtCoupon' => -$judgeCouponVal,
                );
                if($isExec == 1){
                    $deductApiResult = FinanceIao::bidCutFinanceNew($agencyId, 0, 0, -$judgeCouponVal, 0);
                    if($deductApiResult['success']){
                        $isDeductSuccessStr = 'SUCCESS';
                    }else{
                        $isDeductSuccessStr = 'FAIL';
                    }
                    CommonSysLogMod::log('AdminController', 'FIX-赠币冻结差额', 2, 'chenjinlong', $accountId, $agencyId, -$judgeCouponVal, $isDeductSuccessStr);
                }else{
                    $isDeductSuccessStr = 'CHECK';
                }
                $deductParams['flag'] = $isDeductSuccessStr;
                $result['deduct_coupon'][] = $deductParams;
            }else{
                // Skip and do nothing
            }
        }
        return $result;
    }

    /**
     * 同步首页广告位置类型
     *
     * @author chenjinlong 20140512
     * @param $startCityCode
     * @param $isExec
     * @return array
     */
    public function synTuniuAdPosType($startCityCode, $isExec)
    {
        $cityCodeArr = array();
        if($startCityCode == -100){
            $departureRows = $this->_productDao->getAllDepartureCityInfo('');
            foreach($departureRows as $row)
            {
                $cityCodeArr[] = $row['code'];
            }
        }else{
            $cityCodeArr[] = $startCityCode;
        }

        $synResult = array(
            'isExec' => $isExec,
        );

        // 查询出发城市
        $memcacheKey = md5('CommonController.doRestGetStartCity');
        $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
        if(!empty($finalBeginCityResult) && !empty($finalBeginCityResult['major'])){
            $startCityInfo = $finalBeginCityResult;
        } else {
            $startCityInfo = $this->_iaoProductMod->getMultiCityInfo();
        }
        $isMajor = 0;
        foreach($cityCodeArr as $code)
        {
            $newAdKeyList = $this->_tuniuIao->getTuniuAdListAsOneArray($code);
            // 判断出发城市是否是主营城市
            if ($startCityInfo['major']) {
                foreach ($startCityInfo['major'] as $tempArr) {
                    if ($tempArr['code'] == $code) {
                        $isMajor = 1;
                        break;
                    } else {
                        $isMajor = 0;
                    }
                }
            }
            /**
             * 对已有数据作新增、更新
             */
            foreach($newAdKeyList as $eachAdPosType)
            {
                $existBaAdPosTypeInfo = $this->_productDao->queryAdKeyInfo($eachAdPosType['ad_key'],$code);
                if(empty($existBaAdPosTypeInfo)){

                    if ($isExec == 1) {
                        //更新ba_ad_position_type(先逻辑删除,后新增)
                        $deleteParams = array(
                            'startCityCode' => $eachAdPosType['start_city_code'],
                            'adName' => $eachAdPosType['ad_name'],
                            'misc' => '删除同分站同名TAB',
                        );
                        $this->_packageDateDao->postAdDelByName($deleteParams);

                        $insertParams = array(
                            'adKey' => $eachAdPosType['ad_key'],
                            'adName' => $eachAdPosType['ad_name'],
                            'startCityCode' => $eachAdPosType['start_city_code'],
                            'categoryId' => $eachAdPosType['category_ids'],
                            'classBrandTypes' => $eachAdPosType['class_brand_types'],
                            'catType' => $eachAdPosType['cat_types'],
                            'addUid' => 2820,
                            'addTime' => date('Y-m-d H:i:s'),
                            'misc' => '新增网站新TAB',
                            'isMajor' => $isMajor,
                            'adKeyType' => 1,// 首页类型为1
                        );
                        $adPositionTypeId = $this->_packageDateDao->postAdAddNew($insertParams);
                    }

                    $synResult[$code]['create'][] = array(
                        'action' => 'create',
                        'adKey' => $eachAdPosType['ad_key'],
                        'adName' => $eachAdPosType['ad_name'],
                        'startCityCode' => $eachAdPosType['start_city_code'],
                        'generateId' => $adPositionTypeId,
                    );
                }elseif($existBaAdPosTypeInfo[0]['start_city_code'] == $eachAdPosType['start_city_code'] &&
                    $existBaAdPosTypeInfo[0]['ad_key'] == $eachAdPosType['ad_key']){
                    if ($isExec == 1) {
                        $conditionParams = array(
                            'start_city_code' => $eachAdPosType['start_city_code'],
                            'ad_key' => $eachAdPosType['ad_key'],
                        );
                        $updateParams = array(
                            'ad_name' => $eachAdPosType['ad_name'],
                            'category_ids' => $eachAdPosType['category_ids'],
                            'class_brand_types' => $eachAdPosType['class_brand_types'],
                            'cat_types' => $eachAdPosType['cat_types'],
                            'uid' => 2820,
                            'misc' => '同步分类字段',
                            'isMajor' => $isMajor,
                            'adKeyType' => 1,// 首页类型为1
                        );
                        $this->_packageDateDao->updateAdPositionType($updateParams, $conditionParams);
                    }
                    $synResult[$code]['update'][] = array(
                        'action' => 'update',
                        'adKey' => $eachAdPosType['ad_key'],
                        'adName' => $eachAdPosType['ad_name'],
                        'startCityCode' => $eachAdPosType['start_city_code'],
                        'tgtCategoryIds' => $eachAdPosType['category_ids'],
                        'tgtClassBrandTypes' => $eachAdPosType['class_brand_types'],
                    );
                }
            }

            /**
             * 对已有数据作删除操作
             */
            $queryAdPosTypeListCond = array(
                'del_flag' => 0,
                'start_city_code' => $code,
            );
            $adPosTypeList = $this->_packageDateDao->getAdPositionTypeListByCond($queryAdPosTypeListCond);
            foreach($adPosTypeList as $eachTypeItem)
            {
                $isExist = 0;
                foreach($newAdKeyList as $tuniuAdList)
                {
                    if(($eachTypeItem['ad_key'] == $tuniuAdList['ad_key'] &&
                        $eachTypeItem['start_city_code'] == $tuniuAdList['start_city_code']) ||
                        in_array($eachTypeItem['ad_key'], array('class_recommend','search_complex','special_subject', 'brand_zone', 'index_chosen',))){
                        $isExist = 1;
                        break;
                    }
                }
                if($isExist == 0){
                    if($isExec == 1){
                        $delParams = array(
                            'startCityCode' => $code,
                            'adKey' => $eachTypeItem['ad_key'],
                            'misc' => '网站TAB已删除',
                        );
                        $this->_packageDateDao->postAdDel($delParams);
                    }

                    $synResult[$code]['delete'][] = array(
                        'action' => 'delete',
                        'adKey' => $eachTypeItem['ad_key'],
                        'adName' => $eachTypeItem['ad_name'],
                        'startCityCode' => $code,
                    );
                }
            }
        }
        return $synResult;
    }

    /**
     * 同步首页广告位置类型
     *
     * @author chenjinlong 20140512
     * @param $startCityCode
     * @param $isExec
     * @return array
     */
    public function synTuniuOneAdPosType($startCityCode, $adKey, $isExec)
    {
        $adList = array();
        $result = array();
        $synResult = array(
            'isExec' => $isExec,
        );
        if (strpos($adKey,'index_chosen') !== false) {
            $adList = TuniuIao::getAdAddList($startCityCode);
        } elseif (strpos($adKey,'channel_chosen') !== false) {
            $adList = TuniuIao::getTuniuChannelAdList($startCityCode);
        }
        if ($adList) {
            $adKeyTemp = '';
            // 循环拼接数据
            foreach ($adList as $tempAdArr) {
                $header = $tempAdArr['header'];
                $items = $tempAdArr['items'];
                foreach ($items as $tempItemsArr) {
                    if (strpos($adKey,'index_chosen') !== false) {
                        $adKeyTemp = 'index_chosen' . '_' . $header['id'] . '_' . $tempItemsArr['id'];
                        $result['adName'] = '首页' . '-' . $header['title'] . '-' . $tempItemsArr['title'];
                        $result['adKeyType'] = 1;
                    } else if (strpos($adKey,'channel_chosen') !== false) {
                        $adKeyTemp = 'channel_chosen' . '_' . $header['id'] . '_' . $tempItemsArr['id'];
                        $result['adName'] = '频道页' . '-' . $header['title'] . '频道页' . '-' . $tempItemsArr['title'];
                        $result['adKeyType'] = 5;
                        $result['channelId'] = $header['id'];
                        $result['channelName'] = $header['title'];
                        $result['blockId'] = $tempItemsArr['id'];
                        $result['blockName'] = $tempItemsArr['title'];
                    }
                    if ($adKeyTemp == $adKey) {
                        $result['adKey'] = $adKey;
                        $result['startCityCode'] = $startCityCode;
                        //过滤ROR产品类型
                        $srcClassBrandTypes = !empty($tempItemsArr['classify']) ? explode(',', $tempItemsArr['classify']) : array();
                        $intersectClassBrandTypes = array_intersect($srcClassBrandTypes, array(1, 10, 12));
                        $result['classBrandTypes'] = json_encode($intersectClassBrandTypes);
                        $result['categoryId'] = !empty($tempItemsArr['destination']) ? json_encode(explode(',', $tempItemsArr['destination'])) : '[]';
                        $result['catType'] = !empty($header['catType']) ? json_encode($header['catType']) : '[]';
                        break;
                    }
                }
            }

            // 查询出发城市
            $memcacheKey = md5('CommonController.doRestGetStartCity');
            $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
            if(!empty($finalBeginCityResult) && !empty($finalBeginCityResult['major'])){
                $startCityInfo = $finalBeginCityResult;
            } else {
                $startCityInfo = $this->_iaoProductMod->getMultiCityInfo();
            }
            $isMajor = 0;
            // 判断出发城市是否是主营城市
            if ($startCityInfo['major']) {
                foreach ($startCityInfo['major'] as $tempArr) {
                    if ($tempArr['code'] == $startCityCode) {
                        $isMajor = 1;
                        break;
                    } else {
                        $isMajor = 0;
                    }
                }
            }

            $existBaAdPosTypeInfo = $this->_productDao->queryAdKeyInfo($adKey,$startCityCode);
            if(empty($existBaAdPosTypeInfo)){
                if ($isExec == 1) {
                    //更新ba_ad_position_type(先逻辑删除,后新增)
                    $deleteParams = array(
                        'startCityCode' => $startCityCode,
                        'adName' => $result['adName'],
                        'misc' => '删除同预定城市同名广告位',
                    );
                    $this->_packageDateDao->postAdDelByName($deleteParams);

                    $insertParams = array(
                        'adKey' => $adKey,
                        'adName' => $result['adName'],
                        'startCityCode' => $startCityCode,
                        'categoryId' => $result['categoryId'],
                        'classBrandTypes' => $result['classBrandTypes'],
                        'catType' => $result['catType'],
                        'addUid' => 4272,
                        'addTime' => date('Y-m-d H:i:s'),
                        'misc' => '新增网站新广告位',
                        'channelId' => $result['channelId'] ? $result['channelId'] : 0,
                        'channelName' => $result['channelName'] ? $result['channelName'] : '',
                        'blockId' => $result['blockId'] ? $result['blockId'] : 0,
                        'blockName' => $result['blockName'] ? $result['blockName'] : '',
                        'isMajor' => $isMajor,
                        'adKeyType' => $result['adKeyType'],// 首页类型为1
                    );
                    $adPositionTypeId = $this->_packageDateDao->postAdAddNew($insertParams);
                }

                $synResult['create'][] = array(
                    'action' => 'create',
                    'adKey' => $adKey,
                    'adName' => $result['adName'],
                    'startCityCode' => $startCityCode,
                    'generateId' => $adPositionTypeId,
                );
            }elseif($existBaAdPosTypeInfo[0]['start_city_code'] == $startCityCode &&
                $existBaAdPosTypeInfo[0]['ad_key'] == $adKey){
                if ($isExec == 1) {
                    $conditionParams = array(
                        'start_city_code' => $startCityCode,
                        'ad_key' => $adKey,
                    );
                    $updateParams = array(
                        'ad_name' => $result['adName'],
                        'category_ids' => $result['categoryId'],
                        'class_brand_types' => $result['classBrandTypes'],
                        'cat_types' => $result['catType'],
                        'uid' => 4272,
                        'misc' => '更新广告位',
                        'channelId' => $result['channelId'] ? $result['channelId'] : 0,
                        'channelName' => $result['channelName'] ? $result['channelName'] : '',
                        'blockId' => $result['blockId'] ? $result['blockId'] : 0,
                        'blockName' => $result['blockName'] ? $result['blockName'] : '',
                        'isMajor' => $isMajor,
                        'adKeyType' => $result['adKeyType'],// 首页类型为1
                    );
                    $this->_packageDateDao->updateOneAdPositionType($updateParams, $conditionParams);
                }
                $synResult['update'][] = array(
                    'action' => 'update',
                    'adKey' => $adKey,
                    'adName' => $result['adName'],
                    'startCityCode' => $startCityCode,
                    'tgtCategoryIds' => $result['categoryId'],
                    'tgtClassBrandTypes' => $result['classBrandTypes'],
                    'tgtCatTypes' => $result['catType'],
                );
            }
        }
        return $synResult;
    }

    /**
     * 同步“竞价成功-推广开始”期间的变动首页板块TAB广告位
     *
     * @author chenjinlong 20140514
     * @param $synParamsArr
     * @return mixed
     */
    public function synBidBidProductAdPosType($synParamsArr)
    {
        $startCityCode = $synParamsArr['start_city_code'];
        $showDateId = $synParamsArr['show_date_id'];
        $isShowProduct = $synParamsArr['isShowProduct'];

        $bidBidProductRows = array();
        $queryBbpParams = array(
            'bid_mark' => 2, //状态为“竞拍成功”的记录
            'start_city_code' => $startCityCode,
            'show_date_id' => $showDateId,
            'ad_key_like' => 'index_chosen_',
        );
        if($isShowProduct == 1){
            $bidBidProductRows = $this->_bidProductDao->queryBspAdPositionTypeRows($queryBbpParams);
        }elseif($isShowProduct == -1){
            $bidBidProductRows = $this->_bidProductDao->queryBbpAdPositionTypeRows($queryBbpParams);
        }

        $checkAdPositionTypeList = array();
        foreach($bidBidProductRows as $eachBbpRow)
        {
            $queryAdPosTypeParams = array(
                'ad_key' => $eachBbpRow['ad_key'],
                'start_city_code' => $eachBbpRow['start_city_code'],
            );
            $adPositionTypeRow = $this->_bidProductDao->queryBaAdPositionTypeRow($queryAdPosTypeParams);
            $checkAdPositionTypeList[$eachBbpRow['start_city_code']][] = array(
                'ad_key' => $eachBbpRow['ad_key'],
                'ad_name' => $adPositionTypeRow['ad_name'],
                'start_city_code' => $eachBbpRow['start_city_code'],
            );
        }

        $finalDiffResult = array();

        foreach($checkAdPositionTypeList as $startCityCode => $adPositionTypeItem)
        {
            $tuniuAdPosTypeList = $this->_tuniuIao->getTuniuAdListAsOneArray($startCityCode);
            foreach($adPositionTypeItem as $eachRow)
            {
                foreach($tuniuAdPosTypeList as $eachTuniuAd)
                {
                    if($eachTuniuAd['ad_name'] == $eachRow['ad_name'] && $eachTuniuAd['ad_key'] != $eachRow['ad_key'] && $synParamsArr['isExec'] == 1 && $isShowProduct == 1)
                    {
                        //收集需要更正的数据
                        $finalDiffResult[] = array(
                            'execFlag' => 'execute',
                            'show_date_id' => $showDateId,
                            'start_city_code' => $startCityCode,
                            'old_ad_key' => $eachRow['ad_key'],
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                            'ad_name' => $eachRow['ad_name'],
                        );

                        //更新ba_ad_position
                        $updateParams = array(
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                        );
                        $queryParams = array(
                            'start_city_code' => $startCityCode,
                            'show_date_id' => $showDateId,
                            'old_ad_key' => $eachRow['ad_key'],
                            'ad_name' => $eachRow['ad_name'],
                        );
                        $this->_bidProductDao->updateBaAdPositionRows($updateParams, $queryParams);

                        //更新bid_show_product
                        $updateParams = array(
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                        );
                        $queryParams = array(
                            'start_city_code' => $startCityCode,
                            'show_date_id' => $showDateId,
                            'old_ad_key' => $eachRow['ad_key'],
                        );
                        $this->_bidProductDao->updateBidShowProductRows($updateParams, $queryParams);

                        //更新bid_bid_product
                        $updateParams = array(
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                        );
                        $queryParams = array(
                            'start_city_code' => $startCityCode,
                            'show_date_id' => $showDateId,
                            'old_ad_key' => $eachRow['ad_key'],
                        );
                        $this->_bidProductDao->updateBidBidProductRows($updateParams, $queryParams);

                        //更新ba_ad_position_type(先逻辑删除,后新增)
                        $deleteParams = array(
                            'startCityCode' => $startCityCode,
                            'adKey' => $eachRow['ad_key'],
                        );
                        $this->_packageDateDao->postAdDel($deleteParams);
                        $insertParams = array(
                            'adKey' => $eachTuniuAd['ad_key'],
                            'adName' => $eachTuniuAd['ad_name'],
                            'startCityCode' => $eachTuniuAd['start_city_code'],
                            'categoryId' => $eachTuniuAd['category_ids'],
                            'classBrandTypes' => $eachTuniuAd['class_brand_types'],
                            'catType' => $eachTuniuAd['cat_types'],
                            'addUid' => 2820,
                            'addTime' => date('Y-m-d H:i:s'),
                        );
                        $this->_packageDateDao->postAdAdd($insertParams);
                    }
                    elseif($eachTuniuAd['ad_name'] == $eachRow['ad_name'] && $eachTuniuAd['ad_key'] != $eachRow['ad_key'] && $synParamsArr['isExec'] == 1 && $isShowProduct == -1)
                    {
                        //收集需要更正的数据
                        $finalDiffResult[] = array(
                            'execFlag' => 'execute',
                            'show_date_id' => $showDateId,
                            'start_city_code' => $startCityCode,
                            'old_ad_key' => $eachRow['ad_key'],
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                            'ad_name' => $eachRow['ad_name'],
                        );

                        //更新ba_ad_position
                        $updateParams = array(
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                        );
                        $queryParams = array(
                            'start_city_code' => $startCityCode,
                            'show_date_id' => $showDateId,
                            'old_ad_key' => $eachRow['ad_key'],
                            'ad_name' => $eachRow['ad_name'],
                        );
                        $this->_bidProductDao->updateBaAdPositionRows($updateParams, $queryParams);

                        //更新bid_bid_product
                        $updateParams = array(
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                        );
                        $queryParams = array(
                            'start_city_code' => $startCityCode,
                            'show_date_id' => $showDateId,
                            'old_ad_key' => $eachRow['ad_key'],
                        );
                        $this->_bidProductDao->updateBidBidProductRows($updateParams, $queryParams);

                        //更新ba_ad_position_type(先逻辑删除,后新增)
                        $deleteParams = array(
                            'startCityCode' => $startCityCode,
                            'adKey' => $eachRow['ad_key'],
                        );
                        $this->_packageDateDao->postAdDel($deleteParams);
                        $insertParams = array(
                            'adKey' => $eachTuniuAd['ad_key'],
                            'adName' => $eachTuniuAd['ad_name'],
                            'startCityCode' => $eachTuniuAd['start_city_code'],
                            'categoryId' => $eachTuniuAd['category_ids'],
                            'classBrandTypes' => $eachTuniuAd['class_brand_types'],
                            'catType' => $eachTuniuAd['cat_types'],
                            'addUid' => 2820,
                            'addTime' => date('Y-m-d H:i:s'),
                        );
                        $this->_packageDateDao->postAdAdd($insertParams);
                    }
                    elseif($eachTuniuAd['ad_name'] == $eachRow['ad_name'] && $eachTuniuAd['ad_key'] != $eachRow['ad_key'])
                    {
                        //收集需要更正的数据
                        $finalDiffResult[] = array(
                            'execFlag' => 'check',
                            'show_date_id' => $showDateId,
                            'start_city_code' => $startCityCode,
                            'old_ad_key' => $eachRow['ad_key'],
                            'new_ad_key' => $eachTuniuAd['ad_key'],
                            'ad_name' => $eachRow['ad_name'],
                        );
                    }
                }
            }
        }
        return $finalDiffResult;

    }

}
 
