<?php
class BossIao{
	const AGENCY_API_PATH = "restfulServer/vendor";
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
}