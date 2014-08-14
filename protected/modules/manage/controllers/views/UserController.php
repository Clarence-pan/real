<?php
/** UI呈现接口 | Hagrid 账户相关
* Hagrid user interfaces for inner UI system.
* @author wanglongsheng@2013-01-04
* @version 1.0
* @func doRestGetAgencyLists
* @func doRestPostAgencyAdd
* @func doRestGetAccountInfo
* @func doRestPostDeleteAccount
* @func doRestGetBaVendorInfo
*/
Yii::import('application.modules.manage.models.user.UserMod');

class UserController extends restUIServer{
	private $_userMod;
    
    function __construct() {
    	$this->_userMod = new UserMod();
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('rp'=>,'start'=>,'limit'=>,'sortname'=>,'sortorder'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /agencylists
     * @method GET
     * @param string $url 
     * @param  array $data {"rp": 8,"start": 0,"limit": 8,"sortname": "","sortorder": "","uid": "4220","nickname": ""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询供应商帐号列表
     */
    public function doRestGetAgencyLists($url, $data) {
               
        $resultList = $this->_userMod->getAgencyLists($data);
        if($resultList) {
            $this->_returnData ['success'] = true;
            $this->_returnData ['msg'] = __CLASS__.'.'.__FUNCTION__;            
            $this->_returnData['data'] = $resultList;
        } else {
            $this->_returnData ['success'] = false;
            $this->_returnData ['msg'] = '系统异常';
            $this->_returnData ['data'] = array();
        }
        $this->renderJson();
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('uid'=>,'nickname'=>,'addedList'=>array(array('id'=>,'agencyName'=>,'mainBusiness'=>)));
     * $response = $client->request(RESTFUL_POST, $url, $request_data);
     *
     * @mapping /create/agencyadd
     * @method POST
     * @param  array $params {"uid":,"nickname":,"addedList":{{"id":,"agencyName":,"mainBusiness":}}}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 添加收客宝系统和财务账号
     */
    public function doRestPostAgencyAdd($params) {
        $result = array();

        foreach($params['addedList'] as $data){           
            $fabInput = array(
                'agency_id'=>  intval($data['id']),
                'agency_name'=>$data['agencyName'],
                'full_name'=>$data['mainBusiness'],
                'op_saler_id'=>$params['uid']
            );
            $bbInput = array(
                'vendorId'=>$data['id'],
                'agencyName'=>$data['agencyName'],
                'brandName'=>$data['brandName'],// 供应商品牌名
                'add_uid'=>$params['uid'],
                'add_time'=>date('Y-m-d H:i:s')
            );
            $addAgency = $this->_userMod->addAccount($fabInput,$bbInput);
            if($addAgency){
                $result[] = $data['id'];
            }
        }

        if(empty($result)) {
            $this->_returnData ['success'] = true;
            $this->_returnData ['msg'] = __CLASS__.'.'.__FUNCTION__;
            $this->_returnData['data'] = array('fail'=>$result);
        } else {
            $this->_returnData ['success'] = false;
            $this->_returnData ['msg'] = '系统异常';
            $this->_returnData ['data'] = array('fail'=>$result);
        }
        $this->renderJson();
        
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('rp'=>,'start'=>,'limit'=>,'sortname'=>,'sortorder'=>,'accountName'=>,'agencyId'=>,'certificateFlag'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /accountinfo
     * @method GET
     * @param string $url 
     * @param  array $data {"rp":10,"start":0,"limit":10,"sortname":"","sortorder":"","accountName":"","agencyId":"","certificateFlag": "-1","uid":"4220","nickname":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 收客宝帐号列表
     */
     public function doRestGetAccountInfo($url, $data) { 
        $resultList = $this->_userMod->getVerdorLists($data);

        if($resultList) {
            $this->_returnData ['success'] = true;
            $this->_returnData ['msg'] = __CLASS__.'.'.__FUNCTION__;
            $this->_returnData['data'] = $resultList;
        } else {
            $this->_returnData ['success'] = false;
            $this->_returnData ['msg'] = '系统异常';
            $this->_returnData ['data'] = array();
        }
        $this->renderJson();
    }
    /**
     * $client = new RESTClient();
     * $requestData = array('agencyId'=>,'uid'=>,'nickname'=>);
     * $response = $client->request(RESTFUL_POST, $url, $request_data);
     *
     * @mapping /create/deleteaccount
     * @method POST
     * @param  array $params {"agencyId":,"uid":,"nickname":}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 删除供应商之收客宝帐号
     */
    public function doRestPostDeleteAccount($requestData)
    {
        if(!empty($requestData['agencyId']) && !empty($requestData['uid'])){
            $apiParam = array(
                'agencyId' => $requestData['agencyId'],
                'uid' => $requestData['uid'],
                'nickname' => $requestData['nickname'],
            );
            $delResult = $this->_userMod->deleteAgencyBuckbeekAccount($apiParam);
            if($delResult['flag']){
                $this->returnRest(array());
            }else{
                $this->returnRest(array(), false, 230002, $delResult['msg']);
            }
        }else{
            $this->returnRest(array(), false, 230115, '输入参数缺失，供应商编号与操作人ID属于必填项');
        }
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'uid'=> ,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /vendorInfo
     * @method GET
     * @param string $url 
     * @param  array $data {"accountId":"","uid":"","nickname":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询供应商补充信息
     */
    public function doRestGetBaVendorInfo($url, $data) {
        $accountId = $data['accountId'];
        $vendorInfo = $this->_userMod->getVendorInfoByAccountId($accountId);
        $this->returnRest($vendorInfo);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'uid'=> ,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /admessage
     * @method GET
     * @param string $url
     * @param  array $data {"accountId":"","uid":"","nickname":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 查询供应商广告信息
     */
    public function doRestGetAdMessage($url, $data) {
        $adMessage = $this->_userMod->getAdMessage($data);
        $this->returnRest($adMessage);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'uid'=> ,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /create/admessage
     * @method POST
     * @param string $url
     * @param  array $data {"accountId":"","uid":"","nickname":""}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 更新供应商广告信息
     */
    public function doRestPostAdMessage($data) {
        $adMessage = $this->_userMod->updateAdMessage($data);
        if(!$adMessage['success']){
            $this->returnRest(array(), false, 230015, $adMessage['msg']);
        }else{
            $this->returnRest($adMessage['data']);
        }
    }
}
?>