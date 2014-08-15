<?php

Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.dal.iao.NBookingIao');

class restUIServer extends restfulServer {

    private $accountId;
    
    private $loginName;
    
    private $agencyId;
    
    private $adminFlag;

    function beforeRest($data) {
    	// 查询accountId
    	$client = new RESTClient();
//        $url = Yii::app()->params['NB_HOST'] . 'restful/vendor/query-account';
//        $params = array('sessionId'=>$data['JSESSIONIDNB']);
//        $data['accountId'] = 1;
//        $data['loginName'] = 4333;
//        $data['agencyId'] = 4333;
		$url = Yii::app()->params['ADMIN_HOST'] . 'admit/login/is-login';
        $params = array('JSESSIONIDADMIT'=>$data['JSESSIONID']);
        try {
            /**
             * 仅测试环境，开放特殊入口使得前端方便调试BUG
             * added by chenjinlong 20140121
             */
            if(intval($data['accountId']) > 0 && !empty($data['loginName']) && !empty($data['agencyId'])){
                $response = array(
                    'success' => true,
                    'data' => array(
                        'accountId' => intval($data['accountId']),
                        'loginName' => intval($data['loginName']),
                        'agencyId' => intval($data['agencyId']),
                    ),
                );
            }else{
            	// 开启监控
				$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
				var_dump($url, $params);die;
//                $response = $client->get($url, $params);
				$response = $client->post($url, $params);
				BPMoniter::endMoniter($posM, 1000, __METHOD__.'::'.__LINE__);
            }
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            $this->loginFail();
            return false;
        }
        
        // 招客宝登录日志记录(仅测试环境用) - Added by chenjinlong 20140120
        LBSLogger::logging("login_bb",'NB-API','NBooking系统登录判断','',array(
            'url' => $url,
            'parameters' => $params,
            'response' => $response,
        ));
        $accountId='';
        $agencyId='';
        $loginName='';
        // 分类初始化结果
        if ($response['success'] && intval($response['data']['accountId']) > 0 && !empty($response['data']['loginName']) && !empty($response['data']['agencyId'])) {
        	$accountId = $response['data']['accountId'];
        	$agencyId = $response['data']['agencyId'];
        	$loginName = $response['data']['loginName'];
        } else {
            $this->loginFail();
        	return false;
        }
        // $accountId = $data['accountId'];
        $jsessionId = $data['JSESSIONID'];
        // 设置当天账户
        $this->setAccountId($accountId);
        // 设置子账号
        $this->setLoginName($loginName);
        // 设置父账号
        $this->setAgencyId($agencyId);
        // 初始化管理员账号数组
        $adArr = explode('@', $loginName);
        // 判断是否是管理员
        if (0 == strcmp($loginName, $agencyId) || 0 == strcmp('admin', $adArr[0])) {
        	// 为管理员
        	$this->setAdminFlag(true);
        } else {
        	// 为子账号
        	$this->setAdminFlag(false);
        }
        // 操作成功返回true
        return true;
    }

    function loginFail() {
        $innerSkipHost = Yii::app()->params['INNER_SKIP_HOST'];
        /*if ($_SERVER['REMOTE_ADDR'] == $innerSkipHost || $_SERVER['HTTP_X_REAL_IP'] == $innerSkipHost) {
            return true;
        }*/
        $this->errorNo = 230098;
        $this->errorMsg = '登录失效';
        return false;
    }

    function getAccountId() {
        return $this->accountId;
    }

    function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    function getAccountInfo() {
        $manage = new UserManageMod;
        $params = array('id' => $this->getAccountId());
        return $manage->read($params);
    }

	function getLoginName() {
        return $this->loginName;
    }

    function setLoginName($loginName) {
        $this->loginName = $loginName;
    }
    
    function getAgencyId() {
        return $this->agencyId;
    }

    function setAgencyId($agencyId) {
        $this->agencyId = $agencyId;
    }
    
    function getAdminFlag() {
        return $this->adminFlag;
    }

    function setAdminFlag($adminFlag) {
        $this->adminFlag = $adminFlag;
    }

	function isAdmin() {
		return $this->adminFlag;
	}

}