<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/17/12
 * Time: 8:18 PM
 * Description: IaoFmisMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.FmisIao');

class IaoFmisMod
{
    /**
     * 查询某个供应商的历史充值记录[通用版]
     *
     * @author chenjinlong 20121217
     * @param $inParams
     * @return array
     */
    public function getFmisHistoryList($inParams)
    {
        $vendorId = $inParams['vendor_id'];
        $queryType = $inParams['query_type'];
        $beginDate = $inParams['begin_date'];
        $endDate = $inParams['end_date'];
        if(!empty($vendorId) && !empty($queryType)){
            $fmisHistoryList = FmisIao::queryFmisChargeHistoryArr($vendorId, $queryType, $beginDate, $endDate);
            if(!empty($fmisHistoryList) && is_array($fmisHistoryList)){
                return $fmisHistoryList;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    /**
     * 查询某个供应商的历史充值总额
     *
     * @author chenjinlong 20121217
     * @param $vendorId
     * @return int
     */
    public function getTotalRechargeValue($vendorId)
    {
        $paramArr = array(
            'vendor_id' => $vendorId,
            'query_type' => 2,
        );
        $fmisHistoryArr = $this->getFmisHistoryList($paramArr);
        if(!empty($fmisHistoryArr['total_charge_amt'])){
            return $fmisHistoryArr['total_charge_amt'];
        }else{
            return 0;
        }
    }

}
