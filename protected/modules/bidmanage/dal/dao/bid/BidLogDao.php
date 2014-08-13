<?php
Yii::import('application.dal.dao.DaoModule');
//bid
class BidLogDao extends DaoModule{
	private $_tblName = 'bid_log';
	
	public function insert($in) {
		$exeResult = $this->dbRW->createCommand()->insert($this->_tblName,array(
				'account_id'=>$in['account_id'],
				'product_id'=>intval($in['product_id']),
				'bid_date_beg'=>$in['bid_date_beg'],
				'bid_date_end'=>$in['bid_date_end'],
				'ad_key'=>$in['ad_key'],
				'web_class'=>$in['web_class']==null?0:$in['web_class'],
				'start_city_code'=>$in['start_city_code'],
				'bid_price'=>$in['bid_price'],
				'ranking'=>$in['ranking'],
				'bid_ranking'=>$in['bid_ranking'],
				'bid_mark'=>$in['bid_mark']?$in['bid_mark']:0,
				'is_cancel'=>$in['is_cancel']?$in['is_cancel']:0,
				'add_uid'=>$in['add_uid'],
				'add_time'=>$in['add_time'],
				'update_uid'=>$in['update_uid'],
				'update_time' => $in['update_time'],
				'del_flag' => $in['del_flag']?$in['del_flag']:0,
				'misc' => $in['misc']?$in['misc']:'',
		));
		$tblIndexLastID = $this->dbRW->lastInsertID;
	}
}

?>