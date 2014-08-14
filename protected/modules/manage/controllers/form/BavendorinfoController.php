<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
Yii::import('application.modules.manage.dal.iao.FinanceIao');
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
Yii::import("application.models.CurlUploadModel");
Yii::import('application.modules.manage.models.user.UserMod');

class BavendorinfoController extends restUIServer{
    
    private $user;
    private $_client;
    
    function __construct() {
	    $this->user =  new UserMod();
        $this->_client = new RESTClient();
    }
    
    public function actionUpdate() {
		if ($_FILES['filename']['size']>0) {
			// 上传文件
			$url = $this->user->uploadFile($_FILES);
			if($url == null) {
				$returnData['success'] = false;
				$returnData['msg'] = '上传文件失败';
				$returnData['errorCode'] = '230010';
				$returnData['data'] = array();
                echo json_encode($returnData);
                return;
			}
		}
		$data = $_POST;
		$data['attachUrl'] = $url;
                
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
                
                $returnData['success'] = $result;
                if($result){
                    $returnData['errorCode'] = 230000;
                    $returnData['msg'] = '成功';
                }else{
                    $returnData['errorCode'] = 230199;
                    $returnData['msg'] = '未知错误';
                }                
                $returnData['data'] = new stdClass();
                echo json_encode($returnData);
                return;
	}
    
}
?>
