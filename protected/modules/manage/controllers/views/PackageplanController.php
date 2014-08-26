<?php
/** UI呈现接口 | Hagrid 打包计划
 * Hagrid user interfaces for inner UI system.
 * @author p-sunhao@2014-06-10
 * @version 1.0
 * @func doRestGetAgencyinfo
 * @func doRestPostPackageDate
 */
Yii::import('application.modules.manage.models.pack.PackagePlanMod');

/**
 * 打包计划UI接口类
 * @author p-sunhao@2014-06-10
 */
class PackageplanController extends restfulServer{
	 
	/**
	 * 打包计划操作类
	 */
	private $_packagePlanMod = null;
	
	/**
	 * 默认构造函数
	 */ 
	function __construct() {
		// 初始化打包计划操作类
		$this->_packagePlanMod = new PackagePlanMod();
	}
	
	/**
	 * 根据供应商ID获取供应商信息
	 */
	public function doRestGetAgencyinfo($url, $param) {
		// 查询供应商信息
		$result = $this->_packagePlanMod->getAgencyInfo($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 获取搜索的产品列表
	 */
	public function doRestGetPlaproduct($url, $param) {
		// 查询供应商信息
		$result = $this->_packagePlanMod->getPlaProduct($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 获取打包计划列表
	 */
	public function doRestGetPackageplans($url, $param) {
		// 查询打包计划列表
		$result = $this->_packagePlanMod->getPackagePlans($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	
	/**
	 * 获取打包计划产品详情
	 */
	public function doRestGetPlanProductDetail($url, $param) {
		// 查询打包计划产品详情
		$result = $this->_packagePlanMod->getPlanProductDetail($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 保存打包计划
	 */
	public function doRestPostPackplan($param) {
		// 保存打包计划
		$result = $this->_packagePlanMod->savePackPlan($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 发布打包计划
	 */
	public function doRestPostSubmitpackplan($param) {
		// 发布打包计划
		$result = $this->_packagePlanMod->submitPackPlan($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 保存打包计划线路
	 */
	public function doRestPostPackplanproduct($param) {
		// 保存打包计划线路
		$result = $this->_packagePlanMod->savePackPlanProduct($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 删除打包计划
	 */
	public function doRestPostDeletepackplan($param) {
		// 保存打包计划线路
		$result = $this->_packagePlanMod->deletePackPlan($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 查询线路状态
	 */
	public function doRestGetPlanstatus($url, $param) {
		// 查询线路状态
		$result = $this->_packagePlanMod->getPlanStatus($param);
		// 返回结果
		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
	}
	
	/**
	 * 导出打包计划列表
	 */
	public function doRestGetPackageplansexcel($url, $param) {
		// 校验参数
		if (!empty($data['isExcel'])) {
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}
		// 初始化导出标题
        $timeNow = date('Ymdhis');
        $fileName = '招客宝信息统计表-'.$timeNow;
		// 输出Excel文件头 
		header('Content-Type: application/vnd.ms-excel;charset=gbk');
		header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
		header('Cache-Control: max-age=0');
		// PHP文件句柄，php://output 表示直接输出到浏览器 
		$fp = fopen('php://output', 'a');
		// 输出Excel列头信息
		$head = array('序号', '打包服务名称', '供应商编号', '供应商品牌名', '产品经理', '打包截止时间', '打包价格', '打包线路', '添加时间', '发布时间', '打包状态', '是否供应商确认');
		foreach ($head as $i => $v) {
		    // CSV的Excel支持GBK编码，一定要转换，否则乱码 
		    $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 写入列头 
		fputcsv($fp, $head);
		// 计数器
        $start = 0;
        $limit = 500;
        
        do{
        	$param['start'] = $start;
        	$param['limit'] = $limit;
        	$result = $this->_packagePlanMod->getPackagePlans($param);
        	$rows = $result['data']['rows'];
        	$count = $result['data']['count'];
	        if ($rows) {
				foreach ($rows as $row) {
				    $list = array($row['packPlanId'],$row['packPlanName'],$row['agencyId'],$row['agencyName'],$row['managerName'],strval($row['endDate']),$row['planPrice'],strval('"'.$row['productArr'].'"'),strval($row['addDate']),
				    			strval($row['releaseDate']),$row['planStateName'],$row['isAgencySubmitName']);
				    foreach ($list as $i => $v) {
				        $list[$i] = iconv('utf-8', 'gbk', $v);
				    }
				    fputcsv($fp, $list);
				}
	        } else {
	        	break;
	            // $this->returnRest(array(), false, 230116, '下载产品列表表格失败');
	        }
        	$start += 500;
        	
        	if($start > $count){
        		break;
        	}
        } while ( 1==1 );
	}
	 
}
?>
