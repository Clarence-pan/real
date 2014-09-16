<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 4:52 PM
 * Description: StaBbEffectDao.php
 */
Yii::import('application.dal.dao.DaoModule');

class StaBbEffectDao extends DaoModule
{
    /**
     * 查询指定时间段内的BI跟踪统计数值
     *
     * @author chenjinlong 20121225
     * @param $params
     * @return array
     */
    public function queryBbEffectArrByDate($params) {        

            $condSqlSegment .= ' a.del_flag=:delFlag';
            $paramsMapSegment[':delFlag'] = 0;
            if ($params['accountId']) {
                $condSqlSegment .= ' AND a.account_id IN (' . $params['accountId'] . ')';
            }
            if ($params['startDate'] != '0000-00-00' && $params['endDate'] != '0000-00-00') {
                $condSqlSegment .= ' AND a.date>=:startDate AND a.date<=:endDate';
                $paramsMapSegment[':startDate'] = $params['startDate'];
                $paramsMapSegment[':endDate'] = $params['endDate'];
            }


            if ($params['productLineName']) {
                $condSqlSegment .= ' AND b.product_line_name=:productLineName';
                $paramsMapSegment[':productLineName'] = $params['productLineName'];
            }
            if ($params['productType']) {
                $condSqlSegment .= ' AND b.product_type=:productType';
                $paramsMapSegment[':productType'] = $params['productType'];
            }
            if ($params['destinationClass']) {
                $condSqlSegment .= ' AND b.destination_class=:destinationClass';
                $paramsMapSegment[':destinationClass'] = $params['destinationClass'];
            }
            if ($params['startCityCode']) {
                $condSqlSegment .= ' AND b.start_city_code=:startCityCode';
                $paramsMapSegment[':startCityCode'] = $params['startCityCode'];
            }
            if($params['productName']) {
            	$condSqlSegment .= ' AND b.product_name LIKE :productName';
            	$paramsMapSegment[':productName'] = "%" . $params['productName'] ."%";
            }
            if ($params['productId']) {
                $condSqlSegment .= ' AND b.product_id=:productId';
                $paramsMapSegment[':productId'] = $params['productId'];
            }

            $row = $this->dbRO->createCommand()
                    ->select('SUM(reveal) AS reveal, SUM(ip_view) AS ip_view, SUM(click_num) AS click_num, SUM(consumption) AS consumption, SUM(order_conversion) AS order_conversion')
                    ->from('bb_effect' . ' a')
                    ->join('bid_product b', 'a.product_id = b.product_id')
                    ->where($condSqlSegment, $paramsMapSegment)
                    ->queryRow();
            if (!empty($row) && is_array($row)) {
                return $row;
            } else {
                return array();
            }
    }

    /**
     * 查询累积使用过的牛币
     *
     * @author 黄勋 20131120
     * @param $params
     * @return array
     */
    public function queryConsumption($params) {
        $condSqlSegment = ' del_flag = :delFlag AND fmis_id > 0 AND cancel_fmis_id = 0';
        $paramsMapSegment[':delFlag'] = 0;
        if ($params['accountId']) {
            $condSqlSegment .= ' AND account_id = :account_id';
            $paramsMapSegment[':account_id'] = $params['accountId'];
        } else {
            return array();
        }
        $row = $this->dbRO->createCommand()
            ->select('SUM(ROUND(bid_price)) AS consumption')
            ->from('bid_show_product')
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryRow();
        if (!empty($row) && is_array($row)) {
            return $row;
        } else {
            return array();
        }
    }

    /**
     * 新增统计表记录
     *
     * @author chenjinlong 20121224
     * @param $params
     * @return bool
     */
    public function insertBbEffectedRecord($params) {
        $result = $this->dbRW->createCommand()->insert('bb_effect', array(
            'account_id' => $params['account_id'],
            'product_id' => $params['product_id'],
            'product_type' => $params['product_type'],
            'date' => $params['date'],
            'reveal' => $params['reveal'],
            'ip_view' => $params['ip_view'],
            'click_num' => $params['click_num'],
            'consumption' => $params['consumption'],
            'order_conversion' => $params['order_conversion'],
            'add_time' => date('Y-m-d H:i:s'),
            'del_flag' => 0,
            'misc' => !empty($params['misc'])?strval($params['misc']):'',
        ));
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询某帐户-某产品-某日的统计结果
     *
     * @author chenjinlong 20121224
     * @param $params
     * @return array
     */
    public function getSpecificBbEffectRecord($params)
    {
        if ($params['account_id'] <= 0 || $params['product_id'] <= 0 || $params['date'] <= 0 || $params['product_type'] <= 0) {
            return array();
        } else {
            $sql = "SELECT account_id, product_id, product_type, date, reveal, ip_view, click_num, consumption, order_conversion
                    FROM bb_effect
                    WHERE account_id='{$params['account_id']}' AND product_id='{$params['product_id']}' AND product_type='{$params['product_type']}' AND date='{$params['date']}' AND del_flag=0";
            $row = $this->dbRO->createCommand($sql)->queryRow();
            if (!empty($row) && is_array($row)) {
                return $row;
            } else {
                return array();
            }
        }
    }

    /**
     * 更新某帐户-某产品-某日的统计结果
     *
     * @author chenjinlong 20121225
     * @param $tgtArr
     * @param $conditionArr
     * @return bool
     */
    public function updateSpecificBbEffectRecord($tgtArr, $conditionArr)
    {
        $exeResult = $this->dbRW->createCommand()->update('bb_effect',array(
                'consumption' => $tgtArr['consumption'],
                'reveal' => $tgtArr['reveal'],
                'ip_view' => $tgtArr['ip_view'],
                'click_num' => $tgtArr['click_num'],
                'order_conversion' => $tgtArr['order_conversion'],
                'update_time' => date('Y-m-d H:i:s'),
            ),
            'account_id=:account_id AND product_id=:product_id AND date=:date AND product_type=:product_type AND del_flag=0',
            array(':account_id'=>$conditionArr['account_id'], ':product_id'=>$conditionArr['product_id'], ':date'=>$conditionArr['date'], ':product_type'=>$conditionArr['product_type'], ));
        if($exeResult){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 执行逻辑清除影响二次统计的数据
     *
     * @author chenjinlong 20130316
     * @param $params
     * @return boolean
     */
    public function deleteOlderEffectedRows($params)
    {
        $staDate = $params['sta_date'];
        $misc = $params['misc'];
        $sql = "update bb_effect set del_flag=1,misc='{$misc}' where date='{$staDate}' and del_flag=0";
        $queryResult = $this->dbRW->createCommand($sql)->query();
        if($queryResult)
            return true;
        else
            return false;
    }

    public function getSpreadProductList($params) {
    	$condSqlSegment .= ' a.del_flag=:delFlag ';
    	$paramsMapSegment[':delFlag'] = 0;
    	$condSqlSegment .= ' and b.del_flag=:delFlag';
    	$paramsMapSegment[':delFlag'] = 0;
    	if($params['accountId']) {
    		$condSqlSegment .= ' AND a.account_id=:accountId';
    		$paramsMapSegment[':accountId'] = $params['accountId'];
    	}
    	if($params['startDate'] != '0000-00-00' && $params['endDate'] != '0000-00-00'){
            $condSqlSegment .= ' AND a.bid_date>=:startDate AND a.bid_date<=:endDate';
            $paramsMapSegment[':startDate'] = $params['startDate'];
            $paramsMapSegment[':endDate'] = $params['endDate'];
        }
        if($params['productLineName']) {
            $condSqlSegment .= ' AND b.product_line_name=:productLineName';
            $paramsMapSegment[':productLineName'] = $params['productLineName'];
        }
        if($params['productType']) {
            $condSqlSegment .= ' AND b.product_type=:productType';
            $paramsMapSegment[':productType'] = $params['productType'];
        }
        if($params['destinationClass']) {
            $condSqlSegment .= ' AND b.destination_class=:destinationClass';
            $paramsMapSegment[':destinationClass'] = $params['destinationClass'];
        }
        if($params['startCityCode']) {
            $condSqlSegment .= ' AND a.start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $params['startCityCode'];
        }
        if($params['adKey']) {
        	$condSqlSegment .= ' AND a.ad_key=:adKey';
        	$paramsMapSegment[':adKey'] = $params['adKey'];
        }
        if($params['productName']) {
            $condSqlSegment .= ' AND b.product_name=:productName';
            $paramsMapSegment[':productName'] = $params['productName'];
        }
        if($params['productId']) {
            $condSqlSegment .= ' AND b.product_id=:productId';
            $paramsMapSegment[':productId'] = $params['productId'];
        }
        if(2 != intval($params['isPaied'])){
        	if (1 == intval($params['isPaied'])) {    //扣费成功（推广成功）
        		$condSqlSegment .= ' AND a.fmis_mark=:fmisMark ';
        		$paramsMapSegment[':fmisMark'] = 1;
        	} elseif(0 == intval($params['isPaied'])) {    //扣费失败（推广失败）
        		$condSqlSegment .= ' AND a.fmis_mark =:fmisMark ';
        		$paramsMapSegment[':fmisMark'] = -1;
        	} elseif(3 == intval($params['isPaied'])) {    //竞价中（产品竞价进行中）
        		$condSqlSegment .= ' AND a.fmis_mark =:fmisMark AND a.bid_mark =:bidMark ';
        		$paramsMapSegment[':fmisMark'] = 0;
        		$paramsMapSegment[':bidMark'] = 0;
        	}
        }
        if($params['isDownload']) {
        	$row  = $this->dbRO->createCommand()
        	->select('a.account_id accountId,a.product_id productId,a.bid_date spreadDate,a.fmis_mark fmisMark,
                        a.bid_price bidPrice,a.product_type productType,
        				a.ad_key adKey,a.start_city_code startCityCode,b.product_line_name productLineName,a.add_time addTime')
        	->from('bid_bid_product'.' a')
        	->join('bid_product b','a.product_id = b.product_id AND a.account_id=b.account_id')
        	->where($condSqlSegment, $paramsMapSegment)
        	->queryAll();
        } else {
        	$row  = $this->dbRO->createCommand()
        	->select('a.account_id accountId,a.product_id productId,a.bid_date spreadDate,a.fmis_mark fmisMark,a.bid_price bidPrice,a.product_type productType,
        				a.ad_key adKey,a.start_city_code startCityCode,b.product_line_name productLineName,a.add_time addTime')
        	->from('bid_bid_product'.' a')
        	->join('bid_product b','a.product_id = b.product_id AND a.account_id=b.account_id')
        	->where($condSqlSegment, $paramsMapSegment)
        	->limit($params['limit'],$params['start'])
        	->queryAll();
        }
    	if (!empty($row) && is_array($row)) {
    		return $row;
    	} else {
    		return array();
    	}
    }
    
    public function getSpreadProductCount($params) {
    $condSqlSegment .= ' a.del_flag=:delFlag ';
    	$paramsMapSegment[':delFlag'] = 0;
    	$condSqlSegment .= ' and b.del_flag=:delFlag';
    	$paramsMapSegment[':delFlag'] = 0;
    	if($params['accountId']) {
    		$condSqlSegment .= ' AND a.account_id=:accountId';
    		$paramsMapSegment[':accountId'] = $params['accountId'];
    	}
    	if($params['startDate'] != '0000-00-00' && $params['endDate'] != '0000-00-00'){
            $condSqlSegment .= ' AND a.bid_date>=:startDate AND a.bid_date<=:endDate';
            $paramsMapSegment[':startDate'] = $params['startDate'];
            $paramsMapSegment[':endDate'] = $params['endDate'];
        }
        if($params['productLineName']) {
            $condSqlSegment .= ' AND b.product_line_name=:productLineName';
            $paramsMapSegment[':productLineName'] = $params['productLineName'];
        }
        if($params['productType']) {
            $condSqlSegment .= ' AND b.product_type=:productType';
            $paramsMapSegment[':productType'] = $params['productType'];
        }
        if($params['destinationClass']) {
            $condSqlSegment .= ' AND b.destination_class=:destinationClass';
            $paramsMapSegment[':destinationClass'] = $params['destinationClass'];
        }
        if($params['startCityCode']) {
            $condSqlSegment .= ' AND a.start_city_code=:startCityCode';
            $paramsMapSegment[':startCityCode'] = $params['startCityCode'];
        }
        if($params['adKey']) {
        	$condSqlSegment .= ' AND a.ad_key=:adKey';
        	$paramsMapSegment[':adKey'] = $params['adKey'];
        }
        if($params['productName']) {
            $condSqlSegment .= ' AND b.product_name=:productName';
            $paramsMapSegment[':productName'] = $params['productName'];
        }
        if($params['productId']) {
            $condSqlSegment .= ' AND b.product_id=:productId';
            $paramsMapSegment[':productId'] = $params['productId'];
        }
        if(2 != intval($params['isPaied'])){
        	if (1 == intval($params['isPaied'])) {    //扣费成功（推广成功）
        		$condSqlSegment .= ' AND a.fmis_mark=:fmisMark ';
        		$paramsMapSegment[':fmisMark'] = 1;
        	} elseif(0 == intval($params['isPaied'])) {    //扣费失败（推广失败）
        		$condSqlSegment .= ' AND a.fmis_mark =:fmisMark ';
        		$paramsMapSegment[':fmisMark'] = -1;
        	} elseif(3 == intval($params['isPaied'])) {    //竞价中（产品竞价进行中）
        		$condSqlSegment .= ' AND a.fmis_mark =:fmisMark AND a.bid_mark =:bidMark ';
        		$paramsMapSegment[':fmisMark'] = 0;
        		$paramsMapSegment[':bidMark'] = 0;
        	}
        }
        $result  = $this->dbRO->createCommand()
        ->select('count(1) count')
        ->from('bid_bid_product'.' a')
        ->join('bid_product b','a.product_id = b.product_id AND a.account_id=b.account_id')
        ->where($condSqlSegment, $paramsMapSegment)
        ->queryRow();
    	if ($result['count']) {
    		return $result['count'];
    	} else {
    		return 0;
    	}
    }
    
    public function getSpreadProductInfo($params) {
    	if ($params['accountId'] <= 0 || $params['productId'] <= 0 || $params['spreadDate'] <= 0) {
    		return array();
    	} else {
    		$sql = "SELECT account_id, product_id, date, reveal, ip_view, click_num clickNum, consumption, order_conversion orderConversion
    		FROM bb_effect
    		WHERE account_id='{$params['accountId']}' AND product_id='{$params['productId']}' AND date='{$params['spreadDate']}' AND del_flag=0";
    		$row = $this->dbRO->createCommand($sql)->queryRow();
    		if (!empty($row) && is_array($row)) {
    			return $row;
	    	} else {
	    		return array();
	    	}
    	}
    }

}
