<?php
Yii::import('application.dal.dao.DaoModule');
class CommonDao extends DaoModule{
	public function getDepartCityInfo($cityCode) {
		if(empty($cityCode)) {
			return array();
		}
	
		$condSqlSegment = ' AND code=:code';
		$paramsMapSegment[':code'] = $cityCode;
	
		$rows = $this->dbRO->createCommand()
		->select('type,name,letter,address')
		->from('departure')
		->where('mark=0'.$condSqlSegment, $paramsMapSegment)
		->queryRow();
	
		return $rows;
	}
	
	public function readAdPosition($params) {
		if(empty($params['adKey'])) {
			return array();
		}
	
		$sql = "SELECT a.ad_name,a.floor_price,a.ad_product_count
				FROM ba_ad_position a
				LEFT JOIN bid_show_date b ON a.show_date_id = b.id
				WHERE a.del_flag = 0 AND a.ad_key = '".$params['adKey']."' AND b.del_flag = 0 AND b.show_start_date<='".date('Y-m-d')."' AND b.show_end_date>='".date('Y-m-d')."'";
		$rows = $this->dbRO->createCommand($sql)->queryRow();
	
		return $rows;
	}
}

?>