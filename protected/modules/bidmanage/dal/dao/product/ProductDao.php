<?php

Yii::import('application.dal.dao.DaoModule');
Yii::import('application.modules.bidmanage.dal.dao.fmis.StatementDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');

class ProductDao extends DaoModule {

    private $_tblName = 'bid_bid_product';

    /**
     * 查询产品信息
     * @param int $productId
     * @return array
     */
    //mdf by chenjinlong 20121226
    public function getAddedProduct($productIdArr, $userInfo, $productType = null) {
        $condSqlSegment = ' account_id=:account_id';
        $paramsMapSegment[':account_id'] = intval($userInfo['accountId']);

        if ($productType && $productType == 33) {
            $condSqlSegment .= ' AND product_type=33';
        } else {
            $condSqlSegment .= ' AND product_type<>33';
        }

        $info = $this->dbRO->createCommand()
                ->select('account_id accountId,product_id,product_type, del_flag')
                ->from('bid_product')
                ->where(array('and', $condSqlSegment, array('in', 'product_id', $productIdArr)), $paramsMapSegment)
                ->queryAll();
        return $info;
    }

    /**
     * 插入新的竞价产品
     * @param array $productList
     * @param int $accountId
     * @param array $class
     * @return unknown|boolean
     */
    public function createBidProduct($product, $accountId, $class) {
        $result = $this->dbRW->createCommand()->insert('bid_product', array(
            'account_id' => intval($accountId),
            'product_id' => intval($product['productId']),
            'product_type' => intval($product['productType']),
            'start_city_code' => intval($product['startCityCode']),
            'product_name' => mysql_escape_string($product['productName']),
            'agency_product_name' => mysql_escape_string($product['agencyProductName']),
            'checker_flag' => intval($product['checkerFlag']),
            'price' => intval($product['price']),
            'add_uid' => intval($accountId),
            'destination_class'=> intval($product['productLineId']),
            'product_line_name'=> mysql_escape_string($product['productLineName']),
            'web_class_str' => strval(json_encode($class)),
            'add_time' => date('Y-m-d H:i:s'),
            'last_add_uid' => 0,
            'last_add_time' => date('Y-m-d H:i:s'),
            'del_flag' => 0,
            'update_time' => date('Y-m-d H:i:s'),
            'cat_type' => intval($product['catType']),
            'misc' => '',
            'manager_id' => intval($product['managerId']),
                ));
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * [product]更新（添加）已添加的产品
     * @param array $product
     * @param array $condParams
     * @return boolean
     */
    public function updateBidProduct($product, $condParams, $class) {
        $cond = 'product_id=:productId AND product_type=:productType';
        $param = array(':productId' => $condParams['productId'], ':productType' => $condParams['productType']);

        $result = $this->dbRW->createCommand()->update('bid_product', array(
            'product_id' => intval($product['productId']),
            'start_city_code' => intval($product['startCityCode']),
            'product_name' => mysql_escape_string($product['productName']),
            'agency_product_name' => mysql_escape_string($product['agencyProductName']),
            'checker_flag' => intval($product['checkerFlag']),
            'price' => intval($product['price']),
            'cat_type' => intval($product['catType']),
            'web_class_str' => strval(json_encode($class)),
            'del_flag' => intval($product['delFlag']),
            'last_add_uid' => intval($product['lastAddUid']),
            'last_add_time' => !empty($product['lastAddTime'])?$product['lastAddTime']:'0000-00-00 00:00:00',
            'manager_id' => intval($product['managerId']),
                ), $cond, $param);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * [product]查询产品是否存在于已添加的产品列表中
     * @param int $productId
     * @param int $accountId
     * @param int $productType
     * @return array
     */
    public function readBidProduct($productId, $accountId, $productType) {
        $paramsMapSegment[':product_id'] = $productId;
        $paramsMapSegment[':account_id'] = $accountId;
        $paramsMapSegment[':product_type'] = $productType;

        $product = $this->dbRO->createCommand()
                ->select('product_id productId,del_flag delFlag')
                ->from('bid_product')
                ->where('account_id=:account_id AND product_id=:product_id AND product_type=:product_type', $paramsMapSegment)
                ->queryRow();

        return $product;
    }

    public function getProductCount() {
        $info = $this->dbRO->createCommand()
                ->select('count(1) count')
                ->from('bid_product')
                ->queryRow();
        return $info['count'];
    }

    public function getProductListByPage($limit, $start) {
        $info = $this->dbRO->createCommand()
                ->select('product_id productId,product_type productType')
                ->from('bid_product')
                ->limit($limit, $start)
                ->order('last_add_uid')
                ->queryAll();
        return $info;
    }

    public function updateProduct($product) {
        $cond = 'product_id=:productId';
        $param = array(':productId' => $product['productId']);
        
        try {
            $result = $this->dbRW->createCommand()->update('bid_product', array(
                'product_name' => $product['productName'],
                'start_city_code' => $product['startCityCode'],
                'price' => $product['price'],
                'checker_flag' => $product['checkerFlag'],
                'agency_product_name' => $product['agencyProductName'],
                'update_time' => $product['updateTime'],
                'cat_type' => $product['catType'],
                'web_class_str' => $product['webClass'],
                'manager_id' => $product['managerId'],
                    ), $cond, $param);
        } catch (Exception $e) {
            Yii::log($e);
            return false;
        }
        return true;
    }

    /**
     * 查询产品信息
     * @param int $productId
     * @return array
     */
    public function selectProductInfo($productId,$productType,$accountId) {
        if(intval($productId) <= 0 || intval($productType) <= 0){
            return array();
        }
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= ' AND product_id=:product_id AND product_type=:productType';
        $paramsMapSegment[':product_id'] = $productId;
        $paramsMapSegment[':productType'] = $productType;
        if ($accountId && $accountId > 0) {
            $condSqlSegment .= ' AND account_id=:account_id ';
            $paramsMapSegment[':account_id'] = $accountId;
        }
        $info = $this->dbRO->createCommand()
                ->select('account_id accountId,start_city_code,checker_flag,account_id,cat_type,product_name productName,product_line_name productLineName,product_id productId,
                		product_type productType')
                ->from('bid_product')
                ->where('del_flag=0 ' . $condSqlSegment, $paramsMapSegment)
                ->queryRow();

        return $info;
    }

    /**
     * [product]根据产品ID获取产品信息
     * @param array $params
     * @return array
     */
    public function getProductById($productId,$productType) {
        $condSqlSegment = ' del_flag=:delFlag AND product_id=:productId AND product_type=:productType';
        $paramsMapSegment[':delFlag'] = 0;
        $paramsMapSegment[':productId'] = $productId;
        $paramsMapSegment[':productType'] = $productType;

        $product = $this->dbRO->createCommand()->select('product_name productName,
                        agency_product_name agencyProductName')
                ->from('bid_product')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryRow();

        return $product;
    }

    /**
     * 招客宝改版-查询指定打包开始日期的呈现打包日期ID列信息
     *
     * @author huangxun 20131121
     * @param array
     * @return array
     */
    public function getOneDayAgoTillNowIdArr($condParams)
    {
        $condSqlSegment = " del_flag=:delFlag AND status=1 ";
        $paramsMapSegment[':delFlag'] = 0;
        if($condParams['is_pass_bid'] == 1){
            $paramsMapSegment[':nowTime'] = date("Y-m-d H");
            $paramsMapSegment[':oneDayAgo'] = date('Y-m-d H', strtotime("-1 day"));
            $condSqlSegment .= " AND CONCAT(bid_end_date,' ',IF(bid_end_time<10,CONCAT('0',bid_end_time),bid_end_time)) >= :oneDayAgo
            AND CONCAT(bid_end_date,' ',IF(bid_end_time<10,CONCAT('0',bid_end_time),bid_end_time)) < :nowTime";
        }
        $bidShowDateIdArr = $this->dbRO->createCommand()
                                       ->select('*')
                                       ->from('bid_show_date')
                                       ->where($condSqlSegment, $paramsMapSegment)
                                       ->queryColumn();
        if(!empty($bidShowDateIdArr)){
            return $bidShowDateIdArr;
        }else{
            return array();
        }
    }

    /**
     * [product]获取所有一天前到现在的竞价成功的产品
     *
     * @author huangxun 20131121
     * @param array
     * @return array
     */
    public function getOneDayBidProduct($showDateIdArr, $conditionParams) {
        if(!empty($showDateIdArr)){
            $condSqlSegment = " a.del_flag=0 AND a.bid_mark=:bid_mark AND c.del_flag=0 AND c.status=1";
            $condParams = array(
                ':bid_mark' => $conditionParams['bid_mark'],
            );
            $productArray = $this->dbRO->createCommand()
                ->select('a.id,a.account_id accountId,a.product_type productType,a.product_id productId,a.ad_key adKey,a.cat_type catType,
                       a.web_class webClass,a.bid_price bidPrice,a.start_city_code startCityCode,a.search_keyword,b.checker_flag checkerFlag,
                       c.show_start_date showStartDate,c.show_end_date showEndDate')
                ->from('bid_bid_product a')
                ->leftjoin('bid_product b','a.account_id=b.account_id AND a.product_type=b.product_type AND a.product_id=b.product_id AND b.del_flag=0')
                ->leftjoin('bid_show_date c','a.show_date_id=c.id')
                ->where(array('AND', $condSqlSegment, array('IN', 'a.show_date_id', $showDateIdArr)), $condParams)
                ->queryAll();
            return $productArray;
        }else{
            return array();
        }
    }

    /**
     * 增加截图的url到bid_show_product表中
     * @param unknown_type $product
     */
    public function updateScreenshotUrl($product) {
        $cond = 'id=:bidShowProductId';
        $param = array(':bidShowProductId' => $product['bidShowProductId']);
        
        try {
            $result = $this->dbRW->createCommand()->update('bid_show_product', array(
                'screenshot_url' => $product['screenShotUrl'],
                'update_time' => date('Y-m-d H:i:s'),
                    ), $cond, $param);
        } catch (Exception $e) {
            Yii::log($e);
            return false;
        }
        return true;
    }

    /**
     * 搜索条件
     * @param array $condParams
     * @return array
     */
    public function searchCondition($condParams){
        $condSqlSegment = '';
        $condSqlSegment .= " a.del_flag = 0 AND c.del_flag = 0";
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND a.account_id IN ('.$condParams['accountId'].')';
        }
        if ($condParams['startDate'] > '0000-00-00') {
            $condSqlSegment .= " AND c.show_end_date >= '" . $condParams['startDate'] . "'";
        }
        if ($condParams['endDate']> '0000-00-00') {
            $condSqlSegment .= " AND c.show_start_date <= '" . $condParams['endDate'] . "'";
        }
        if ($condParams['showDateId'] > 0) {
            $condSqlSegment .= " AND a.show_date_id = " . $condParams['showDateId'];
        }
        if (!empty($condParams['adKey'])) {
            if ($condParams['adKey'] == 'index_chosen') {
                // 首页广告位统一处理
                $condSqlSegment .= " AND a.ad_key LIKE 'index_chosen%'";
            } elseif ($condParams['adKey'] == 'channel_chosen') {
                // 频道页广告位统一处理
                $condSqlSegment .= " AND a.ad_key LIKE 'channel_chosen%'";
            } else {
                $condSqlSegment .= " AND a.ad_key = '" . $condParams['adKey'] . "'";
            }
        }
        if (!empty($condParams['startCityCode'])) {
            $condSqlSegment .= ' AND a.start_city_code = ' . $condParams['startCityCode'];
        }
        // 匹配产品线ID串
        if (!empty($condParams['productLineIdStr'])) {
            $condSqlSegment .= ' AND b.destination_class IN ('.$condParams['productLineIdStr'].')';
        }
        if (!empty($condParams['productId'])) {
            if (is_numeric($condParams['productId'])) {
                $condSqlSegment .= ' AND a.product_id = ' . $condParams['productId'];
            } else {
                return array();
            }
        }
        if (!empty($condParams['productName'])) {
            $condSqlSegment .= " AND b.product_name LIKE '%" . $condParams['productName'] ."%'";
        }
        if (!empty($condParams['productLineName'])) {
            $condSqlSegment .= " AND b.product_line_name LIKE '%" . $condParams['productLineName'] ."%'";
        }
        if (!empty($condParams['checkFlag'])) {
            $condSqlSegment .= ' AND b.checker_flag = ' . $condParams['checkFlag'];
        }
        if (!empty($condParams['managerId'])) {
            $condSqlSegment .= ' AND b.manager_id = ' . $condParams['managerId'];
        }
        if (!empty($condParams['bidState'])) {
            $nowTime = date("Y-m-d H");
            $condSqlSegment .= " AND c.status = 1";
            if ($condParams['bidState'] == 1) {
                $condSqlSegment .= " AND a.bid_mark IN (2,-3) AND '" . $nowTime . "' >= CONCAT(c.bid_start_date,' 00')
                AND '" . $nowTime . "' < CONCAT(c.bid_end_date,' ',IF(c.bid_end_time<10,CONCAT('0',c.bid_end_time),c.bid_end_time))";
            } else if ($condParams['bidState'] == 2) {
                $condSqlSegment .= " AND a.bid_mark = 2
                AND '" . $nowTime . "' >= CONCAT(c.bid_end_date,' ',IF(c.bid_end_time<10,CONCAT('0',c.bid_end_time),c.bid_end_time))
                AND '" . $nowTime . "' < CONCAT(c.show_start_date,' 00')";
            } else if ($condParams['bidState'] == -1) {
                $condSqlSegment .= " AND a.bid_mark IN (-1,-2,-3)
                AND '" . $nowTime . "' >= CONCAT(c.bid_end_date,' ',IF(c.bid_end_time<10,CONCAT('0',c.bid_end_time),c.bid_end_time))";
            } else if ($condParams['bidState'] == 3) {
                $condSqlSegment .= " AND a.bid_mark = 1 AND '" . $nowTime . "' >= CONCAT(c.show_start_date,' 00')";
            }
        }
        // 广告位名称模糊查询条件
        if (!empty($condParams['adName']) && 'class_recommend' != $condParams['adKey']) {
            $condSqlSegment .= " AND d.del_flag=0 AND d.ad_name LIKE '%".$condParams['adName']."%'";
        }
        // 分类页新增查询条件
        if (!empty($condParams['adName']) && 'class_recommend' == $condParams['adKey']) {
            if (!empty($condParams['webClassId'])) {
                $condSqlSegment .= " AND a.web_class IN (".implode(",",$condParams['webClassId']).")";
            } else {
                return array();
            }
        }
        return array('conSqlSegment'=>$condSqlSegment);
    }

    /**
     * [product]hg-查询推广产品列表
     * @param $condParams
     * @return array
     */
    public function getHgProductList($condParams) {
        $condSql = '';
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $statementDao = new StatementDao();
            $accountIdStr = $statementDao->getIdByBrandName($condParams['vendorName']);
            if($accountIdStr){
                $condParams['accountId'] = $accountIdStr;
            } else {
                return array();
            }
        }
        // 根据供应商编号获取accountId
        if($condParams['vendorId']){
            $userManageDao = new UserManageDao();
            $accountId = $userManageDao->getAccountInfoByAgentId($condParams['vendorId']);
            if ($accountId) {
                $condParams['accountId'] = $accountId['id'];
            } else {
                return array();
            }
        }
        if($condParams){
            // 拼接搜索条件
            $condition = $this->searchCondition($condParams);
            $condSql .= $condition['conSqlSegment'];
        }
        $result = $this->getResults($condParams,$condSql);
        return $result;
    }

    /**
     * [product]hg-查询推广产品列表sql语句
     * @param $condParams,$condSqlSegment,$paramsMapSegment
     * @return array
     */
    public function getResults($condParams,$condSql) {

        // 添加监控
        $posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);

        if (empty($condSql)) {
            return array();
        } else {
            $orderSql = ' ORDER BY c.show_start_date,a.id';
            if ($condParams['bidState'] == 3 && $condParams['sortName'] == 'bidPrice') {
                $orderSql .= ",a.bid_price " . $condParams['sortOrder'] . " ";
            }
            $limitSql = " LIMIT " . $condParams['start'] . "," . $condParams['limit'];
            $sql = "SELECT " .
                        "a.id bidId,a.product_id productId,a.product_type productType,a.show_date_id showDateId,a.ad_key adKey,a.start_city_code startCityCode,
						a.web_class webClass,a.bid_price bidPrice,a.bid_mark bidMark,a.ranking ranking,ROUND(a.max_limit_price) maxLimitPrice,a.bid_price_niu bidPriceNiu,a.max_limit_price_niu maxLimitPriceNiu,a.bid_price_coupon bidPriceCoupon,a.max_limit_price_coupon maxLimitPriceCoupon,
						b.product_name productName,b.checker_flag checkerFlag,b.manager_id managerId,c.show_start_date showStartDate,
						c.show_end_date showEndDate,c.bid_start_date bidStartDate,c.bid_start_time bidStartTime,c.bid_end_date bidEndDate,c.bid_end_time bidEndTime,a.search_keyword searchName,a.account_id accountId " .
                    "FROM " .
                        "bid_bid_product a " .
                    "LEFT JOIN " .
                        "bid_product b " .
                    "ON " .
                        "a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type AND b.del_flag=0 " .
                    "LEFT JOIN " .
                        "bid_show_date c " .
                    "ON " .
                        "a.show_date_id = c.id ";
            if (!empty($condParams['adName']) && 'class_recommend' != $condParams['adKey']) {
                $sql .= "LEFT JOIN " .
                            "ba_ad_position d " .
                        "ON " .
                            "a.ad_key = d.ad_key AND a.start_city_code = d.start_city_code AND d.show_date_id = c.id ";
            }
            $sql .= "WHERE" . $condSql . $orderSql . $limitSql;

            try {var_dump($sql);die;
                $result = $this->executeSql($sql, self::ALL);
            } catch(Exception $e) {
                // 抛异常
                throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON.$sql.Symbol::CONS_DOU_COLON."向数据库查询竞拍列表异常", $e);
            }
        }

        // 添加收尾日志，对应性能监控
        $bbLog = new BBLog();
        if ($bbLog->isInfo()) {
            $bbLog->logSql($sql, $posTry);
        }

        return $result;
    }

    /**
     * [product]hg-查询推广产品列表总数
     * @param $condParams
     * @return array
     */
    public function getHgProductListCount($condParams) {
        // 添加监控
        $posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);

        $condSql = '';
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $statementDao = new StatementDao();
            $accountIdStr = $statementDao->getIdByBrandName($condParams['vendorName']);
            if($accountIdStr){
                $condParams['accountId'] = $accountIdStr;
            } else {
                return array();
            }
        }
        // 根据供应商编号获取accountId
        if($condParams['vendorId']){
            $userManageDao = new UserManageDao();
            $accountId = $userManageDao->getAccountInfoByAgentId($condParams['vendorId']);
            if ($accountId) {
                $condParams['accountId'] = $accountId['id'];
            } else {
                return array();
            }
        }
        if($condParams){
            $condition = $this->searchCondition($condParams);
            $condSql .= $condition['conSqlSegment'];
        }
        if (empty($condSql)) {
            return array();
        } else {
            $sql = "SELECT " .
                        "COUNT(0) count " .
                    "FROM " .
                        "bid_bid_product a " .
                    "LEFT JOIN " .
                        "bid_product b " .
                    "ON " .
                        "a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type AND b.del_flag=0 " .
                    "LEFT JOIN " .
                        "bid_show_date c " .
                    "ON " .
                        "a.show_date_id = c.id ";
            if (!empty($condParams['adName']) && 'class_recommend' != $condParams['adKey']) {
                $sql .= "LEFT JOIN " .
                    "ba_ad_position d " .
                    "ON " .
                    "a.ad_key = d.ad_key AND a.start_city_code = d.start_city_code AND d.show_date_id = c.id ";
            }
            $sql .= "WHERE" . $condSql;

            try {
                $result = $this->executeSql($sql, self::ROW);
            } catch(Exception $e) {
                // 抛异常
                throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON.$sql.Symbol::CONS_DOU_COLON."向数据库查询竞拍列表异常", $e);
            }

        }

        // 添加收尾日志，对应性能监控
        $bbLog = new BBLog();
        if ($bbLog->isInfo()) {
            $bbLog->logSql($sql, $posTry);
        }

        return $result;
    }

    /**
     * 查询列表打包时间参数
     * @param array $param
     * @return array
     */
    public function getDateParam($condParams) {
        $condSqlSegment = 'a.del_flag=0 AND b.del_flag = 0 AND a.status = 1';
        $paramsMapSegment = array();
        if (!empty($condParams['bidState'])) {
            $paramsMapSegment[':nowTime'] = date("Y-m-d H");
            if ($condParams['bidState'] == 1) {
                $condSqlSegment .= " AND :nowTime >= CONCAT(a.bid_start_date,' ',IF(a.bid_start_time<10,CONCAT('0',a.bid_start_time),a.bid_start_time))
                AND :nowTime < CONCAT(a.bid_end_date,' ',IF(a.bid_end_time<10,CONCAT('0',a.bid_end_time),a.bid_end_time))";
            } else if ($condParams['bidState'] == 2) {
                $condSqlSegment .= " AND :nowTime >= CONCAT(a.bid_end_date,' ',IF(a.bid_end_time<10,CONCAT('0',a.bid_end_time),a.bid_end_time))
                AND :nowTime < CONCAT(a.show_start_date,' 00')";
            } else if ($condParams['bidState'] == 3) {
                $condSqlSegment .= " AND :nowTime >= CONCAT(a.show_start_date,' 00') AND :nowTime < CONCAT(a.show_end_date,' 00')";
            }
            $rows = $this->dbRO->createCommand()
                ->select('DISTINCT(a.id) AS dateId,CONCAT(a.show_start_date, " ~ ", a.show_end_date) AS showDate, GROUP_CONCAT(b.ad_name ORDER BY b.ad_key) AS adName')
                ->from('bid_show_date a')
                ->leftjoin('ba_ad_position b','a.id = b.show_date_id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->group('a.id')
                ->queryAll();
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * 搜索条件-3.0
     * @param array $condParams
     * @return array
     */
    public function newSearchCondition($condParams){
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= " a.del_flag=:del_flag AND b.del_flag=:del_flag";
        $paramsMapSegment[':del_flag'] = 0;
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND a.account_id IN ('.$condParams['accountId'].')';
        }
        if ($condParams['startDate'] > '0000-00-00') {
            $condSqlSegment .= " AND b.show_end_date>=:startDate";
            $paramsMapSegment[':startDate'] = $condParams['startDate'];
        }
        if ($condParams['endDate']> '0000-00-00') {
            $condSqlSegment .= " AND b.show_start_date<=:endDate";
            $paramsMapSegment[':endDate'] = $condParams['endDate'];
        }
        if (!empty($condParams['showDateId'])) {
            $condSqlSegment .= ' AND a.show_date_id IN ('.trim(implode(',', $condParams['showDateId'])).')';
        }
        if (!empty($condParams['adKey']) && $condParams['adKey'] != 'all') {
            if ($condParams['adKey'] == 'index_chosen_all') {
                // 首页广告位统一处理
                $condSqlSegment .= " AND a.ad_key LIKE 'index_chosen%'";
            } elseif ($condParams['adKey'] == 'channel_chosen_all') {
                // 频道页广告位统一处理
                $condSqlSegment .= " AND a.ad_key LIKE 'channel_chosen%'";
            } else {
                $condSqlSegment .= ' AND a.ad_key = :adKey';
                $paramsMapSegment[':adKey'] = $condParams['adKey'];
            }
        }
        if (!empty($condParams['startCityCode'])) {
            $condSqlSegment .= ' AND a.start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $condParams['startCityCode'];
        }
        if (!empty($condParams['bidState'])) {
            $paramsMapSegment[':nowTime'] = date("Y-m-d H");
            $condSqlSegment .= " AND b.status = 1";
            if ($condParams['bidState'] == 1) {
                $condSqlSegment .= " AND a.bid_mark IN (2,-3)
                AND :nowTime >= CONCAT(b.bid_start_date,' ',IF(b.bid_start_time<10,CONCAT('0',b.bid_start_time),b.bid_start_time))
                AND :nowTime < CONCAT(b.bid_end_date,' ',IF(b.bid_end_time<10,CONCAT('0',b.bid_end_time),b.bid_end_time))";
            } else if ($condParams['bidState'] == 2) {
                $condSqlSegment .= " AND a.bid_mark = 2
                AND :nowTime >= CONCAT(b.bid_end_date,' ',IF(b.bid_end_time<10,CONCAT('0',b.bid_end_time),b.bid_end_time))
                AND :nowTime < CONCAT(b.show_start_date,' 00')";
            } else if ($condParams['bidState'] == 3) {
                $condSqlSegment .= " AND :nowTime >= CONCAT(b.show_start_date,' 00') AND :nowTime < CONCAT(b.show_end_date,' 24')";
            } else if ($condParams['bidState'] == 4) {
                $condSqlSegment .= " AND :nowTime > CONCAT(b.show_end_date,' 00')";
            } else if ($condParams['bidState'] == -1) {
                $condSqlSegment .= " AND a.bid_mark IN (-1,-2,-3)
                AND :nowTime >= CONCAT(b.bid_end_date,' ',IF(b.bid_end_time<10,CONCAT('0',b.bid_end_time),b.bid_end_time))";
            }
        }
        return array('conSqlSegment'=>$condSqlSegment,'paramsMapSegment' =>$paramsMapSegment);
    }

    /**
     * [product]查询推广位置列表-3.0
     * @param $condParams
     * @return array
     */
    public function getNewProductList($condParams) {
        $rows = array();
        $condSqlSegment = '';
        $paramsMapSegment = array();
        if($condParams){
            // 拼接搜索条件
            $condition = $this->newSearchCondition($condParams);
            $condSqlSegment = $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $orderSqlSegment = ' b.show_start_date,a.ad_key,a.id';
        if ($condParams['bidState'] == 1 || $condParams['bidState'] == 2 || $condParams['bidState'] == -1) {
            $rows = $this->dbRO->createCommand()
                ->select('a.id bidId,a.show_date_id showDateId,a.ad_key adKey,a.start_city_code startCityCode,a.cat_type catType,a.search_keyword searchName,a.account_id accountId,
				    a.web_class webClass,a.bid_mark bidMark,a.is_buyout isBuyout,b.show_start_date showStartDate,b.show_end_date showEndDate,
				    b.bid_start_date bidStartDate,b.bid_start_time bidStartTime,b.bid_end_date bidEndDate,b.bid_end_time bidEndTime,b.replace_end_time replaceEndTime')
                ->from('bid_bid_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->order($orderSqlSegment)
                // 使用group by来唯一确定数据
                ->group('a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id')
                ->limit($condParams['limit'], $condParams['start'])
                ->queryAll();
        } elseif ($condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
            $rows = $this->dbRO->createCommand()
                ->select('a.id bidId, a.bid_id bidIdNew, a.show_date_id showDateId,a.ad_key adKey,a.start_city_code startCityCode,a.cat_type catType,a.search_keyword searchName,a.account_id accountId,
				    a.web_class webClass,a.is_buyout isBuyout,b.show_start_date showStartDate,b.show_end_date showEndDate,
				    b.bid_start_date bidStartDate,b.bid_start_time bidStartTime,b.bid_end_date bidEndDate,b.bid_end_time bidEndTime,b.replace_end_time replaceEndTime')
                ->from('bid_show_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->order($orderSqlSegment)
                // 使用group by来唯一确定数据
                ->group('a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id')
                ->limit($condParams['limit'], $condParams['start'])
                ->queryAll();
        }
        return $rows;
    }

    /**
     * [product]查询推广位置列表总数-3.0
     * @param $condParams
     * @return array
     */
    public function getNewProductListCount($condParams) {
        $rows = array();
        $condSqlSegment = '';
        $paramsMapSegment = array();
        if($condParams){
            $condition = $this->newSearchCondition($condParams);
            $condSqlSegment = $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        if ($condParams['bidState'] == 1 || $condParams['bidState'] == 2 || $condParams['bidState'] == -1) {
            $rows = $this->dbRO->createCommand()
                // 使用distinct来唯一确定数据条数
                ->select('COUNT(DISTINCT a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id) count')
                ->from('bid_bid_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryScalar();
        } elseif ($condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
            $rows = $this->dbRO->createCommand()
                // 使用distinct来唯一确定数据条数
                ->select('COUNT(DISTINCT a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id) count')
                ->from('bid_show_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryScalar();
        }
        return $rows;
    }

    /**
     * [product]查询位置信息-3.0
     * @param $condParams
     * @return array
     */
    public function getPositionInfo($param) {
   		// 分类获取广告位底价
   		if ('class_recommend' == $param['ad_key'] && 136 < $param['show_date_id']) {
   			// 分类页   新版  后上线
   			// 查询分类页父级信息
   			$sqlFa = "SELECT web_class, start_city_code, class_depth, parent_class, parent_depth FROM position_sync_class WHERE web_class = ".$param['web_class']." AND start_city_code = ".$param['start_city_code']." AND del_flag = 0 AND parent_depth IN (1,2)";
   			$faRows = $this->executeSql($sqlFa, self::ALL);
   			$data = array();
   			// 获取一级和二级分类报价信息
   			foreach ($faRows as $faRowsObj) {
   				if (1 == $faRowsObj['parent_depth']) {
   					// 一级分类报价
   					$sqlOne = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$param['start_city_code']." AND del_flag = 0 AND show_date_id = ".$param['show_date_id'];
   					array_push($data, $this->executeSql($sqlOne, self::ROW));
   				} else if (2 == $faRowsObj['parent_depth']) {
   					// 二级分类报价
   					$sqlTwo = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$param['start_city_code']." AND del_flag = 0 AND show_date_id = ".$param['show_date_id'];
   					array_push($data, $this->executeSql($sqlTwo, self::ROW));
   				}
   				
   			}
   			// 查询自身的分类报价
   			$sqlOwn = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$param['web_class']." AND start_city_code = ".$param['start_city_code']." AND del_flag = 0 AND show_date_id = ".$param['show_date_id'];
   			$dataOwn = $this->executeSql($sqlOwn, self::ROW);
   			// 对比更新时间
   			foreach ($data as $dataObj) {
   				if (empty($dataOwn) || strtotime($dataObj['update_time']) > strtotime($dataOwn['update_time'])) {
   					$dataOwn['floor_price'] = $dataObj['floor_price'];
   					$dataOwn['ad_product_count'] = $dataObj['ad_product_count'];
   					$dataOwn['coupon_use_percent'] = $dataObj['coupon_use_percent'];
   					$dataOwn['update_time'] = $dataObj['update_time'];
   				}
   			}
   			
   			// 返回结果
   			return $dataOwn;
   		} else {
   			// 初始化动态SQL
			$dySql = "";
			if (!empty($param['start_city_code']) && (strpos($param['ad_key'],'index_chosen') !== false || strpos($param['ad_key'],'channel_chosen') !== false)) {
				$dySql = $dySql." and start_city_code =".$param['start_city_code'];
			}
			$sqlRow = "SELECT id,ad_key,ad_name,floor_price,ad_product_count ".
					"FROM ba_ad_position WHERE show_date_id = ".$param['show_date_id']." AND ad_key = '".$param['ad_key']."' AND del_flag = 0 ".$dySql;
			return $this->executeSql($sqlRow, self::ROW);
   		}
    }
    
    /**
     * 获取竞价记录详细信息
     */
    public function getBidDetailInfo($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['showDateId'])) {
            $condSqlSegment .= ' AND show_date_id=:showDateId';
            $paramsMapSegment[':showDateId'] = $condParams['showDateId'];
        }
        if (!empty($condParams['adKey'])) {
            $condSqlSegment .= ' AND ad_key=:adKey';
            $paramsMapSegment[':adKey'] = $condParams['adKey'];
        }
        if (!empty($condParams['startCityCode'])) {
            $condSqlSegment .= ' AND start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $condParams['startCityCode'];
        }
        if (!empty($condParams['webClass'])) {
            $condSqlSegment .= ' AND web_class=:webClass';
            $paramsMapSegment[':webClass'] = $condParams['webClass'];
        }
        if (!empty($condParams['searchKeyword'])) {
            $condSqlSegment .= ' AND search_keyword=:searchKeyword';
            $paramsMapSegment[':searchKeyword'] = $condParams['searchKeyword'];
        }
        $rows = $this->dbRO->createCommand()
                    ->select('id')
                    ->from('bid_bid_product')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->order('ranking')
                    ->queryAll();
        return $rows;
    }

    /**
     * [product]查询参与竞价产品数量和排名信息-3.0
     * @param $condParams
     * @return array
     */
    public function getBidAdInfo($condParams,$flag) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['showDateId'])) {
            $condSqlSegment .= ' AND show_date_id=:showDateId';
            $paramsMapSegment[':showDateId'] = $condParams['showDateId'];
        }
        if (!empty($condParams['adKey'])) {
            $condSqlSegment .= ' AND ad_key=:adKey';
            $paramsMapSegment[':adKey'] = $condParams['adKey'];
        }
        if (!empty($condParams['startCityCode'])) {
            $condSqlSegment .= ' AND start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $condParams['startCityCode'];
        }
        if (!empty($condParams['webClass'])) {
            $condSqlSegment .= ' AND web_class=:webClass';
            $paramsMapSegment[':webClass'] = $condParams['webClass'];
        }
        if (!empty($condParams['searchKeyword'])) {
            $condSqlSegment .= ' AND search_keyword=:searchKeyword';
            $paramsMapSegment[':searchKeyword'] = $condParams['searchKeyword'];
        }
        if ($condParams['bidState'] == 1) {
            $condSqlSegment .= ' AND bid_mark IN (2,-3)';
        }
        if ($condParams['bidState'] == -1) {
            $condSqlSegment .= ' AND bid_mark IN (-1,-2,-3)';
        }
        if ($flag == 1) {
            if ($condParams['bidState'] == 1 || $condParams['bidState'] == 2 || $condParams['bidState'] == -1) {
                if ($condParams['bidState'] != 1 && $condParams['bidState'] != -1 && !empty($condParams['bidMark'])) {
                    $condSqlSegment .= ' AND bid_mark=:bidMark';
                    $paramsMapSegment[':bidMark'] = $condParams['bidMark'];
                }
                $rows = $this->dbRO->createCommand()
                    ->select('id,ranking,bid_mark bidMark, login_name loginName')
                    ->from('bid_bid_product')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->order('ranking')
                    ->queryAll();
            } elseif ($condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
                $rows = $this->dbRO->createCommand()
                    ->select('id,ranking')
                    ->from('bid_show_product')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->order('ranking')
                    ->queryAll();
            }
        } elseif ($flag == 2) {
            if ($condParams['bidState'] != 1 && $condParams['bidState'] != -1 && !empty($condParams['bidMark'])) {
                $condSqlSegment .= ' AND bid_mark=:bidMark';
                $paramsMapSegment[':bidMark'] = $condParams['bidMark'];
            }
            $rows = $this->dbRO->createCommand()
                ->select('id,ranking,CAST(bid_price AS DECIMAL(11,0)) bidPrice,CAST(max_limit_price AS DECIMAL(11,0)) maxLimitPrice,bid_mark bidMark, login_name loginName')
                ->from('bid_bid_product')
                ->where($condSqlSegment, $paramsMapSegment)
                ->order('ranking')
                ->queryAll();
        }
        return $rows;
    }

    /**
     * [product]查询推广结束的show_id-3.0
     * @param $condParams
     * @return array
     */
    public function getShowId($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['bidId'])) {
            $condSqlSegment .= ' AND bid_id=:bidId';
            $paramsMapSegment[':bidId'] = $condParams['bidId'];
        }
        $rows = $this->dbRO->createCommand()
            ->select('id showId')
            ->from('bid_show_product')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();
        return $rows;
    }

    /**
     * [product]查询竞价成功的产品信息-3.0
     * @param $condParams
     * @return array
     */
    public function bidProductInfo($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['bidId'])) {
            if ($condParams['bidState'] == 2) {
                $condSqlSegment .= ' AND bid_id IN ('.trim(implode(',', $condParams['bidId'])).')';
                $rows = $this->dbRO->createCommand()
                    ->select('id,content_type contentType,content_id contentId')
                    ->from('bid_bid_content')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryAll();
            } elseif ($condParams['bidState'] == 3) {
                $condSqlSegment .= ' AND show_id IN ('.trim(implode(',', $condParams['bidId'])).')';
                $rows = $this->dbRO->createCommand()
                    ->select('id,content_type contentType,content_id contentId')
                    ->from('bid_show_content')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryAll();
            }
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * [product]查询广告内容信息-3.0
     * @param $condParams
     * @return array
     */
    public function getAdContent($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['id'])) {
            if ($condParams['bidState'] == -1) {
                $condSqlSegment .= ' AND bid_id=:id';
                $paramsMapSegment[':id'] = $condParams['id'];
                $rows = $this->dbRO->createCommand()
                    ->select('content_id contentId,content_type contentType')
                    ->from('bid_bid_content')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryRow();
            } elseif ($condParams['bidState'] == 4) {
                $condSqlSegment .= ' AND show_id=:id';
                $paramsMapSegment[':id'] = $condParams['id'];
                $rows = $this->dbRO->createCommand()
                    ->select('content_id contentId,content_type contentType')
                    ->from('bid_show_content')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryRow();
            }
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * [product]查询广告附加信息-3.0
     * @param $condParams
     * @return array
     */
    public function getAdAddition($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['id'])) {
            if ($condParams['bidState'] == -1) {
                $condSqlSegment .= ' AND bid_id=:id';
                $paramsMapSegment[':id'] = $condParams['id'];
                $rows = $this->dbRO->createCommand()
                    ->select('vas_key vasKey,CAST(bid_price AS DECIMAL(11,0)) bidPrice')
                    ->from('bid_bid_vas')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryAll();
            } elseif ($condParams['bidState'] == 4) {
                $condSqlSegment .= ' AND show_id=:id';
                $paramsMapSegment[':id'] = $condParams['id'];
                $rows = $this->dbRO->createCommand()
                    ->select('vas_key vasKey,CAST(bid_price AS DECIMAL(11,0)) bidPrice')
                    ->from('bid_show_vas')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryAll();
            }
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * [product]查询附加信息名称-3.0
     * @param $condParams
     * @return array
     */
    public function getVasName($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['vasKey'])) {
            $condSqlSegment .= ' AND vas_key=:vasKey';
            $paramsMapSegment[':vasKey'] = $condParams['vasKey'];
        }
        $rows = $this->dbRO->createCommand()
            ->select('vas_name vasName')
            ->from('ba_ad_vas_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->limit(1)
            ->queryRow();
        return $rows;
    }

    /**
     * [product]查询参与竞价产品数量和排名信息-3.0
     * @param $condParams
     * @return array
     */
    public function getBidProductInfo($condParams) {
        $condSqlSegment = 'del_flag=0';
        $paramsMapSegment = array();
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
        }
        if (!empty($condParams['productId']) && !empty($condParams['productType'])) {
            $condSqlSegment .= ' AND product_id=:productId AND product_type=:productType';
            $paramsMapSegment[':productId'] = $condParams['productId'];
            $paramsMapSegment[':productType'] = $condParams['productType'];
            $rows = $this->dbRO->createCommand()
                ->select('product_name productName,agency_product_name agencyProductName,checker_flag checkerFlag')
                ->from('bid_product')
                ->where($condSqlSegment, $paramsMapSegment)
                ->limit(1)
                ->queryRow();
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * [product]查询出发城市名称
     * @param unknown_type $condParams
     * @return unknown
     */
    public function getCityName($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= " mark=:mark";
        $paramsMapSegment[':mark'] = 0;
        if (!empty($condParams)) {
            $condSqlSegment .= ' AND code=:code';
            $paramsMapSegment[':code'] = $condParams;
            $cityName = $this->dbRO->createCommand()
                ->select('name')
                ->from('departure')
                ->where($condSqlSegment, $paramsMapSegment)
                ->limit(1)
                ->queryRow();
            return $cityName;
        } else {
            return array();
        }
    }

    /**
     * 查询出价唯独
     * @param array $param
     * @return array
     */
    public function queryBidColumn($param) {
    	// 初始化SQL语句
		// $sql = "SELECT DISTINCT(id) AS dateId, CONCAT(show_start_date, ' ~ ', show_end_date) AS showDate FROM bid_show_date WHERE DATE_FORMAT(CONCAT(bid_start_date, CONCAT(' ', bid_start_time)), '%Y-%m-%d %H') <= DATE_FORMAT(NOW(), '%Y-%m-%d %H') AND DATE_FORMAT(CONCAT(bid_end_date, CONCAT(' ', bid_end_time)), '%Y-%m-%d %H') > DATE_FORMAT(NOW(), '%Y-%m-%d %H') AND del_flag=0 AND status = 1";
    	$sql = "SELECT DISTINCT(a.id) AS dateId, CONCAT(show_start_date, ' ~ ', show_end_date) AS showDate, IFNULL(GROUP_CONCAT(b.ad_name ORDER BY b.ad_key), '') AS adName, IFNULL(GROUP_CONCAT(b.ad_key), '') AS adKey FROM bid_show_date a LEFT JOIN ba_ad_position b ON a.id = b.show_date_id AND b.del_flag = 0 WHERE DATE_FORMAT(CONCAT(bid_start_date, CONCAT(' ', bid_start_time)), '%Y-%m-%d %H') <= DATE_FORMAT(NOW(), '%Y-%m-%d %H') AND DATE_FORMAT(CONCAT(bid_end_date, CONCAT(' ', bid_end_time)), '%Y-%m-%d %H') > DATE_FORMAT(NOW(), '%Y-%m-%d %H') AND a.del_flag=0 AND STATUS = 1 GROUP BY a.id";
    		    
		// 查询并返回参数
		return $this->dbRO->createCommand($sql)->queryAll();
    }
    
    /**
     * 招客宝改版-查询所有出发城市数组
     *
     * @author chenjinlong 20131121
     * @param $params
     * @return array
     */
    public function getAllDepartureCityInfo($params)
    {
        $appendSql = '';
        if($params['cityCode'] > 0){
            $code = $params['cityCode'];
            $appendSql .= ' and code={$code}';
        }
        $sql = "select code,type,name,letter from departure where mark=0" . $appendSql;
        $cityRows = $this->dbRO->createCommand($sql)->queryAll();
        if(!empty($cityRows)){
            return $cityRows;
        }else{
            return array();
        }
    }

    /**
     * 查询即将上线的产品的邮件信息
     * @return array
     */
    public function queryMailLineProduct() {
    	// 初始化SQL语句
    	$sql = "SELECT a.id, a.account_id accountId, d.vendor_id agencyId, a.product_type productType, a.bid_price bidPrice, a.product_id productId, IFNULL(b.product_name, '') productName, d.account_name accountName, e.name startCityName, a.ad_key adKey,
    			a.cat_type catType, a.web_class webClass,a.start_city_code startCityCode,b.checker_flag checkerFlag, c.show_start_date showStartDate,c.show_end_date showEndDate, a.search_keyword FROM bid_bid_product a LEFT JOIN bid_product b 
				ON a.account_id=b.account_id AND a.product_type=b.product_type AND a.product_id=b.product_id AND b.del_flag=0 LEFT JOIN bid_show_date c ON a.show_date_id=c.id LEFT JOIN bb_account d ON a.account_id = d.id 
				LEFT JOIN departure e ON a.start_city_code = e.code WHERE a.del_flag=0 AND c.del_flag=0 AND c.status=1 AND c.show_start_date = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') AND a.bid_mark = 2";
    	// 查询并返回参数
		return $this->dbRO->createCommand($sql)->queryAll();
    }

    /**
     * 查询关键词
     * @return array
     */
    public function queryKeyWord($param) {
    	// 预初始化SQL语句
    	$sql = "";
    	// 分类初始化SQL
    	if (empty($param) || null == $param || '' == $param) {
    		// 初始化无参数的SQL语句
    		$sql = "select id, keyword, py, py_index as pyIndex, search_num as searchNum, refresh_date as refreshDate from ba_search_keyword where del_flag=0 ORDER BY refresh_date DESC, search_num DESC";
    	} else {
    		// 初始化动态where条件
    		$where = '';
    		// 初始化动态limit条件
    		$limit = '';
    		// 初始化出发城市前参数
    		$cityBefore = '';
    		// 初始化出发城市后参数
    		$cityAfter = '';
    		// 动态初始化动态limit条件
    		if (!empty($param['limit'])) {
    			$limit = ' limit '.$param['limit'];
    		}
    		// 分类初始化关键词参数
    		if (!empty($param['keyword'])) {
    			$where = $where." AND keyword like '%".$param['keyword']."%'";
    		}
    		// 分类初始化首字母参数
    		if (!empty($param['pyIndex'])) {
    			$where = $where." AND py_index = '".strtoupper($param['pyIndex'])."'";
    		}
    		// 分类初始化出发城市参数
    		if (!empty($param['startCityCode'])) {
    			$cityBefore = ", b.name as startCityName, concat(b.name, '-', a.keyword) as viewName ";
    			$cityAfter = "left join departure as b on code = ".$param['startCityCode'];
    			$where = $where." AND b.mark = 0";
    		}
    		// 初始化有参数的SQL语句
    		$sql = "select a.id, a.keyword, a.py, a.py_index as pyIndex, a.search_num as searchNum, a.refresh_date as refreshDate ".$cityBefore." from ba_search_keyword as a ".$cityAfter." where a.del_flag=0 ".$where." ORDER BY refresh_date DESC, search_num DESC".$limit;
    	}
    	// 查询并返回参数
		return $this->dbRO->createCommand($sql)->queryAll();
    }

     /**
     * 搜索页关键词改版-插入搜索页关键词表
     *
     * @author p-sunhao 20131231
     * @param array() $param
     * @return boolean
     */
    public function insertKeyword($param) {
    	// 插入数据
    	$result = $this->dbRW->createCommand()->insert('ba_search_keyword', array(
            'keyword' => mysql_escape_string($param['key_word']),
            'del_flag' => 0,
            'misc' => '',
            'py' => $param['py'],
            'py_index' => strtoupper(substr($param['py'], 0, 1)),
            'search_num' => $param['py'],
            'refresh_date' => date('Y-m-d'),
            'add_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s')
                ));
        // 插入成功返回true，否则返回false
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 搜索页关键词改版-更新搜索页关键词表
     *
     * @author p-sunhao 20131231
     * @param array() $param
     * @return boolean
     */
    public function updateKeyword($param) {
    	// 初始化更新过滤条件  未删除且ID相等
        $cond = 'del_flag=:del_flag AND id=:id';
        $condParams = array(':del_flag' => 0, ':id' => $param['id']);
		// 更新关键词数据
        $result = $this->dbRW->createCommand()->update('ba_search_keyword', array(
            'search_num' => intval($param['search_num']),
            'refresh_date' => date('Y-m-d'),
            'update_time' => date('Y-m-d H:i:s')
                ), $cond, $condParams);
		// 更新成功返回true，否则返回false
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 根据adKey获取广告位配置信息
     *
     * @return array()
     */
    public function queryAdKeyInfo($adKey, $startCityCode) {
        if (empty($adKey)) {
            return array();
        } elseif ('all' == $adKey) {
            $sql = "SELECT * FROM ba_ad_position_type WHERE del_flag = 0";
        } elseif (!empty($startCityCode) && 'class_recommend' != $adKey && 'search_complex' != $adKey && 'brand_zone' != $adKey) {
            $sql = "SELECT * FROM ba_ad_position_type WHERE del_flag = 0 AND ad_key = '$adKey' AND start_city_code = ".$startCityCode;
        } else {
        	$sql = "SELECT * FROM ba_ad_position_type WHERE del_flag = 0 AND ad_key = '$adKey'";
        }
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryAll();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array();
        }
    }

    /**
     * 根据adKey获取广告位配置信息,包括被删除的广告位
     *
     * @return array()
     */
    public function queryAdKeyAllInfo($adKey, $startCityCode) {
        if (empty($adKey)) {
            return array();
        } elseif ('all' == $adKey) {
            $sql = "SELECT * FROM ba_ad_position_type WHERE 1=1";
        } elseif (!empty($startCityCode) && 'class_recommend' != $adKey && 'search_complex' != $adKey && 'brand_zone' != $adKey) {
            $sql = "SELECT * FROM ba_ad_position_type WHERE ad_key = '$adKey' AND start_city_code = ".$startCityCode." ORDER BY update_time DESC";
        } else {
            $sql = "SELECT * FROM ba_ad_position_type WHERE ad_key = '$adKey' ORDER BY update_time DESC";
        }
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryAll();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array();
        }
    }

    /**
     * 根据adKey获取某个广告位信息
     *
     * @return array()
     */
    public function queryIndexInfo($adKey) {
        if (empty($adKey)) {
            return array();
        } else {
            $sql = "SELECT * FROM ba_ad_position_type WHERE ad_key = '$adKey'";
        }
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryRow();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array();
        }
    }

    /**
	 * 获取广告位类型
	 * 
	 * @return array()
	 */
	public function queryPositionType() {
	    // 初始化sql语句
		$sql = "SELECT ad_key as adKey, ad_name as adName, start_city_code as startCityCode FROM ba_ad_position_type WHERE del_flag = 0";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
	 * 查询广告位信息  数量  底价
	 */
	public function queryPositionInfo($param) {
		// 初始化SQL语句
		$sql = "SELECT a.id, a.ad_key, a.ad_key_type, a.web_class, a.ad_name, a.floor_price, a.start_city_code, a.ad_product_count, a.show_date_id, CONCAT(b.show_start_date, ' ~ ', b.show_end_date) AS showDate, a.update_time FROM ba_ad_position a LEFT JOIN bid_show_date b ON a.show_date_id = b.id WHERE a.show_date_id IN (".$param.") and b.del_flag=0 and a.del_flag=0";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

	/**
	 * 查询竞价成功的产品排名
	 */
	public function queryBidListRank($param) {
		// 预初始化cityCode和关键词动态参数
		$codeAndkeyword = " ";
		// 分类初始化cityCode和关键词动态参数
		/*if ("" != $param['start_city_code'] && "" == $param['search_keyword']) {
			// 单独初始化cityCode  并集  分类页
			$codeAndkeyword = $codeAndkeyword."AND start_city_code IN (".$param['start_city_code'].")";
		} else if ("" == $param['start_city_code'] && "" != $param['search_keyword'] && 0 == $param['web_id']) {
			// 单独初始化keyword
			$codeAndkeyword = $codeAndkeyword."AND search_keyword IN (".$param['search_keyword'].")";
		} else if ("" != $param['start_city_code'] && "" == $param['search_keyword'] && 0 != $param['web_id']) {
			// 单独初始化分类页参数
			$codeAndkeyword = $codeAndkeyword."AND start_city_code IN (".$param['start_city_code'].") AND web_class IN (".$param['web_id'].")";
		} else if ("" != $param['start_city_code'] && "" != $param['search_keyword']) {
			// 同时初始化cityCode和keyword  并集  分类页
			$codeAndkeyword = $codeAndkeyword."AND (search_keyword IN (".$param['search_keyword'].") AND start_city_code IN (".$param['start_city_code']."))";
		}*/

        if (strpos($param['ad_key'], 'index_chosen') !== false || $param['ad_key'] == 'brand_zone' || strpos($param['ad_key'], 'channel_chosen') !== false) {
            $codeAndkeyword = " AND start_city_code IN (".$param['start_city_code'].")";
        } else if ($param['ad_key'] == 'class_recommend') {
            $codeAndkeyword = " AND start_city_code IN (".$param['start_city_code'].") AND web_class IN (".$param['web_id'].")";
        } else if ($param['ad_key'] == 'search_complex') {
            $codeAndkeyword = " AND (search_keyword IN (".$param['search_keyword'].") AND start_city_code IN (".$param['start_city_code']."))";
        } else{
            return array();
        }

		// 初始化SQL语句
		$sql = "SELECT id, show_date_id, ad_key, ranking as bid_ranking, web_class, start_city_code, search_keyword
		FROM bid_bid_product
		WHERE del_flag = 0 AND bid_mark = 2 AND account_id = ".$param['account_id']." AND show_date_id IN (".$param['show_date_id'].") AND ad_key IN ('".$param['ad_key']."')".$codeAndkeyword." order by bid_ranking";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

    /**
	 * 获取显示名称
	 */
	public function queryViewName($param) {
		$sql = "";
		// 预初始化城市表条件
		$cityBefore = "";
		$cityAfter = "";
		if (strpos($param['adKey'], 'channel_chosen') !== false && !empty($param['startCityCode']) && '' != $param['startCityCode'] && 0 != $param['startCityCode']) {
			// 初始化SQL语句
			$sql = "SELECT a.ad_key, a.ad_name, b.name, b.code FROM ba_ad_position_type a LEFT JOIN departure b ON a.start_city_code = b.code WHERE a.del_flag = 0 AND b.code = ".$param['startCityCode']." AND b.mark=0 AND a.ad_key = '".$param['adKey']."'";
		} else {
			// 判断是否需要追加城市表条件
			if (!empty($param['startCityCode']) && '' != $param['startCityCode'] && 0 != $param['startCityCode']) {
				$cityBefore = ", b.name, b.code ";
				$cityAfter = "LEFT JOIN departure b ON b.code = ".$param['startCityCode']." AND b.mark=0";
			}
			// 初始化SQL语句
			$sql = "SELECT a.ad_key, a.ad_name".$cityBefore." FROM ba_ad_position_type a ".$cityAfter." WHERE a.del_flag = 0 AND a.ad_key = '".$param['adKey']."'";
		}
		
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
		
	}

	/**
	 * 获取广告位总数
	 */
	public function queryPositionCount($param) {
		// 分类获取广告位底价
   		if ('class_recommend' == $param['adKey'] && 136 < $param['showDateId']) {
   			
   			// 分类页   新版  后上线
   			// 查询分类页父级信息
   			$sqlFa = "SELECT web_class, start_city_code, class_depth, parent_class, parent_depth FROM position_sync_class WHERE web_class = ".$param['webId']." AND start_city_code = ".$param['startCityCode']." AND del_flag = 0 AND parent_depth IN (1,2)";
   			$faRows = $this->executeSql($sqlFa, self::ALL);
   			$data = array();
   			// 获取一级和二级分类报价信息
   			foreach ($faRows as $faRowsObj) {
   				if (1 == $faRowsObj['parent_depth']) {
   					// 一级分类报价
   					$sqlOne = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$param['startCityCode']." AND del_flag = 0 AND show_date_id = ".$param['showDateId'];
   					array_push($data, $this->executeSql($sqlOne, self::ROW));
   				} else if (2 == $faRowsObj['parent_depth']) {
   					// 二级分类报价
   					$sqlTwo = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$param['startCityCode']." AND del_flag = 0 AND show_date_id = ".$param['showDateId'];
   					array_push($data, $this->executeSql($sqlTwo, self::ROW));
   				}
   				
   			}
   			// 查询自身的分类报价
  			$sqlOwn = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$param['webId']." AND start_city_code = ".$param['startCityCode']." AND del_flag = 0 AND show_date_id = ".$param['showDateId'];
   			$dataOwn = $this->executeSql($sqlOwn, self::ROW);
   			// 对比更新时间
   			foreach ($data as $dataObj) {
   				if (empty($dataOwn) || strtotime($dataObj['update_time']) > strtotime($dataOwn['update_time'])) {
   					$dataOwn['floor_price'] = $dataObj['floor_price'];
   					$dataOwn['ad_product_count'] = $dataObj['ad_product_count'];
   					$dataOwn['coupon_use_percent'] = $dataObj['coupon_use_percent'];
   					$dataOwn['update_time'] = $dataObj['update_time'];
   				}
   			}
   			$row = $dataOwn;
   		} else {
			// 预初始化动态SQL条件  老版
			$dySql = '';
			// 分类初始化动态SQL条件
			if (!empty($param['positionId']) && -1 != $param['positionId']) {
				$dySql = " and id = ".$param['positionId'];
			}
			// 初始化SQL语句
			$sql = "SELECT ad_product_count, ROUND(floor_price) AS floor_price FROM ba_ad_position WHERE ad_key='".$param['adKey']."' AND show_date_id = ".$param['showDateId']." AND del_flag=0".$dySql;
			// 查询并返回参数
			$row = $this->dbRO->createCommand($sql)->queryRow();
   		}
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

	/**
	 * 获取广告位总信息
	 */
	public function queryBidTotalInfo($param) {
        $adKey = $param['adKey'];
		// 初始化动态拼接条件
        $dySql = "AND start_city_code = ".$param['startCityCode']." and a.ad_key='$adKey'";
        if ('search_complex' == $adKey) {
            $dySql = "AND search_keyword = '".$param['searchKeyword']."' AND start_city_code = ".$param['startCityCode']." and a.ad_key='$adKey'";
        }
        if ('class_recommend' == $adKey) {
            $dySql = "AND start_city_code = ".$param['startCityCode']." AND web_class = ".$param['webId']." and a.ad_key='$adKey'";
        }
		// 初始化SQL语句
		$sql = "SELECT COUNT(1) AS count_ad, a.ad_key, a.start_city_code, a.search_keyword, a.show_date_id, a.web_class, SUM(a.bid_price) as bid_price, SUM(a.max_limit_price) as max_limit_price,SUM(a.bid_price_coupon) AS bid_price_coupon,SUM(a.bid_price_niu) AS bid_price_niu,SUM(a.max_limit_price_coupon) AS max_limit_price_coupon,SUM(a.max_limit_price_niu) AS max_limit_price_niu, a.show_date_id, CONCAT(b.show_start_date, ' ~ ', b.show_end_date) AS showDate FROM bid_bid_product a LEFT JOIN bid_show_date b ON a.show_date_id = b.id WHERE a.del_flag = 0 AND (a.bid_mark = 2 OR a.bid_mark = 1) AND a.ad_key = '".$param['adKey']."' AND a.show_date_id = ".$param['showDateId']." and a.account_id = ".$param['account_id']." ".$dySql." GROUP BY ad_key, show_date_id";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
	 * 获取附加信息出价
	 */
	public function queryVasTotalPrice($param) {
        $adKey = $param['adKey'];
        // 初始化动态拼接条件
        $dySql = " and a.start_city_code =".$param['startCityCode']." and a.ad_key='$adKey'";
        if ('search_complex' == $adKey) {
            $dySql = " and a.search_keyword ='".$param['searchKeyword']."' and a.start_city_code =".$param['startCityCode']." and a.ad_key='$adKey'";
        }
        if ('class_recommend' == $adKey) {
            $dySql = " and a.start_city_code =".$param['startCityCode']." and web_class = ".$param['webId']." and a.ad_key='$adKey'";
        }
		// 初始化SQL语句
		$sql = "SELECT IFNULL(SUM(b.bid_price), 0) AS floor_price FROM bid_bid_product a LEFT JOIN bid_bid_vas b ON a.id = b.bid_id AND b.del_flag = 0 LEFT JOIN bid_show_date c ON a.show_date_id = c.id WHERE a.account_id = ".$param['account_id']." AND a.show_date_id = ".$param['showDateId']." AND (a.bid_mark = 2 OR (a.bid_mark=1 AND DATE_FORMAT(NOW(), '%Y-%m-%d') <= c.show_end_date)) AND a.del_flag = 0 AND c.del_flag = 0".$dySql;
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
	 * 根据ID查询打包时间
	 */
	public function queryDateByid($param) {
		// 初始化SQL语句
		$sql = "select CONCAT(show_start_date, ' ~ ', show_end_date) AS show_date FROM bid_show_date  WHERE id = ".$param." and del_flag=0";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
    /**
	 * 查询编辑列表
	 */
	public function queryEditList($param) {
        $adKey = $param['ad_key'];
        // 初始化动态拼接条件
        $dySql = " and a.start_city_code =".$param['start_city_code']." and a.ad_key='$adKey'";
        if ('search_complex' == $adKey) {
            $dySql = " and a.search_keyword ='".$param['search_keyword']."' and a.start_city_code =".$param['start_city_code']." and a.ad_key='$adKey'";
        }
        if ('class_recommend' == $adKey) {
            $dySql = " and a.start_city_code =".$param['start_city_code']." and web_class = ".$param['web_id']." and a.ad_key='$adKey'";
        }
		// 初始化sql语句
		$sql = "SELECT a.id, a.product_type, a.product_id, a.show_date_id, a.bid_mark, a.fmis_mark, ROUND(a.bid_price) AS bid_price, ROUND(a.max_limit_price) AS max_limit_price, a.start_city_code, a.web_class, a.search_keyword, a.ad_key, a.ranking as bid_ranking, IFNULL(b.product_name, '') AS product_name, IFNULL(b.agency_product_name, '') AS agency_product_name, IFNULL(b.checker_flag, 0) AS checker_flag, a.login_name FROM bid_bid_product a LEFT JOIN bid_product b ON a.product_id = b.product_id AND a.product_type = b.product_type and b.account_id=".$param['account_id']." AND b.del_flag = 0 LEFT JOIN bid_show_date c ON a.show_date_id = c.id WHERE a.del_flag = 0 AND a.ad_key='".$param['ad_key']."' ".$dySql." AND (a.bid_mark = 2 OR (a.bid_mark=1 AND DATE_FORMAT(NOW(), '%Y-%m-%d') <= c.show_end_date)) AND a.show_date_id=".$param['show_date_id']." and a.account_id=".$param['account_id']." ORDER BY a.ranking ASC LIMIT ".$param['start'].",".$param['limit'];
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
	 * 查询编辑列表数量
	 */
	public function queryEditListCount($param) {
		// 预初始化动态SQL条件
		$dySql = '';
		// 分类判断是首页，专题页SQL条件还是搜索页SQL条件
		if ('index_chosen' == $param['ad_key']) {
			// 首页
			$dySql = " and a.start_city_code =".$param['start_city_code']." and a.ad_key='index_chosen'";
		} else if ('special_subject' == $param['ad_key']) {
			// 专题页
			$dySql = " and a.start_city_code =".$param['start_city_code']." and a.ad_key='special_subject'";
		} else if ('brand_zone' == $param['ad_key']) {
            // 品牌专区
            $dySql = " and a.start_city_code =".$param['start_city_code']." and a.ad_key='brand_zone'";
        } else if ('search_complex' == $param['ad_key']) {
			// 搜索页
			$dySql = " and a.search_keyword ='".$param['search_keyword']."' and a.start_city_code =".$param['start_city_code']." and a.ad_key='search_complex'";
		} else if ('class_recommend' == $param['ad_key']) {
			// 分类页
			$dySql = " and a.start_city_code =".$param['start_city_code']." and web_class = ".$param['web_id']." and a.ad_key='class_recommend'";
		} 
		// 初始化sql语句
		$sql = "SELECT count(*) AS list_count FROM bid_bid_product a LEFT JOIN bid_product b ON a.product_id = b.product_id AND a.product_type = b.product_type and b.account_id=".$param['account_id']." AND b.del_flag = 0 LEFT JOIN bid_show_date c ON a.show_date_id = c.id WHERE a.del_flag = 0 AND a.ad_key='".$param['ad_key']."' ".$dySql." AND (a.bid_mark = 2 OR (a.bid_mark=1 AND DATE_FORMAT(NOW(), '%Y-%m-%d') <= c.show_end_date)) AND a.show_date_id=".$param['show_date_id']." and a.account_id=".$param['account_id'];
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
	 * 根据ad_key和date_id查询附加信息
	 */
	public function queryVasByAdkeyDateid($param) {
		// 初始化sql语句
		// $sql = "SELECT a.id, a.vas_key, a.vas_position, a.floor_price, IFNULL(b.vas_name, '') AS vas_name, b.unit_floor_price FROM ba_ad_vas a LEFT JOIN ba_ad_vas_type b ON a.vas_key = b.vas_key  AND b.del_flag = 0 LEFT JOIN ba_ad_position c ON a.ad_position_id = c.id AND c.del_flag = 0 WHERE a.del_flag = 0 AND c.ad_key = '".$param['adKey']."' AND c.show_date_id = ".$param['showDateId'];
		$sql = "SELECT d.id, d.vas_key, d.vas_position, d.floor_price, IFNULL(c.vas_name, '') as vas_name, c.unit_floor_price, IFNULL(e.ranking, '') AS ranking  FROM ba_ad_position a LEFT JOIN ba_ad_position_type b ON a.ad_key = b.ad_key LEFT JOIN ba_ad_vas_type c ON b.id = c.position_type_id LEFT JOIN ba_ad_vas d ON a.id = d.ad_position_id AND c.vas_key = d.vas_key LEFT JOIN bid_bid_product e ON e.id = ".$param['bidId']." WHERE a.del_flag = 0 AND a.ad_key = '".$param['adKey']."' AND a.show_date_id = ".$param['showDateId']." AND b.del_flag = 0 AND c.del_flag = 0 AND d.del_flag = 0";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	} 
	
	/**
	 * 查询竞价附加信息
	 */
	public function queryBidVas($param) {
		// 初始化sql语句
		$sql = "SELECT id, bid_id, vas_key, ROUND(bid_price) AS bid_price, bid_mark, fmis_mark FROM bid_bid_vas WHERE del_flag = 0 AND bid_id = ".$param['bidId']." and account_id = ".$param['accountId'];
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

	/** 
	 * 删除竞价内容表替换的产品
	 * 
	 * @param $param
	 * @return boolean
	 */
	public function updateBidBidContent($param) {
		// 初始化更新筛选条件
		$condSqlSegment = " bid_id = ".$param['bidId'];
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('bid_bid_content', array (
				'del_flag' => 1,
				'update_time' => date('y-m-d H:i:s',time())
			), $condSqlSegment);
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 更新正确，返回true
		return true;
	}
	
	/** 
	 * 删除附加信息表内容 
	 * 
	 * @param $param
	 * @param $fmisState
	 * @return boolean
	 */
	public function deleteBidBidVas($param) {
		// 初始化更新筛选条件
		$condSqlSegment = " id in (".$param.")";
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('bid_bid_vas', array (
				'del_flag' => 1,
				'update_time' => date('y-m-d H:i:s',time())
			), $condSqlSegment);
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 更新正确，返回true
		return true;
	}
	
    /**
     * 插入新的附加信息
     * @param array $param
     * @return unknown|int
     */
    public function insertBidVasInfo($param) {
    	try {
	    	// 插入数据
    	    $result = $this->dbRW->createCommand()->insert('bid_bid_vas', array(
				'account_id' => $param['accountId'], 
	        	'bid_id' => intval($param['bidId']), 
				'vas_key' => strval($param['vasKey']), 
				'bid_price' => $param['price'], 
				'bid_mark' => intval($param['bidMark']), 
				'fmis_mark' => intval($param['fmisMark']),
				'add_uid' => $param['accountId'], 
				'add_time' => date('y-m-d H:i:s',time()), 
				'update_uid' => $param['accountId'],
				'update_time' => date('y-m-d H:i:s',time()),
				'del_flag' => 0,
				'misc' => ''
        	 ));
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 插入正确，返回true
		return $this->dbRW->lastInsertID;
    }
    
    /**
     * 查询附加信息已冻结金额
     */
    public function queryVasFmis($param) {
    	// 初始化sql语句
		$sql = "SELECT IFNULL(SUM(bid_price), 0) AS bid_price FROM bid_bid_vas WHERE del_flag = 0 AND bid_id in (".$param['bidIdArr'].") AND account_id = ".$param['accountId']." AND fmis_mark = 0";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}	
    }
    
    /**
     * 查询附加信息已冻结金额详细信息
     */
    public function queryVasFmisDetail($param) {
    	// 初始化查询上一次冻结金额的集合SQL
		$sql = "SELECT SUM(a.bid_price) AS bid_price, a.bid_id, b.product_id, b.ranking FROM bid_bid_vas a LEFT JOIN bid_bid_product b ON a.bid_id = b.id WHERE a.del_flag = 0 AND a.bid_id IN (".$param['bidIdArr'].") AND a.account_id = ".$param['accountId']." AND a.fmis_mark = 0 GROUP BY a.bid_id";
    	// 查询上一次冻结金额的集合
    	$row = $this->dbRW->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}	
    }

    
    /**
     * 插入竞价内容表
     */
    public function insertBidBidContent($param) {
    	try {
	    	// 插入数据
    	    $result = $this->dbRW->createCommand()->insert('bid_bid_content', array(
				'account_id' => $param['accountId'], 
	        	'content_type' => intval($param['productType']), 
				'content_id' => strval($param['productId']), 
				'bid_id' => $param['bidId'], 
				'add_uid' => $param['accountId'], 
				'add_time' => date('y-m-d H:i:s',time()), 
				'update_uid' => $param['accountId'],
				'update_time' => date('y-m-d H:i:s',time()),
				'del_flag' => 0,
				'misc' => ''
        	 ));
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 更新正确，返回true
		return true;
    }
    
    /**
     * 更新附加信息财务状态
     */
    public function updateVasFmisState($param, $fmisState) {
    	// 初始化更新筛选条件
		$condSqlSegment = " id in (".$param.")";
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('bid_bid_vas', array (
				'fmis_mark' => $fmisState,
				'update_time' => date('y-m-d H:i:s',time())
			), $condSqlSegment);
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 更新正确，返回true
		return true;
    }
    
    /**
     * 更新附加信息财务状态
     */
    public function deleteVasFmisState($fmisVasId) {
    	// 初始化更新筛选条件
		$condSqlSegment = " id in (".$fmisVasId.")";
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('bid_bid_vas', array (
				'del_flag' => 1,
				'update_time' => date('y-m-d H:i:s',time())
			), $condSqlSegment);
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 更新正确，返回true
		return true;
    }
    
    /** 
     * 删除附加信息
     */
    public function deleteBidVasInfo($param) {
    	// 初始化更新筛选条件
		$condSqlSegment = " del_flag = 0 AND bid_id in (".$param['bidIdArr'].") AND account_id = ".$param['accountId']." AND fmis_mark = 0";
		try {
			// 更新
			$result = $this->dbRW->createCommand()->update('bid_bid_vas', array (
				'del_flag' => 1,
				'update_time' => date('y-m-d H:i:s',time())
			), $condSqlSegment);
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		// 更新正确，返回true
		return true;
    }

	/**
	 * 获取显示名称
	 */
	public function queryStartCity($param) {
		// 预初始化动态SQL
		$dySql = '';
		// 动态初始化SQL
		if(!empty($param['code'])) {
			$dySql = " and code in (".$param['code'].")";	
		}
		// 初始化SQL语句
		$sql = "SELECT code, name FROM departure WHERE mark = 0".$dySql;
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}

	/**
     * 查询出哪些打包计划在广告位置表里没有对应的数据
     */
    public function queryNotSyncShowDateIds(){
    	$sql = "SELECT
					DISTINCT(id)
				FROM
					bid_show_date
				WHERE
					del_flag=0
				AND
					STATUS = 1
				AND
					id NOT IN (
							SELECT
								DISTINCT(show_date_id)
							FROM
								ba_ad_position
							WHERE
								del_flag=0
							ORDER BY
								show_date_id
							)
				ORDER BY id";
		$row = $this->dbRO->createCommand($sql)->queryAll();
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
    }

	/**
	 * 添加广告位置信息
	 */
	public function addAdPosition($data,$id){
		try {
	    	// 插入数据
    	    $result = $this->dbRW->createCommand()->insert('ba_ad_position', array(
				'ad_key' => $data['adKey'], 
	        	'ad_name' => $data['adName'], 
				'floor_price' =>  $data['floorPrice'], 
				'ad_product_count' => $data['adProductCount'], 
				'show_date_id' => $id, 
				'add_time' => date('y-m-d H:i:s',time()),
				'del_flag' => 0,
				'misc' => "3.0改版前兼容数据"
        	 ));
		} catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
		return $result;
	}
	
	/**
     * 根据竞价ID获取推广开始时间和结束时间
     */
    public function queryStartEndDateByBidId($param) {
    	// 初始化SQL语句
		$sql = "SELECT show_start_date, show_end_date, show_date_id FROM bid_bid_product a LEFT JOIN bid_show_date b ON b.id = show_date_id where a.id in (".$param.") group by show_date_id";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
    }
	
	/**
	 * 过滤出当前支持的广告位类型
	 */
	public function getAvailableType(){
		// 初始化SQL语句
//		$sql = "SELECT DISTINCT(b.ad_key) AS adKey, b.ad_name AS adName
//				FROM bid_show_date a
//				LEFT JOIN ba_ad_position b
//				ON a.id = b.show_date_id AND b.del_flag = 0
//				WHERE DATE_FORMAT(CONCAT(bid_start_date, CONCAT(' ', bid_start_time)), '%Y-%m-%d %H') <= DATE_FORMAT(NOW(), '%Y-%m-%d %H')
//				AND DATE_FORMAT(CONCAT(bid_end_date, CONCAT(' ', bid_end_time)), '%Y-%m-%d %H') > DATE_FORMAT(NOW(), '%Y-%m-%d %H')
//				AND a.del_flag=0 AND a.status = 1
//				GROUP BY b.ad_key";
		$sql = "SELECT CASE b.ad_key_type WHEN 1 THEN 'index_chosen' WHEN 2 THEN 'class_recommend' WHEN 21 THEN 'class_recommend' WHEN 3 THEN 'search_complex' WHEN 4 THEN 'special_subject' WHEN 5 THEN 'channel_chosen' WHEN 6 THEN 'brand_zone' END AS adKey, 
				CASE b.ad_key_type WHEN 1 THEN '首页' WHEN 2 THEN '分类页' WHEN 21 THEN '分类页' WHEN 3 THEN '搜索页' WHEN 4 THEN '专题页' WHEN 5 THEN '频道页' WHEN 6 THEN '品牌专区' END  AS adName
				FROM bid_show_date a
				LEFT JOIN ba_ad_position b
				ON a.id = b.show_date_id AND b.del_flag = 0
				WHERE DATE_FORMAT(CONCAT(bid_start_date, CONCAT(' ', bid_start_time)), '%Y-%m-%d %H') <= DATE_FORMAT(NOW(), '%Y-%m-%d %H')
				AND DATE_FORMAT(CONCAT(bid_end_date, CONCAT(' ', bid_end_time)), '%Y-%m-%d %H') > DATE_FORMAT(NOW(), '%Y-%m-%d %H')
				AND a.del_flag=0 AND a.status = 1
				GROUP BY b.ad_key_type";
		// 查询并返回参数
		$row = $this->dbRO->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
	}
	
	/**
     * 保存推广中编辑列表，查询推广产品表ID
     */
    public function queryEditShow($param) {
    	// 初始化SQL语句
		$sql = "SELECT id AS show_id, bid_id, product_id, product_type FROM bid_show_product WHERE del_flag = 0 AND bid_id IN (".$param.")";
		// 查询并返回参数
		$row = $this->dbRW->createCommand($sql)->queryAll();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空数组
			return array ();
		}
    	
    }
    
    /**
     * 保存推广中编辑列表，保存推广产品表和推广内容表
     */
    public function saveEditShowContent($showIDArr, $showUpdArr, $contentAddArr) {
    	// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 删除bid_show_content表旧记录
			$result = $this->dbRW->createCommand()->update('bid_show_content', array (
				'del_flag' => 1
			), "del_flag = 0 and show_id in (".$showIDArr.")");
			// 循环更新bid_show_product表
			foreach ($showUpdArr as $showUpdArrObj) {
				$result = $this->dbRW->createCommand()->update('bid_show_product', array (
					'product_id' => $showUpdArrObj['product_id'],
					'product_type' => $showUpdArrObj['product_type']
				), "del_flag = 0 and bid_id =".$showUpdArrObj['bid_id']);
			}
			// 循环插入bid_show_content表
			foreach ($contentAddArr as $contentAddArrObj) {
				$result = $this->dbRW->createCommand()->insert('bid_show_content', array(
					'account_id' => $contentAddArrObj['account_id'], 
	        		'content_type' => $contentAddArrObj['content_type'], 
					'content_id' => $contentAddArrObj['content_id'], 
					'show_id' => $contentAddArrObj['show_id'], 
					'add_uid' => $contentAddArrObj['account_id'], 
					'add_time' => date('y-m-d H:i:s',time()), 
					'update_uid' => $contentAddArrObj['account_id'],
					'update_time' => date('y-m-d H:i:s',time()),
					'del_flag' => 0,
					'misc' => ''
        	 	));
			}
			//提交事务会真正的执行数据库操作
    		$transaction->commit(); 
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw new Exception();
		}
		
		// 操作成功，返回true
		return true;
    }
    
    /**
     * 查询供应商日志信息
     */
    public function queryAgencyLog($param) {
		try {
			// 预初始化动态SQL
			$dySql = '';
			// 若登录名不为空，则初始化登录名查询条件
			if ((empty($param['subAgency']) || '' == $param['subAgency']) && !$param['isFather']) {
				// 默认子账号精确查询
				$dySql = " AND login_name = '".$param['subAgencyDefault']."'";
			} else if (!empty($param['subAgency']) && '' != $param['subAgency'] && $param['isFather']) {
				// 父账号模糊查询
				$dySql = " AND login_name like '%".$param['subAgency']."%'";
			} else if (!empty($param['subAgency']) && '' != $param['subAgency'] && !$param['isFather']) {
				// 子账号精确查询
				$dySql = " AND login_name = '".$param['subAgency']."'";
			}
			// 如果是出价日志和替换产品日志查询，则不区分登录名
			if ((1 == $param['type'] || 2 == $param['type']) && (empty($param['subAgency']) || '' == $param['subAgency'])) {
				$dySql = "";
			} else if ((1 == $param['type'] || 2 == $param['type']) && (!empty($param['subAgency']) && '' != $param['subAgency'])) {
				$dySql = " AND login_name like '%".$param['subAgency']."%'";
			} else if ((1 != $param['type'] && 2 != $param['type']) && (!empty($param['subAgency']) && '' != $param['subAgency'])) {
				$dySql = " AND login_name = '".$param['subAgency']."'";
			} 
			// 过滤竞价ID查询条件
			if ('0' != $param['bidIdArr']) {
				$dySql = $dySql."AND bid_id IN (".$param['bidIdArr'].")";
			}
			// 若查询时间范围不为空，则初始化时间查询条件
			if (!empty($param['startTime']) && '' != $param['startTime'] && !empty($param['endTime']) && '' != $param['endTime']) {
				$dySql = $dySql." AND '".$param['startTime']."' <= DATE_FORMAT(add_time, '%Y-%m-%d %H') AND '".$param['endTime']."' > DATE_FORMAT(add_time, '%Y-%m-%d %H')";
			}
    		// 初始化SQL语句
			$sql = "SELECT id, bid_id AS bidId, type, content, login_name AS subAgency, add_time AS operationTime, account_id as accountId FROM bb_sub_account_log WHERE del_flag = 0 AND type = ".$param['type']." AND account_id = ".$param['accountId']." ".$dySql." ORDER BY add_time DESC LIMIT ".$param['start'].", ".$param['limit'];
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryAll();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row;
			} else {
				// 为空，返回空集合
				return array();
			}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }
    
    /**
     * 查询供应商日志信息
     */
    public function queryAgencyLogCount($param) {
		try {
			// 预初始化动态SQL
			$dySql = '';
			// 若登录名不为空，则初始化登录名查询条件
			if ((empty($param['subAgency']) || '' == $param['subAgency']) && !$param['isFather']) {
				// 默认子账号精确查询
				$dySql = " AND login_name = '".$param['subAgencyDefault']."'";
			} else if (!empty($param['subAgency']) && '' != $param['subAgency'] && $param['isFather']) {
				// 父账号模糊查询
				$dySql = " AND login_name like '%".$param['subAgency']."%'";
			} else if (!empty($param['subAgency']) && '' != $param['subAgency'] && !$param['isFather']) {
				// 子账号精确查询
				$dySql = " AND login_name = '".$param['subAgency']."'";
			}
			// 如果是出价日志和替换产品日志查询，则不区分登录名
			if ((1 == $param['type'] || 2 == $param['type']) && (empty($param['subAgency']) || '' == $param['subAgency'])) {
				$dySql = "";
			} else if ((1 == $param['type'] || 2 == $param['type']) && (!empty($param['subAgency']) && '' != $param['subAgency'])) {
				$dySql = " AND login_name like '%".$param['subAgency']."%'";
			} else if ((1 != $param['type'] && 2 != $param['type']) && (!empty($param['subAgency']) && '' != $param['subAgency'])) {
				$dySql = " AND login_name = '".$param['subAgency']."'";
			} 
			// 过滤竞价ID查询条件
			if ('0' != $param['bidIdArr']) {
				$dySql = $dySql."AND bid_id IN (".$param['bidIdArr'].")";
			}
			// 若查询时间范围不为空，则初始化时间查询条件
			if (!empty($param['startTime']) && '' != $param['startTime'] && !empty($param['endTime']) && '' != $param['endTime']) {
				$dySql = $dySql." AND '".$param['startTime']."' <= DATE_FORMAT(add_time, '%Y-%m-%d %H') AND '".$param['endTime']."' > DATE_FORMAT(add_time, '%Y-%m-%d %H')";
			}		
    		// 初始化SQL语句
			$sql = "SELECT count(*) as count FROM bb_sub_account_log WHERE del_flag = 0 AND type = ".$param['type']." AND account_id = ".$param['accountId']."  ".$dySql;
			// 查询并返回参数
			$row = $this->dbRW->createCommand($sql)->queryRow();
			// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row['count'];
			} else {
				// 为空，返回空集合
				return 0;
			}
    	} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    }
    
    /**
     * 插入供应商替换日志
     */
    public function insertReplaceLog($param) {
    	// 初始化SQL语句
		$sql = "SELECT product_id, product_type FROM bid_bid_product WHERE id = ".$param['bidId'];
		// 查询并返回参数
		$row = $this->dbRW->createCommand($sql)->queryRow();
		// 判断是否需要插入日志
		if ($row['product_id'] != $param['productId'] || $row['product_type'] != $param['productType']) {
			// 设置原来产品类型
			$bpType = '';
			if ('1' == $row['product_type']) {
				$bpType = '跟团游';
			} else if ('3' == $row['product_type']) {
				$bpType = '自助游';
			} else if ('33' == $row['product_type']) {
				$bpType = '门票';
			}
			// 设置产品类型
			$pType = '';
			if ('1' == $param['productType']) {
				$pType = '跟团游';
			} else if ('3' == $param['productType']) {
				$pType = '自助游';
			} else if ('33' == $param['productType']) {
				$pType = '门票';
			}
			// 替换产品校验处理
			if (empty($row['product_id']) || 0 == $row['product_id']) {
				$row['product_id'] = '--';
			}
			// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => $param['bidId'], 
      			'account_id' => $param['accountId'], 
				'type' => 2, 
				'content' => "{\"content\":\"".$param['subAgency']."替换了产品<br/>广告位：".$param['viewName']."<br/>替换前产品：".$bpType."产品".$row['product_id']."<br/>" .
						"替换后产品：".$pType."产品".$param['productId']."<br/>排名：".$param['ranking']."\", \"productId\":".$param['productId'].", \"productType\":".$param['productType']."}", 
				'login_name' => strval($param['subAgency']),
				'add_uid' => $param['accountId'], 
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
       		));
		}
    }
    
    /**
     * 插入供应商发起竞拍日志
     */
    public function insertAgencyInitBidLog($params) {
   		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
	    	// 如果为子供应商，则更新冻结日志竞价ID字段
	    	if (!empty($params['log_id']) && 0 != $params['log_id']) {
				$result = $this->dbRW->createCommand()->update('bb_sub_account_log', array(
					'bid_id' => $params['bidId'],
    	   	 	), 'id = '.$params['log_id']);		
	    	}
       	 	// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => $params['bidId'], 
	   			'account_id' => $params['account_id'], 
				'type' => 1, 
				'content' => "{\"content\":\"".$params['login_name']."发起了该广告位的竞拍<br/>广告位：".$params['ad_name']."<br/>上一次出价：".(round($params['src_bid_price_niu'])+round($params['src_bid_price_coupon']))."牛币(".round($params['src_bid_price_niu'])."牛币+".round($params['src_bid_price_coupon'])."赠币)<br/>上一次最高出价：".(round($params['src_max_limit_price_niu'])+round($params['src_max_limit_price_coupon']))."牛币(".round($params['src_max_limit_price_niu'])."牛币+".round($params['src_max_limit_price_coupon'])."赠币)<br/>当前出价：".(round($params['bid_price_niu'])+round($params['bid_price_coupon']))."牛币(".round($params['bid_price_niu'])."牛币+".round($params['bid_price_coupon'])."赠币)<br/>最高出价：".(round($params['max_limit_price_niu'])+round($params['max_limit_price_coupon']))."牛币(".round($params['max_limit_price_niu'])."牛币+".round($params['max_limit_price_coupon'])."赠币)<br/>排名：".$params['ranking']."\"}",
				'login_name' => $params['login_name'],
				'add_uid' => $params['account_id'], 
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
   			));
    		//提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 插入供应商更新竞拍日志
     */
    public function insertAgencyUpdateBidLog($params) {
   		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
    	try {
       	 	// 插入日志
			$result = $this->dbRW->createCommand()->insert('bb_sub_account_log', array(
				'bid_id' => $params['bidId'], 
	   			'account_id' => $params['account_id'], 
				'type' => 1, 
				'content' => "{\"content\":\"".$params['login_name']."参与了该广告位的竞拍<br/>广告位：".$params['ad_name']."<br/>上一次出价：".(round($params['src_bid_price_niu'])+round($params['src_bid_price_coupon']))."牛币(".round($params['src_bid_price_niu'])."牛币+".round($params['src_bid_price_coupon'])."赠币)<br/>上一次最高出价：".(round($params['src_max_limit_price_niu'])+round($params['src_max_limit_price_coupon']))."牛币(".round($params['src_max_limit_price_niu'])."牛币+".round($params['src_max_limit_price_coupon'])."赠币)<br/>当前出价：".(round($params['bid_price_niu'])+round($params['bid_price_coupon']))."牛币(".round($params['bid_price_niu'])."牛币+".round($params['bid_price_coupon'])."赠币)<br/>最高出价：".(round($params['max_limit_price_niu'])+round($params['max_limit_price_coupon']))."牛币(".round($params['max_limit_price_niu'])."牛币+".round($params['max_limit_price_coupon'])."赠币)<br/>排名：".$params['ranking']."\"}", 
				'login_name' => $params['login_name'],
				'add_uid' => $params['account_id'], 
				'add_time' => date('y-m-d H:i:s',time()),
				'misc' => ''
   			));	
    		//提交事务会真正的执行数据库操作
    		$transaction->commit(); 
    	} catch (Exception $e) {
 			// 如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
    	// 操作成功返回true
    	return true;
    }
    
    /**
     * 通过竞价ID查询登录名
     */
    public function queryLoginNameByBidId($param) {
    	// 初始化SQL语句
		$sql = "SELECT login_name FROM bid_bid_product WHERE id = ".$param['bidId'];
		// 查询并返回参数
		$row = $this->dbRW->createCommand($sql)->queryRow();
		// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			// 不为空，返回查询结果
			return $row;
		} else {
			// 为空，返回空集合
			return array();
		}
    }
    
    /**
	 * 获取招客宝推广成功的产品
	 * 
	 * @author wenrui 2014-03-21
	 */
    public function getShowProduct($condition,$dateCondition){
    	$sql = "SELECT b.vendor_id AS agencyId,a.content_id AS contentId,a.content_type AS contentType,c.ranking
				FROM bid_show_content a
				LEFT JOIN bb_account b ON b.id = a.account_id
				LEFT JOIN bid_show_product c ON c.id = a.show_id
				LEFT JOIN bid_show_date d ON d.id = c.show_date_id
				WHERE a.del_flag = 0 AND a.content_id>0 AND d.show_start_date <= '" . $dateCondition . "' AND d.show_end_date >= '". $dateCondition . "'" . $condition . " ORDER BY c.ranking";
    	$row = $this->dbRW->createCommand($sql)->queryAll();
    	// 判断返回结果是否为空
		if (!empty ($row) && is_array($row)) {
			return $row;
		} else {
			return array();
		}
    }
    
    /**
     * 查询海格广告位的替换记录
     */
    public function queryProductHis($param) {
    	try {
    		// 初始化动态SQL
    		$sql = "";
    		
    		// 分类初始化SQL
    		if (2 == $param['viewState']) {
    			// 竞价成功
    			$sql = "select content_type as contentType, content_id as contentId, add_uid AS addUid, add_time AS addTime from bid_bid_content where bid_id = ".$param['bidId']." order by add_time asc limit ".$param['start'].", ".$param['limit'];
    			
    		} else {
    			// 推广成功
    			$sql = "select a.content_type as contentType, a.content_id as contentId, a.add_uid AS addUid, a.add_time AS addTime from bid_show_content a LEFT JOIN bid_show_product b ON a.show_id = b.id WHERE b.bid_id = ".$param['bidId']." order by a.add_time asc limit ".$param['start'].", ".$param['limit'];
    		}
	    	// 查询数据库
	    	$row = $this->dbRW->createCommand($sql)->queryAll();
	    	// 返回结果
			return $row;
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw new Exception('查询失败，数据错误！', 230001);
		}
	}
	
	/**
     * 查询海格广告位的替换记录数量
     */
    public function queryProductHisCount($param) {
    	try {
    		// 初始化动态SQL
    		$sql = "";
    		
    		// 分类初始化SQL
    		if (2 == $param['viewState']) {
    			// 竞价成功
    			$sql = "select count(0) AS countRe from bid_bid_content where bid_id = ".$param['bidId']." order by add_time asc";
    			
    		} else {
    			// 推广成功
    			$sql = "select count(0) AS countRe from bid_show_content a LEFT JOIN bid_show_product b ON a.show_id = b.id WHERE b.bid_id = ".$param['bidId']." order by a.add_time asc";;
    		}
    		
	    	// 查询数据库
	    	$row = $this->dbRW->createCommand($sql)->queryRow();
	    	// 返回结果
			return $row['countRe'];
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw new Exception('查询失败，数据错误！', 230001);
		}
	}
	
	/**
	 * 更新竞价相关表，并获取该产品在竞价产品表的数量
	 */
	public function saveBidBidAndQueryProCount($dataParamObj) {
		
		// 初始化事务实例
    	$transaction = $this->dbRW->beginTransaction();
		
		try {
			// 初始化更新竞价记录表筛选条件
			$condSqlSegment = ' id = '.$dataParamObj['bidId'];
			// 更新竞价记录表
			$this->dbRW->createCommand()->update('bid_bid_product', array (
				'ad_key' => $dataParamObj['adKey'],
				'start_city_code' => $dataParamObj['startCityCode'],
				'search_keyword' => $dataParamObj['searchKeyword'],
		        'product_id' => $dataParamObj['productId'],
	    	    'product_type' => $dataParamObj['productType'],
	    	    'web_class' => $dataParamObj['webId'],
	        	'update_time' => date('y-m-d H:i:s',time())
			), $condSqlSegment);
			
			// 初始化校验SQL
			$checkSql = "select count(0) as countRe from bid_bid_content where del_flag = 0 and bid_id = ".$dataParamObj['bidId']." and content_id = ".$dataParamObj['productId']." and content_type = ".$dataParamObj['productType'];
			// 查询竞价内容表里是否有这条一模一样的数据
			$check = $this->dbRW->createCommand($checkSql)->queryRow();
			
			// 判断是否需要更新竞价内容表，若数据没动，则无需更新竞价内容表，否则，更新
			if (0 == $check['countRe']) {
				// 初始化删除竞价内容表条件
				$condSqlSegment = " bid_id = ".$dataParamObj['bidId'];
				// 删除竞价内容表
				$result = $this->dbRW->createCommand()->update('bid_bid_content', array (
					'del_flag' => 1,
					'update_time' => date('y-m-d H:i:s',time())
				), $condSqlSegment);
				
				// 插入一条新数据至竞价内容表
	    	    $result = $this->dbRW->createCommand()->insert('bid_bid_content', array(
					'account_id' => $dataParamObj['accountId'], 
		        	'content_type' => intval($dataParamObj['productType']), 
					'content_id' => strval($dataParamObj['productId']), 
					'bid_id' => $dataParamObj['bidId'], 
					'add_uid' => $dataParamObj['accountId'], 
					'add_time' => date('y-m-d H:i:s',time()), 
					'update_uid' => $dataParamObj['accountId'],
					'update_time' => date('y-m-d H:i:s',time()),
					'del_flag' => 0,
					'misc' => ''
	        	 ));
			}

			// 初始化sql语句查询竞价产品表的数量
			$productSql = "SELECT COUNT(*) as count_pro FROM bid_product WHERE product_id=".$dataParamObj['productId']." and product_type = ".$dataParamObj['productType']." and account_id =".$dataParamObj['accountId'];
			// 查询竞价产品表并返回参数数量
			$row = $this->dbRW->createCommand($productSql)->queryRow();

			//提交事务会真正的执行数据库操作
    		$transaction->commit();
    		
    		// 判断返回结果是否为空
			if (!empty ($row) && is_array($row)) {
				// 不为空，返回查询结果
				return $row;
			} else {
				// 为空，返回空数组
				return array ();
			} 
		} catch (Exception $e) {
			//如果操作失败, 数据回滚
    		$transaction->rollback();
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
            throw $e;
		}
		
	}
	
	/**
	 * 获取赠币使用占比
	 * 
	 * @author wenrui 2014-04-10
	 */
	public function getCouponUsePercent($param){
		$showDateId = $param['showDateId'];
		$adKey = $param['adKey'];
		$webId = $param['webClassId'];
		$cityCode = $param['startCityCode'];
		// 分类获取广告位底价
    	if ('class_recommend' == $adKey) {
    		// 分类页   新版  后上线
    		// 查询分类页父级信息
    		$sqlFa = "SELECT web_class, start_city_code, class_depth, parent_class, parent_depth FROM position_sync_class WHERE web_class = ".$webId." AND start_city_code = ".$cityCode." AND del_flag = 0 AND parent_depth IN (1,2)";
    		$faRows = $this->executeSql($sqlFa, self::ALL);
    		$data = array();
    		// 获取一级和二级分类报价信息
    		foreach ($faRows as $faRowsObj) {
    			if (1 == $faRowsObj['parent_depth']) {
    				// 一级分类报价
    				$sqlOne = "SELECT coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$cityCode." AND del_flag = 0 AND show_date_id = ".$showDateId;
    				array_push($data, $this->executeSql($sqlOne, self::ROW));
    			} else if (2 == $faRowsObj['parent_depth']) {
    				// 二级分类报价
    				$sqlTwo = "SELECT coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$cityCode." AND del_flag = 0 AND show_date_id = ".$showDateId;
    				array_push($data, $this->executeSql($sqlTwo, self::ROW));
    			}
    			
    		}
    		// 查询自身的分类报价
    		$sqlOwn = "SELECT coupon_use_percent as couponUsePercent, update_time FROM ba_ad_position WHERE web_class = ".$webId." AND start_city_code = ".$cityCode." AND del_flag = 0 AND show_date_id = ".$showDateId;
    		$dataOwn = $this->executeSql($sqlOwn, self::ROW);
    		
    		// 对比更新时间
    		foreach ($data as $dataObj) {
    			if (empty($dataOwn) || strtotime($dataObj['update_time']) > strtotime($dataOwn['update_time'])) {
    				$dataOwn['couponUsePercent'] = $dataObj['coupon_use_percent'];
    				$dataOwn['update_time'] = $dataObj['update_time'];
    			}
    		}
    		// 返回结果
    		return $dataOwn;
    	} else {
    		// 其他  老版  先上线
    		// 初始化动态SQL
			$dySql = "";
			if (!empty($cityCode) && (strpos($adKey,'index_chosen') !== false || strpos($adKey,'channel_chosen') !== false)) {
				$dySql = $dySql." and start_city_code =".$cityCode;
			}
			$sqlRow = "SELECT coupon_use_percent AS couponUsePercent ".
				"FROM ba_ad_position WHERE show_date_id = ".$showDateId." AND ad_key = '".$adKey."' AND del_flag = 0 ".$dySql;
			return $this->executeSql($sqlRow, self::ROW);
    	}
	}

    /**
     * 查询当前可参与竞拍的首页的广告位
     * @param array $param
     * @return array
     */
    public function queryIndexAdKey($param) {
        if (!$param['startCityCode']) {
            return array();
        }
        $startCityCode = intval($param['startCityCode']);
        $start = ($param['start']) ? intval($param['start']) : 0;
        $limit = ($param['limit']) ? intval($param['limit']) : 10;

        // 初始化SQL语句
        $sql = "SELECT MAX(a.id) AS showDateId, CONCAT(show_start_date, ' ~ ', show_end_date) AS showDate, b.ad_name AS adName, b.ad_key AS adKey, b.start_city_code AS startCityCode FROM bid_show_date a,ba_ad_position b WHERE DATE_FORMAT(CONCAT(bid_start_date, CONCAT(' ', bid_start_time)), '%Y-%m-%d %H') <= DATE_FORMAT(NOW(), '%Y-%m-%d %H') AND DATE_FORMAT(CONCAT(bid_end_date, CONCAT(' ', bid_end_time)), '%Y-%m-%d %H') > DATE_FORMAT(NOW(), '%Y-%m-%d %H')
                AND a.del_flag = 0 AND b.is_open = 0 AND a.STATUS = 1 AND a.id = b.show_date_id AND b.del_flag = 0 AND b.ad_key like 'index_chosen_%' AND b.start_city_code = '$startCityCode' GROUP BY b.ad_key,b.start_city_code ORDER BY showDateId DESC LIMIT $start,$limit";

        // 查询并返回参数
        return $this->dbRO->createCommand($sql)->queryAll();
    }
	/**
	 * 获取当前也推广的广告位
	 * 
	 * @author wenrui 2014-04-25
	 * 
	 */
	public function getReleaseAdKeyInfo(){
//		$sql = "SELECT ad_key
//				FROM ba_ad_position a
//				LEFT JOIN bid_show_date b ON a.show_date_id = b.id
//				WHERE b.show_start_date = '".RELEASE_DATE."' AND a.del_flag = 0 AND b.del_flag = 0";
		$sql = "SELECT DISTINCT ad_key
				FROM bid_bid_product a
				LEFT JOIN bid_show_date b ON a.show_date_id = b.id
				WHERE a.bid_mark = 2 AND b.show_start_date = '".RELEASE_DATE."' AND a.del_flag = 0 AND b.del_flag = 0";
		$row = $this->dbRW->createCommand($sql)->queryAll();
		return $row;
	}

    /**
     * 更新竞价内容表的产品信息
     *
     * @param $param
     * @return boolean
     */
    public function updateBidContent($param) {
        // 初始化更新筛选条件
        $condSqlSegment = " bid_id = ".$param['bidId'];
        try {
            // 更新
            $result = $this->dbRW->createCommand()->update('bid_bid_content', array (
                'content_type' => intval($param['productType']),
                'content_id' => strval($param['productId']),
                'update_uid' => $param['accountId'],
                'update_time' => date('y-m-d H:i:s',time()),
            ), $condSqlSegment);
        } catch (Exception $e) {
            Yii::log($e);
            throw new Exception();
        }
        // 更新正确，返回true
        return true;
    }

    /**
     * 删除包场记录
     *
     * @param $params
     * @return array
     */
    public function delBuyout($params) {
        $condSqlSegment = " id = :bidId AND start_city_code = :startCityCode AND ad_key = :adKey AND show_date_id = :showDateId AND is_buyout = 1";
        $paramsMapSegment = array(
            ':bidId' => $params['bidId'],
            ':startCityCode' => $params['startCityCode'],
            ':adKey' => $params['adKey'],
            ':showDateId' => $params['showDateId'],
        );
        $data = $this->queryBuyout($params);
       
        if (sizeof($data) > 0) {
            $result = $this->dbRW->createCommand()->update('bid_bid_product', array(
                'del_flag' => 1,
                'update_time' => date('y-m-d H:i:s',time()),
                'misc' => strval($params['misc']),
            ), $condSqlSegment, $paramsMapSegment);
            
            // 更新成功返回true，否则返回false
            if ($result) {
                $bidId = $data[0]['bidId'];
                $sql = "select account_id as accountId, bid_id as bidId, content_type as contentType, content_id as contentId from bid_bid_content where del_flag = 0 AND bid_id = ".$bidId."";
                // 查询数据库
                $row = $this->dbRW->createCommand($sql)->queryRow();
                if ($row) {
                    // 删除产品内容
                    $params['bidId'] = $bidId;
                    $delete = $this->updateBidBidContent($params);
                    if ($delete) {
                        return true;
                    } else {
                        return false;
                    }
                }else{
                	return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 获得包场信息
     *
     * @param $condParams
     * @return array
     */
    public function queryBuyout($condParams) {
        $rows = array();
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $statementDao = new StatementDao();
            $accountIdStr = $statementDao->getIdByBrandName($condParams['vendorName']);
            if($accountIdStr){
                $condParams['accountId'] = $accountIdStr;
            } else {
                return array();
            }
        }
        // 根据供应商编号获取accountId
        if($condParams['vendorId']){
            $userManageDao = new UserManageDao();
            $accountId = $userManageDao->getAccountInfoByAgentId($condParams['vendorId']);
            if ($accountId) {
                $condParams['accountId'] = $accountId['id'];
            } else {
                return array();
            }
        }
        if($condParams){
            // 拼接搜索条件
            $condition = $this->buyoutSearchCondition($condParams);
            $condSqlSegment = $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $orderSqlSegment = ' b.show_start_date,a.ad_key,a.id';
        if ($condParams['bidState'] == 1 || $condParams['bidState'] == 2 || $condParams['bidState'] == -1) {
            $rows = $this->dbRO->createCommand()
                ->select('a.id bidId,a.show_date_id showDateId,a.ad_key adKey,a.start_city_code startCityCode,a.cat_type catType,a.search_keyword searchName,a.account_id accountId,
				    a.product_id productId,a.product_type productType,a.web_class webClass,a.ranking ranking,b.show_start_date showStartDate,b.show_end_date showEndDate,
				    b.bid_start_date bidStartDate,b.bid_start_time bidStartTime,b.bid_end_date bidEndDate,b.bid_end_time bidEndTime,b.replace_end_time replaceEndTime')
                ->from('bid_bid_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->order($orderSqlSegment)
                ->limit(($condParams['limit']) ? $condParams['limit'] : 10 , ($condParams['start']) ? $condParams['start'] : 0)
                ->queryAll();
        } elseif ($condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
            $rows = $this->dbRO->createCommand()
                ->select('a.id showId,a.show_date_id showDateId,a.ad_key adKey,a.start_city_code startCityCode,a.cat_type catType,a.search_keyword searchName,a.account_id accountId,
				    a.product_id productId,a.product_type productType,a.web_class webClass,a.ranking ranking,b.show_start_date showStartDate,b.show_end_date showEndDate,
				    b.bid_start_date bidStartDate,b.bid_start_time bidStartTime,b.bid_end_date bidEndDate,b.bid_end_time bidEndTime,b.replace_end_time replaceEndTime')
                ->from('bid_show_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->order($orderSqlSegment)
                ->limit(($condParams['limit']) ? $condParams['limit'] : 10 , ($condParams['start']) ? $condParams['start'] : 0)
                ->queryAll();
        }
        return $rows;
    }

    /**
     * 获得包场信息总数
     * @param $condParams
     * @return array
     */
    public function queryBuyoutCount($condParams) {
        $rows = array();
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $statementDao = new StatementDao();
            $accountIdStr = $statementDao->getIdByBrandName($condParams['vendorName']);
            if($accountIdStr){
                $condParams['accountId'] = $accountIdStr;
            } else {
                return array();
            }
        }
        // 根据供应商编号获取accountId
        if($condParams['vendorId']){
            $userManageDao = new UserManageDao();
            $accountId = $userManageDao->getAccountInfoByAgentId($condParams['vendorId']);
            if ($accountId) {
                $condParams['accountId'] = $accountId['id'];
            } else {
                return array();
            }
        }
        if($condParams){
            $condition = $this->buyoutSearchCondition($condParams);
            $condSqlSegment = $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        if ($condParams['bidState'] == 1 || $condParams['bidState'] == 2 || $condParams['bidState'] == -1) {
            $rows = $this->dbRO->createCommand()
                ->select('COUNT(*) count')
                ->from('bid_bid_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryScalar();
        } elseif ($condParams['bidState'] == 3 || $condParams['bidState'] == 4) {
            $rows = $this->dbRO->createCommand()
                ->select('COUNT(*) count')
                ->from('bid_show_product a')
                ->leftjoin('bid_show_date b','a.show_date_id = b.id')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryScalar();
        }
        return $rows;
    }

    /**
     * 包场搜索条件
     * @param array $condParams
     * @return array
     */
    public function buyoutSearchCondition($condParams){
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= " a.del_flag=:del_flag AND b.del_flag=:del_flag AND a.is_buyout = 1";
        $paramsMapSegment[':del_flag'] = 0;
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND a.account_id IN ('.$condParams['accountId'].')';
        }
        if ($condParams['startDate'] > '0000-00-00') {
            $condSqlSegment .= " AND b.show_end_date>=:startDate";
            $paramsMapSegment[':startDate'] = $condParams['startDate'];
        }
        if ($condParams['endDate']> '0000-00-00') {
            $condSqlSegment .= " AND b.show_start_date<=:endDate";
            $paramsMapSegment[':endDate'] = $condParams['endDate'];
        }
        if (!empty($condParams['showDateId'])) {
            $condSqlSegment .= ' AND a.show_date_id = :showDateId';
            $paramsMapSegment[':showDateId'] = $condParams['showDateId'];
        }
        if (!empty($condParams['adKey']) && $condParams['adKey'] != 'all') {
            // 首页广告位统一处理
            if ($condParams['adKey'] == 'index_chosen') {
                $condSqlSegment .= " AND a.ad_key LIKE '%index_chosen%'";
            } else {
                $condSqlSegment .= ' AND a.ad_key = :adKey';
                $paramsMapSegment[':adKey'] = $condParams['adKey'];
            }
        }
        if (!empty($condParams['startCityCode'])) {
            $condSqlSegment .= ' AND a.start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $condParams['startCityCode'];
        }
        if (!empty($condParams['productId'])) {
            $condSqlSegment .= ' AND a.product_id=:productId';
            $paramsMapSegment[':productId'] = $condParams['productId'];
        }
        if (!empty($condParams['productType'])) {
            $condSqlSegment .= ' AND a.product_type=:productType';
            $paramsMapSegment[':productType'] = $condParams['productType'];
        }
        if (!empty($condParams['ranking'])) {
            $condSqlSegment .= ' AND a.ranking=:ranking';
            $paramsMapSegment[':ranking'] = $condParams['ranking'];
        }
        if (!empty($condParams['webClass'])) {
            $condSqlSegment .= ' AND a.web_class=:webClass';
            $paramsMapSegment[':webClass'] = $condParams['webClass'];
        }
        if (!empty($condParams['searchKeyword'])) {
            $condSqlSegment .= ' AND a.search_keyword=:searchKeyword';
            $paramsMapSegment[':searchKeyword'] = $condParams['searchKeyword'];
        }
        if (!empty($condParams['bidId'])) {
            $condSqlSegment .= ' AND a.id=:bidId';
            $paramsMapSegment[':bidId'] = $condParams['bidId'];
        }
        if (!empty($condParams['bidState'])) {
            $paramsMapSegment[':nowTime'] = date("Y-m-d H");
            $condSqlSegment .= " AND b.status = 1";
            if ($condParams['bidState'] == 1) {
                $condSqlSegment .= " AND a.bid_mark = 2
                AND :nowTime < CONCAT(b.bid_start_date,' ',IF(b.bid_start_time<10,CONCAT('0',b.bid_start_time),b.bid_start_time))";
            } else if ($condParams['bidState'] == 2) {
                $condSqlSegment .= " AND a.bid_mark = 2
                AND :nowTime >= CONCAT(b.bid_start_date,' ',IF(b.bid_start_time<10,CONCAT('0',b.bid_start_time),b.bid_start_time))
                AND :nowTime < CONCAT(b.show_start_date,' 00')";
            } else if ($condParams['bidState'] == 3) {
                $condSqlSegment .= " AND :nowTime >= CONCAT(b.show_start_date,' 00') AND :nowTime < CONCAT(b.show_end_date,' 24')";
            } else if ($condParams['bidState'] == 4) {
                $condSqlSegment .= " AND :nowTime > CONCAT(b.show_end_date,' 00')";
            } else if ($condParams['bidState'] == -1) {
                $condSqlSegment .= " AND a.bid_mark IN (-1,-2,-3)
                AND :nowTime >= CONCAT(b.bid_end_date,' ',IF(b.bid_end_time<10,CONCAT('0',b.bid_end_time),b.bid_end_time))";
            }
        }
        return array('conSqlSegment'=>$condSqlSegment,'paramsMapSegment' =>$paramsMapSegment);
    }

    /**
     * 过滤出当前支持的包场广告位类型
     */
    public function getBuyoutType($condParams){
        // 初始化SQL语句
        $sql = "SELECT DISTINCT(b.ad_key) AS adKey, b.ad_name AS adName, b.start_city_code startCityCode
				FROM bid_show_date a
				LEFT JOIN ba_ad_position b
				ON a.id = b.show_date_id AND b.del_flag = 0
				WHERE DATE_FORMAT(CONCAT(bid_start_date, CONCAT(' ', bid_start_time)), '%Y-%m-%d %H') > DATE_FORMAT(NOW(), '%Y-%m-%d %H')
				AND a.del_flag=0 AND a.status = 1";
        if ($condParams['showDateId']) {
            $showDateId = $condParams['showDateId'];
            $sql .= " AND a.id = '$showDateId'";
        }
        $sql .= " GROUP BY b.ad_key";
        // 查询并返回参数
        $row = $this->dbRO->createCommand($sql)->queryAll();
        // 判断返回结果是否为空
        if (!empty ($row) && is_array($row)) {
            // 不为空，返回查询结果
            return $row;
        } else {
            // 为空，返回空数组
            return array ();
        }
    }
    
	/**
     * 判断招客宝是否有竞拍中或者即将竞拍的打包计划包含此广告位
     * 
     * @author wenrui 2014-05-28
     */
	public function queryBidPackageExist($data){
		$dydql = "";
		if(!empty($data["startCityCode"])){
			$dydql = " AND b.start_city_code = " . $data["startCityCode"];
		}
		$sql = "SELECT IFNULL(max(a.show_end_date), '0000-00-00') as show_end_date 
				FROM bid_show_date a
				LEFT JOIN ba_ad_position b ON a.id=b.show_date_id
				WHERE a.del_flag=0 AND b.del_flag=0 AND b.ad_key='".$data['adKey']."' AND a.status = 1 AND DATE_FORMAT(a.release_time, '%Y-%m-%d')<='".$data['date']."' AND DATE_FORMAT(a.bid_end_date, '%Y-%m-%d')>='".$data['date']."'".$dydql;
		$row = $this->dbRW->createCommand($sql)->queryRow();
		return $row;
	}
	
	/**
     * 判断当前时间此广告位是否已在招客宝被竞拍
     * 
     * @author wenrui 2014-05-28
     */
	public function queryBidProductExist($data){
		$dydql = "";
		if(!empty($data["startCityCode"])){
			$dydql = " AND a.start_city_code = " . $data["startCityCode"];
		}
		$sql = "SELECT IFNULL(max(b.show_end_date), '0000-00-00') as show_end_date
				FROM bid_bid_product a
				LEFT JOIN bid_show_date b ON a.show_date_id=b.id
				WHERE a.del_flag=0 AND a.fmis_mark IN (1,2) AND b.del_flag=0 AND a.ad_key='".$data['adKey']."' AND b.bid_start_date<='".$data['date']."' AND b.show_end_date>='".$data['date']."'".$dydql;
		$row = $this->dbRW->createCommand($sql)->queryRow();
		return $row;
	}
	
	/**
     * 删除招客宝的广告位
     * 
     * @author wenrui 2014-05-28
     */
	public function delBBPosition($data){
		$cond = 'del_flag = 0 and ad_key=:adKey and start_city_code=:startCityCode';
        $param = array(':adKey' => $data['adKey'],':startCityCode' => $data['startCityCode']);
        $result = $this->dbRW->createCommand()->update('ba_ad_position_type', array('del_flag' => 1), $cond, $param);
        if ($result) {
            return true;
        } else {
            return false;
        }
	}
	
	/**
	 * 查询广告位信息完整性
	 */
	public function queryAdWholeness($param) {
		try {
			// 初始化SQL
			$sql = "SELECT COUNT(*) AS countRe FROM bid_bid_product AS a LEFT JOIN bid_show_date AS b ON a.show_date_id = b.id 
					WHERE a.product_id = 0 AND a.del_flag = 0 AND a.bid_mark = 2 AND b.del_flag = 0 AND b.status = 1 AND DATE_FORMAT(b.show_start_date, '%Y-%m-%d') >= '".date('Y-m-d H')."' 
					AND CONCAT(DATE_FORMAT(b.bid_end_date, '%Y-%m-%d'), ' ', IF(b.bid_end_time<10,CONCAT('0',b.bid_end_time),b.bid_end_time)) < '".date('Y-m-d H')."' AND a.account_id = ".$param['accountId'];
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 查询查询竞价结束时间
	 */
	public function queryBidEndTime($showDateId) {
		try {
			// 初始化SQL
			$sql = "select bid_end_time as bidEndTime from bid_show_date where id = ".$showDateId;
			// 查询供应商信息
			return $this->dbRW->createCommand($sql)->queryRow();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}
	
	/**
	 * 清除包场位置数据
	 */
	public function clearBuyoutPosData() {
		try {
			// 初始化SQL
			$sql = "UPDATE 
						buyout_position_config
					SET
						ad_key = ''	
					WHERE 
						del_flag = 0 
					AND
						ad_key_type = 1;
					UPDATE
						buyout_position_config
					SET
						ad_product_count = 0
					WHERE 
						del_flag = 0;";
			// 清除包场位置数据
			$this->executeSql($sql,self::SROW);
		} catch (Exception $e) {
    		// 抛异常
          	throw new BBException(ErrorCode::ERR_231605, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231605)], $sql, $e);
		}
	}
	
	/**
	 * 查询包场位置维度
	 */
	public function queryBuyoutPosWd() {
		try {
			// 初始化SQL
			$sql = "SELECT 	
						id,
						ad_name,
						ad_key_type, 
						start_city_code, 
						web_class 
					FROM 
						buyout_position_config 
					WHERE 
						del_flag = 0 ";
			// 查询包场位置维度
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sql, $e);
		}
	}
	
	/**
	 * 查询包场位置首页数量
	 */
	public function queryBuyoutIndexCount($param) {
		try {
			// 初始化SQL
			$sql = "SELECT 	
						ad_key, 
						start_city_code,  
						ad_product_count
					FROM 
						ba_ad_position 
					WHERE 
						is_open = 0 
					AND 
						del_flag = 0 
					AND 
						ad_key_type = 1 
					AND 
						ad_name = '".$param['adName']."' 
					AND 
						show_date_id = ".$param['showDateId']." 
					AND 
						start_city_code IN (".$param['startCityCodes'].")";
			// 查询包场位置维度
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sql, $e);
		}
	}
	
	/**
	 * 查询包场位置分类页分类信息
	 */
	public function queryBuyoutClassInfo($param) {
		try {
	
			// 初始化包场父分类SQL
			$sqlFa = "SELECT 	
						DISTINCT parent_class as parentClass
					FROM 
						position_sync_class 
					WHERE 
						web_class IN (".$param['webClasses'].") 
					AND 
						start_city_code IN (".$param['startCityCodes'].") 
					AND 
						del_flag = 0
					AND 
						parent_depth = 1 			 
					ORDER BY 
						parent_depth 
					ASC ";
			// 查询包场父分类
			$rowFa = $this->dbRW->createCommand($sqlFa)->queryRow();
			
			// 初始化包场层级SQL
			$sqlDep = "SELECT 	
						DISTINCT web_class as webClass,
						class_depth as classDepth
					FROM 
						position_sync_class 
					WHERE 
						web_class IN (".$param['webClasses'].") 
					AND 
						start_city_code IN (".$param['startCityCodes'].") 
					AND 
						del_flag = 0 
					ORDER BY 
						parent_depth 
					ASC ";
			// 查询包场父分类
			$rowDep = $this->dbRW->createCommand($sqlDep)->queryAll();
			
			// 获取二级和三级分类
			$webTwoStr =Symbol::EMPTY_STRING;
			$webThreeStr =Symbol::EMPTY_STRING;
			foreach ($rowDep as $rowDepObj) {
				if (intval(chr(50)) == $rowDepObj['classDepth']) {
					$webTwoStr = $rowDepObj['webClass'];
				} else if (intval(chr(51)) == $rowDepObj['classDepth']) {
					$webThreeStr = $webThreeStr.$rowDepObj['webClass'].chr(44);
				}
			}
			$webThreeStr = substr($webThreeStr, 0, strlen($webThreeStr) - 1);
			
			// 初始化包场一级分类信息SQL
			$sqlPosOne = "SELECT 	
							start_city_code as startCityCode,
							web_class as webClass,
							ad_product_count as adProductCount,
							update_time as updateTime
						FROM 
							ba_ad_position 
						WHERE 
							web_class = ".$rowFa['parentClass']." 
						AND 
							start_city_code IN (".$param['startCityCodes'].")
						AND 
							show_date_id = ".$param['showDateId']." 
						AND 
							del_flag = 0";
			// 查询包场一级分类
			$rowPosOne = $this->dbRW->createCommand($sqlPosOne)->queryAll();
			
			// 初始化包场二级分类信息SQL
			$sqlPosTwo = "SELECT 	
							start_city_code as startCityCode,
							web_class as webClass,
							ad_product_count as adProductCount,
							update_time as updateTime
						FROM 
							ba_ad_position 
						WHERE 
							web_class = ".$webTwoStr." 
						AND 
							start_city_code IN (".$param['startCityCodes'].") 
						AND 
							show_date_id = ".$param['showDateId']."
						AND 
							del_flag = 0";
			// 查询包场二级分类
			$rowPosTwo = $this->dbRW->createCommand($sqlPosTwo)->queryAll();
			
			// 初始化包场三级分类信息SQL
			$sqlPosThree = "SELECT 	
							start_city_code as startCityCode,
							web_class as webClass,
							ad_product_count as adProductCount,
							update_time as updateTime
						FROM 
							ba_ad_position 
						WHERE 
							web_class IN (".$webThreeStr.") 
						AND 
							start_city_code IN (".$param['startCityCodes'].") 
						AND 
							show_date_id = ".$param['showDateId']."
						AND 
							del_flag = 0";
			// 查询包场三级分类
			$rowPosThree = $this->dbRW->createCommand($sqlPosThree)->queryAll();
			
			// 初始化返回结果
			$result['one'] = $rowPosOne;
			$result['two'] = $rowPosTwo;
			$result['three'] = $rowPosThree;
			
			// 返回结果
			return $result;
			
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $param, $e);
		}
	}
	
	/**
	 * 查询包场位置信息
	 */
	public function queryBuyoutPosInfo($param) {
		try {
			// 分类初始化adKeyType
			$adKeyType = intval(chr(48));
			if (intval(chr(49)) == $param['adKeyType']) {
				$adKeyType = $param['adKeyType'];
			} else {
				$adKeyType = Symbol::TWENTY_TWO.chr(44).Symbol::TWENTY_THREE;
			}
			// 初始化SQL
			$sql = "SELECT 	
						ad_name as adName, 
						ad_key as adKey, 
						web_class as webClass, 
						ad_product_count as adProductCount	 
					FROM 
						buyout_position_config 
					WHERE 
						ad_key_type IN (".$adKeyType.") 
					AND 
						del_flag = 0 
					AND 
						start_city_code = ".$param['startCityCode'];
			// 查询包场位置信息
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sql, $e);
		}
	}
	
	/**
	 * 删除旧的包场配置
	 */
	public function deleteBuyoutPosConfig($param) {
		try {
			
			// 初始化SQL
			$sql = "UPDATE 	
						bid_bid_product 
					SET
						del_flag = 1 
					WHERE
						bid_mark = 2 
					AND 
						account_id = ".$param['accountId']."
					AND
						start_city_code IN (".$param['startCityCodes'].")
					AND 
						del_flag = 0 
					AND 
						web_class IN (".$param['webClasses'].") 
					AND 
						is_buyout = 1 
					AND 
						ad_key in (".$param['adKey'].")
					AND 
						ranking in (".$param['rankings'].")			
					AND 
						show_date_id = ".$param['showDateId'];
			// 删除旧的包场配置
			return $this->executeSql($sql, self::SROW);
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231605, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231605)], $sql, $e);
		}
	}
	
	/**
	 * 查询包场位置限制
	 */
	public function queryBuyoutPosLimit($param) {
		try {
			// 分类初始化adKeyType
			$adKeyType = intval(chr(48));
			if (intval(chr(49)) == $param['adKeyType']) {
				$adKeyType = $param['adKeyType'];
			} else {
				$adKeyType = Symbol::TWENTY_TWO.chr(44).Symbol::TWENTY_THREE;
			}
			
			// 初始化SQL
			$sql = "SELECT 	
						ad_name, 
						ad_key, 
						web_class, 
						ad_product_count,
						start_city_code
					FROM 
						buyout_position_config 
					WHERE 
						ad_key_type IN (".$adKeyType.") 
					AND 
						del_flag = 0 
					AND 
						start_city_code IN (".$param['startCityCodes'].")
					AND 
						web_class = ".$param['webClass'];
			// 查询包场位置限制
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sql, $e);
		}
	}
	
	/**
	 * 查询包场记录重复排名
	 */
	public function queryBuyoutRecordRank($param) {
		try {
			
			// 初始化SQL
			$sql = "SELECT 	
						id
					FROM 
						bid_bid_product 
					WHERE 
						account_id = ".$param['accountId']."
					AND 
						bid_mark = 2 
					AND 
						start_city_code = ".$param['startCityCode']."
					AND 
						del_flag = 0 
					AND 
						web_class = ".$param['webClass']."
					AND 
						is_buyout = 1 
					AND 
						ad_key = '".$param['adKey']."'
					AND 
						show_date_id = ".$param['showDateId']."
					AND 
						ranking = ".$param['ranking'];
			// 查询包场记录重复排名
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sql, $e);
		}
	}
	
	/**
	 * 更新包场记录
	 */
	public function updateBuyoutRecord($param) {
		try {
			
			// 初始化SQL
			$sql = "update
						bid_bid_product 
					SET 
						ranking = ".$param['ranking'].",
						bid_ranking = ".$param['ranking'].",
						product_id = ".$param['productId'].",
						product_type = ".$param['productType']."
					WHERE
						id = ".$param['bidId'];
			// 更新包场记录
			return $this->executeSql($sql, self::SROW);
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231602, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231602)], $sql, $e);
		}
	}
	
	/**
	 * 根据城市查询首页adKey
	 */
	public function queryIndexKeyByCity($cities, $adKeyType, $webClass) {
		try {
			// 分类初始化adKeyType
			$adKeyTypeLoc = intval(chr(48));
			if (intval(chr(49)) == $adKeyType) {
				$adKeyTypeLoc = $adKeyType;
			} else {
				$adKeyTypeLoc = Symbol::TWENTY_TWO.chr(44).Symbol::TWENTY_THREE;
			}
			// 初始化SQL
			$sql = "select ad_key as adKey, start_city_code as startCityCode from buyout_position_config where del_flag = 0 and web_class = ".$webClass." AND  ad_key_type IN (".$adKeyTypeLoc.") and start_city_code in (".$cities.")";
			// 根据城市查询首页adKey
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
	}

	/**
	 * 查询包场重复的产品
	 */
	public function queryBuyoutDouProduct($param) {
		try {
			// 初始化动态SQL
			$dySql = "";
			if (!empty($param['bidId'])) {
				$dySql = " AND id != ".$param['bidId'];
			}
			// 初始化SQL
			$sql = "SELECT 	
						product_id,
						start_city_code,
						web_class,
						ad_key
					FROM 
						bid_bid_product 
					WHERE 
						account_id = 18
					AND 
						bid_mark = 2 
					AND 
						start_city_code IN (".$param['startCityCodes'].")
					AND 
						del_flag = 0 
					AND 
						web_class = ".$param['webClass']."
					AND 
						is_buyout = 1 
					AND 
						ad_key IN (".$param['adKeys'].") 
					AND 
						show_date_id = ".$param['showDateId'].$dySql;
			// 查询包场记录重复排名
			return $this->dbRW->createCommand($sql)->queryAll();
		} catch (Exception $e) {
			// 抛异常
          	throw new BBException(ErrorCode::ERR_231550, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231550)], $sql, $e);
		}
	}

    /**
     * 查询部门信息
     */
    public function queryDepartmentInfo() {
        try {
            // 初始化SQL
            $sql = "select id as id, department_id as departmentId, update_time as updateTime from department_info where del_flag = 0";
            // 查询供应商信息
            return $this->dbRW->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            // 打印错误日志
            Yii::log($e);
            // 抛异常
            throw $e;
        }
    }
    
}