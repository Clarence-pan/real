<?php
class HagridIao {

    private $_restfulClient;

    function __construct()
    {
        $this->_restfulClient = new RESTClient;
    }

    /**
     * 添加退款申请单
     * @param array $param
     * @return array
     */
    public static function createRecharge($params) {
        $client = new RESTClient;
        
        $url = Yii::app()->params['HAGRID_HOST'].'hg/public/refund/create/refund';
        
        $response = $client->post($url, $params);
        return $response;
    }

    /**
     * 查询供应商补充信息
     * @param unknown_type $params
     * @return unknown
     */
    public static function getVendorInfo($params) {
        $client = new RESTClient;
        $url = Yii::app()->params['HAGRID_HOST'].'hg/public/user/baVendorInfo';
        $params = array(
            'accountId' => $params['accountId'],
        );
        $format = 'encrypt';
        $response = $client->get($url, $params);
        return $response;
    }

    /**
     * 查询BI产品跟踪结果数组
     *
     * @author chenjinlong 20130106
     * @param $productIdArr
     * @return array
     */
    public function queryBiProductTrackSta($productIdArr)
    {
        $url = Yii::app()->params['HAGRID_HOST'].'hg/public/user/PrdTrackSta';
        $requestParamsArr = array(
            'productIds' => $productIdArr,
            'staDate' => defined('STA_DATE') ? STA_DATE : date("Y-m-d", strtotime('-1 day')),
        );
        try{
            $responseArr = $this->_restfulClient->get($url, $requestParamsArr);

            if($responseArr['success'] && !empty($responseArr['data'])){
                return $responseArr['data'];
            }else{
                return array();
            }
        }catch (Exception $e){
            //记录接口监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'HG之BI跟踪产品统计数据查询失败',1,'chenjinlong', -1, 0, $url, json_encode($requestParamsArr), json_encode($e->getTraceAsString()));
            return array();
        }
    }

    /**
     * 查询BI跟踪结果之URL跟踪数组
     *
     * @author chenjinlong 20120106
     * @param $urlSet
     * @return array
     */
    public function queryBiUrlTrackSta($urlSet)
    {
        $url = Yii::app()->params['HAGRID_HOST'].'hg/public/user/UrlTrackSta';
        $requestParamsArr = array(
            'urlSet' => $urlSet,
            'staDate' => defined('STA_DATE') ? STA_DATE : date("Y-m-d", strtotime('-1 day')),
        );
        try{
            $responseArr = $this->_restfulClient->get($url, $requestParamsArr);

            if($responseArr['success'] && !empty($responseArr['data'])){
                return $responseArr['data'];
            }else{
                return array();
            }
        }catch (Exception $e){
            //记录接口监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'HG之BI跟踪URL统计数据查询失败',1,'chenjinlong', -1, 0, $url, json_encode($requestParamsArr), json_encode($e->getTraceAsString()));
            return array();
        }
    }

    /**
     * 更新供应商补充信息
     * @param unknown_type $params
     * @return unknown
     */
    public static function updateVendorInfo($params) {
    	$client = new RESTClient();
		$uri = Yii::app()->params['HAGRID_HOST'].'hg/public/user/create/vendorinfo';
    	$format = 'encrypt';
    	$res = $client->post($uri, $params);
    	return $res;
    }
}
