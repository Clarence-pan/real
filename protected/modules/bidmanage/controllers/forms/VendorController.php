<?php
Yii::import("application.models.CurlUploadModel");
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');

class VendorController extends CController {
    private $manage;
    
    function __construct() {
        $this->manage = new UserManageMod();
    }
    
	public function actionUpdate() {
		if ($_FILES['filename']['size']>0) {
			// 上传文件
			$url = $this->uploadFile($_FILES);
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
		$vendorId = $this->manage->getVendorIdByAccountId($data['accountId']);
		$data['vendorId'] = $vendorId;
		// 调用hagrid接口，更新附加信息
		$res = HagridIao::updateVendorInfo($data);
		
		if($res['success']) {
			// 更新收客宝账户信息
			$dataLevel = $this->getAccountDataLevel($data['accountId']);
			$data['infoCompleteRate'] = $dataLevel;
            $data['certFlag'] = 2;
			$result = $this->manage->updateAccountDataLevel($data);

            $returnData['success'] = $result;
            $returnData['msg'] = $res['msg'];
            $returnData['errorCode'] = $res['errorCode'];
            $returnData['data'] = true;
            echo json_encode($returnData);
		}else{
            echo json_encode($res);
        }
	}
	
	/**
	 * 获取纳税人扫描件
	 * @param string $url
	 * @param array $data
	 */
	public function actionTaxFile() {
	    $accountId = Yii::app()->request->getQuery('id');
	    
	    if(empty($accountId)) {
            header('Content-type: text/html; charset=utf8');
            echo "<script>";
            echo "window.alert('下载文件失败');";
            echo "</script>";
            die();
	    }
	    
	    $params = array('accountId' => $accountId);
	    $vendor = HagridIao::getVendorInfo($params);
	    $url = $vendor['data']['attachUrl'];
	    
	    if (empty($url)) {
	        header('Content-type: text/html; charset=utf8');
	        echo "<script>";
	        echo "window.alert('您未上传过附件');";
	        echo "</script>";
	        die();
	    }
	    
	    $urlArray = explode('/', $url);
	    $urlCount = count($urlArray) - 1;
	    
	    if ($urlCount < 0) {
	        header('Content-type: text/html; charset=utf8');
            echo "<script>";
            echo "window.alert('下载文件失败');";
            echo "</script>";
            die();
	    }
	    
	    $fileName = $urlArray[$urlCount];
	    
	    $nameArray = explode('.', $fileName);
	    $nameCount = count($nameArray) - 1;
	     
	    if ($nameCount < 0) {
	        header('Content-type: text/html; charset=utf8');
            echo "<script>";
            echo "window.alert('下载文件失败');";
            echo "</script>";
            die();
	    }
	     
	    $fileType = $nameArray[$nameCount];
	    $contentType = Utils::getContentType($fileType);
	    
	    Header("Content-type: ".$contentType);
	    Header("Accept-Ranges: bytes");
	    Header("Content-Disposition: attachment; filename=".$fileName);
	    Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    Header("Pragma: no-cache");
	    Header("Expires: 0");
	    readfile($url);
	    die();
	}
	
	public function uploadFile($_FILE) {
		$filename = isset($_POST['filename']) ? $_POST['filename'] : '';
		if (!empty($_FILES)) {
			foreach ($_FILES as $file){
				if (isset($file['size']) && $file['size'] > 0) {
					//处理文件名
					$imageInfo = getimagesize($file['tmp_name']);
					$extension = image_type_to_extension($imageInfo[2]);
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
					$result = json_decode(CurlUploadModel::save($file),true);
					if ($result['success']) {
						//save to database
						$url = $result['data'][0]['url'];
						return $url;
					} else {
						return null;
					}
				}
			}
		}
	}
	
	/**
	 * 计算资料完整度
	 * @param unknown_type $accountId
	 */
	public function getAccountDataLevel($accountId) {
		$params['accountId'] = $accountId;
		$res = HagridIao::getVendorInfo($params);
		$level = 0;
		if($res['data']['id']==null) {
			$level = 0;
		} else if($res['data']['invoiceType'] == 1 && $res['data']['cmpName'] != null &&
					$res['data']['cmpPhone'] != null && $res['data']['contractor'] != null &&
					$res['data']['contractorTel'] != null && $res['data']['contractorTel2'] != null &&
					$res['data']['attachUrl'] != null) {
			$level = 100;	
		} else if($res['data']['invoiceType'] == 2 && $res['data']['cmpName'] != null &&
					$res['data']['cmpPhone'] != null && $res['data']['contractor'] != null &&
					$res['data']['contractorTel'] != null && $res['data']['contractorTel2'] != null &&
					$res['data']['attachUrl'] != null && $res['data']['cmpBank'] != null &&
					$res['data']['cmpAccount'] != null && $res['data']['cmpAddress'] != null &&
					$res['data']['taxNo'] != null) {
			$level = 100;
		}else {
			$level = 50;
		}
		return $level;
	}
}

?>