<?php
Yii::import("application.models.CurlUploadModel");
class FileUploadController extends Controller{
	
	public function actionIndex() {
		$this->render('index');
	}
	
	public function actionUpload() {
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
						} else {
							
						}
						$return_struct['msg'] = urlencode($result['msg']);
					}
				}
			} 
		}
}

?>