<?php
/**
 * 对外系统接口 | Hagrid账户相关
 * * hagrid interfaces for outer system.
 * @author wanglongsheng@2013-01-17
 * @version 1.0
 * @func doRestGetVendorInfo
 * @func doRestGetBaVendorInfo
 * @func doRestPostVendorInfo
 * @func doRestPostVendor
 * @func doRestGetPrdTrackSta
 * @func doRestGetUrlTrackSta
 */
Yii::import('application.modules.manage.models.user.UserMod');
Yii::import('application.modules.manage.models.user.StaBiWebFlowMod');

class UserController extends restfulServer{
	
    private $user;
    private $_staBiWebFlowMod;
    
    function __construct() {
        $this->user = new UserMod();
        $this->_staBiWebFlowMod = new StaBiWebFlowMod;
    }
    
    
    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'uid'=> ,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /vendorinfo
     * @method GET
     * @param  string $url
     * @param  array $data {"accountId":"","uid":"","nickname":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询供应商补充信息
     */
    public function doRestGetVendorInfo($url, $data) {
        $accountId = $data['accountId'];
        $vendorInfo = $this->user->getVendorInfoByAccountId($accountId);
        $this->returnRest($vendorInfo);
    }
	
    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'uid'=> ,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /bavendorInfo
     * @method GET
     * @param  string $url
     * @param  array $data {"accountId":"","uid":"","nickname":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询供应商补充信息
     */
    public function doRestGetBaVendorInfo($url, $data) {
        $accountId = $data['accountId'];
        $vendorInfo = $this->user->getVendorInfoByAccountId($accountId);
        $this->returnRest($vendorInfo);
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('vendorId'=>,'accountId'=>,'cmpName'=>,'cmpPhone'=>,'contractor'=>,'contractorTel'=>,'contractorTel2'=>,'invoiceType'=>);
     * $response = $client->request(RESTFUL_POST, $url, $request_data);
     *
     * @mapping /create/vendorinfo
     * @method POST
     * @param  array $data {"vendorId":,"accountId":,"cmpName":,"cmpPhone":,"contractor":,"contractorTel":,"contractorTel2":,"invoiceType":}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 更新供应商补充信息
     */
    public function doRestPostVendorInfo($data) {
        $fmisRes = $this->user->updateVendorFmisInfo($data);
        if($fmisRes['success']) {
            $accountId = $data['accountId'];
            $verdorInfo = $this->user->getVendorInfoByAccountId($accountId);
            if($verdorInfo['id']==null) {
                $result = $this->user->insertVendorInfo($data);
            } else {
                $result = $this->user->updateVendorInfo($verdorInfo,$data);
            }
        } else {
            $this->returnRest(array(), false, 230099, $fmisRes['msg']);
            return;
        }
        $this->returnRest($accountId);
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('vendorId'=>,'accountId'=>,'cmpName'=>,'cmpPhone'=>,'contractor'=>,'contractorTel'=>,'contractorTel2'=>,'invoiceType'=>);
     * $response = $client->request(RESTFUL_POST, $url, $request_data);
     *
     * @mapping /create/vendor
     * @method POST
     * @param  array $data {"vendorId":,"accountId":,"cmpName":,"cmpPhone":,"contractor":,"contractorTel":,"contractorTel2":,"invoiceType":}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 财务更新供应商附加信息
     */
    public function doRestPostVendor($data) {
        if (!$data['vendorId']) {
            $this->returnRest(array(), false, 230107, '财务更新供应商附加信息错误：vendorId为空');
            return;
        }
        
        $account = $this->user->getVendorInfoByVendorId($data['vendorId']);
        
        if (!$account) {
            $this->returnRest(array(), false, 230107, '财务更新供应商附加信息错误：vendorId错误');
            return;
        }
        
        $vendor = $this->user->getVendorInfoByAccountId($account['accountId']);
        
        $response = $this->user->updateVendorInfo($vendor, $data);
        $this->returnRest($response['data'], $response['success'], $response['errorCode'], $response['msg']);
    }
    /**
     * $client = new RESTClient();
     * $requestData = array('productIds'=>'', 'staDate'=>'');
     * $response = $client->get($url, $request_data);
     *
     * @mapping /prdtracksta
     * @method GET
     * @param  string $urlVar
     * @param  array $reqData {"productIds":[{"productId":"","productType":""}],"staDate":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询产品跟踪BI统计数据
     */
    public function doRestGetPrdTrackSta($urlVar, $reqData)
    {
        if(!empty($reqData['productIds']) && is_array($reqData['productIds']) && !empty($reqData['staDate'])){
            $productIdArr = $reqData['productIds'];
            //暂不兼容门票产品的检索结果的解决办法
            $productIds = array();
            foreach($productIdArr as $item)
            {
                $productIds[] = $item['productId'];
            }
            //指定统计日期
            $staDate = $reqData['staDate'];
            $resultArr = $this->_staBiWebFlowMod->getBiProductTrackStaInfo($productIds, $staDate);
            $this->returnRest($resultArr);
        }else{
            $this->returnRest('', false, 230115, '输入参数缺漏，请完善输入参数');
        }
    }
    /**
     * $client = new RESTClient();
     * $requestData = array('urlSet'=>'', 'staDate'=>'');
     * $response = $client->get($url, $request_data);
     *
     * @mapping /urltracksta
     * @method GET
     * @param  string $urlVar
     * @param  array $reqData {"urlSet":[{'http://www...','http://www...'}],"staDate":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询URL跟踪BI统计数据
     */
    public function doRestGetUrlTrackSta($urlVar, $reqData)
    {
        if(!empty($reqData['urlSet']) && !empty($reqData['staDate'])){
            $urlSet = $reqData['urlSet'];
            $staDate = $reqData['staDate'];
            $resultArr = $this->_staBiWebFlowMod->getBiUrlTrackStaInfo($urlSet, $staDate);
            $this->returnRest($resultArr);
        }else{
            $this->returnRest('', false, 230115, '输入参数缺漏，请完善输入参数');
        }
    }

    /**
     *
     * 添加招客宝帐号
     *
     * @author chenjinlong 20140418
     * @param $urlVar
     * @param $params
     */
    public function doRestPostAgencyAdd($params)
    {
        $fabInput = array(
            'agency_id'=>  intval($params['agencyId']),
            'agency_name'=>$params['agencyName'],
            'full_name'=>$params['mainBusiness'],
            'op_saler_id'=>$params['uid']
        );
        $bbInput = array(
            'vendorId'=>intval($params['agencyId']),
            'agencyName'=>$params['agencyName'],
            'brandName'=>$params['brandName'],// 供应商品牌名
            'add_uid'=>$params['uid'],
            'add_time'=>date('Y-m-d H:i:s')
        );
        $addAgency = $this->user->addAccount($fabInput,$bbInput);

        if($addAgency) {
            $this->_returnData ['success'] = true;
            $this->_returnData ['msg'] = __CLASS__.'.'.__FUNCTION__;
            $this->_returnData['data'] = array();
        } else {
            $this->_returnData ['success'] = false;
            $this->_returnData ['msg'] = '系统异常';
            $this->_returnData ['data'] = array();
        }
        $this->renderJson();
    }
    
    
}


?>