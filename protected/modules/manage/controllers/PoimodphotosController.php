<?php 

Yii::import('application.models.PoiModPhotosModel'); 
Yii::import('application.dal.iao.UserLoadIao'); 
Yii::import('application.dal.iao.PagerForUsIao'); 
Yii::import('application.models.PoiModPagesModel'); 
Yii::import('application.models.PoiInfoModel'); 
Yii::import('application.models.DistrictInfoModel');
Yii::import('application.models.PoiModel');
/**
 * 
 * @author bianshiwu
 * @version 2012-07-19
 */

class PoimodphotosController extends Controller {
	const  NAV = '图片管理';
	const pagesize = 25;
	public $menulist;	//定义在特定poi下查看时高亮 菜单对象
	
	function getNav() {
		$pageInfo['url'] = '/poimodphotos';
		$pageInfo['nav'] = self::NAV;
		return $pageInfo;
	}
	
	function actionIndex() {
		$poi_mod_photos = new PoiModPhotosModel();
        $userload = new UserLoadIao();
		 
		//分页
		$page = isset($_GET['page']) && $_GET['page'] ? (int)$_GET['page'] : 1;
		$pagesize = 25;
		$total = $poi_mod_photos->getphotoCount();
		if(ceil($total/$pagesize)<$page && ceil($total/$pagesize)>0) {
			$page = ceil($total/$pagesize);
		}
		$start = max(($page - 1) * $pagesize, 0);
		
		//获取图片信息
		$photoInfo = $poi_mod_photos->getPhotoInfo($start,$pagesize);
		foreach($photoInfo as $key=>$photo_info) {
			 $username = $userload->doFuncMemcache("getUserNmae",$photo_info['add_uid']); 
			 if(!empty($username)) {
			 	$photoInfo[$key]['add_uname'] = $username;		//添加人
			 }else {
			 	$photoInfo[$key]['add_uname'] =  '';
			 }  
             
		}
		$pager = new PagerForUsIao('/index.php/poimodphotos/index?page=', $total, $pagesize, $page);
        $pager  = $pager->getBar();  //分页
		$this->render('index',array('photoInfo'=>$photoInfo,'pager'=>$pager));
	}
	
	/*按id删除一条记录*/
	function actionDelete() {
		$poi_mod_photos = new PoiModPhotosModel();
		$id = isset($_GET['id']) && $_GET['id'] ? (int)$_GET['id'] :'';
		if($id) {
			$poi_mod_photos->deletePhoto($id);  
		 }
		 $this->redirect($_SERVER['HTTP_REFERER']);
	}
	
	//批量删除记录
	function actionBatchOperate() {
		$poi_mod_photos = new PoiModPhotosModel();
		if(isset($_GET['batch_id'])) {
			$batch_ids = $_GET['batch_id'];
		}
		if(empty($batch_ids)) {
		    echo "<script>";
			echo "window.alert('请选择需要操作的id');";
			echo "window.history.back();";
			echo "</script>";
			die();
		}
	 
		if(isset($_GET['delete'])) {
			foreach($batch_ids as $batch_id) {
				$poi_mod_photos->deletePhoto($batch_id);
			}
		}
	   $this->redirect($_SERVER['HTTP_REFERER']);
	}
	//按用户名搜索功能
	function actionSearch() {
		$userload = new UserLoadIao();
	    $page = isset($_GET['page']) && $_GET['page'] ? (int)$_GET['page'] : 1;
		$userId = isset($_GET['userId']) && $_GET['userId'] ? $_GET['userId'] : '';
		$add_uname=isset($_REQUEST['add_uname']) && $_REQUEST['add_uname'] ? trim($_REQUEST['add_uname']) : '';
		$pagesize = 25;
		
		if($_POST) {
			if(trim($_POST["add_uname"])) {
				$userInfo = $userload->doFuncMemcache('queryByKey',trim($_POST["add_uname"]));
				if($userInfo['res'] == TRUE) {
					$userId = $userInfo['cust']['cust_id'];
				}
				else
				{
					echo "<script>";
					echo "window.alert('该用户不存在');";
					echo "window.history.back();";
					echo "</script>";
					die();
				}
			}
		}
		$total = PoiModPhotosModel::searchCount($userId);
		if(ceil($total/$pagesize)<$page && ceil($total/$pagesize)>0) {
			$page = ceil($total/$pagesize);
		}
		$start = max(($page - 1) * $pagesize, 0);
		
		$searchResult = PoiModPhotosModel::search($userId,$start,$pagesize);
		foreach($searchResult as $key=>$result) {
			if(empty($add_uname)) {
				$username = $userload->doFuncMemcache("getUserNmae",$result['add_uid']);
			    if(!empty($username)) {
			 		$photoInfo[$key]['add_uname'] = $username;		//添加人
			    }else {
			 		$photoInfo[$key]['add_uname'] =  '';
			    }
			}else{
				$searchResult[$key]['add_uname']=$add_uname;
			}
		}
		$pager = new PagerForUsIao('/poimodphotos/search?userId='.$userId.'&add_uname='.$add_uname.'&page=', $total, $pagesize, $page);
        $pager  = $pager->getBar();  //分页
		$this->render('index',array('photoInfo'=>$searchResult,'pager'=>$pager));
	}
	
	
	public function actionPhotolistsearch() {
		$poi_mod_photos = new PoiModPhotosModel();
		$id = isset($_POST['id']) && $_POST['id'] ? intval(trim($_POST['id'])) : '';
		if(!empty($id)) {
			$photoInfo=$poi_mod_photos->getPhotoById($id);
			$this->render('update',array('photoInfo'=>$photoInfo));
		}else {
			$this->redirect($_SERVER['HTTP_REFERER']);
		}
	}
	
	/**
	 * 根据poi_id显示数据
	 * @author	yangyang2
	 */
	public function actionPoilist() {
		$disModel = new DistrictInfoModel();
		$poiInfoModel = new PoiInfoModel();
		$userload = new UserLoadIao();
		$page = isset($_GET['page']) && $_GET['page'] ? (int)$_GET['page'] : 1;
		$poiModel = new PoiModPhotosModel();
		$parPid = null;	//传递的参数
		$parUid = null;
		$parUname = "";
		$parName = "";
		$poiInfo = array();
		if (isset($_REQUEST["poi_id"])) {
			$parPid = $_REQUEST["poi_id"];
			//modified by liqing3
			$poiInfo = PoiModel::get_instance()->getPoiInfoById($_REQUEST["poi_id"]);
		}
		$info = $poiModel->showListByPoi($parPid,$page, self::pagesize);
		$total = $info[1][0]["count(*)"]; /* 返回数据总数 */
		if ((ceil($total/self::pagesize) < $page)&&(ceil($total/self::pagesize)>0)) {
			$page = ceil($total/self::pagesize);
			$info = $poiModel->showListByPoi($parPid,$page,self::pagesize);
		}
		$dataSource = $info[0];
		//print_r($dataSource);exit;
		for ($count=0;$count<sizeof($dataSource);$count++) {
			$username = $userload->doFuncMemcache("getUserNmae", $dataSource[$count]["add_uid"]);
			$dataSource[$count]["username"]=$username;
		}	//获取添加人名字
	
	
		for ($count=0;$count<sizeof($dataSource);$count++) {
			$poiName = $disModel->getNameById($dataSource[$count]["poi_id"]); /* 根据poi_id获取页面所属poi的名字 */
			if (empty($poiName["name"])) {	/* if no.3 */
	
				$poiName = $poiInfoModel->getPoiInfoById($dataSource[$count]["poi_id"]);
				if (empty($poiName["name"])) {
					$poiName["name"]="";	/* 通过几重保证poiName的值存在 */
				}
			}
			$dataSource[$count]["poiname"]=$poiName["name"];
		}
		$pager = new PagerForUsIao('/poimodphotos/poilist/poi_id/'.$parPid.'?page=', $total, self::pagesize, $page);
		$pager = $pager->getBar();  /* 使用分页 */
		if (isset($_REQUEST["poi_id"])) {
			$this->menulist = PoiModel::get_instance()->getMenuList(PoiModel::get_instance()->getPoiInfoById($_REQUEST["poi_id"]));
			$this->menulist["photos"]["selected"] = true;
		}
		$this->render('poilist',array("datasource"=>$dataSource,"pager"=>$pager,"pagenum"=>$page,"pagesize"=>self::pagesize,"poiInfo"=>$poiInfo));
	}
	
	
	public function actionPoilistsearch() {
		$userload = new UserLoadIao();
		$poi_mod_photos = new PoiModPhotosModel();
		$page = isset($_GET['page']) && $_GET['page'] ? (int)$_GET['page'] : 1;
		$userId = isset($_GET['userId']) && $_GET['userId'] ? $_GET['userId'] : '';
		$poiId= isset($_REQUEST['poi_id']) && $_REQUEST['poi_id'] ? intval($_REQUEST['poi_id']) : '';
		$add_uname=isset($_REQUEST['add_uname']) && $_REQUEST['add_uname'] ? trim($_REQUEST['add_uname']) : '';
		$pagesize = 25;
		 
		if ($poiId) {
			$poiInfo = PoiModel::get_instance()->getPoiInfoById($poiId);
		}
		if($_GET) {
			if($add_uname) {
				$userInfo = $userload->doFuncMemcache('queryByKey',$add_uname);
				if($userInfo['res'] == TRUE) {
					$userId = $userInfo['cust']['cust_id'];
				}else {
					echo "<script>";
					echo "window.alert('该用户不存在');";
					echo "window.history.back();";
					echo "</script>";
					die();
				}
			}
		}
		$total = PoiModPhotosModel::photoCount($poiId,$userId);
		if(ceil($total/$pagesize)<$page && ceil($total/$pagesize)>0) {
			$page = ceil($total/$pagesize);
		}
		$start = max(($page - 1) * $pagesize, 0);
		
		$searchResult = PoiModPhotosModel::photoSearch($poiId,$userId,$start,$pagesize);
		foreach($searchResult as $key=>$result) {
			if(empty($add_uname)) {
				$username = $userload->doFuncMemcache("getUserNmae",$result['add_uid']);
				if(!empty($username)) {
					$searchResult[$key]['username'] = $username;		//添加人
				}else {
					$searchResult[$key]['username'] =  '';
				}
			}else{
				$searchResult[$key]['username']=$add_uname;
			}
		}
		if (isset($poiId)) {
			$this->menulist = PoiModel::get_instance()->getMenuList(PoiModel::get_instance()->getPoiInfoById($poiId));
			$this->menulist["photos"]["selected"] = true;
		}
		$pager = new PagerForUsIao('/poimodphotos/poilistsearch?poi_id='.$poiId.'&userId='.$userId.'&add_uname='.$add_uname.'&page=', $total, $pagesize, $page);
		$pager  = $pager->getBar();  //分页
		$this->render('poilist',array('datasource'=>$searchResult,'pager'=>$pager,'poiInfo'=>$poiInfo,"pagesize"=>self::pagesize));
	}
	
	function actionPhotolist() {
		$poi_mod_photos = new PoiModPhotosModel();
		$poiTypes = PoiModel::get_instance()->getAllTypes();
			
		//分页
		$page = isset($_GET['page']) && $_GET['page'] ? (int)$_GET['page'] : 1;
		$pagesize = 150;
		$total = $poi_mod_photos->getphotoCount();
		if(ceil($total/$pagesize)<$page && ceil($total/$pagesize)>0) {
			$page = ceil($total/$pagesize);
		}
		$start = max(($page - 1) * $pagesize, 0);
		
		//获取图片信息
		$photoInfo = $poi_mod_photos->getPhotoList($start,$pagesize,$poiTypes);
		$pager = new PagerForUsIao('/index.php/poimodphotos/photolist?page=', $total, $pagesize, $page);
		$pager  = $pager->getBar();  //分页
		$this->render('photolist',array('photolist'=>$photoInfo,'pager'=>$pager));
	}
	
	function actionEdit() {
		$poi_mod_photos = new PoiModPhotosModel();
		$id = isset($_REQUEST['id']) && $_REQUEST['id'] ?  $_REQUEST['id'] : '';
		$name = isset($_POST['name']) && $_POST['name'] ?  trim($_POST['name']) : '';
		$prourl = isset($_REQUEST['prourl']) && $_REQUEST['prourl'] ?  $_REQUEST['prourl'] : '';
		$trash = isset($_POST['trash']) && $_POST['trash'] ?  $_POST['trash'] : '';
		$back = isset($_POST['back']) && $_POST['back'] ?  $_POST['back'] : '';
		$be_sure=isset($_POST['be_sure']) && $_POST['be_sure'] ?  $_POST['be_sure'] : '';
		$result=0;
		
		if(!empty($back)) {
			$this->redirect(urldecode($prourl));
			exit;
		}
		if(!empty($be_sure)) {
			if(empty($name)) {
				echo "<script>";
				echo "window.alert('图片名称不能为空');";
				echo "window.history.back();";
				echo "</script>";
				die();
			}
			$result=PoiModPhotosModel::editPhoto($id,$name);
		}else if(!empty($trash)) {
			$poi_mod_photos->deletePhoto($id);
			$this->redirect($_SERVER['HTTP_REFERER']);
		}
		$photoInfo=PoiModPhotosModel::getPhotoById($id);
		$this->render('update',array('photoInfo'=>$photoInfo,'result'=>$result));
	}
	
	function actionTrash() {
		$poi_mod_photos = new PoiModPhotosModel();
		$poiTypes = PoiModel::get_instance()->getAllTypes();
		$id = isset($_POST['id']) && $_POST['id'] ?  $_POST['id'] : '';
		$recover= isset($_POST['recover']) && $_POST['recover'] ?  $_POST['recover'] : '';
        $page = isset($_GET['page']) && $_GET['page'] ? (int)$_GET['page'] : 1;
		$pagesize = 150;
		
		if(!empty($recover)) {
			$poi_mod_photos->recoverPhoto($id);
			$this->redirect($_SERVER['HTTP_REFERER']);
		}
		
		$total = $poi_mod_photos->getTrashphotoCount();
		if(ceil($total/$pagesize)<$page && ceil($total/$pagesize)>0) {
			$page = ceil($total/$pagesize);
		}
		$start = max(($page - 1) * $pagesize, 0);
		
		//获取图片信息
		$photoInfo = $poi_mod_photos->getTrashPhotoList($start,$pagesize,$poiTypes);
		$pager = new PagerForUsIao('/index.php/poimodphotos/trash?page=', $total, $pagesize, $page);
		$pager  = $pager->getBar();  //分页
		$this->render('trash',array('photolist'=>$photoInfo,'pager'=>$pager ));
	}
	
	public function actionDeletesome() {
		$poi_mod_photos = new PoiModPhotosModel();
		$del = array();
		if (isset($_REQUEST["del_id"])) {
			$id_array = explode(",",$_REQUEST["del_id"]);
			for($i=0;$i<sizeof($id_array);$i++) {
				$id_array[$i] = intval($id_array[$i]);
			}
			
			$poi_mod_photos->deleteSome($id_array);
			exit('1');
		}
		else {
			exit('-1');
		}
	}
	
	public function actionSetdefaultpic() {
		if(!isset($_REQUEST['pic_id'])||!isset($_REQUEST['poi_id'])) {
			$this->redirect($_SERVER['HTTP_REFERER']);
		}
		$pic_id = $_REQUEST['pic_id'];
		$poi_id = $_REQUEST['poi_id'];
		$poi_mod_photos = new PoiModPhotosModel();
		$poi_mod_photos->setDefaultPic($pic_id, $poi_id);
		$this->redirect($_SERVER['HTTP_REFERER']); /* 设置默认图之后重新展示 */
	}
	/**
	* 上传poi图片
	*/
	public function actionPoiimageupload() {
		Yii::import('application.models.CurlUploadModel');
		$return_struct = array(
				'success' => false,
				'msg' => urlencode('参数错误'),
				'data'=> array(),
				);
		$poiId = $_POST['poi_id'];
		$imageInfo = array();
		$filename = isset($_POST['filename']) ? $_POST['filename'] : '';
		//检查poi是否存在
		$poiModel = PoiModel::get_instance();
		$poiInfo = $poiModel->getPoiInfoById($poiId);
		if (!$poiInfo) {
			$return_struct['msg'] = urlencode('没有找到对应poi信息');
			exit(urldecode(json_encode($return_struct)));
		}
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
						//北京返回的cdn地址
						$outcdn_url = 'http://m.tuniucdn.com';
						//南京返回的cdn地址
						$innercdn_url = "http://int-file-01.cdn.tuniu.org";
						//截取地址
						if ( stristr($url,$outcdn_url) ){
							$pos = substr($url, strlen($outcdn_url));
						} elseif ( stristr($url,$innercdn_url) ) {
							$pos = '/filebroker/cdn'.substr($url,strlen($innercdn_url));
						} else {
							$pos = stristr($url,'/filebroker');
						}
						$uniq_id_str = explode('.',$pos);
						$sub_url = $uniq_id_str[0];
						
						$filename_arr = explode('.', $file['name']);
						list($imgwidth,$imgheight) = getimagesize($file['tmp_name']);
						
						$imageInfo['name'] 		= $filename_arr[0];
						$imageInfo['uniq_id'] 	= $sub_url;
						$imageInfo['tail'] 		= $uniq_id_str[1];
						$imageInfo['width'] 	= $imgwidth;
						$imageInfo['height'] 	= $imgheight;
						$imageInfo['size'] 		= $file['size'];
						$imageInfo['poi_code']  = $poiInfo['poi_code'];
						
						PoiModPhotosModel::savePhoto($poiId,$imageInfo,$poiInfo);
						$return_struct['success'] = true;
						$return_struct['data']['url'] = $url;
					} else {
						
					}
					$return_struct['msg'] = urlencode($result['msg']);
				}
			}
		}
		
		exit(urldecode(json_encode($return_struct)));
	}
}