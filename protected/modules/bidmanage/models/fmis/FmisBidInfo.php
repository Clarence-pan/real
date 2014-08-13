<?php

Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.dal.dao.fmis.FmisManageDao'); //竞价财务信息 - getHasAssignBalance
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');// - readUser


class FmisBidInfo {

    private $manageDao;
    private $fmisManageDao;

    function __construct() {
        $this->manageDao = new UserManageDao();
        $this->fmisManageDao = new FmisManageDao();
    }

    /**
     * [bid-fmis]获取账户竞价财务信息
     * @param unknown_type $accountId
     */
    public function getBidFinanceInfo($accountId) {
        $params['id'] = $accountId;
        $accountInfo = $this->manageDao->readUser($params);
        $agencyId = $accountInfo['vendorId'];
        $financeIaoInfo = FinanceIao::getAccountAvailableBalance($agencyId);
        $controlMoney = $financeIaoInfo['controlMoney'];
        $currentMoney = $financeIaoInfo['currentMoney'];
        $hasAssignMoney = $currentMoney - $controlMoney;
        
        $couponControlMoney = $financeIaoInfo['couponControlMoney'];
        $couponCurrentMoney = $financeIaoInfo['couponCurrentMoney'];
        $couponHasAssignMoney = $couponCurrentMoney - $couponControlMoney;
        $startDate = date("Y-m-d", strtotime("-1Day"));
        $hasAssignBalance = $this->fmisManageDao->getHasAssignBalance($accountId, $startDate);
        $financeInfo = array(
            'hasAssignBalance' => floor($hasAssignBalance),
            'controlMoney' => floor($controlMoney),
            'currentMoney' => floor($currentMoney),
            'hasAssignMoney' => floor($hasAssignMoney),
            'couponControlMoney' => floor($couponControlMoney),
            'couponCurrentMoney' => floor($couponCurrentMoney),
            'couponHasAssignMoney' => floor($couponHasAssignMoney)
            );
        return $financeInfo;
    }

    /**
     * 批量查询多个招客宝账户财务信息
     *
     * @author chenjinlong 20131113
     * @param $accountIds
     * @return array
     */
    public function batchGetAccountFmisInfo($accountIds)
    {
        $result = array();
        if(!empty($accountIds)){
            $accountIds = array_unique($accountIds);
            foreach($accountIds as $id)
            {
                $params['id'] = $id;
                $accountInfo = $this->manageDao->readUser($params);
                $agencyId = $accountInfo['vendorId'];
                $financeIaoInfo = FinanceIao::getAccountAvailableBalance($agencyId);
                $controlMoney = $financeIaoInfo['controlMoney'];
                $currentMoney = $financeIaoInfo['currentMoney'];
                $hasAssignMoney = $currentMoney - $controlMoney;
                $couponControlMoney = $financeIaoInfo['couponControlMoney'];
                $couponCurrentMoney = $financeIaoInfo['couponCurrentMoney'];
                $couponHasAssignMoney = $couponCurrentMoney - $couponControlMoney;
                $result[$id] = array(
                    'controlMoney' => $controlMoney,
                    'currentMoney' => $currentMoney,
                    'hasAssignMoney' => $hasAssignMoney,
                    'couponControlMoney' => $couponControlMoney,
                    'couponCurrentMoney' => $couponCurrentMoney,
                    'couponHasAssignMoney' => $couponHasAssignMoney,
                );
            }
        }
        return $result;
    }

} 