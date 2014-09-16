<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 4:13 PM
 * Description: ReleaseProductDao.php
 */
Yii::import('application.dal.dao.DaoModule');

class ReleaseProductDao extends DaoModule
{
    private $_tblBidShowProduct = 'bid_show_product';

    private $_tblBidProduct = 'bid_bid_product';
    
    private $_tblBidShowVas = 'bid_show_vas';
    
    private $_tblBidVas = 'bid_bid_vas';
    
    private $_tblBidShowContent = 'bid_show_content';

    /**
     * 查询当天推广的产品列表-网站首页
     *
     * @author chenjinlong 20121209
     * @return array()
     */
    public function queryReleaseToIndexCoreProductArr()
    {
        //@TODO chenjinlong 仅测试阶段使用
        $curDate = RELEASE_DATE;//date('Y-m-d');
        $sql = "SELECT id,account_id,product_id,bid_date,ad_key,cat_type,web_class,start_city_code,bid_price,ranking,bid_ranking,bid_mark,product_type
                FROM bid_bid_product
                WHERE ad_key='index_chosen' AND bid_mark IN (0,1) AND bid_date='{$curDate}' AND del_flag=0 
                ORDER BY ranking ASC";
        $rows = $this->dbRO->createCommand($sql)->queryAll();

        if(!empty($rows) && is_array($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 查询当天推广的产品列表-网站频道页
     *
     * @author chenjinlong 20121209
     * @return array()
     */
    public function queryReleaseToChannelHotProductArr()
    {
        //@TODO chenjinlong 仅测试阶段使用
        $curDate = RELEASE_DATE;//date('Y-m-d');
        $sql = "SELECT id,account_id,product_id,bid_date,ad_key,cat_type,web_class,start_city_code,bid_price,ranking,bid_ranking,bid_mark,product_type
                FROM bid_bid_product
                WHERE ad_key='channel_hot' AND bid_mark IN (0,1) AND bid_date='{$curDate}' AND del_flag=0 ORDER BY ranking ASC";
        $rows = $this->dbRO->createCommand($sql)->queryAll();
        if(!empty($rows) && is_array($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 查询当天推广的产品列表-分类页
     *
     * @author chenjinlong 20121209
     * @return array()
     */
    public function queryReleaseToClsRecommendProductArr()
    {
        $curDate = RELEASE_DATE;//date('Y-m-d');
        $sql = "SELECT id,account_id,product_id,bid_date,ad_key,cat_type,web_class,start_city_code,bid_price,ranking,bid_ranking,bid_mark,product_type
                FROM bid_bid_product
                WHERE ad_key='class_recommend' AND bid_mark IN (0,1) AND bid_date='{$curDate}' AND del_flag=0 ORDER BY ranking ASC";
        $rows = $this->dbRO->createCommand($sql)->queryAll();
        if (!empty($rows) && is_array($rows)) {
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * 推广成功，新增推广表记录
     *
     * @author chenjinlong 20121209
     * @param $inParams
     * @return $showProductId
     */
    public function insertReleaseShowProductRecords($inParams)
    {
        $accountId = $inParams['account_id'];
        $productId = $inParams['product_id'];
        $showDateId = $inParams['show_date_id'];
        $adKey = $inParams['ad_key'];
        $catType = $inParams['cat_type']?$inParams['cat_type']:0;
        $webClass = ($adKey == 'class_recommend')?$inParams['web_class']:0;
        $startCityCode = $inParams['start_city_code'];
        $bidPrice = $inParams['bid_price'];
        $ranking = $inParams['ranking'];
        $bidId = $inParams['bid_id'];
        $productType = $inParams['product_type'];
		// 设置搜索关键词
        $searchKeyword = $inParams['search_keyword'];
        // 设置包场状态
        $isBuyout = $inParams['is_buyout'];
        
        $addTime = date('Y-m-d H:i:s');
        $updateUid = 0;//$inParams['update_uid'];

        $exeResult = $this->dbRW->createCommand()->insert($this->_tblBidShowProduct,array(
            'account_id' => $accountId,
            'product_id' => $productId,
            'show_date_id' => $showDateId,
            'ad_key' => $adKey,
            'cat_type' => $catType,
            'web_class' => $webClass,
            'start_city_code' => $startCityCode,
            'bid_price' => $bidPrice,
            'ranking' => $ranking,
            'bid_id' => $bidId,
            'add_time' => $addTime,
            'update_uid' => $updateUid,
            'product_type' =>$productType,
        	'search_keyword' =>$searchKeyword,
            'del_flag'=>0,
            'bid_price_niu'=>$inParams['bid_price_niu'],
            'bid_price_coupon'=>$inParams['bid_price_coupon'],
            'is_buyout'=>$isBuyout,
        ));
        if($exeResult){
            return $this->dbRW->lastInsertID;
        }else{
            return 0;
        }
    }

    /**
     * 查询批量条件决定的记录的存在性
     *
     * @author chenjinlong 20130116
     * @param $inParams
     * @return int
     */
    public function isExistReleaseShowProduct($inParams)
    {
        $accountId = $inParams['account_id'];
        $productId = $inParams['product_id'];
        $bidDate = $inParams['bid_date'];
        $adKey = $inParams['ad_key'];
        $catType = $inParams['cat_type']?$inParams['cat_type']:0;
        $webClass = ($adKey == 'class_recommend')?$inParams['web_class']:0;
        $startCityCode = $inParams['start_city_code'];
        $searchKeyword = $inParams['search_keyword'];
        $sql = "select id
                from bid_show_product
                where account_id='{$accountId}'
                and product_id='{$productId}'
                and bid_date='{$bidDate}'
                and ad_key='{$adKey}'
                and cat_type='{$catType}'
                and web_class='{$webClass}'
                and start_city_code='{$startCityCode}'
                and search_keyword='{$searchKeyword}'
                and del_flag=0";
        $existId = $this->dbRO->createCommand($sql)->queryScalar();
        if($existId){
            return $existId;
        }else{
            return 0;
        }
    }

    /**
     * 移除推广成功记录
     *
     * @author chenjinlong 20130116
     * @param $inParams
     * @return bool
     */
    public function deleteExistReleaseShowProduct($inParams)
    {
        $bidShowProductId = $inParams['id'];
        $reasonDesc = $inParams['misc']?$inParams['misc']:'';
        $udtTime = date('Y-m-d H:i:s');
        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidShowProduct,array(
            'update_time' => $udtTime,
            'misc' => $reasonDesc,
            'del_flag'=>1,
        ),'id=:id AND del_flag=0',array(':id'=>$bidShowProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 扣费成功，更新推广表记录
     *
     * @author chenjinlong 20121209
     * @param $inParams
     * @param $condParams
     * @return int
     */
    public function updateShowProductAftDeduct($inParams, $condParams)
    {
        $bidShowProductId = $condParams['id'];

        $fmisId = $inParams['fmis_id'];
        $fmisIdCoupon = $inParams['fmis_id_coupon'];

        $addTime = date('Y-m-d H:i:s');
        $updateUid = 0;//$inParams['update_uid'];

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidShowProduct,array(
            'fmis_id' => $fmisId,
            'fmis_id_coupon' => $fmisIdCoupon,
            'add_time' => $addTime,
            'update_uid' => $updateUid,
            'del_flag'=>0,
        ),'id=:id AND del_flag=0',array(':id'=>$bidShowProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 扣费成功后的退款成功，更新推广表记录
     *
     * @author chenjinlong 20121209
     * @param $inParams
     * @return $showProductId
     */
    public function updateShowProductAftCancelDeduct($inParams, $condParams)
    {
        $bidShowProductId = $condParams['id'];

        $isCancel = 1;
        $cancelFmisId = $inParams['cancel_fmis_id'];
        $cancelReason = $inParams['cancel_reason'];
        $cancelTime = date('Y-m-d H:i:s');

        $updateTime = date('Y-m-d H:i:s');
        $updateUid = 0;//$inParams['update_uid'];

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidShowProduct,array(
            'is_cancel' => $isCancel,
            'cancel_fmis_id' => $cancelFmisId,
            'cancel_time' => $cancelTime,
            'cancel_reason' => $cancelReason,
            'update_time' => $updateTime,
            'update_uid' => $updateUid,
        ),'id=:id AND del_flag=0',array(':id'=>$bidShowProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新出价记录表之推广结果状态
     *
     * @author chenjinlong 20121211
     * @param $inParams
     * @param $condParams
     */
    public function updateBidReleaseStateAftRelease($inParams, $condParams)
    {
        $bidProductId = $condParams['id'];

        $bidMark = $inParams['bid_mark'];
        $updateTime = date('Y-m-d H:i:s');
        $updateUid = 0;//$inParams['update_uid'];

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidProduct,array(
            'bid_mark' => $bidMark,
            'update_time' => $updateTime,
            'update_uid' => $updateUid,
        ),'id=:id AND del_flag=0',array(':id'=>$bidProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新出价记录表之财务扣款结果状态
     *
     * @author chenjinlong 20121211
     * @param $inParams
     * @param $condParams
     */
    public function updateBidFmisStateAftRelease($inParams, $condParams)
    {
        $bidProductId = $condParams['id'];

        $fmisMark = $inParams['fmis_mark'];
        $updateTime = date('Y-m-d H:i:s');
        $updateUid = 0;//$inParams['update_uid'];

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidProduct,array(
            'fmis_mark' => $fmisMark,
            'update_time' => $updateTime,
            'update_uid' => $updateUid,
        ),'id=:id AND del_flag=0',array(':id'=>$bidProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询当天的所有有效出价记录
     *
     * @author chenjinlong 20121218
     * @return array
     */
    public function queryCurDateBidProductList()
    {
        //@TODO chenjinlong 仅测试阶段使用
        $curDate = RELEASE_DATE;//date('Y-m-d');
        $sql = "SELECT id,account_id,product_id,bid_date,ad_key,cat_type,web_class,start_city_code,bid_price,ranking,bid_ranking,bid_mark,fmis_mark
                FROM bid_bid_product
                WHERE bid_date='{$curDate}' AND del_flag=0";
        $rows = $this->dbRO->createCommand($sql)->queryAll();
        if(!empty($rows) && is_array($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 查询前一天推广成功的收客宝产品记录集合
     *
     * @author chenjinlong 20121223
     * @return array
     */
    public function queryYesterdayShowProductList()
    {
        $yesterdayDate = defined('STA_DATE') ? STA_DATE : date("Y-m-d", strtotime('-1 day'));
        $sql = "SELECT id,account_id,product_id,bid_date,ad_key,cat_type,web_class,start_city_code,bid_price,ranking,bid_id,product_type
                FROM bid_show_product
                WHERE bid_date='{$yesterdayDate}' AND is_cancel=0 AND del_flag=0";
        $rows = $this->dbRO->createCommand($sql)->queryAll();
        if(!empty($rows) && is_array($rows)){
            return $rows;
        }else{
            return array();
        }
    }

    /**
     * 查询指定日期的推广成功的收客宝产品记录集合
     * 招客宝改造-新增查询条件 MDF by chenjinlong 20131121
     *
     * @author chenjinlong 20130314
     * @param $conditionParam
     * @return array
     */
    public function queryCustomShowProductList($conditionParam)
    {
        $appendSql = '';
        $showDate = strval($conditionParam['show_date']);
        if($showDate){
            $appendSql .= " AND bid_date='{$showDate}'";
        }
        if(!empty($conditionParam['show_date_ids'])){
            $showDateIdStr = implode(',', $conditionParam['show_date_ids']);
        }else{
            $showDateIdStr = array();
        }
        if($showDateIdStr){
            $appendSql .= " AND show_date_id in ('{$showDateIdStr}')";
        }
        $accountId = intval($conditionParam['account_id']);
        if($accountId){
            $appendSql .= " AND account_id=$accountId";
        }

        $sql = "SELECT id,account_id,product_id,bid_date,ad_key,cat_type,web_class,start_city_code,bid_price,ranking,bid_id,product_type,show_date_id
                FROM bid_show_product
                WHERE is_cancel=0 AND del_flag=0" . $appendSql;
        $rows = $this->dbRO->createCommand($sql)->queryAll();
        if(!empty($rows) && is_array($rows)){
            return $rows;
        }else{
            return array();
        }
    }
    
    public function getReleaseProductInfo($spreadInfo) {
    	$condSqlSegment = ' is_cancel=:isCancel AND del_flag=:delFlag AND bid_date=:bidDate AND product_id=:productId';
    	$paramsMapSegment[':isCancel'] = 0;
    	$paramsMapSegment[':delFlag'] = 0;
    	$paramsMapSegment[':bidDate'] = $spreadInfo['date'];
    	$paramsMapSegment[':productId'] = $spreadInfo['productId'];
    	
    	$row = $this->dbRO->createCommand()
    	->select('ad_key adKey,bid_price bidPrice,start_city_code startCityCode')
    	->from('bid_show_product')
    	->where($condSqlSegment, $paramsMapSegment)
    	->queryRow();
    	
    	return $row ? $row : array();
    }

    /**
     * 获取推广开始时间为当天的产品列表-网站首页
     *
     * @author
     * @return array()
     */
    public function getProductArrTowardsIndexCore()
    {
        $curDate = RELEASE_DATE;//date('Y-m-d');
        $sql = "SELECT a.id,a.account_id,a.product_id,a.product_type,a.bid_date,a.ad_key,a.cat_type,a.web_class,a.start_city_code,a.bid_price,a.ranking,a.bid_ranking,a.fmis_mark,a.bid_mark,a.add_uid,a.add_time,a.update_uid,a.update_time,a.del_flag,a.misc,a.product_line_name,a.show_date_id,a.max_limit_price,a.search_keyword,b.show_start_date,b.show_end_date,a.login_name
                FROM bid_bid_product a
                LEFT JOIN bid_show_date b
                ON a.show_date_id = b.id
                WHERE a.ad_key='index_chosen' AND a.bid_mark = 2 AND b.show_start_date='{$curDate}' AND a.del_flag=0 AND b.del_flag=0
                ORDER BY ranking ASC";
        $rows = $this->dbRO->createCommand($sql)->queryAll();

        if(!empty($rows) && is_array($rows)){
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
        $curDate = RELEASE_DATE;//date('Y-m-d');
        $sql = "SELECT a.id,a.account_id,a.product_id,a.product_type,a.bid_date,a.ad_key,a.cat_type,a.web_class,a.start_city_code,a.bid_price,a.ranking,a.bid_ranking,a.fmis_mark,a.bid_mark,a.add_uid,a.add_time,a.update_uid,a.update_time,a.del_flag,a.misc,a.product_line_name,a.show_date_id,a.max_limit_price,a.search_keyword,b.show_start_date,b.show_end_date,a.login_name
                FROM bid_bid_product a
                LEFT JOIN bid_show_date b
                ON a.show_date_id = b.id
                WHERE a.ad_key='class_recommend' AND a.bid_mark = 2 AND b.show_start_date='{$curDate}' AND a.del_flag=0 AND b.del_flag=0
                ORDER BY ranking ASC";
        $rows = $this->dbRO->createCommand($sql)->queryAll();

        if(!empty($rows) && is_array($rows)){
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
    	//date('Y-m-d');
        $curDate = RELEASE_DATE;
        // 初始化SQL语句
        $sql = "SELECT a.id,a.account_id,a.product_id,a.product_type,a.bid_date,a.ad_key,a.cat_type,a.web_class,a.start_city_code,a.bid_price,a.ranking,a.bid_ranking,a.fmis_mark,a.bid_mark,a.add_uid,a.add_time,a.update_uid,a.update_time,a.del_flag,a.misc,a.product_line_name,a.show_date_id,a.max_limit_price,a.search_keyword,b.show_start_date,b.show_end_date,a.login_name
                FROM bid_bid_product a
                LEFT JOIN bid_show_date b
                ON a.show_date_id = b.id
                WHERE a.ad_key='search_complex' AND a.bid_mark = 2 AND b.show_start_date='{$curDate}' AND a.del_flag=0 AND b.del_flag=0
                ORDER BY ranking ASC";
        // 查询数据库
        $rows = $this->dbRO->createCommand($sql)->queryAll();

        if(!empty($rows) && is_array($rows)){
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
    public function getProductArr($params)
    {
    	//date('Y-m-d');
        $curDate = RELEASE_DATE;
        // 初始化动态SQL
        $dySql = "";
        if (strpos($params['ad_key'],'channel_chosen') !== false) {
        	$dySql = " and a.start_city_code = ".$params['start_city_code'];
        } else if ('class_recommend' == $params['ad_key']) {
        	$dySql = " and a.start_city_code = ".$params['start_city_code']." and a.web_class = ".$params['web_class'];
        }
        $type = $params['ad_key'];
        // 初始化SQL语句
        $sql = "SELECT a.id,a.account_id,a.product_id,a.product_type,a.bid_date,a.ad_key,a.cat_type,a.web_class,a.start_city_code,a.bid_price,a.ranking,a.bid_ranking,a.fmis_mark,a.bid_mark,a.add_uid,a.add_time,a.update_uid,a.update_time,a.del_flag,a.misc,a.product_line_name,a.show_date_id,a.max_limit_price,a.search_keyword,b.show_start_date,b.show_end_date,a.login_name,a.bid_price_coupon,a.bid_price_niu,max_limit_price_coupon,max_limit_price_niu,a.is_buyout
                FROM bid_bid_product a
                LEFT JOIN bid_show_date b
                ON a.show_date_id = b.id
                WHERE a.ad_key='{$type}' AND a.bid_mark = 2 AND b.show_start_date='{$curDate}' AND a.del_flag=0 AND b.del_flag=0 ".$dySql.
				" ORDER BY ranking ASC";
        // 查询数据库
        $rows = $this->dbRW->createCommand($sql)->queryAll();

        if(!empty($rows) && is_array($rows)){
            return $rows;
        }else{
            return array();
        }
    }
    
    /**
     * 查看产品的是否有附加属性
     * 
     * @author wenrui
     */
    public function isExistProductVas($bidId){
    	$sql = "SELECT id,account_id,bid_id,vas_key,bid_price,bid_mark,fmis_mark,misc
				FROM bid_bid_vas
				WHERE bid_id = ".$bidId." AND del_flag = 0";
    	$productVas = $this->dbRW->createCommand($sql)->queryAll();
    	if($productVas){
            return $productVas;
        }else{
            return array();
        }
    }
    
    /**
     * 插入推广附加属性表
     * 
     * @author wenrui
     */
    public function insertReleaseShowProductVasRecords($recordId,$productVas){
    	$addTime = date('Y-m-d H:i:s');
    	$updateTime = date('Y-m-d H:i:s');
    	foreach($productVas as $vas){
    		$this->dbRW->createCommand()->insert($this->_tblBidShowVas,array(
	            'account_id' => $vas['account_id'],
	            'vas_key' => $vas['vas_key'],
	            'bid_price' => $vas['bid_price'],
	            'show_id' => $recordId,
	            'add_time' => $addTime,
	            'add_uid' => 0,
	            'update_uid' => 0,
	            'update_time' => $updateTime,
	            'del_flag'=>0,
	        ));
    	}
    }
    
    /**
     * 插入出价内容表
     * 
     * @author wenrui
     */
    public function insertReleaseShowProductContentRecords($recordId,$paramsArr){
    	$this->dbRW->createCommand()->insert($this->_tblBidShowContent,array(
            'account_id' => $paramsArr['account_id'],
            'content_type' => $paramsArr['product_type'],
            'content_id' => $paramsArr['product_id'],
            'show_id' => $recordId,
            'add_uid' => 0,
            'add_time' => date('Y-m-d H:i:s'),
            'update_uid' => 0,
            'update_time' => date('Y-m-d H:i:s'),
            'del_flag' => 0,
        ));
    }
    
    /**
     * 更新竞价附加属性表的竞价状态
     * 
     * @author wenrui
     */
    public function updateBidVasReleaseStateAftRelease($inParams, $condParams)
    {
        $bidProductId = $condParams['id'];
        $bidMark = $inParams['bid_mark'];
        $updateTime = date('Y-m-d H:i:s');
        $updateUid = 0;

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidVas,array(
            'bid_mark' => $bidMark,
            'update_time' => $updateTime,
            'update_uid' => $updateUid,
        ),'bid_id=:id AND del_flag=0',array(':id'=>$bidProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 更新竞价附加属性表的财务状态
     * 
     * @author wenrui
     */
    public function updateBidVasFmisStateAftRelease($inParams, $condParams)
    {
        $bidProductId = $condParams['id'];
        $fmisMark = $inParams['fmis_mark'];
        $updateTime = date('Y-m-d H:i:s');
        $updateUid = 0;

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidVas,array(
            'fmis_mark' => $fmisMark,
            'update_time' => $updateTime,
            'update_uid' => $updateUid,
        ),'bid_id=:id AND del_flag=0',array(':id'=>$bidProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 扣费成功，更新推广附加属性表扣费财务流水ID
     * 
     * @author wenrui
     */
    public function updateShowProductVasAftDeduct($inParams, $condParams)
    {
        $bidShowProductId = $condParams['id'];
        $fmisId = $inParams['fmis_id'];
        $updateTime = date('Y-m-d H:i:s');
        $updateUid = 0;

        $exeResult = $this->dbRW->createCommand()->update($this->_tblBidShowVas,array(
            'fmis_id' => $fmisId,
            'update_time' => $updateTime,
            'update_uid' => $updateUid,
            'del_flag'=> 0,
        ),'show_id=:id AND del_flag=0',array(':id'=>$bidShowProductId));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 查询附加信息扣款信息
     */
    public function queryReductVasInfo($param) {
    	// 初始化SQL语句
		$sql = "SELECT account_id, bid_id, bid_price FROM bid_bid_vas WHERE del_flag = 0 AND bid_id = ".$param['bid_id'];
		// 查询并返回参数
		$row = $this->dbRW->createCommand($sql)->queryAll();
		// 如果不为空，则返回数据
    	if(!empty($row)){
            return $row;
        }else{
            return array();
        }
	}
	
	/**
	 * 查询推广位置维度
	 */
	public function queryPositionWd() {
		try {
			// 初始化SQL，将所有广告位维度查出来
			$sql = "SELECT ad_key, start_city_code, web_class
				FROM bid_bid_product a
				LEFT JOIN bid_show_date b ON a.show_date_id = b.id
				WHERE a.bid_mark = 2 AND b.show_start_date = '".RELEASE_DATE."' AND a.del_flag = 0 AND b.del_flag = 0";
			// 查询维度信息
			$wdRows = $this->executeSql($sql, self::ALL);
			
			// 初始化返回集合
			$result = array();
			// 初始化adKey集合
			$keyArr = array();
			
			// 整合adKey集合
			foreach ($wdRows as $wdRowsObj) {
				// 填充adKey
				array_push($keyArr, $wdRowsObj['ad_key'].'-'.$wdRowsObj['start_city_code'].'-'.$wdRowsObj['web_class']);
			}
			// 去除重复adKey
			$keyArr = array_unique($keyArr);
			
			// 整合结果
			foreach ($keyArr as $keyArrObj) {
				$keyArrObjTemp = explode('-',$keyArrObj);
				$resultTemp = array();
				$resultTemp['ad_key'] = $keyArrObjTemp[0];
				$resultTemp['start_city_code'] = $keyArrObjTemp[1];
				$resultTemp['web_class'] = $keyArrObjTemp[2];
				array_push($result, $resultTemp);
			}
								
			// 返回结果
			return $result;
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
 
	}
    
}
