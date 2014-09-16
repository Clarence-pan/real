<?php
/**
 * Coypright © 2014 Tuniu Inc. All rights reserved.
 * Author: wenrui
 * Date: 2014-01-24
 * Time: 11:14 AM
 * Description: sync_ad_position.php
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

/**
 * 同步3.0以前版本打包时间没有对应广告位置的数据
 */
function script(){
	class SyncAdPositon
	{
		private $_productMod;

		function __construct()
		{
			$this->_productMod = new ProductMod();
		}

		public function runRelease()
		{
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
//			var_dump($ids);
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
			if(LOG_TO_FRONTPAGE)
				print_r('<br />=> 运行完毕');
		}

	}
	//设置脚本运行时控选项为不限
	set_time_limit(0);
	$syncAdPositon = new SyncAdPositon;
	$syncAdPositon->runRelease();
}

ScriptRuner::run('script');
