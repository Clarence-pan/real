<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * @desc 书写相关tbl::bid_bid_content的业务逻辑处理操作
 * 
 * @author chenjinlong
 * @date 14-1-18
 * @time 下午1:40
 * @description BidBidContentMod.php
 */
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.dao.product.BidContentDao');
class BidContentMod
{
    private $_productDao;
    private $_bidContentDao;

    public function __construct()
    {
        $this->_productDao = new ProductDao();
        $this->_bidContentDao = new BidContentDao();
    }

    /**
     * 查询竞价内容记录
     *
     * @author chenjinlong 20140118
     * @param $bidId
     * @return array
     */
    public function queryBidContentRow($bidId)
    {
        if($bidId > 0){
            $bidContentInfo = $this->_bidContentDao->queryBidContentRow($bidId);
            return $bidContentInfo;
        }else{
            return array();
        }
    }

    /**
     * 保存竞价内容记录
     *
     * @author chenjinlong 20140118
     * @param $bidId
     * @param $bidContent
     * Contains Keys:
     * "accountId":,
     * "contentType":,
     * "contentId":,
     * @return integer
     */
    public function saveBidContent($bidId, $bidContent)
    {
        if(intval($bidId) > 0){
            $existRow = $this->_bidContentDao->queryBidContentRow($bidId);
            if(empty($existRow) ||
                ($existRow['content_id'] != $bidContent['content_id'] &&
                    $existRow['content_type'] != $bidContent['content_type'])){
                $updateParams = array(
                    'bidId' => intval($bidId),
                );
                $this->_productDao->updateBidBidContent($updateParams);
                $insertParams = array(
                    'accountId' => $bidContent['account_id'],
                    'bidId' => intval($bidId),
                    'productType' => $bidContent['content_type'],
                    'productId' => $bidContent['content_id'],
                );
                $newBidContentId = $this->_productDao->insertBidBidContent($insertParams);
                return $newBidContentId;
            }else{
                return $existRow['id'];
            }
        }else{
            return 0;
        }
    }

}
 