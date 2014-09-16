<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 14-1-18
 * @time 下午2:10
 * @description BidContentDao.php
 */ 
class BidContentDao extends DaoModule
{
    const CUR_TBL = 'bid_bid_content';

    /**
     * 查询竞价内容有效行内容
     *
     * @author chenjinlong 20140118
     * @param $bidId
     * @return array
     */
    public function queryBidContentRow($bidId)
    {
        $conditionString = ' del_flag=0 AND bid_id=' . $bidId;
        $row = $this->dbRO
            ->createCommand()
            ->select('id,account_id,content_type,content_id')
            ->from(self::CUR_TBL)
            ->where($conditionString)
            ->queryRow();
        if(!empty($row)){
            return $row;
        }else{
            return array();
        }
    }

}
 