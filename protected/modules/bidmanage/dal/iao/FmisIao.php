<?php

class FmisIao {    
    /**
     * 添加充值申请单
     * @param array $param
     * @return array
     */
    public static function createRecharge($param) {
        $client = new RESTClient;
        
        $url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyRecharge/';
        $func = 'add';
        $params = array(
                'func' => $func,
                'params' => $param
        );
        
        $response = $client->get($url, $params);
        return $response;
    }

    /**
     * 查询供应商的充值历史记录
     *
     * @author chenjinlong 20121217
     * @param $vendorId
     * @param $queryType, 1=返回列表|2=返回充值总额
     * @param string $beginDate
     * @param string $endDate
     * @return array
     */
    public static function queryFmisChargeHistoryArr($vendorId, $queryType, $beginDate='0000-00-00', $endDate='0000-00-00')
    {
        $client = new RESTClient;
        $url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyRecharge/';
        $params = array(
            'func' => 'query',
            'params' => array(
                'agency_id' => $vendorId,
                'query_type' => $queryType,
            ),
        );
        if($beginDate != '0000-00-00' && !empty($beginDate)){
            $params['params']['begin_date'] = $beginDate;
        }
        if($endDate != '0000-00-00' && !empty($endDate)){
            $params['params']['end_date'] = $endDate;
        }
        try{
            $respArr = $client->get($url, $params);
            if($respArr['success']){
                return $respArr['data'];
            }else{
                return array();
            }
        }catch (Exception $e){
            CommonSysLogMod::log(__FUNCTION__, '财务查询供应商充值历史记录-失败', 1, 'chenjinlong', 0, 0, json_encode($url), json_encode($params), json_encode($e->getTraceAsString()));
            return array();
        }
    }
    
    /**
	 * 财务账户增加牛币和赠币的区分后，需要对财务表老数据进行更新操作
	 * 
	 * add by wenrui 2014-04-21
	 */
    public static function update($account){
    	$client = new RESTClient;
    	$url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyCount/';
    	$params = array(
            'func' => 'update',
            'params' => array(
                'agency_id' => $account['agency_id'],
                'balance_niu' => $account['balance_niu'],
                'balance_coupon' => $account['balance_coupon'],
            ),
        );
        try{
            $respArr = $client->get($url, $params);
            if($respArr['success']){
            	return $respArr['data'];
            }else{
            	return array('flag'=>false,'agencyId'=>$account['agency_id'],'msg'=>$respArr['msg']);
            }
        }catch (Exception $e){
            return array('flag'=>false,'agencyId'=>$account['agency_id'],'msg'=>$account['agency_id'].'失败,调用财务接口异常');
        }
    }

}
