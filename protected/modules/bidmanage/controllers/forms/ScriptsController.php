<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/11/12
 * Time: 5:02 PM
 * Description: ScriptsController.php
 */
require_once dirname(__FILE__).'/../../models/release/ReleaseMessageMod.php';
require_once dirname(__FILE__).'/../../models/user/StaIntegrateMod.php';
require_once dirname(__FILE__).'/../../models/product/ProductMod.php';
require_once dirname(__FILE__).'/../../models/release/ReleaseMod.php';
require_once dirname(__FILE__).'/../../models/product/ChannelMod.php';
require_once dirname(__FILE__).'/../../models/product/ClsrecommendMod.php';
require_once dirname(__FILE__).'/../../models/pack/PackageplanMod.php';


define("_TEST_LEVEL_", 11);
define("_TRUE_LEVEL_", 12);
class ScriptsController extends CController
{
    private $_relMessageMod;

    private $_staIntegrateMod;
   
	private $_productMod;
        
    private $_releaseMod;
    
    private $_channelMod;
    
    private $_packageplanMod;
    
    private $_clsrecommendMod;

    function __construct()
    {
        $this->_relMessageMod = new ReleaseMessageMod();
        $this->_staIntegrateMod = new StaIntegrateMod();
        $this->_productMod = new ProductMod();
        $this->_releaseMod = new ReleaseMod;
        $this->_channelMod = new ChannelMod();
        $this->_packageplanMod = new PackageplanMod();
        $this->_clsrecommendMod = new ClsrecommendMod();
    }

    /**
     * 开发调试使用
     */
    public function actionTest()
    {
        echo '$TNSRV_INTERFACE_LOCATION:' .  getenv("TNSRV_INTERFACE_LOCATION");
        var_dump($_SERVER);
    }

    /**
     * 收客宝产品推广与发布
     */
    public function actionRelease()
    {
    	Yii::app()->buckbeek_master->createCommand("SET SESSION wait_timeout=2*3600")->query();
        Yii::app()->buckbeek_slave->createCommand("SET SESSION wait_timeout=2*3600")->query();
        //权限约定
        if($_GET['password'] != '06b8f6d2f44740f0893044642dae3963'){
            echo '对不起，您不具备执行权限。请联系开发人员，非常感谢！';
            return;
        }

        if(!empty($_GET['date'])){
            define('RELEASE_DATE', strval($_GET['date']));
        }else{
            define('RELEASE_DATE', date('Y-m-d'));
        }
        if(!empty($_GET['type'])){
            define('RELEASE_TYPE', intval($_GET['type']));
        }else{
            define('RELEASE_TYPE', 0);
        }
        
        print_r('当前发布的产品日期限定为：'.RELEASE_DATE);

        //招客宝产品推送
        $this->_releaseMod->run();
        print_r('<br />=> 推送至前台网站');
        
        CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '本次推广全部结束', 11, 'wenrui',0,0,'');
        print_r('<br />=> 运行完毕');
    }

    public function actionReleaseSta()
    {
        //权限约定
        if($_GET['password'] != '06b8f6d2f44740f0893044642dae3963'){
            echo '对不起，您不具备执行权限。请联系开发人员，非常感谢！';
            return;
        }

        if(!empty($_GET['date'])){
            define('STA_DATE', strval($_GET['date']));
        }else{
            define('STA_DATE', date("Y-m-d", strtotime('-1 day')));
        }

        print_r('当前执行统计的日期为：' . STA_DATE);
        $this->_staIntegrateMod->runStatisticTask();
        print_r('<br />=> 运行完毕');
    }
    
	public function actionSendemail() {

        $flag = $_GET['flag'];
        if (!$flag || $flag != 'mail') {
            echo 'send email !';
            return;
        }

        Yii::import('application.modules.bidmanage.models.product.ProductMod');
        Yii::import('application.modules.bidmanage.models.release.ReleaseEmailMod');

        $pro = new ProductMod();
        $release = new ReleaseEmailMod();
        $productArray = $pro->getOneDayBidProduct();
        if (count($productArray) == 0) {
            echo 'no date !';
            return;
        }
        $params = $release->genEmaiBody($productArray);
        Yii::import('application.models.EmaiTool');
        $mail = new EmaiTool();


        if ($mail->sendEmail($params)) {
            echo 'send email success!';
        } else {
            echo 'send email fail!';
        }
    }

	public function actionScreenshot(){
		Yii::import('application.modules.bidmanage.models.release.ScreenshotMod');
		$_screenshotMod = new ScreenshotMod();
		$_screenshotMod->generatePdf();
	}
        
        public function actionProduct() {
            // 更新产品信息
            $this->_productMod->updateProductProcess();

            //打印到页面呈现
            $this->returnRest('更新供应商产品信息成功');
        }

	/**
	 * 发送即将发布的产品
	 */        
   	public function actionSendsuccemail() {
        $flag = $_GET['flag'];
        if (!$flag || $flag != 'mail') {
            echo 'send email !';
            return;
        }
        Yii::import('application.modules.bidmanage.models.product.ProductMod');
        Yii::import('application.modules.bidmanage.models.release.ReleaseEmailMod');

        $pro = new ProductMod();
        $release = new ReleaseEmailMod();
        $productArray = $pro->getMailLineProduct();
        if (count($productArray) == 0) {
            echo 'no data!';
            return;
        }
        $params = $release->genEmaiBody($productArray);
        Yii::import('application.models.EmaiTool');
        $mail = new EmaiTool();
        if ($mail->sendEmail($params)) {
            echo 'send email success!';
        } else {
            echo 'send email fail!';
        }
    }
    
    /**
   	 * 接口调用次数标记
   	 */
   	private $iaoFlag = 1;
    	
   	/**
   	 * 数据库调用次数标记
   	 */
   	private $dbFlag = 1;
    	
    /**
     * 刷新搜索表脚本入口
     */
    public function actionSearchiao(){
       // 导入接口调用文件
        	Yii::import('application.modules.bidmanage.dal.iao.SearchIao');
        	// 导入业务逻辑处理文件
        	Yii::import('application.modules.bidmanage.models.product.ProductMod');
        	// 初始化业务逻辑处理类
        	$productMod = new ProductMod();
        	// 初始化MD5加密key
        	$key="$#%^%&1234()&asdfKY&^%&H";
        	// 初始化接口调用源参数
        	$from="search_client";
			// $url="http://10.10.30.54:11411/tuniu_search_api/searchApi.action?do=api&from=". $from;
			// 初始化接口调用地址  正式环境
			$url="http://public-api.bj.pla.tuniu.org:82/tuniu_search_api/searchApi.action?do=api&from=". $from;
			// 初始化接口调用参数  需要调用服务端的方法  需要的数据数量
			$input= array('fun'=>'getHotSearchRanking',
				'limit'=>500);
			// 获取表中原有关键词数据
			$keywordData = $productMod -> getKeywordData('');
			// 当调用接口失败时，循环三次调用接口
			while(true) {
				// 如果操作成功或失败，则跳出循环
				if (4 == $this->iaoFlag) {
					break;
				}
				// 打印刷新搜索页关键词表接口调用状况
        		echo '第'.$this->iaoFlag.'次调用远程接口获取关键词数据。。。<br />';
        		// 调用接口  取得数据
				$data = SearchIao::fetchData($input,$key,$url);
				// 判断是否取得数据
				if (!empty($data) && is_array($data) && !empty($data['result']['type_7_day']) && is_array($data['result']['type_7_day'])) {
					// 获得按点击量倒序排序并且分组后的结果数组
					$result = $this->arraySortGroup($data['result']['type_7_day'], 'search_num');
					// 初始化删除计数标记
					$flag = 0;
					// 初始化数据库操作成功失败标记
					$dbDoFlag = false;
					// 当插入数据库失败时，循环尝试插入数据库
					while(true) {
						// 判断是否需要跳出循环
						if ($dbDoFlag) {
							// 打印成功日志
							echo '刷新关键词表成功！！！<br />';
							// 将接口调用标记置为4，提示中断最终循环
							$this->iaoFlag = 4;
							// 中断循环
							break;
						} else if (!$dbDoFlag && 3 < $this->dbFlag) {
							// 将接口调用标记置为4，提示中断最终循环
							$this->iaoFlag = 4;
							// 中断循环
							break;
						}
						// 打印刷新搜索页关键词表数据库操作状况
		        		echo '第'.$this->dbFlag.'次操作数据库刷新关键词表数据。。。<br />';
						// 分组将关键词插入数据库
						foreach($result as $resultObj) {
							// 将数据插入数据库
							$dbDoFlag = $productMod->refreshKeywordTable($resultObj, $flag, $keywordData);
							// 判断是否操作数据库成功，若成功，则继续，否则，中断循环重新尝试刷新关键词表操作
							if ($dbDoFlag) {
								// 修改删除标记为不刷新关键词表
								$flag++;
							} else if (3 > $this->dbFlag) {
								// 打印错误日志
								echo '第'.$this->dbFlag.'次操作数据库刷新关键词表数据失败。<br />';
								// 将数据库调用次数标记加1
								$this->dbFlag = $this->dbFlag + 1;
								// 重置删除计数标记
								$flag = 0;
								// 中断循环
								break;
							} else if (2 < $this->dbFlag) {
								// 打印错误日志
								echo '第'.$this->dbFlag.'次操作数据库刷新关键词表数据失败。<br />';
								echo '三次操作数据库失败后，系统放弃本次操作，刷新关键词表失败！！！<br />';
								// 将数据库调用次数标记加1
								$this->dbFlag = $this->dbFlag + 1;
								// 中断循环
								break;
							}
						}
					}
				} else if (3 > $this->iaoFlag) {
					// 打印错误日志
					echo '第'.$this->iaoFlag.'次调用远程接口获取关键词数据失败，数据为空。<br />';
					// 将接口调用次数标记加1
					$this->iaoFlag = $this->iaoFlag + 1;
					// 再次执行本方法
				} else if (2 < $this->iaoFlag) {
					// 打印错误日志
					echo '第'.$this->iaoFlag.'次调用远程接口获取关键词数据失败，数据为空。<br />';
					echo '三次调用接口失败后，系统放弃本次操作，刷新关键词表失败！！！<br />';
					// 将接口调用次数标记加1
					$this->iaoFlag = $this->iaoFlag + 1;
					// 中断循环
					break;
				}
			}
    }
	    
    /**
     * 排序分组函数，可升降序
     * 
     * @param array() $arr 需要排序的数组
     * @param String $keys 需要排序的字段
     * @param String $type 需要排序的类型  升序  降序
     * @return array() 排序分组后的二维数组
     */
    function arraySortGroup($arr,$keys,$type='desc'){
    	// 初始化用于排序的键数组
		$keysvalue = array();
		// 初始化分组子元素结果数组
		$new_array = array();
		// 初始化最终结果数组
		$result = array();
		// 初始化分组数量标记
		$countFlag = 0;
		// 取出需要排序的键，用以排序
		foreach ($arr as $k=>$v){
			$keysvalue[$k] = $v[$keys];
		}
		
		// 根据排序类型进行排序
		if($type == 'asc'){
			// 升序
			asort($keysvalue);
		}else{
			// 降序
			arsort($keysvalue);
		}
		// 把数组的内部指针指向第一个元素，方便生成排序后的数组
		reset($keysvalue);
		
		// 循环生成排序和分组后的数组
		foreach ($keysvalue as $k=>$v){
			// 如果计数标记小于100，则正常添加数组
			if (100 > $countFlag) {
				// 正常添加结果
				$new_array[$k] = $arr[$k];
				// 计数标记加1
				$countFlag++;
			} else {
				// 计数标记大于等于100，填满了一个分组，重新初始化另一个分组，并向结果分组添加已填充满的分组
				// 添加已填充满的分组
				array_push($result, $new_array);
				// 重新初始化临时结果数组
				$new_array = array();
				// 填充结果
				$new_array[$k] = $arr[$k];
				// 重置计数标记
				$countFlag = 1;
			}
		}
		// 填充最后一组分组
		array_push($result, $new_array);
		
		// 返回结果
		return $result;
	}
	
	/**
	 * 同步3.0以前版本打包时间没有对应广告位置的数据
	 */
	public function actionSyncAdPositon(){
		// 定义广告位的初始值
		$initPosition = array(
			array(
				"adKey" => "index_chosen",
				"adName" => "首页",
				"floorPrice" => 500,
				"adProductCount" => 10,
			),
			array(
				"adKey" => "class_recommend",
				"adName" => "分类页",
				"floorPrice" => 1,
				"adProductCount" => 8,
			),
			array(
				"adKey" => "search_complex",
				"adName" => "搜索页",
				"floorPrice" => 1,
				"adProductCount" => 10,
			),
		);
		// 查询出哪些打包计划在广告位置表里没有对应的数据
		$ids = $this->_productMod->queryNotSyncShowDateIds();
		if(!empty($ids)){
			foreach($initPosition as $data){
				foreach($ids as $id){
					// 添加广告位置信息
					$this->_productMod->addAdPosition($data,$id["id"]);
				}
			}
		}else{
			print_r('<br />=> 没有未同步的打包计划的id');
		}
		//打印到页面呈现
		print_r('<br />=> 运行完毕');
	}
	
	/**
	 * 同步网站广告位
	 */
	public function actionSyncTuniuPositon(){
		// 判断参数合法性
		if (empty($_GET['adKeyType'])) {
			print_r('<br />=> 参数错误，请输入广告位大类adKeyType');
			return;
		}
		// 初始化调用参数
		$params = array();
		$params['adKeyType'] = $_GET['adKeyType'];
		if (!empty($_GET['startCityCode'])) {
			$params['startCityCode'] = $_GET['startCityCode'];
		} else {
			$params['startCityCode'] = -1;
		}
		if (!empty($_GET['unitFloorPrice'])) {
			$params['unitFloorPrice'] = $_GET['unitFloorPrice'];
		} else {
			$params['unitFloorPrice'] = 0;
		}
		
		try {
			// 默认初始化标记为广告位类型错误
			$cityCode = -2;
			// 分类同步广告位
			if (5 == $params['adKeyType']) {
				// 调用接口，同步数据
				$cityCode = $this->_channelMod->syncChannelPositon($params);
			}
			
			if (-2 == $cityCode) {
				print_r('<br />=> 广告位类型错误，同步失败');
			} else if (-1 == $cityCode) {
				print_r('<br />=> 广告位同步成功');
			} else {
				print_r('<br />=> 部分广告位同步成功,但以下城市的广告位没有同步:'.$cityCode);
			}
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            print_r('<br />=> 广告位同步失败，发生异常:'.$e);
        }
	}
	
	public function actionUpdateTime(){
		$this->_packageplanMod->updateTime();
    	print_r('<br />=> 更新发布时间成功');
    }
    
    public function actionStablePackPlan() {
    	// 判断参数合法性
		if (empty($_GET['dick']) || 'daoyongzhequsi' !== $_GET['dick'] || empty($_GET['rows']) || empty($_GET['flag'])) {
			print_r('<br />=> 参数错误');
			return;
		} 
		
		$param['rows'] = $_GET['rows'];
		$param['flag'] = $_GET['flag'];
		$return = $this->_packageplanMod->stablePackPlan($param);
		print_r('<br />=> 执行完毕');
		var_dump($return);
    }
    
    /**
     * 同步分类页位置数据
     */
    public function actionSyncWebClass() {
    	// $return = $this->_clsrecommendMod->syncWebClassAndCity();
    	try {
    		if (!empty($_GET['startCityCode'])) {
    			$this->_clsrecommendMod->syncWebClassPosition($_GET);
    		} else {
    			throw new Exception();
    		}
    		
    	} catch(Exception $e) {
    		print_r('<br />=> 执行失败');
    	}
    	
		print_r('<br />=> 执行完毕');
    }
    
    /**
     * 同步包场位置数据
     */
    public function actionSyncBuyout() {
    	try {
    		if (!empty($_GET['showDateId'])) {
    			$this->_productMod->syncBuyout($_GET['showDateId']);
    		} else {
    			throw new Exception();
    		}
    		
    	} catch(Exception $e) {
    		print_r('<br />=> 执行失败');
    	}
    	
		print_r('<br />=> 执行完毕');
    }
    
}
