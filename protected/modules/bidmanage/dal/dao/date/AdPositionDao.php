<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 14-1-16
 * @time 下午9:41
 * @description AdPostionDao.php
 */ 
class AdPositionDao extends DaoModule
{
    const CUR_TBL = 'ba_ad_position';

    /**
     * 查询出价广告位类型配置信息
     *
     * @author chenjinlong 20140116
     * @param $showDateId
     * @return array
     */
    public function queryAdPositionByShowDateId($param)
    {
    	try {
    		// 分类获取广告位底价
    		if (BusinessType::CLASS_RECOMMEND == $param['ad_key'] && 136 < $param['show_date_id']) {
    			// 分类页   新版  后上线
    			// 查询分类页父级信息
    			$sqlFa = "SELECT web_class, start_city_code, class_depth, parent_class, parent_depth FROM position_sync_class WHERE web_class = ".$param['web_class']." AND start_city_code = ".$param['start_city_code']." AND del_flag = 0 AND parent_depth IN (1,2)";
    			$faRows = $this->executeSql($sqlFa, self::ALL);
    			$data = array();
    			// 获取一级和二级分类报价信息
    			foreach ($faRows as $faRowsObj) {
    				if (intval(chr(49)) == $faRowsObj['parent_depth']) {
    					// 一级分类报价
    					$sqlOne = "SELECT ROUND(floor_price) as floor_price, ad_product_count, coupon_use_percent, update_time FROM ba_ad_position WHERE web_class = ".$faRowsObj['parent_class']." AND start_city_code = ".$param['start_city_code']." AND del_flag = 0 AND show_date_id = ".$param['show_date_id'];
    					array_push($data, $this->executeSql($sqlOne, self::ROW));
    				} else if (intval(chr(50)) == $faRowsObj['parent_depth']) {
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
    			// 其他  老版  先上线
    			// 初始化动态SQL
				$dySql = "";
				if (!empty($param['start_city_code']) && (strpos($param['ad_key'],'index_chosen') !== Symbol::CONS_FALSE || strpos($param['ad_key'],'channel_chosen') !== Symbol::CONS_FALSE)) {
					$dySql = $dySql." and start_city_code =".$param['start_city_code'];
				}
				$sqlRow = "SELECT id,ad_key,ad_name,floor_price,ad_product_count ".
						"FROM ba_ad_position WHERE show_date_id = ".$param['show_date_id']." AND ad_key = '".$param['ad_key']."' AND del_flag = 0 ".$dySql;
				return $this->executeSql($sqlRow, self::ROW);
    		}
			
		} catch (Exception $e) {
    		// 打印错误日志
    		Yii::log($e);
    		// 抛异常
          	throw $e;
		}
    }
}
 