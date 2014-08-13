<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/10/12
 * Time: 11:49 AM
 * Description: IaoFinanceMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
class IaoFinanceMod
{
    private $_financeIao;

    function __construct()
    {
        $this->_financeIao = new FinanceIao;
    }

    /**
     * 推广成功之财务扣费
     *
     * @author chenjinlong 20121210
     * @param $reqParams
     * @return int
     */
    public function bidSuccessFinanceDeduct($reqParams)
    {
        $fmisId = $this->_financeIao->bidSuccessFinance($reqParams, false);
        if($fmisId)
            return $fmisId;
        else
            return 0;
    }

    /**
     * 推广成功之财务扣费成功后的退款操作
     *
     * @author chenjinlong 20121210
     * @param $reqParams
     * @return int
     */
    public function bidSuccessFinanceCancelDeduct($reqParams)
    {
        $fmisId = $this->_financeIao->bidSuccessFinance($reqParams, true);
        if($fmisId)
            return $fmisId;
        else
            return 0;
    }

    /**
     * 推广失败后的退款操作
     *
     * @author chenjinlong 20121210
     * @param $reqParams
     * @return int
     */
    public function bidFailFinanceRefund($reqParams)
    {
        $fmisId = $this->_financeIao->bidFailFinance($reqParams);
        if($fmisId)
            return $fmisId;
        else
            return 0;
    }

}
