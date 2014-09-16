<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * 本测试类仅实现对于接口的通畅与否的监控指标，并不会检查具体的业务逻辑
 *
 * Author: chenjinlong
 * Date: 1/4/13
 * Time: 2:08 PM
 * Description: UserapiController.php
 */
class UserapiController extends CController
{
    private $_client;

    /**
     * 用例套件注册
     * @var array
     */
    private $_caseSet = array(
        '1' => 'actionShowEffect',
        '2' => 'actionMessage',
        '3' => 'actionAccountMessage',
        '4' => 'actionPMessage',
        '5' => 'actionInfo',
        '6' => 'actionAdPosition',
        '7' => 'actionVendorAppendInfo',
        '8' => 'actionVendorCertInfo',
        '9' => 'actionAddAccount',
        '10' => 'actionAccountIds',
        '11' => 'actionVendorList',
        '12' => 'actionAccountInfo',
        '13' => 'actionAuditVendorInfo',
        '14' => 'actionDelAccount',
    );

    /**
     * 接口版本参数
     * 1: 生产环境版本v1.0
     * 2: 重构中版本
     */
    const VER = 2;

    /**
     * 登录收客宝的TOKEN值
     */
    const TOKEN = 'dfdd105fe1c691d69041e2';

    /**
     * 收客宝帐号
     */
    const ACCOUNT_ID = 16;

    /**
     * 供应商编号
     */
    const AGENCY_ID = 440;

    /**
     * INNER_API_HOST 内部接口域名
     * OUTER_API_HOST 对外接口域名
     */
    private $_apiHost = array(
        '1' => array(
            'INNER_API_HOST' => 'http://bb.test.tuniu.org',
            'OUTER_API_HOST' => 'http://bb.test.tuniu.org',
        ),
        '2' => array(
            'INNER_API_HOST' => 'http://dev.bb.tuniu.org',
            'OUTER_API_HOST' => 'http://dev.bb.tuniu.org',
        ),
    );

    function __construct()
    {
        $this->_client = new RESTClient();
    }

    public function actionTest()
    {
        echo '开发调试';
    }

    /**
     * 统一输出函数
     *
     * @param $data
     */
    public static function output($data)
    {
        print_r($data);
        var_dump($data);
        echo '<br /><span style="color: red;">+++++++++++++++++++++++++++++++++++++++++++++++++++</span><br />';
    }

    /**
     * 用例套件集成测试
     */
    public function actionSuiteTest()
    {
        if(!empty($this->_caseSet) && is_array($this->_caseSet)){
            foreach($this->_caseSet as $index => $case)
            {
                var_dump($index, $case);
                $this->$case();
            }
        }else{
            echo '提示：用例套件为空';
        }
    }

    ///////////////////////////////////////////////////////////////////
    // 原Bid模块-各类接口
    ///////////////////////////////////////////////////////////////////
    /**
     * 【账号】产品推广效果
     *
     * @see BidController.doRestGetShowEffect
     */
    public function actionShowEffect()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/bid/showeffect';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/showeffect';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
            'period' => '0000-00-00',
        );
        self::output($this->_client->get($url, $param));
    }


    /**
     * 【账号】获取消息中心列表
     *
     * @see BidController.doRestGetMessage
     */
    public function actionMessage()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/bid/message';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/message';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * [账号]获取账户变动列表
     *
     * @see BidController.doRestGetAccountMessage
     */
    public function actionAccountMessage()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/bid/accountmessage';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/accountmessage';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
            'start' => '',
            'limit' => '',
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * 【账号】查询供应商附加信息
     *
     * @see BidController.doRestGetBaVendorInfo
     */
    /*public function actionGVendorInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/bid/bavendorinfo';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/bavendorinfo';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
        );
        self::output($this->_client->get($url, $param));
    }*/

    /**
     * 【账号】更新供应商补充信息
     *
     * @see BidController.doRestPostVendorInfo
     */
    /*public function actionPVendorInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/manage/vendor/update';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/update-vendorinfo';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
            'cmpName' => '',
            'cmpPhone' => '',
            'contractor' => '',
            'contractorTel' => '',
            'contractorTel2' => '',
            'invoiceType' => '',
            'filename' => '',
            'cmpBank' => '',
            'cmpAccount' => '',
            'taxNo' => '',
            'cmpAddress' => '',
        );
        self::output($this->_client->post($url, $param));
    }*/

    /**
     * [账号]财务信息添加到消息中心
     *
     * @see BidsysController.doRestPostMessage
     */
    public function actionPMessage()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/bid/update-message';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/create-message';
        }
        $param = array(
            'vendorId' => '',
            'type' => '',
            'content' => '',
            'amount' => '',
        );
        self::output($this->_client->post($url, $param));
    }

    ///////////////////////////////////////////////////////////////////
    // 原Manage模块-各类接口
    ///////////////////////////////////////////////////////////////////

    /**
     * [账号]收客宝帐号信息查询
     *
     * @see ManageController.doRestGetInfo
     */
    public function actionInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/info';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/info';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * [账号]查询广告位信息
     *
     * @see ManageController.doRestGetAdPosition
     */
    public function actionAdPosition()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/manage/adposition';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/adposition';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
            'adKey' => '',
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * 【账号】查询供应商补充信息
     * (备注：貌似缺漏上线)
     *
     * @see ManageController.doRestGetVendorInfo
     */
    public function actionVendorAppendInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/manage/vendorinfo';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/vendorinfo';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * [账号] 查询收客宝帐户认证状态信息
     *
     * @see ManageController.doRestGetVendorCertInfo
     */
    public function actionVendorCertInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/manage/vendorcertinfo';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['INNER_API_HOST'].'/bb/user/vendorcertinfo';
        }
        $param = array(
            'accountId' => self::ACCOUNT_ID,
            'token' => self::TOKEN,
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * 【账号】为供应商添加收客宝账户
     * (备注：目前接口允许单个供应商多收客宝帐号)
     *
     * @see ManagesysController.doRestPostAddAccount
     */
    public function actionAddAccount()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/manage/update-addaccount';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/create-addaccount';
        }
        $param = array(
            'vendorId' => '',
            'agencyName' => 'agency-name',
            'add_uid' => 2820,
            'add_time' => 'chenjinlong',
        );
        self::output($this->_client->post($url, $param));
    }

    /**
     * 【账号】通过供应商ID查收客宝ID
     *
     * @see ManagesysController.doRestGetAccountIds
     */
    public function actionAccountIds()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/manage/accountids';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/accountids';
        }
        $param = array(
            4333,
            440,
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * 【账号】收客宝列表接口
     *
     * @see ManagesysController.doRestGetVendorList
     */
    public function actionVendorList()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/manage/vendorlist';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/vendorlist';
        }
        $param = array(
            'agencyId' => '',
            'accountName' => '',
            'certificateFlag' => '',
            'start' => 0,
            'limit' => 10,
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * 【帐号】查询供应商的收客宝帐号信息
     *
     * @see ManagesysController.doRestGetAccountInfo
     */
    public function actionAccountInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/manage/accountinfo';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/accountinfo';
        }
        $param = array(
            'vendorId' => 4333,
            'jsessionId' => '',
            'loginIp' => '',
        );
        self::output($this->_client->get($url, $param));
    }

    /**
     * 【帐号】财务审核供应商补充信息
     *
     * @see ManagesysController.doRestPostAuditVendorInfo
     */
    public function actionAuditVendorInfo()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/manage/update-auditvendorinfo';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/create-auditvendorinfo';
        }
        $param = array(
            'accountId' => 0, //此处为供应商ID，键值命名有误
            'certFlag' => 1,
        );
        self::output($this->_client->post($url, $param));
    }

    /**
     * 【帐号】删除供应商之收客宝账户
     *
     * @see ManagesysController.doRestPostDeleteAccount
     */
    public function actionDelAccount()
    {
        if(self::VER == 1){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/manage/update-deleteaccount';
        }elseif(self::VER == 2){
            $url = $this->_apiHost[self::VER]['OUTER_API_HOST'].'/bb/public/user/create-deleteaccount';
        }
        $param = array(
            'agencyId' => 0,
            'uid' => '2820',
            'nickname' => 'chenjinlong',
        );
        self::output($this->_client->post($url, $param));
    }

}
