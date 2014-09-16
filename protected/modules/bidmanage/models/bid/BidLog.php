<?php
Yii::import('application.modules.bidmanage.dal.dao.bid.BidLogDao');
Yii::import('application.modules.bidmanage.models.product.ProductMod');
//bid
class BidLog {
	private $bidLogDao;
	private $product;
	
	function __construct() {
		$this->bidLogDao = new BidLogDao();
		$this->product = new ProductMod();
	}

    /**
     * 招客宝改版-新增日志记录
     *
     * @author chenjinlong 20131114
     * @param array $params
     * @return mixed
     */
    public function insertBidLogRecord($params)
    {
        $params['ranking'] = intval($params['ranking']);
        $params['bid_ranking'] = intval($params['bid_ranking']);
        $params['is_cancel'] = intval($params['is_cancel']);
        $data = $this->getInsertData($params);
        $this->bidLogDao->insert($data);
    }
	
	private function getInsertData($inParams) {
		$in['account_id'] = intval($inParams['account_id']);
		$in['product_id'] = intval($inParams['product_id']);
        if(!empty($inParams['date_list'])){
            $bidDateStr = $inParams['date_list'];
            $bidDateList = explode(',',$bidDateStr);
            $bidDateBeg  = $bidDateList[0];
            $bidDateEnd = $bidDateList[count($bidDateList)-1];
        }else{
            $bidDateBeg  = 0;
            $bidDateEnd = 0;
        }

		$in['bid_date_beg'] = $bidDateBeg;
		$in['bid_date_end'] = $bidDateEnd;
		$in['ad_key'] = strval($inParams['ad_key']);
		$in['web_class'] = intval($inParams['web_class']);
		$in['start_city_code'] = intval($inParams['start_city_code']);
		$in['bid_price'] = intval($inParams['bid_price']);
		$in['ranking'] = intval($inParams['ranking']);
		$in['bid_ranking'] = intval($inParams['bid_ranking']);
		$in['bid_mark'] = intval($inParams['bid_mark']);
		$in['is_cancel'] = intval($inParams['is_cancel']);
		$in['add_uid'] = intval($inParams['account_id']);
		$in['add_time'] = date('Y-m-d H:i:s');
		$in['update_uid'] = intval($inParams['account_id']);
		$in['update_time'] = date('Y-m-d H:i:s');
		$in['del_flag'] = 0;
		$in['misc'] = intval($inParams['misc']);
		return $in;
	}
}

?>