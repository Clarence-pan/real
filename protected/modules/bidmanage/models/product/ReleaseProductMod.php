<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 5:29 PM
 * Description: ReleaseProductMod.php
 */
Yii::import('application.modules.bidmanage.dal.dao.product.ReleaseProductDao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');

class ReleaseProductMod
{
    /**
     * 兑奖供应商之跟团外产品硬编码数组
     * (FOR: 解决兑奖供应商产品问题)
     */
    private $_appendProductArr = array(
        '156862' => array(
            'vendorId' => '45',
            'productId' => '156862',
            'checkerFlag' => '2',
        ),
        '151689' => array(
            'vendorId' => '2346',
            'productId' => '151689',
            'checkerFlag' => '2',
        ),
    );

    private $_releaseProductDao;

    private $_iaoProductMod;

    private $_packageDateDao;

    function __construct()
    {
        $this->_releaseProductDao = new ReleaseProductDao;
        $this->_iaoProductMod = new IaoProductMod;
        $this->_packageDateDao = new PackageDateDao();
    }

    /**
     * 获取当天推广的产品列表-网站首页
     *
     * @author chenjinlong 20121209
     * @return array()
     */
    public function getReleaseToIndexCoreProductArr()
    {
        $rows = $this->_releaseProductDao->queryReleaseToIndexCoreProductArr();
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 获取当天推广的产品列表-网站频道页
     *
     * @author chenjinlong 20121209
     * @return array()
     */
    public function getReleaseToChannelHotProductArr()
    {
        $rows = $this->_releaseProductDao->queryReleaseToChannelHotProductArr();
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 获取当天推广的产品列表-分类页
     *
     * @author chenjinlong 20121211
     * @return array
     */
    public function getReleaseToClsRecommendProductArr()
    {
        $rows = $this->_releaseProductDao->queryReleaseToClsRecommendProductArr();
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 新增产品推广表记录
     *
     * @author chenjinlong 20121210
     * @param $paramsArr
     * @return int
     */
    public function insertReleaseShowPrdArr($paramsArr)
    {
    	// del by wenrui 2014.01.16 插入推广表前校验推广表中是否有重复数据
//        $existBidShowProductId = $this->_releaseProductDao->isExistReleaseShowProduct($paramsArr);
//        if($existBidShowProductId){
//            $this->_releaseProductDao->deleteExistReleaseShowProduct(array('id'=>$existBidShowProductId, 'misc'=>'手工二次推广操作',));
//        }
		$recordId = $this->_releaseProductDao->insertReleaseShowProductRecords($paramsArr);
		// 插入出价内容表
		$this->_releaseProductDao->insertReleaseShowProductContentRecords($recordId,$paramsArr);
		// 查看产品的是否有附加属性
		$productVas = $this->_releaseProductDao->isExistProductVas($paramsArr['bid_id']);
		// 产品存在附加属性插入推广附加属性表
		if ($productVas) {
			$this->_releaseProductDao->insertReleaseShowProductVasRecords($recordId,$productVas);
		}
        return $recordId;
    }

    /**
     * 扣款成功，更新产品推广表之财务数据
     *
     * @author chenjinlong 20121210
     * @param $udtParamsArr
     * @param $udtCondParamsArr
     * @return int
     */
    public function updateShowPrdArrayAftDeduct($udtParamsArr, $udtCondParamsArr)
    {
        $executedResult = $this->_releaseProductDao->updateShowProductAftDeduct($udtParamsArr, $udtCondParamsArr);
        // 扣费成功，更新推广附加属性表扣费财务流水ID
        $this->_releaseProductDao->updateShowProductVasAftDeduct($udtParamsArr, $udtCondParamsArr);
        return $executedResult;
    }

    /**
     * 扣款成功后的撤销扣费，更新产品推广表之财务数据
     *
     * @author chenjinlong 20121210
     * @param $udtParamsArr
     * @param $udtCondParamsArr
     * @return bool
     */
    public function updateShowPrdArrayAftCancelDeduct($udtParamsArr, $udtCondParamsArr)
    {
        $executedResult = $this->_releaseProductDao->updateShowProductAftCancelDeduct($udtParamsArr, $udtCondParamsArr);
        return $executedResult;
    }

    /**
     * 推广完成后，更新出价记录表之推广结果状态
     *
     * @author chenjinlong 20121211
     * @param $udtParamsArr
     * @param $udtCondParamsArr
     * @return bool
     */
    public function updateRelStateAftRelease($bidId, $tgtStateDigit)
    {
        $udtParamsArr = array(
            'bid_mark' => $tgtStateDigit,
        );
        $udtCondParamsArr = array(
            'id' => $bidId,
        );
        $executedResult = $this->_releaseProductDao->updateBidReleaseStateAftRelease($udtParamsArr, $udtCondParamsArr);
        // 更新竞价附加属性表的竞价状态
        $this->_releaseProductDao->updateBidVasReleaseStateAftRelease($udtParamsArr, $udtCondParamsArr);
        return $executedResult;
    }

    /**
     * 推广完成后，更新出价记录表和附加属性表之财务扣款结果状态
     *
     * @author chenjinlong 20121211
     * @param $udtParamsArr
     * @param $udtCondParamsArr
     * @return bool
     */
    public function updateFmisStateAftRelease($bidId, $tgtStateDigit)
    {
        $udtParamsArr = array(
            'fmis_mark' => $tgtStateDigit,
        );
        $udtCondParamsArr = array(
            'id' => $bidId,
        );
        $executedResult = $this->_releaseProductDao->updateBidFmisStateAftRelease($udtParamsArr, $udtCondParamsArr);
        $this->_releaseProductDao->updateBidVasFmisStateAftRelease($udtParamsArr, $udtCondParamsArr);
        return $executedResult;
    }

    /**
     * (工具函数)过滤出已审核产品状态的产品数据
     *
     * @author chenjinlong 20121209
     * @param $srcReleaseAllRows
     * @return array()
     */
    public function filterValidProductArr($srcReleaseAllRows, $maxRowsLimit)
    {
        if (!empty($srcReleaseAllRows) && is_array($srcReleaseAllRows)) {

            //记录无效产品状态的竞价产品信息
            $tgtReleaseRows = array(
                'valid' => array(),
                'invalid' => array(),
            );

            //拼装产品编号
            $productIdArr = array();
            //有效数据执行过滤
            $tgtReleaseRowsValid = array();
            foreach ($srcReleaseAllRows as $key => $row) {
                if ($row['product_id']!=0&&in_array($row['product_id'], $productIdArr)) {
                    //如果同一组竞价中存在2个一样的产品ID，则取排名靠前的推广,删除推广失败的
                    $tgtReleaseRows['invalid'][] = $this->invalidPackage($row);
                    unset($srcReleaseAllRows[$key]);
                    continue;
                } else if (count($tgtReleaseRowsValid) >= $maxRowsLimit) {
                	//超出最大广告产品数量记录推广失败的
                    $tgtReleaseRows['invalid'][] = $this->invalidPackage($row);
                    continue;
                } else if (count($tgtReleaseRowsValid) < $maxRowsLimit) {
                    $tgtReleaseRowsValid[$key] = $row;
                }
                $productIdArr[] = $row['product_id'];
            }
			
            $tgtReleaseRows['valid'] = array_merge($tgtReleaseRows['valid'], $tgtReleaseRowsValid);


            //记录监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '过滤-成功', 11, 'wuke', 12, sizeof($tgtReleaseRows['invalid']), '有效：'.sizeof($tgtReleaseRows['valid']).',无效：'.sizeof($tgtReleaseRows['invalid']),json_encode($srcReleaseAllRows), json_encode($tgtReleaseRows));
            return $tgtReleaseRows;
        } else {
            //记录监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '过滤-源数据空', 11, 'wuke', -12, 0, json_encode($srcReleaseAllRows));

            return array(
                'valid' => array(),
                'invalid' => array(),
            );
        }
    }
    
    /**
     * 过滤无效数据封装
     * Author wenrui
     */
    function invalidPackage ($val){
    	// 查看产品的是否有附加属性,若有，增加附加属性金额
    	$productVas = $this->_releaseProductDao->isExistProductVas($val['id']);
    	// 初始化竞价总额
    	$bidPrice = 0;
    	// 算出附加属性总额
    	if(!empty($productVas)){
    		foreach($productVas as $vas){
        		$bidPrice += $vas['bid_price'];
        	}
    	}
        $result = array(
            'account_id' => $val['account_id'],
            'amt' => $val['max_limit_price']+$bidPrice,
			'amt_niu' => $val['max_limit_price_niu'],
			'amt_coupon' => $val['max_limit_price_coupon'],
			'vasPrice' => $bidPrice,
            //记录出价ID作更新用
            'bid_id' => $val['id'],

            'bid_mark' => $val['bid_mark'],
            // 设置搜索关键词
            'search_keyword' => $val['search_keyword'],
            'login_name' => $val['login_name'],
        );
        return $result;
    }

    function getProductInfoByIds($productIdArr,$productParam){
        
        $gtProductIds =array();
        $menpiaoProductIds =array();
        $diyProductIds =array();
        
        foreach ($productIdArr as $Ids) {
            
        }
        
        //批量查询产品信息
        $productInfoArr = $this->_iaoProductMod->getProductInfoArr($productParam);
        
        //整合-硬编码行为之跟团外产品信息(FOR: 解决兑奖供应商产品问题)
        $appendProductInfoArr = $this->integrateAppendProductArr($productIdArr);
        
        $productInfoArr = array_merge($productInfoArr, $appendProductInfoArr);
        
        return $productInfoArr;
    }
    
    /**
     * 抽取硬编码行为之跟团外产品信息
     * (FOR: 解决兑奖供应商产品问题)
     *
     * @author chenjinlong 20130108
     * @param $srcProductIdArr
     * @return array
     */
    public function integrateAppendProductArr($srcProductIdArr)
    {
    	
        if(!empty($srcProductIdArr) && is_array($srcProductIdArr)){
            $appendProductIdArr = array_keys($this->_appendProductArr);

            $outAppendProductArr = array();
            foreach($srcProductIdArr as $productId)
            {
            	
                if(in_array($productId, $appendProductIdArr)){
                    $outAppendProductArr[] = $this->_appendProductArr[$productId];
                }
            }
            return $outAppendProductArr;
        }else{
            return array();
        }
    }

    /**
     * 查询当天的所有有效出价记录
     *
     * @author chenjinlong 20121218
     * @return array
     */
    public function getCurDateBidProductArray()
    {
        $bidProductArr = $this->_releaseProductDao->queryCurDateBidProductList();
        if(!empty($bidProductArr)){
            return $bidProductArr;
        }else{
            return array();
        }
    }

    /**
     * 查询指定日期的推广成功的招客宝产品列表
     * 招客宝改版-MDF by chenjinlong 20131121
     *
     * @author chenjinlong 201303014
     * @param $condition
     * @return array
     */
    public function getCustomDateShowProductArray($condition)
    {
        $showDateIdArr = $this->_packageDateDao->getShowDateIdArrBySpecificDate($condition['show_date']);
        $queryShowProductParams = array(
            'show_date_ids' => $showDateIdArr,
            'account_id' => $condition['account_id'],
        );
        if(!empty($showDateIdArr) && $condition['account_id'] > 0){
            $showProductArr = $this->_releaseProductDao->queryCustomShowProductList($queryShowProductParams);
            foreach($showProductArr as &$item)
            {
                $showDateCount = $this->_packageDateDao->countBidShowDays($item['show_date_id']);
                if($showDateCount > 0){
                    $item['bid_price'] = $item['bid_price'] / $showDateCount;
                }else{
                    $item['bid_price'] = 0;
                }
            }
        }else{
            $showProductArr = array();
        }
        if(!empty($showProductArr)){
            return $showProductArr;
        }else{
            return array();
        }
    }

    /**
     * 查询昨日的所有推广成功的收客宝产品集合
     *
     * @author chenjinlong 20121223
     * @return array
     */
    public function getCurDateShowProductArray()
    {
        $showProductArr = $this->_releaseProductDao->queryYesterdayShowProductList();
        if(!empty($showProductArr)){
            return $showProductArr;
        }else{
            return array();
        }
    }

    /**
     * 查询今日的所有推广成功的收客宝产品集合
     *
     * @return array
     */
    public function getCurDateReleaseProductArray()
    {
        $curDate = array(
            'show_date' => date('Y-m-d'),
        );
        $showProductArr = $this->_releaseProductDao->queryCustomShowProductList($curDate);
        if(!empty($showProductArr)){
            return $showProductArr;
        }else{
            return array();
        }
    }

    public function groupingReleaseProduct($srcRelProductArr)
    {
        $group = array();
        foreach($srcRelProductArr as $bid){
            $groupKey = $this->getGroupKey($bid);
            $group[$groupKey][] = $bid;
        }
        return $group;
    }

    function  getGroupKey($bid){
        if($bid['ad_key'] == 'index_chosen'){
            return  $bid['start_city_code'];
        }
        if($bid['ad_key'] == 'class_recommend'){
            return  $bid['start_city_code'].'_'.$bid['web_class'];
        }

        if($bid['ad_key'] == 'channel_hot'){
            return  $bid['start_city_code'].'_'.$bid['cat_type'];
        }
        
        // 返回搜索页标识
        if($bid['ad_key'] == 'search_keyword'){
            return  $bid['search_keyword'];
        }
    }
    
    public function getReleaseProductInfo($spreadInfo) {
    	$showProductArr = $this->_releaseProductDao->getReleaseProductInfo($spreadInfo);
    	if(!empty($showProductArr)){
    		return $showProductArr;
    	}else{
    		return array();
    	}
    }

    /**
     * 获取推广开始时间为当天的产品列表-网站首页
     *
     * @author
     * @return array()
     */
    public function getProductArrTowardsIndexCore()
    {
        $rows = $this->_releaseProductDao->getProductArrTowardsIndexCore();
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 获取推广开始时间为当天的产品列表-分类页
     *
     * @author
     * @return array()
     */
    public function getProductArrTowardsClsRecommend()
    {
        $rows = $this->_releaseProductDao->getProductArrTowardsClsRecommend();
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }

	/**
     * 获取推广开始时间为当天的产品列表-搜索页
     *
     * @author
     * @return array()
     */
    public function getProductArrTowardsSearchCom()
    {
        $rows = $this->_releaseProductDao->getProductArrTowardsSearchCom();
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }
    
    /**
     * 获取推广开始时间为当天的产品列表
     *
     * @author
     * @return array()
     */
    public function getProductArr($adKeyObj)
    {
        $rows = $this->_releaseProductDao->getProductArr($adKeyObj);
        if(!empty($rows)){
            return $rows;
        }else{
            return array();
        }
    }
    
    
    /**
     * 查询推广位置维度
     *
     * @author
     * @return array()
     */
    public function getPositionWd()
    {
        // 初始化返回结果
		$result = array();
		
		try {
			// 查询推广位置维度
			$rows = $this->_releaseProductDao->queryPositionWd();
			
			// 整合最终返回的正确结果
			$result['data'] = $rows;
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
    

}
