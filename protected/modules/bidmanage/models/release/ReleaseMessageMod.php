<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/18/12
 * Time: 8:03 PM
 * Description: ReleaseMessageMod.php
 */
Yii::import('application.modules.bidmanage.models.product.ReleaseProductMod');
Yii::import('application.modules.bidmanage.models.release.ReleaseMessageTpl');
Yii::import('application.modules.bidmanage.models.user.BidMessage');

class ReleaseMessageMod
{
    private $_relProductMod;

    private $_bidMessageMod;

    private $_dataBeforeRel = array();

    function __construct()
    {
        $this->_relProductMod = new ReleaseProductMod;
        $this->_bidMessageMod = new BidMessage;
    }

    public function getReleaseMsgDataBeforeRelease()
    {
        $this->_dataBeforeRel = $this->_relProductMod->getCurDateBidProductArray();
    }

    /**
     * 发布收客宝产品推广相关的模版消息
     *
     * @author chenjinlong 20121219
     */
    public function publishReleaseMessage()
    {
        $relMsgArr = $this->integrateCurDateBidProductList();
        if(!empty($relMsgArr) && is_array($relMsgArr)){
            foreach($relMsgArr as $eachAccArr)
            {
                foreach($eachAccArr as $subStatisticItem)
                {
                    if($subStatisticItem['product_count'] > 0 && $subStatisticItem['release_unit_count'] > 0 && $subStatisticItem['amount'] > 0 && !empty($subStatisticItem['product_id_arr'])){
                        $msgString = ReleaseMessageTpl::buildReleaseMessage(
                            $subStatisticItem['msg_type'],
                            $subStatisticItem['product_count'],
                            $subStatisticItem['release_unit_count'],
                            $subStatisticItem['amount']);

                        //执行插入消息操作
                        $insertArr = array(
                            'account_id' => $subStatisticItem['account_id'],
                            //备注：此处的类型type归属于表bb_message
                            'type' => $subStatisticItem['msg_type'],
                            'content' => $msgString,
                            'amount' => $subStatisticItem['amount'],
                            'add_uid' => 2820,
                            'misc' => '收客宝产品推广变动消息',
                        );
                        $this->_bidMessageMod->insertIntoMessageCenter($insertArr);
                    }
                }
            }
        }
    }

    /**
     * 整合数据格式[适用于消息中心所需要的推广相关的消息]
     *
     * @author chenjinlong 20121218
     * @param $srcArr
     * @return array
     */
    public function integrateCurDateBidProductList()
    {
        $tmpArr1 = $this->prepareCurDateBidProductArr();

        $finalArr = array();
        foreach($tmpArr1 as $accId => $elemArr)
        {
            $finalArr[$accId] = self::doStatistic($accId, $elemArr);
        }

        return $finalArr;
    }

    public function diffCurDateBidProductArray()
    {
        $bidProductArr = $this->_relProductMod->getCurDateBidProductArray();
        $dataBeforeRel = self::formatRowsUsingKeyIndex($this->_dataBeforeRel, 'id');
        $dataAfterRel = self::formatRowsUsingKeyIndex($bidProductArr, 'id');
        if(!empty($dataBeforeRel) && !empty($dataAfterRel)){
            $outDataRel = array();
            foreach($dataAfterRel as $elem)
            {
                if($elem['bid_mark'] == $dataBeforeRel[$elem['id']]['bid_mark'] && $elem['fmis_mark'] == $dataBeforeRel[$elem['id']]['fmis_mark']){
                    continue;
                }else{
                    $outDataRel[] = $elem;
                }
            }
            return $outDataRel;
        }else{
            return array();
        }
    }

    /**
     * 准备待处理的竞价列表记录条目[第一维度整理]
     *
     * @author chenjinlong 20121219
     * @return array
     */
    public function prepareCurDateBidProductArr()
    {
        $bidProductArr = $this->diffCurDateBidProductArray();
        $tmpArr1 = array();
        if(!empty($bidProductArr) && is_array($bidProductArr)){
            //第一维度整理
            foreach($bidProductArr as $prd)
            {
                $tmpArr1[$prd['account_id']][] = array(
                    'id' => $prd['id'],
                    'account_id' => $prd['account_id'],
                    'product_id' => $prd['product_id'],
                    'bid_price' => $prd['bid_price'],
                    'bid_mark' => $prd['bid_mark'],
                    'fmis_mark' => $prd['fmis_mark'],
                );
            }
        }
        return $tmpArr1;
    }

    /**
     * 面向每一个收客宝帐号旗下的所有竞价条目,析取竞价结果统计
     *
     * @author chenjinlong 20121219
     * @param $accountId
     * @param $subAccountBidRows
     * @return array
     */
    public static function doStatistic($accountId, $subAccountBidRows)
    {
        $tplOutputArr = self::exportStatisticTplArr($accountId);
        if(!empty($subAccountBidRows) && is_array($subAccountBidRows)){
            foreach($subAccountBidRows as $bidRow)
            {
                //产品未审核
                if($bidRow['bid_mark'] == -1){
                    $tplOutputArr[2]['product_count'] += in_array($bidRow['product_id'], $tplOutputArr[101]['product_id_arr'])?0:1;
                    $tplOutputArr[2]['product_id_arr'][] = $bidRow['product_id'];
                    $tplOutputArr[2]['release_unit_count'] += 1;
                    $tplOutputArr[2]['amount'] += $bidRow['bid_price'];
                }
                //有效排名之外
                if($bidRow['bid_mark'] == -2){
                    $tplOutputArr[3]['product_count'] += in_array($bidRow['product_id'], $tplOutputArr[101]['product_id_arr'])?0:1;
                    $tplOutputArr[3]['product_id_arr'][] = $bidRow['product_id'];
                    $tplOutputArr[3]['release_unit_count'] += 1;
                    $tplOutputArr[3]['amount'] += $bidRow['bid_price'];
                }
                //推广成功+扣费成功
                if($bidRow['bid_mark'] == 1 && $bidRow['fmis_mark'] == 1){
                    //扣款成功
                    $tplOutputArr[101]['product_count'] += in_array($bidRow['product_id'], $tplOutputArr[101]['product_id_arr'])?0:1;
                    $tplOutputArr[101]['product_id_arr'][] = $bidRow['product_id'];
                    $tplOutputArr[101]['release_unit_count'] += 1;
                    $tplOutputArr[101]['amount'] += $bidRow['bid_price'];
                }
                //推广费用退回[解冻]
                /*if(in_array($bidRow['bid_mark'], array(-1, -2, -100)) && $bidRow['fmis_mark'] == -1){
                    //退款成功
                    $tplOutputArr[103]['product_count'] += in_array($bidRow['product_id'], $tplOutputArr[101]['product_id_arr'])?0:1;
                    $tplOutputArr[103]['product_id_arr'][] = $bidRow['product_id'];
                    $tplOutputArr[103]['release_unit_count'] += 1;
                    $tplOutputArr[103]['amount'] += $bidRow['bid_price'];
                }*/
            }
        }
        return $tplOutputArr;
    }

    /**
     * 构建统计模型数组
     *
     * @author chenjinlong 20121219
     * @param $accountId
     * @return array
     */
    public static function exportStatisticTplArr($accountId)
    {
        $tplArr = array();
        //产品未审核
        $tplArr[2] = array(
            'account_id' => $accountId,
            'product_id_arr' => array(),
            'product_count' => 0,
            'release_unit_count' => 0,
            'amount' => 0,
            'msg_type' => 2,
        );
        //排名之外
        $tplArr[3] = array(
            'account_id' => $accountId,
            'product_id_arr' => array(),
            'product_count' => 0,
            'release_unit_count' => 0,
            'amount' => 0,
            'msg_type' => 3,
        );
        //扣款成功
        $tplArr[101] = array(
            'account_id' => $accountId,
            'product_id_arr' => array(),
            'product_count' => 0,
            'release_unit_count' => 0,
            'amount' => 0,
            'msg_type' => 101,
        );
        //退款成功
        /*$tplArr[103] = array(
            'account_id' => $accountId,
            'product_id_arr' => array(),
            'product_count' => 0,
            'release_unit_count' => 0,
            'amount' => 0,
            'msg_type' => 103,
        );*/
        return $tplArr;
    }

    private static function formatRowsUsingKeyIndex($rows, $indexKey)
    {
        if(!empty($rows) && is_array($rows)){
            $outArr = array();
            $indexKey = strval($indexKey);
            foreach($rows as $key => $val)
            {
                $index = $indexKey?$indexKey:$key;
                $outArr[$val[$index]] = $val;
            }
            return $outArr;
        }else{
            return array();
        }
    }


}
