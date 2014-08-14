<?php
/**
 * AgencyAccountIao.php
 * @copyright
 * @abstract  收客宝后台管理供应商列表接口
 * @author xiongyun
 * @version
 */
class AgencyAccountIao {
    const AGENCY_API_PATH = "restfulServer/vendor";
    const BB_API_PATH = "bb/public/manage";
    const FAB_API_PATH = "restfulApi/AgencyCount";
    /**
     * 通过供应商获得收客宝ID接口
     * @param array $params
     * @return array
     */
    public static function getIdByAgency($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'] . self::BB_API_PATH . '/accountids';

        try {
            $response = $client->get($url, $params);
            if ($response['success']) {
                return $response['data'];
            }else{
                return array();
            }
        } catch (Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
    }
    /**
     * 获取供应商列表接口
     * @param array $params
     * @return array
     */
    public static function getAgencyAccountList($params){
        $client = new RESTClient();
        $url = Yii::app()->params['CRM_HOST'].self::AGENCY_API_PATH.'/condlists';
        
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
    /**
     * 新增收客宝帐号接口
     * @param array $params
     * @return array
     */
    public static function addVendorAccount($params){

        $agencyId = $params['verdor_id'];
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].self::BB_API_PATH.'/update-addaccount';
      
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
     * 获取收客宝信息接口
     * @param array $params
     * @return array
     */
    public static function getVerdorList($params){

        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].self::BB_API_PATH.'/vendorlist';

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
}
