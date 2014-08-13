<?php
/**
 * 对外系统接口 | 收客宝帐号相关
 * * Buckbeek account interfaces for outer system.
 * @author chenjinlong@2013-01-04
 * @version 1.1
 * @func doRestPostMessage
 * @func doRestPostAddAccount
 * @func doRestGetAccountIds
 * @func doRestGetVendorList
 * @func doRestGetAccountInfo
 * @func doRestPostAuditVendorInfo
 * @func doRestPostDeleteAccount
 * @func doRestGetTrackUrlSet
 * @func doRestGetCheckAccountAuth
 * @func doRestGetSpreadOverview
 * @func doRestPostUpdateSta
 */
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.models.user.BidMessage');
Yii::import('application.modules.bidmanage.models.user.StaIntegrateMod');
Yii::import('application.modules.bidmanage.models.user.StaBbEffectMod');
Yii::import('application.modules.bidmanage.models.fmis.StatementMod');

class UserController extends restSysServer {

    private $_bidMessageMod;
    private $_manageMod;
    private $_staIntegrateMod;
    private $_statement;
    private $_staBbEffectMod;

    function __construct() {
        $this->_bidMessageMod = new BidMessage();
        $this->_manageMod = new UserManageMod();
        $this->_staIntegrateMod = new StaIntegrateMod;
        $this->_statement = new StatementMod();
        $this->_staBbEffectMod = new StaBbEffectMod();
    }

    /*************************************原ManagesysController.php接口*******************************************/

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->post($url, $requestData);
     *
     * @mapping /addaccount
     * @method POST
     * @param  array $params {"vendorId": ,"agencyName": ,"add_uid": ,"add_time": ,}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】供应商账号添加接口
     */
    public function doRestPostAddAccount($params){
        /**
         * 招客宝改版-默认添加后直接“已认证”，跳过1.0版的财务审核流程
         * mdf by chenjinlong 20131115
         */
        $params['certificate_flag'] = 1;

        $acount = $this->_manageMod->addVerdorAccount($params);

        if($acount) {
            $this->returnRest($acount);
        }else {
            $this->returnRest(array(), false, 230021, '添加收客宝账号失败');
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /accountids
     * @method GET
     * @param string $url 
     * @param  array $params {1,2,3,...}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】通过供应商ID查收客宝ID
     */
    public function doRestGetAccountIds($url,$ids){
        $ids = trim(implode(',', $ids));
        $vendorLists = $this->_manageMod->getBBIds($ids);
        $this->returnRest($vendorLists);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /vendorlist
     * @method GET
     * @param string $url 
     * @param  array $data {"agencyId":"","accountName":"","certificateFlag":-1,"start":1,"limit":10} 
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】收客宝列表接口
     */
    public function doRestGetVendorList($url, $data) {
         if (!isset($data['limit'])) {
            $this->returnRest(array(), false, 230030, '查询供应商帐号列表入参错误：limit为空');
            return;
        }
        if (!isset($data['certificateFlag']) || $data['certificateFlag'] == '') {
            $data['certificateFlag'] = -1;
        }
        $input = array(
            'vendor_id' => $data['agencyId'],
            'account_name' => $data['accountName'],
            'certificate_flag' => $data['certificateFlag'],
            'limit' => $data['limit'],
            'start' => $data['start'],
        );

        $vendorLists = $this->_manageMod->getVendorList($input);
        if(empty($vendorLists)){
            $vendorLists = array('count'=>0,'rows'=>array());
        }
        $this->returnRest($vendorLists);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /accountinfo
     * @method GET
     * @param string $url 
     * @param  array $data {"vendorId": ,"jsessionId": ,"loginIp": ,}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】查询供应商的收客宝帐号信息
     */
    public function doRestGetAccountInfo($url, $data) {
        $params = array(
            'vendorId' => $data['vendorId'],
            'lastLoginIp' => $data['loginIp']
        );
        $account = $this->_manageMod->getAccountInfoByAgentId($params['vendorId']);
        if (empty($account)) {
            $this->returnRest(array(), false, 230022, '该供应商没有收客宝帐号');
        } else {

            // 计算 至上次登录到现在的 消息中心--竞价排名变更
            $params['id'] = $account['id'];
            $this->_bidMessageMod->insertRankMessage($params);
            // 更新账户上次登录时间和上次登录IP
            $loginParams = array(
                'last_login_ip' => $params['lastLoginIp'],
                'last_login_time' => date('Y-m-d H:i:s')
            );

            $condParams['id'] = $account['id'];
            $this->_manageMod->update($loginParams, $condParams);
            
            $this->returnRest(array('accountId' => $account['id']));
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->post($url, $requestData);
     *
     * @mapping /auditvendorinfo
     * @method POST
     * @param  array $data {"accountId": ,"certFlag": ,}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】财务审核供应商补充信息
     */
    public function doRestPostAuditVendorInfo($data) {
        if (!$data['accountId']) {
            $this->returnRest(array(), false, 230023, '获取供应商ID入参错误：accountId错误');
            return;
        }
        $vendorId = $data['accountId'];
        $vendorInfo = $this->_manageMod->getAccountInfoByAgentId($vendorId);
        if(empty($vendorInfo['id'])) {
            $this->returnRest(array(), false, 230022, '该供应商不存在收客宝帐号');
            return;
        }
        $data['accountId'] = $vendorInfo['id'];
        $result = $this->_manageMod->auditVendorInfo($data);
        $this->returnRest($result);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->post($url, $requestData);
     *
     * @mapping /deleteaccount
     * @method POST
     * @param  array $requestData {"agencyId": ,"uid": ,"nickname": ,}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】删除供应商之收客宝账户
     */
    public function doRestPostDeleteAccount($requestData)
    {
        $vendorId = $requestData['agencyId'];
        $userId = $requestData['uid'];
        $nickname = $requestData['nickname'];
        if(!empty($vendorId) && $vendorId > 0){
            $delResultArr = $this->_manageMod->deleteBuckbeekAccount($vendorId);
            if($delResultArr['flag']){
                $this->returnRest(array());
            }else{
                $this->returnRest(array(), false, 230001, $delResultArr['msg']);
            }
        }else{
            $this->returnRest(array(), false, 230024, '输入参数不符合');
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /trackurlset
     * @method GET
     * @param string $urlVar 
     * @param  array $reqData {"uid":2820}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】查询所有当天BI发布数据相关的跟踪URL
     */
    public function doRestGetTrackUrlSet($urlVar, $reqData)
    {
        if(!empty($reqData['uid'])){
            $urlSet = $this->_staIntegrateMod->getStatisticUrlSet();
            $this->returnRest(array('urlSet'=>$urlSet));
        }else{
            $this->returnRest('', false, 230024, '请完善输入参数');
        }
    }

    /**
     * $client = new RESTClient();
     * $params = array();
     * $format = 'encrypt';
     * $res = $client->post($url, $params, $format);
     * @mapping /updatesta
     * @method POST
     * @author chenjinlong 20130314
     * @param  array $requestData {"accountId":2820,"productType":,"productId":,"staDate":,"consumption":,"reveal":,"ipView":,"clickNum":,"orderConversion":}
     * @return array {"success":true,"msg":"成功","errorCode":230000,"data":}
     * @desc  更新bb_effect统计数据
     */
    public function doRestPostUpdateSta($requestData)
    {
        if(!empty($requestData)){
            $inputParam = array();
            foreach($requestData as $elem)
            {
                $inputParam[] = array(
                    'account_id' => $elem['accountId'],
                    'product_id' => $elem['productId'],
                    'product_type' => $elem['productType'],
                    'date' => $elem['staDate'],
                    'consumption' => $elem['consumption'],
                    'reveal' => $elem['reveal'],
                    'ip_view' => $elem['ipView'],
                    'click_num' => $elem['clickNum'],
                    'order_conversion' => $elem['orderConversion'],
                );
            }

            try{
                $this->_staIntegrateMod->doUpdateStatisticRec($inputParam);
                $this->returnRest(array());
            }catch (Exception $e){
                $this->returnRest('', false, 230002, '更新操作异常:'.$e->getCode());
            }

        }else{
            $this->returnRest('', false, 230001, '参数不符合接口约定');
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /admessage
     * @method GET
     * @param string $url
     * @param  array $data {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b"}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】获取消息中心列表
     */
    public function doRestGetAdmessage($url, $data) {
        $data['accountId'] = -1;
        $bidMessageList = $this->_bidMessageMod->readAdMessage($data);
        if (count($bidMessageList) > 0) {
            $this->returnRest($bidMessageList);
        } else {
            $this->returnRest(array(), false, 230099, '未知原因失败');
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->post($url, $requestData);
     *
     * @mapping /admessage
     * @method POST
     * @param  array $data {"accountId": ,"token": ,}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 广告信息添加到消息中心
     */
    public function doRestPostAdmessage($data) {
        if (empty($data['uid']) || empty($data['content'])) {
            $this->returnRest(array(), false, 230012, '缺少参数');
        } else {
            $params = array(
                'uid' => $data['uid'],
                'accountId' => $data['accountId'] ? $data['accountId'] : -1,
                'content' => $data['content']
            );
            $updateResult = $this->_bidMessageMod->updateAdMessage($params);
            if ($updateResult) {
                $this->returnRest($updateResult);
            } else {
                $this->returnRest(array(), false, 230012, '更新广告消息失败');
            }
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /reportforms
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-招客宝报表
     */
    public function doRestGetReportForms ($url, $data) {
        $params = array(
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'] ? $data['adKey'] : '',
            'startCityCode' => $data['startCityCode'] ? $data['startCityCode'] : '',
            'productId' => $data['productId'] ? $data['productId'] : '',
            'productName' => $data['productName'] ? $data['productName'] : '',
            'vendorId' => $data['vendorId'] ? $data['vendorId'] : '',
            'vendorName' => $data['vendorName'] ? $data['vendorName'] : '',
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10,
        );
        $result = $this->_statement->getHgReportFormsList($params);
        if (count($result) > 0) {
            $this->returnRest(array('count' => $result['count'], 'rows' => $result['rows']));
        } else {
            $this->returnRest(array('count' => 0, 'rows' => array()), true, 230000, array());
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /biInfo
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-招客宝报表-查询所有的BI数据
     */
    public function doRestGetBIInfo ($url, $data) {
        $params = array(
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'] ? $data['adKey'] : '',
            'startCityCode' => $data['startCityCode'] ? $data['startCityCode'] : '',
            'productId' => $data['productId'] ? $data['productId'] : '',
            'productName' => $data['productName'] ? $data['productName'] : '',
            'vendorId' => $data['vendorId'] ? $data['vendorId'] : '',
            'vendorName' => $data['vendorName'] ? $data['vendorName'] : '',
        );
        //设置缓存
        $key = md5(json_encode($params));
        $data = Yii::app()->memcache->get($key);
        if (!empty($data)) {
            $result = $data;
        } else {
            $result = $this->_statement->getAllBIInfo($params);
            if ($result) {
                Yii::app()->memcache->set(md5(json_encode($params)), $result, 43200);
            }
        }
        if (count($result) > 0) {
            $this->returnRest(array('count' => 1, 'rows' => array($result)));
        } else {
            $this->returnRest(array('count' => 0, 'rows' => array()), true, 230000, array());
        }
    }
    
    /**
     * 开通配置自营供应商
     */
    public function doRestPostAgencyopenconfig($data) {
    	$result = $this->_manageMod->saveConfigAgency($data);
    	// 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
}