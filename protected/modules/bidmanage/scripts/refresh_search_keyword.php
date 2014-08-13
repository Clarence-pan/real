<?php
/*
 * Created on 2013-12-31
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once 'ScriptApplication.php';
echo '访问'.__FILE__.'成功<br />';
define('LOG_TO_FRONTPAGE',true);

function searchKeyWord(){
    class Search
    {
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
        public function searchiao(){
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
    }
    
    // 设置脚本运行时控选项为不限
    set_time_limit(0);
    // 初始化脚本执行类
    $search = new Search();
    // 执行脚本方法
    $search->searchiao();
}

// 执行脚本
ScriptRuner::run('searchKeyWord');
?>