<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 2:35 PM
 * Description: release_product.php
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

//@TODO chenjinlong 仅测试阶段使用
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

function script(){
	class UpdateProduct
	{
		private $_productMod;

		function __construct()
		{
			$this->_productMod = new ProductMod();
		}

		public function runRelease()
		{
			print_r('<br />=> 更新供应商产品信息');
			// 更新产品信息
			$this->_productMod->updateProductProcess();

			//打印到页面呈现
			if(LOG_TO_FRONTPAGE)
				print_r('<br />=> 运行完毕');
		}

	}
	//设置脚本运行时控选项为不限
	set_time_limit(0);
	$updateProduct = new UpdateProduct;
	$updateProduct->runRelease();
}

ScriptRuner::run('script');
