<?php
class FinanceIao {    
	const FAB_API_PATH = "restfulApi/AgencyCount";
    /**
     * 添加退款申请
     * @param array $param
     * @return array
     */
    public static function createRefund($param) {
        $client = new RESTClient;
        
        $url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyRefund/';
        $func = 'add';
        $params = array(
                'func' => $func,
                'params' => $param
        );
        try {
            $response = $client->post($url, $params);
            return $response;
        } catch (Exception $e) {
            return array(
                    'data' => array(),
                    'success' => false,
                    'errorCode' => 230199,
                    'msg' => '系统错误'
            );
        }
    }
    
    public static function updateVendorInfo($financeParams) {
    	$client = new RESTClient();
    	$url = Yii::app()->params['FMIS_HOST'].'restfulApi/AgencyInfo/';
    	$format = 'encrypt';
    	$params = array('func' => 'add',
    			'params' => $financeParams,);
     	try {
            $response = $client->post($url, $params);
            return $response;
        } catch (Exception $e) {
            return array(
                    'data' => array(),
                    'success' => false,
                    'errorCode' => 230199,
                    'msg' => '系统错误'
            );
        }
    }
    
/**
     * 获取收客宝财务信息接口
     * @param array $params
     * @return array
     */
    public static function getVerdorListFab($params){
        
        $client = new RESTClient();
        $url = Yii::app()->params['FMIS_HOST'].self::FAB_API_PATH.'/';
        $fabParams = trim(implode(',', $params));
        $func = 'query';
        $params = array(
            'func'=>$func,
            'params'=>array(
                'agency_id'=>$fabParams,
            ),
        );
        try {
            $response = $client->get($url, $params);
                                
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        
        if($response['success']) {
            return $response['data'];
        }
        
        return array();
    }
    
    /**
     * 获取收客宝财务信息接口
     * @param array $params
     * @return array
     * @author wenrui
     */
    public static function getVerdorList($params){
        $client = new RESTClient();
        $url = Yii::app()->params['FMIS_HOST'].self::FAB_API_PATH.'/';
        $fabParams = trim(implode(',', $params));
        $func = 'queryList';
        $params = array(
            'func'=>$func,
            'params'=>array(
                'agency_id'=>$fabParams,
            ),
        );
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }
    
/**
     * 新增收客宝财务帐号接口
     * @param array $params
     * @return array
     */
    public static function addAgencyAccount($params){
        $agencyId = $params['agency_id'];
        $client = new RESTClient();

        $url = Yii::app()->params['FMIS_HOST'].self::FAB_API_PATH.'/';
        $func = 'add';
        $params = array(
            'func'=>$func,
            'params'=>$params,
        );
        
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        
        if($response['success']) {
            return $agencyId;
        }
        
        return array();
    }
    
}
