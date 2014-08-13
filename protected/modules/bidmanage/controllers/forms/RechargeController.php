<?php
Yii::import("application.models.CurlUploadModel");
Yii::import('application.modules.bidmanage.models.fmis.FmisManageMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');

class RechargeController extends CController {
    
    private $manage;
    private $fmis;
    
    function __construct() {
        $this->manage = new UserManageMod();
        $this->fmis = new FmisManageMod();
    }
    
	public function actionRecharge() {
        //获取上传文件
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        if (!empty($_FILES)) {
            foreach ($_FILES as $file) {
                if (isset($file['size']) && $file['size'] > 0) {
                    //处理文件名
                    $image = getimagesize($file['tmp_name']);
                    $extension = image_type_to_extension($image[2]);
                    $replacements = array(
                    		'jpeg' => 'jpg',
                    		'tiff' => 'tif',
                    );
                    $extension = strtr($extension, $replacements);
                    if (!$filename) {
                    	$filename = $file['name'].$extension;
                    } else {
                    	$filename = $filename.$extension;
                    }
                    $result = json_decode(CurlUploadModel::save($file), true);
                    if ($result['success']) {
                    	$url = $result['data'][0]['url'];
                    } else {
                    	//上传文件失败
                    	$returnData['success'] = false;
                    	$returnData['msg'] = '上传文件失败';
                    	$returnData['errorCode'] = '230010';
                    	$returnData['data'] = $result['data'];
                    	echo json_encode($returnData);
                    	return;
                    }
                }
            }
        }
		
        $amount = floor($_POST['amount']*100)/100;
        
        if (0 >= $amount) {
            $returnData['success'] = false;
            $returnData['msg'] = '充值金额必须大于0元';
            $returnData['errorCode'] = '230002';
            $returnData['data'] = array();
            echo json_encode($returnData);
            return;
        }
        
		$vendorId = $this->manage->getVendorIdByAccountId($_POST['accountId']);
		
		$params = array(
		        'agency_id' => intval($vendorId),
		        'pay_bank' => $_POST['payBank'],
		        'charge_time' => date('Y-m-d'),
		        'op_saler_id' => $_POST['accountId'],
		        'source_flag' => 0,
		        'discount_type' => 70,
		        'serial_num' => strtotime(date('Y-m-d H:i:s')),
		        'attach_url' => $url,
		        'amt' => array(
		                'charge' => $amount
		        )
		);
		
		$response = $this->fmis->createRecharge($params);
		
		$returnData['success'] = $response['success'];
		$returnData['msg'] = $response['msg'];
		$returnData['errorCode'] = $response['errorCode'];
		$returnData['data'] = $response['data'];
		echo json_encode($returnData);
	}
}
?>