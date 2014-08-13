<?php
Yii::import('application.dal.dao.DaoModule');
//bid
class StatementDao extends DaoModule {
    /**
     *
     * @var bid_bid_product
     */
    private $_tblName = 'bid_bid_product';

    /**
     * 搜索条件：供应商名称查询
     * @param array $condParams
     * @return array
     */
    function getIdByVendorName($vendorName) {
        $accountIdStr = '';
        $vendorSql = ' del_flag = 0 AND account_name LIKE :vendorName';
        $paramsMapSegment[':vendorName'] = '%' . $vendorName . '%';
        $accountId = $this->dbRO->createCommand()
                ->select('id')
                ->from('bb_account')
                ->where($vendorSql, $paramsMapSegment)
                ->queryAll();
        if ($accountId) {
            $accountIdArr = array();
            foreach ($accountId as $value) {
                $accountIdArr[] = $value['id'];
            }
            $accountIdStr = trim(implode(',', $accountIdArr));           
        } 
        return $accountIdStr;
    }
    /**
     * 搜索条件：供应商品牌名查询
     * @param array $condParams
     * @return array
     */
    function getIdByBrandName($brandName) {
        $accountIdStr = '';
        $vendorSql = ' del_flag = 0 AND brand_name LIKE :brandName';
        $paramsMapSegment[':brandName'] = '%' . $brandName . '%';
        $accountId = $this->dbRO->createCommand()
            ->select('id')
            ->from('bb_account')
            ->where($vendorSql, $paramsMapSegment)
            ->queryAll();
        if ($accountId) {
            $accountIdArr = array();
            foreach ($accountId as $value) {
                $accountIdArr[] = $value['id'];
            }
            $accountIdStr = trim(implode(',', $accountIdArr));
        }
        return $accountIdStr;
    }

    /**
     * 招客宝报表查询附加费费用
     * @param array $condParams
     * @return array
     */
    public function getAdditionPrice($condParams){
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= " del_flag=0 AND bid_mark=1";
        if (!empty($condParams['accountId']) || $condParams['bidId']) {
            if (!empty($condParams['accountId'])) {
                $condSqlSegment .= ' AND account_id IN ('.$condParams['accountId'].')';
            }
            if ($condParams['bidId']) {
                $condSqlSegment .= ' AND bid_id IN ('.$condParams['bidId'].')';
            }
            $rows = $this->dbRO->createCommand()
                ->select('sum(bid_price) additionPrice')
                ->from('bid_bid_vas')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryRow();

            return $rows;
        } else {
            return array();
        }
    }

    /**
     * 招客宝报表搜索条件
     * @param array $condParams
     * @return array
     */
    public function formsSearchCondition($condParams){
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= " a.del_flag=:del_flag AND b.del_flag=:del_flag AND c.del_flag=:del_flag AND a.bid_mark=1 AND b.product_id !=0";
        $paramsMapSegment[':del_flag'] = 0;
        if (!empty($condParams['accountId'])) {
            $condSqlSegment .= ' AND a.account_id IN ('.$condParams['accountId'].')';
        }
        if ($condParams['startDate'] > '0000-00-00') {
            $condSqlSegment .= " AND c.show_end_date>=:startDate";
            $paramsMapSegment[':startDate'] = $condParams['startDate'];
        }
        if ($condParams['endDate']> '0000-00-00') {
            $condSqlSegment .= " AND c.show_start_date<=:endDate";
            $paramsMapSegment[':endDate'] = $condParams['endDate'];
        }
        if ($condParams['showDateId'] > 0) {
            $condSqlSegment .= " AND a.show_date_id=:showDateId";
            $paramsMapSegment[':showDateId'] = $condParams['showDateId'];
        }
        if (!empty($condParams['adKey'])) {
            if ($condParams['adKey'] == 'index_chosen') {
                // 首页广告位统一处理
                $condSqlSegment .= " AND a.ad_key LIKE 'index_chosen%'";
            } elseif ($condParams['adKey'] == 'channel_chosen') {
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
        if (!empty($condParams['productId'])) {
            $condSqlSegment .= ' AND a.product_id=:productId';
            $paramsMapSegment[':productId'] = $condParams['productId'];
        }
        if (!empty($condParams['productName'])) {
            $condSqlSegment .= ' AND b.product_name LIKE :productName';
            $paramsMapSegment[':productName'] = '%'.$condParams['productName'].'%';
        }
        return array('conSqlSegment'=>$condSqlSegment,'paramsMapSegment' =>$paramsMapSegment);
    }

    /**
     * [product]bb-招客宝报表
     * @param $condParams
     * @return array
     */
    public function getReportFormsList($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $orderSqlSegment = ' c.id,c.show_start_date,a.ad_key DESC,a.id';
        $rows = $this->dbRO->createCommand()
            ->select('a.id bidId,a.product_id productId,a.product_type productType,a.show_date_id showDateId,a.ad_key adKey,a.start_city_code startCityCode,a.cat_type catType,
				a.web_class webClass,a.bid_price bidPrice,a.ranking ranking,b.web_class_str webClassStr,
				b.product_name productName,b.checker_flag checkerFlag,b.agency_product_name agencyProductName,b.manager_id managerId,c.show_start_date showStartDate,
				c.show_end_date showEndDate,c.bid_start_date bidStartDate,c.bid_start_time bidStartTime,c.bid_end_date bidEndDate,c.bid_end_time bidEndTime,c.replace_end_time replaceEndTime,a.search_keyword searchName,a.account_id accountId')
            ->from('bid_bid_product a')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->where($condSqlSegment, $paramsMapSegment)
            ->order($orderSqlSegment)
            // 使用group by来唯一确定数据
            ->group('a.product_id,a.product_type,a.account_id,a.show_date_id')
            ->limit($condParams['limit'], $condParams['start'])
            ->queryAll();

        return $rows;
    }

    /**
     * [product]bb-招客宝报表
     * @param $condParams
     * @return array
     */
    public function getReportFormsAllList($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $orderSqlSegment = ' c.id,c.show_start_date,a.ad_key DESC,a.id';
        $rows = $this->dbRO->createCommand()
            ->select('a.id AS bidId, a.product_id AS productId, a.product_type AS productType, c.show_start_date AS showStartDate, c.show_end_date AS showEndDate,  a.account_id AS accountId')
            ->from('bid_bid_product a')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->order($orderSqlSegment)
            // 使用group by来唯一确定数据
            ->group('a.product_id,a.product_type,a.account_id,a.show_date_id')
            ->queryAll();

        return $rows;
    }

    /**
     * [product]bb-招客宝报表
     * @param $condParams
     * @return array
     */
    public function getReportFormsCount($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $rows = $this->dbRO->createCommand()
            // 使用distinct来唯一确定数据条数
            ->select('COUNT(DISTINCT a.product_id,a.product_type,a.account_id,a.show_date_id) count')
            ->from('bid_bid_product a')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryScalar();
        return $rows;
    }

    /**
     * [product]hg-招客宝报表
     * @param $condParams
     * @return array
     */
    public function getHgReportFormsList($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $orderSqlSegment = ' c.id,c.show_start_date,a.ad_key DESC,a.id';
        $rows = $this->dbRO->createCommand()
            ->select('a.id AS bidId, a.product_id AS productId, a.product_type AS productType, a.show_date_id AS showDateId, a.ad_key AS adKey, 
						a.start_city_code AS startCityCode, a.web_class AS webClass, a.bid_price AS bidPrice, a.ranking AS ranking, 
						b.product_name AS productName, b.checker_flag AS checkerFlag, c.show_start_date AS showStartDate, c.show_end_date AS showEndDate, 
						c.bid_start_date AS bidStartDate, c.bid_start_time AS bidStartTime, c.bid_end_date AS bidEndDate, c.bid_end_time AS bidEndTime,
						a.search_keyword AS searchName, a.account_id AS accountId')
            ->from('bid_bid_product a')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->where($condSqlSegment, $paramsMapSegment)
            ->order($orderSqlSegment)
            // 使用group by来唯一确定数据
            ->group('a.product_id,a.product_type,a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id,a.account_id')
            ->limit($condParams['limit'], $condParams['start'])
            ->queryAll();

        return $rows;
    }

    /**
     * [product]总的消费金额
     * @param $condParams
     * @return
     */
    public function getAllConsumption($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $rows = $this->dbRO->createCommand()
            ->select('SUM(a.bid_price) bidAllPrice')
            ->from('bid_bid_product a')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();

        return $rows;
    }

    /**
     * [product]hg-招客宝报表
     * @param $condParams
     * @return array
     */
    public function getHgReportFormsAllList($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $orderSqlSegment = ' c.id,c.show_start_date,a.ad_key DESC,a.id';
        $rows = $this->dbRO->createCommand()
            ->select('a.id AS bidId, a.product_id AS productId, a.product_type AS productType, a.show_date_id AS showDateId, a.ad_key AS adKey, a.start_city_code AS startCityCode,
						a.web_class AS webClass, a.bid_price AS bidPrice, a.ranking AS ranking, a.search_keyword AS searchName, a.account_id AS accountId')
            ->from('bid_bid_product a')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->where($condSqlSegment, $paramsMapSegment)
            // 使用group by来唯一确定数据
            ->group('a.product_id,a.product_type,a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id,a.account_id')
            ->queryAll();

        return $rows;
    }

    /**
     * [product]hg-招客宝报表
     * @param $condParams
     * @return array
     */
    public function getHgReportFormsCount($condParams) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        // 根据供应商名称获取accountId串
        if($condParams['vendorName']){
            $accountIdStr = $this->getIdByVendorName($condParams['vendorName']);
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
            $condition = $this->formsSearchCondition($condParams);
            $condSqlSegment .= $condition['conSqlSegment'];
            $paramsMapSegment = $condition['paramsMapSegment'];
        }
        $rows = $this->dbRO->createCommand()
            // 使用distinct来唯一确定数据条数
            ->select('COUNT(DISTINCT a.product_id,a.product_type,a.ad_key,a.start_city_code,a.web_class,a.search_keyword,a.show_date_id,a.account_id) count')
            ->from('bid_bid_product a')
            ->leftjoin('bid_product b','a.account_id = b.account_id AND a.product_id = b.product_id AND a.product_type = b.product_type')
            ->leftjoin('bid_show_date c','a.show_date_id = c.id')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryScalar();
        return $rows;
    }

}
?>