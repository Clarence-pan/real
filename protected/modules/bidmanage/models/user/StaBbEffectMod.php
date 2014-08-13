<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 5:05 PM
 * Description: StaBbEffectMod.php
 */
Yii::import('application.modules.bidmanage.dal.dao.user.StaBbEffectDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');

class StaBbEffectMod
{
    private $_staBbEffectDao;
    private $_userManageDao;

    function __construct()
    {
        $this->_staBbEffectDao = new StaBbEffectDao;
        $this->_userManageDao = new UserManageDao();
    }

    /**
     * 查询指定时间段的BI推广跟踪统计数据
     *
     * @author chenjinlong 20121225
     * @param $inParams  $accId
     * @return array
     */
    public function getStaEffectArrByDate($inParams, $accId)
    {       
    	if($inParams['vendorName']) {
    		$accountId = $this->getAccountIdStrByVendorName($inParams['vendorName']);
    		if($inParams['accountId'] && $accountId) {
    			$accountId .= ',' . $inParams['accountId'];
    		}
    		$inParams['accountId'] = $accountId;
    	}	
        $row = $this->_staBbEffectDao->queryBbEffectArrByDate($inParams);
        // $result = $this->_staBbEffectDao->queryConsumption($inParams);
         // 获得agency_id
        $agency_id = $this->_userManageDao->getVendorInfo($accId);
        // 调用财务接口，获取供应商累计消费金额
        $agCon = FinanceIao::getAgencyExp($agency_id['vendorId']);
        // 预初始化过期金额和扣费金额
        $agGq = 0;
        $agCf = 0;
        $agGqCoupon = 0;
        $agCfCoupon = 0;
        // 分类初始化牛币已消费金额和已冻结金额
        foreach($agCon['data']['niu'] as $agObj) {
        	if(1 == $agObj['remark']) {
        		// 过期金额
        		$agGq = $agObj['consumption'];
        	} else if(0 == $agObj['remark']) {
        		// 扣费金额
        		$agCf = $agObj['consumption'];
        	}
        }
        // 分类初始化赠币已消费金额和已冻结金额
        foreach($agCon['data']['coupon'] as $agObj) {
        	if(1 == $agObj['remark']) {
        		// 过期金额
        		$agGqCoupon = $agObj['couponConsumption'];
        	} else if(0 == $agObj['remark']) {
        		// 扣费金额
        		$agCfCoupon = $agObj['couponConsumption'];
        	}
        }
    	if(!empty($row) && is_array($row)){
            $resArr = array(
                'reveal' => !empty($row['reveal'])?$row['reveal']:0,
                'ipView' => !empty($row['ip_view'])?$row['ip_view']:0,
                'clickNum' => !empty($row['click_num'])?$row['click_num']:0,
                'orderConversion' => !empty($row['order_conversion'])?$row['order_conversion']:0,
                //网页转化率（点击/展现）
                'webPercentConversion' => !empty($row['reveal'])? number_format($row['click_num']/$row['reveal'],2):0,
                //订单转化率（转化（订单）/IP访问）
                'orderPercentConversion' => !empty($row['ip_view'])? number_format($row['order_conversion']/$row['ip_view'],2):0,
                // 'consumption' => !empty($result['consumption'])?intval($result['consumption']):0,
                // 设置供应商累计牛币消费金额
                'consumption' => $agCf,
                // 设置供应商累计牛币过期金额
                'overdate' => $agGq,
                // 设置供应商累计赠币消费金额
                'couponConsumption' => $agCfCoupon,
                // 设置供应商累计赠币过期金额
                'couponOverdate' => $agGqCoupon,
                //平均点击（消费/点击）
                'averageClick' => !empty($row['click_num'])?  number_format($row['consumption']/$row['click_num'],2):0,
            );
            return $resArr;
        }else{
            return array(
                'reveal' => 0,
                'ipView' => 0,
                'clickNum' => 0,
                'orderConversion' => 0,
                'webPercentConversion' => 0,
                'orderPercentConversion' => 0,
                'consumption' => 0,
                'overdate' => 0,
                'couponConsumption' => 0,
                'couponOverdate' => 0,
                'averageClick' => 0,
            );
        }
    }
    
    public function getAccountIdStrByVendorName($vendorName){
    	$accountId = $this->_userManageDao->getAccountIdArrByVendorName($vendorName);
    	if(!empty($accountId)) {
    		$accountIdArr = array();
    		foreach ($accountId as $value) {
    			$accountIdArr[] = $value['id'];
    		}
    		$accountIdStr = trim(implode(',', $accountIdArr));
    	}
    	return $accountIdStr;
    }

    /**
     * 新增收客宝推广效果表记录
     *
     * @author chenjinlong 20121224
     * @param $inParamArr
     * @return bool
     */
    public function insertEffectedRecord($inParamArr)
    {
        if(!empty($inParamArr) && is_array($inParamArr)){
            $execResult = $this->_staBbEffectDao->insertBbEffectedRecord($inParamArr);
            if($execResult){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 查询某帐户-某产品-某日的统计结果
     *
     * @author chenjinlong 20121224
     * @param $params
     * @return array
     */
    public function getSpecificBbEffectRecordArr($params)
    {
        if(!empty($params)){
            $resultArr = $this->_staBbEffectDao->getSpecificBbEffectRecord($params);
            if(!empty($resultArr) && is_array($resultArr)){
                return $resultArr;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    /**
     * 更新某帐户-某产品-某日的统计结果
     *
     * @author chenjinlong 20121225
     * @param $tgtArr
     * @param $conditionArr
     * @return bool
     */
    public function updateSpecificBbEffectRecordArr($tgtArr, $conditionArr)
    {
        if(!empty($conditionArr) && !empty($tgtArr)){
            $udtResult = $this->_staBbEffectDao->updateSpecificBbEffectRecord($tgtArr, $conditionArr);
            if($udtResult){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }   

    /**
     * 逻辑清除影响二次统计的数据(约定：参数都为必传)
     *
     * @author chenjinlong 20130316
     * @param $params
     * @return boolean
     */
    public function removeOlderEffectedRows($params)
    {
        $staDate = trim($params['sta_date']);
        $misc = strval($params['misc']);
        if($staDate && $misc){
            return $this->_staBbEffectDao->deleteOlderEffectedRows($params);
        }else{
            return false;
        }
    }

}
