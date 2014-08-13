<?php
class ScreenshotMod{


	private $_staIntegrateMod;
	private $_productMod;

	function __construct()
	{
		$this->_staIntegrateMod = new StaIntegrateMod;
		$this->_productMod = new ProductMod;
	}
	public function generatePdf(){
		$output = $this->_staIntegrateMod->getStatisticUrlSet();
		ini_set('max_execution_time', '0');
		//获取时间
		$date_d=date("Y-m-d h:m:s");
		//pdf、程序文件存放目录
		$wkhtmltopd_dir = Yii::app()->basePath.'/extensions/wkhtmltopdf/';
		//pdf文件临时存放目录
		$pdf_dir        = Yii::app()->basePath.'/extensions/wkhtmltopdf';
		//遍历数据
		foreach($output as $key=>$value)
		{
			$tempUrl = explode('//',$value);
			$tempUrl1 = $tempUrl[1];
			$url=explode("/",$tempUrl1);

			$len=count($url);
			$url_head=explode(".",$url[0]);
			$linux_cmd = '';
			if(count($url)== 1)
			{
				$file_name = 'tuniu_'.$url_head[0].'.pdf';
			}else if((count($url)== 2&&$url[1]!='')||(count($url)==3&&$url[2]==''))
			{
				$file_name = 'tuniu_'.$url_head[0].'_'.$url[1].'.pdf';
			}else if((count($url)==3 && $url[2]!='')||(count($url)==4 && $url[3]==''))
			{
				$file_name = 'tuniu_'.$url_head[0].'_'.$url[1].'_'.$url[2].'.pdf';
			}
			$linux_cmd = $wkhtmltopd_dir.'wkhtmltopdf '.$value.' '.$pdf_dir.'/'.$file_name;
			//生成文件
			exec($linux_cmd);
			$filenamePath = $pdf_dir.'/'.$file_name;
			
		    if(!file_exists($filenamePath)){
                CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'截图-生成-失败',2,'wanglongsheng',1,-1,$filenamePath,$linux_cmd);
                continue;
            }
			//上传文件
			$file = array('name' => '@'.$filenamePath,
                              'size'=>abs(filesize($filenamePath)),
                              'tmp_name'=>$filenamePath);

			$url = $this->uploadFile($file); 
			//上传成功后保存url到数据库中
			if($url){
				$product = array('bidShowProductId'=>$key,'screenShotUrl'=>$url);
				$result = $this->_productMod->updateScreenshotUrl($product);
			}else{
				CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'截图-上传-失败',2,'wanglongsheng',1,-1,$file_name);
			}
			//文件已上传，删除文件
			$linux_cmd = 'rm '.$filenamePath;
			exec($linux_cmd);
			if(file_exists($filenamePath)){
				CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'截图-删除-失败',2,'wanglongsheng',1,-1,$filenamePath);
			}
		}
	}

	public function uploadFile($file) { 
		if (!empty($file)) {
			if (isset($file['size']) && $file['size'] > 0) {
				//处理文件名
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
        return null;
	}
}