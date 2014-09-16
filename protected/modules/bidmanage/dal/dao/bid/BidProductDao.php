<?php

Yii::import('application.dal.dao.DaoModule');

/**
 * 
 * @author chuhui@2013-01-11
 * @version 1.0
 */
class BidProductDao extends DaoModule {

    /**
     * bid_bid_product
     */
    private $_tblName = 'bid_bid_product';

    /**
     * 新增竞价记录
     * @param unknown_type $in
     * @return unknown|boolean
     */
    public function insertBidRecord($in) {
        $bidDate = $in['bid_date'];
        $newBidRecordRanking = $in['newBidRecordRanking'];
        // 获取出发城市信息
        $startCityCode = $in['start_city_code'];
        // 插入新增竞价记录
        $exeResult = $this->dbRW->createCommand()->insert($this->_tblName, array(
            'account_id' => $in['account_id'],
            'product_id' => $in['product_id'],
            'bid_date' => $bidDate,
            'ad_key' => $in['ad_key'],
            'web_class' => $in['web_class'] == null ? 0 : $in['web_class'],
            'cat_type' => intval($in['cat_type']),
            'start_city_code' => $startCityCode == null ? 0 : $startCityCode,
            'bid_price' => $in['bid_price'],
            'ranking' => $newBidRecordRanking,
            'bid_ranking' => $newBidRecordRanking,
            'bid_mark' => $in['bid_mark'] == null ? 0 : $in['bid_mark'],
            'fmis_mark' => !isset($in['fmis_mark']) ? 2 : $in['fmis_mark'],
            'add_uid' => $in['account_id'],
            'add_time' => date('Y-m-d H:i:s'),
            'update_uid' => $in['account_id'],
            'update_time' => date('Y-m-d H:i:s'),
            'product_type'=>$in['product_type'],
            'search_keyword'=>$in['search_keyword'],
            'del_flag' => 0,
            'show_date_id' => intval($in['show_date_id']),
            'max_limit_price' => intval($in['max_limit_price']),
            'bid_price_niu' => intval($in['bid_price_niu']),
			'max_limit_price_niu' => intval($in['max_limit_price_niu']),
			'bid_price_coupon' => intval($in['bid_price_coupon']),
			'max_limit_price_coupon' => intval($in['max_limit_price_coupon']),
            'login_name' => strval($in['login_name']),
                ));
        return $this->dbRW->lastInsertID;
    }

    /**
     * 获取竞价记录的信息
     */
    public function getBidBidProductRows($params) {
    	$condSqlSegment = ' del_flag = :del_flag';
        $paramsMapSegment = array(':del_flag'=>0);
        if (0 < $params['id']) {
        	$condSqlSegment .= ' AND id=:id';
        	$paramsMapSegment[':id'] = $params['id'];
        }
        if (0 != $params['bid_mark']) {
        	$condSqlSegment .= ' AND bid_mark=:bid_mark';
        	$paramsMapSegment[':bid_mark'] = $params['bid_mark'];
        }
        
        $info = $this->dbRO->createCommand()
                ->select('*')
                ->from($this->_tblName)
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryAll();
        return $info;
    }

    /**
     * 招客宝改版-根据条件，查询出价信息
     *
     * @author chenjinlong 20131113
     * @param mixed
     * @return mixed
     */
    public function getBidRecords($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if($condParams['bid_id'] > 0){
            $condSqlSegment .= ' AND id=:id';
            $paramsMapSegment[':id'] = $condParams['bid_id'];
        }
        if($condParams['start_city_code'] > 0){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $condParams['start_city_code'];
        }
        if($condParams['account_id'] > 0){
            $condSqlSegment .= ' AND account_id=:account_id';
            $paramsMapSegment[':account_id'] = $condParams['account_id'];
        }
        if($condParams['ad_key']){
            $condSqlSegment .= ' AND ad_key=:ad_key';
            $paramsMapSegment[':ad_key'] = $condParams['ad_key'];
        }
        if($condParams['ad_key'] == 'class_recommend' && $condParams['web_class'] > 0){
            $condSqlSegment .= ' AND web_class=:web_class';
            $paramsMapSegment[':web_class'] = $condParams['web_class'];
        }
        if($condParams['show_date_id'] > 0){
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $condParams['show_date_id'];
        }
        if($condParams['is_new_bb_version'] == 1){
            $condSqlSegment .= ' AND show_date_id>:new_bb_version_start_val';
            $paramsMapSegment[':new_bb_version_start_val'] = 0;
        }
        if(isset($condParams['bid_mark'])){
            $condSqlSegment .= ' AND bid_mark=:bid_mark';
            $paramsMapSegment[':bid_mark'] = $condParams['bid_mark'];
        }
        if(isset($condParams['fmis_mark'])){
            $condSqlSegment .= ' AND fmis_mark=:fmis_mark';
            $paramsMapSegment[':fmis_mark'] = $condParams['fmis_mark'];
        }
        if('search_complex' == $condParams['ad_key']){
            $condSqlSegment .= ' AND search_keyword=:search_keyword';
            $paramsMapSegment[':search_keyword'] = $condParams['search_keyword'];
        }
        if($condParams['product_type']){
            $condSqlSegment .= ' AND product_type=:product_type';
            $paramsMapSegment[':product_type'] = $condParams['product_type'];
        }
		// 包场过滤
        if(!empty($condParams['is_buyout']) && 1 == $condParams['is_buyout']){
            $condSqlSegment .= ' AND is_buyout=:is_buyout';
            $paramsMapSegment[':is_buyout'] = $condParams['is_buyout'];
        } else if(!empty($condParams['is_buyout']) && 2 == $condParams['is_buyout']){
            $condSqlSegment .= ' AND is_buyout=:is_buyout';
            $paramsMapSegment[':is_buyout'] = 0;
        } 
        if(intval($condParams['only_count']) == 1){
            $queryFieldString = 'count(1)';
            $info = $this->dbRO->createCommand()
                ->select($queryFieldString)
                ->from($this->_tblName)
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryScalar();
        }else{
            $queryFieldString = 'id bid_id,account_id,product_id,product_type,ad_key,web_class,ranking,bid_ranking,bid_price,
                      max_limit_price,bid_price_coupon,bid_price_niu,max_limit_price_coupon,max_limit_price_niu,bid_mark,
                      fmis_mark,add_time,start_city_code,show_date_id,search_keyword,login_name,is_buyout';
            if(intval($condParams['need_pager']) == 1){
                $info = $this->dbRO->createCommand()
                    ->select($queryFieldString)
                    ->from($this->_tblName)
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->limit($condParams['limit'], $condParams['start'])
                    ->queryAll();
            }else{
                $info = $this->dbRO->createCommand()
                    ->select($queryFieldString)
                    ->from($this->_tblName)
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryAll();
            }
        }
        return $info;
    }

    /**
     * 招客宝改版-更新bid_bid_product行记录
     *
     * @author chenjinlong 20131114
     * @param $updateParams 更新值
     * @param $conditionParams 条件
     * @return mixed
     */
    public function updateBidRecordNew($updateParams, $conditionParams)
    {
        $condSqlSegment = '';
        $condSqlSegment .= ' account_id=:account_id';
        $paramsMapSegment[':account_id'] = $conditionParams['account_id'];
        $condSqlSegment .= ' AND ad_key=:ad_key';
        $paramsMapSegment[':ad_key'] = $conditionParams['ad_key'];

        $condSqlSegment .= ' AND start_city_code=:start_city_code';
        $paramsMapSegment[':start_city_code'] = $conditionParams['start_city_code'];
        $condSqlSegment .= ' AND show_date_id=:show_date_id';
        $paramsMapSegment[':show_date_id'] = $conditionParams['show_date_id'];
        if($conditionParams['ad_key'] == 'class_recommend'){
            $condSqlSegment .= ' AND web_class=:web_class';
            $paramsMapSegment[':web_class'] = intval($conditionParams['web_class']);
        }
        if($conditionParams['bid_id'] > 0){
            $condSqlSegment .= ' AND id=:id';
            $paramsMapSegment[':id'] = $conditionParams['bid_id'];
        }elseif($conditionParams['product_id'] > 0){
            $condSqlSegment .= ' AND product_id=:product_id';
            $paramsMapSegment[':product_id'] = $conditionParams['product_id'];
            $condSqlSegment .= ' AND product_type=:product_type';
            $paramsMapSegment[':product_type'] = $conditionParams['product_type'];
        }
		if('search_complex'==$conditionParams['ad_key']){
            $condSqlSegment .= ' AND search_keyword=:search_keyword';
            $paramsMapSegment[':search_keyword'] = strval($conditionParams['search_keyword']);
        }
        $exeResult = $this->dbRW->createCommand()->update($this->_tblName, array(
            'del_flag' => 0,
            'update_time' => date('Y-m-d H:i:s'),
            'bid_price' => $updateParams['bid_price'],
            'max_limit_price' => $updateParams['max_limit_price'],
            'bid_price_niu' => $updateParams['bid_price_niu'],
            'max_limit_price_niu' => $updateParams['max_limit_price_niu'],
            'bid_price_coupon' => $updateParams['bid_price_coupon'],
            'max_limit_price_coupon' => $updateParams['max_limit_price_coupon'],
            'bid_mark' => $updateParams['bid_mark'],
            'ranking' => $updateParams['ranking'],
            'fmis_mark' => $updateParams['fmis_mark'],
            'bid_ranking' => $updateParams['bid_ranking'],
            'search_keyword' => $updateParams['search_keyword'],
            'login_name' => $updateParams['login_name'],
        ), $condSqlSegment, $paramsMapSegment);
        if ($exeResult) {
            return true;
        } else {
            return false;
        }
    }

    public function getBidRankInfo($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= ' account_id=:account_id';
        $paramsMapSegment[':account_id'] = $condParams['account_id'];
        $condSqlSegment .= ' AND ad_key=:ad_key';
        $paramsMapSegment[':ad_key'] = $condParams['ad_key'];

        if(intval($condParams['bid_id']) > 0){
            $condSqlSegment .= ' and id=:id';
            $paramsMapSegment[':id'] = $condParams['bid_id'];
        }elseif(intval($condParams['product_id']) > 0){
            $condSqlSegment .= ' and product_id=:product_id';
            $paramsMapSegment[':product_id'] = $condParams['product_id'];
            $condSqlSegment .= ' and product_type=:product_type';
            $paramsMapSegment[':product_type'] = $condParams['product_type'];
        }

        if (isset($condParams['del_flag'])) {
            $condSqlSegment .= ' AND del_flag=:del_flag';
            $paramsMapSegment[':del_flag'] = $condParams['del_flag'];
        }else{
            $condSqlSegment .= ' AND del_flag=0';
        }
        if (!empty($condParams['show_date_id'])) {
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $condParams['show_date_id'];
        }
        if (!empty($condParams['bid_date'])) {
            $condSqlSegment .= ' AND bid_date=:bid_date';
            $paramsMapSegment[':bid_date'] = $condParams['bid_date'];
        }
        if (!empty($condParams['start_city_code'])) {
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $condParams['start_city_code'];
        }
        if ($condParams['ad_key'] == 'channel_hot') {
            if (!empty($condParams['cat_type'])) {
                $condSqlSegment .= ' AND cat_type=:cat_type';
                $paramsMapSegment[':cat_type'] = $condParams['cat_type'];
            }
        }
        if ($condParams['ad_key'] == 'class_recommend') {
            if (!empty($condParams['web_class'])) {
                $condSqlSegment .= ' AND web_class=:web_class';
                $paramsMapSegment[':web_class'] = $condParams['web_class'];
            }
        }
        // 判断是否有竞价的搜索页存在
        if ($condParams['ad_key'] == 'search_complex') {
            $condSqlSegment .= ' AND search_keyword=:search_keyword';
            $paramsMapSegment[':search_keyword'] = $condParams['search_keyword'];
        }
        $bidRecord = $this->dbRO->createCommand()
                ->select('id,ranking,bid_ranking,del_flag,bid_price')
                ->from($this->_tblName)
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryRow();
        return $bidRecord;
    }

    /**
     * 招客宝改版-上次竞价成功+冻结成功的总和
     *
     * @author chenjinlong 20131114
     * @param $condParams
     * @return mixed
     */
    public function getLastBidSumMoneyNew($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= ' del_flag=:del_flag';
        $paramsMapSegment[':del_flag'] = 0;
        $condSqlSegment .= ' AND bid_mark=:bid_mark';
        $paramsMapSegment[':bid_mark'] = 2;
        $condSqlSegment .= ' AND fmis_mark=:fmis_mark';
        $paramsMapSegment[':fmis_mark'] = 0;
        $condSqlSegment .= ' and account_id=:account_id';
        $paramsMapSegment[':account_id'] = $condParams['account_id'];
        $condSqlSegment .= ' and id=:bid_id';
        $paramsMapSegment[':bid_id'] = intval($condParams['bid_id']);
        /*if(intval($condParams['product_id']) > 0){
            $condSqlSegment .= ' and product_id=:product_id';
            $paramsMapSegment[':product_id'] = $condParams['product_id'];
            $condSqlSegment .= ' and product_type=:product_type';
            $paramsMapSegment[':product_type'] = $condParams['product_type'];
        }
        if(intval($condParams['bid_id']) > 0){
            $condSqlSegment .= ' and id=:bid_id';
            $paramsMapSegment[':bid_id'] = $condParams['bid_id'];
        }*/
        $condSqlSegment .= ' AND ad_key=:ad_key';
        $paramsMapSegment[':ad_key'] = $condParams['ad_key'];
        $condSqlSegment .= ' AND start_city_code=:start_city_code';
        $paramsMapSegment[':start_city_code'] = intval($condParams['start_city_code']);
        $condSqlSegment .= ' AND show_date_id=:show_date_id';
        $paramsMapSegment[':show_date_id'] = $condParams['show_date_id'];

        if (($condParams['ad_key'] == 'class_recommend') && !empty($condParams['web_class'])) {
            $condSqlSegment .= ' AND web_class=:web_class';
            $paramsMapSegment[':web_class'] = $condParams['web_class'];
        }
        // 添加搜索页检索条件
        if (($condParams['ad_key'] == 'search_complex') && !empty($condParams['search_keyword'])) {
            $condSqlSegment .= ' AND search_keyword=:search_keyword';
            $paramsMapSegment[':search_keyword'] = $condParams['search_keyword'];
        }

        $info = $this->dbRO->createCommand()
                           ->select('account_id,sum(max_limit_price) lastBidSumMoney ,sum(max_limit_price_niu) lastBidSumMoneyNiu ,sum(max_limit_price_coupon) lastBidSumMoneyCoupon')
                           ->from($this->_tblName)
                           ->where($condSqlSegment, $paramsMapSegment)
                           ->queryRow();
        if (empty($info)) {
            return array('bid'=>0,'niu'=>0,'coupon'=>0);
        } else {
            return array('bid'=>$info['lastBidSumMoney'],'niu'=>$info['lastBidSumMoneyNiu'],'coupon'=>$info['lastBidSumMoneyCoupon']);
        }
    }

    /**
     * [bid]获取上次登录至今排名发生变化的竞价记录数
     * @param array $params
     * @param string $lastLoginTime
     * @return int
     */
    public function readRankChangeCount($params, $lastLoginTime) {
        if (empty($params['id'])) {
            return 0;
        }
        $paramsMapSegment = array();
        $condSqlSegment = ' AND account_id=:accountId';
        $paramsMapSegment[':accountId'] = intval($params['id']);
        if ('0000-00-00 00:00:00' != $lastLoginTime) {
            $condSqlSegment .= ' AND update_time>:lastLoginTime';
            $paramsMapSegment[':lastLoginTime'] = $lastLoginTime;
        }

        $rankChangeCount = $this->dbRO->createCommand()
                ->select('COUNT(*) count')
                ->from($this->_tblName)
                ->where('del_flag=0 AND ranking!=bid_ranking' . $condSqlSegment, $paramsMapSegment)
                ->queryScalar();

        return $rankChangeCount;
    }

    /**
     * 管理员控制器用：查询竞拍记录列表(附带去重的SQL效果)
     *
     * @see BidAdminMod::synBidBidProductAdPosType
     * @author chenjinlong 20140514
     * @param $queryParams
     * @return mixed
     */
    public function queryBbpAdPositionTypeRows($queryParams)
    {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if($queryParams['bid_mark'] > 0){
            $condSqlSegment .= ' AND bid_mark=:bid_mark';
            $paramsMapSegment[':bid_mark'] = $queryParams['bid_mark'];
        }
        if($queryParams['start_city_code'] > 0){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $queryParams['start_city_code'];
        }
        if($queryParams['show_date_id'] > 0){
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $queryParams['show_date_id'];
        }
        if(!empty($queryParams['ad_key_like'])){
            $condSqlSegment .= " AND ad_key LIKE :ad_key_like";
            $paramsMapSegment[':ad_key_like'] = "%" . $queryParams['ad_key_like'] . "%";
        }
        $queryFieldString = 'start_city_code,ad_key';
        $info = $this->dbRO->createCommand()
            ->selectDistinct($queryFieldString)
            ->from($this->_tblName)
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryAll();
        return $info;
    }

    /**
     * 管理员控制器用：查询推广记录列表(附带去重的SQL效果)
     *
     * @see BidAdminMod::synBidBidProductAdPosType
     * @author chenjinlong 20140514
     * @param $queryParams
     * @return mixed
     */
    public function queryBspAdPositionTypeRows($queryParams)
    {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if($queryParams['start_city_code'] > 0){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $queryParams['start_city_code'];
        }
        if($queryParams['show_date_id'] > 0){
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $queryParams['show_date_id'];
        }
        if(!empty($queryParams['ad_key_like'])){
            $condSqlSegment .= " AND ad_key LIKE :ad_key_like";
            $paramsMapSegment[':ad_key_like'] = "%" . $queryParams['ad_key_like'] . "%";
        }
        $queryFieldString = 'start_city_code,ad_key';
        $info = $this->dbRO->createCommand()
            ->selectDistinct($queryFieldString)
            ->from('bid_show_product')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryAll();
        return $info;
    }

    /**
     * 管理员控制器用：更新竞拍记录列表
     *
     * @see BidAdminMod::synBidBidProductAdPosType
     * @author chenjinlong 20140514
     * @param $updateParams
     * @param $queryParams
     * @return mixed
     */
    public function updateBidBidProductRows($updateParams, $queryParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if($queryParams['start_city_code'] > 0){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $queryParams['start_city_code'];
        }
        if($queryParams['show_date_id'] > 0){
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $queryParams['show_date_id'];
        }
        if(!empty($queryParams['old_ad_key'])){
            $condSqlSegment .= ' AND ad_key=:old_ad_key';
            $paramsMapSegment[':old_ad_key'] = $queryParams['old_ad_key'];
        }
        $exeResult = $this->dbRW->createCommand()->update(
            'bid_bid_product',
            array(
                'ad_key' => $updateParams['new_ad_key'],
                'update_time' => date('Y-m-d H:i:s'),
                'misc' => 'src_ad_key:' . $queryParams['old_ad_key'],
            ), $condSqlSegment, $paramsMapSegment);
        return $exeResult;
    }

    /**
     * 管理员控制器用：更新推广记录列表
     *
     * @see BidAdminMod::synBidBidProductAdPosType
     * @author chenjinlong 20140514
     * @param $updateParams
     * @param $queryParams
     * @return mixed
     */
    public function updateBidShowProductRows($updateParams, $queryParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if($queryParams['start_city_code'] > 0){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $queryParams['start_city_code'];
        }
        if($queryParams['show_date_id'] > 0){
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $queryParams['show_date_id'];
        }
        if(!empty($queryParams['old_ad_key'])){
            $condSqlSegment .= ' AND ad_key=:old_ad_key';
            $paramsMapSegment[':old_ad_key'] = $queryParams['old_ad_key'];
        }
        $exeResult = $this->dbRW->createCommand()->update(
            'bid_show_product',
            array(
                'ad_key' => $updateParams['new_ad_key'],
                'update_time' => date('Y-m-d H:i:s'),
                'misc' => 'src_ad_key:' . $queryParams['old_ad_key'],
            ), $condSqlSegment, $paramsMapSegment);
        return $exeResult;
    }

    /**
     * 管理员控制器用：更新打包推广的广告位配置表
     *
     * @see BidAdminMod::synBidBidProductAdPosType
     * @author chenjinlong 20140514
     * @param $updateParams
     * @param $queryParams
     * @return mixed
     */
    public function updateBaAdPositionRows($updateParams, $queryParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if($queryParams['start_city_code'] > 0){
            $condSqlSegment .= ' AND start_city_code=:start_city_code';
            $paramsMapSegment[':start_city_code'] = $queryParams['start_city_code'];
        }
        if($queryParams['show_date_id'] > 0){
            $condSqlSegment .= ' AND show_date_id=:show_date_id';
            $paramsMapSegment[':show_date_id'] = $queryParams['show_date_id'];
        }
        if(!empty($queryParams['old_ad_key'])){
            $condSqlSegment .= ' AND ad_key=:old_ad_key';//@TODO-chenjinlong 20140514
            $paramsMapSegment[':old_ad_key'] = $queryParams['old_ad_key'];
        }
        if(!empty($queryParams['ad_name'])){
            $condSqlSegment .= ' AND ad_name=:ad_name';
            $paramsMapSegment[':ad_name'] = $queryParams['ad_name'];
        }
        $exeResult = $this->dbRW->createCommand()->update(
            'ba_ad_position',
            array(
                'ad_key' => $updateParams['new_ad_key'],
                'update_time' => date('Y-m-d H:i:s'),
                'misc' => 'src_ad_key:' . $queryParams['old_ad_key'],
            ), $condSqlSegment, $paramsMapSegment);
        return $exeResult;
    }

    /**
     * 管理员控制器用：查询baAdPositionType有效信息
     *
     * @see BidAdminMod::synBidBidProductAdPosType
     * @author chenjinlong 20140514
     * @param $queryParams
     * @return array
     */
    public function queryBaAdPositionTypeRow($queryParams) {
        $adKey = $queryParams['ad_key'];
        $startCityCode = $queryParams['start_city_code'];
        $sql = "SELECT *
                FROM ba_ad_position_type
                WHERE del_flag = 0
                AND ad_key = '$adKey'
                AND start_city_code='$startCityCode'
                AND del_flag=0";
        $row = $this->dbRO->createCommand($sql)->queryRow();
        if (!empty ($row) && is_array($row)) {
            return $row;
        } else {
            return array();
        }
    }

}

?>