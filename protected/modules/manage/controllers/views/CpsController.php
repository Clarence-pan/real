<?php
/** UI呈现接口 | Hagrid 频道页
 * Hagrid user interfaces for inner UI system.
 * @author p-sunhao@2013-11-12
 * @version 1.0
 * @func doRestGetPackageDate
 * @func doRestPostPackageDate
 */
Yii::import('application.modules.manage.models.cps.CpsMod');

/**
 * CPS UI接口类
 * @author p-sunhao@2014-08-30
 */
class CpsController extends restfulServer{
	 
	/**
	 * CPS操作类
	 */
	private $cpsMod = null;
	
	/**
	 * 默认构造函数
	 */ 
	function __construct() {
		// 初始化CPS操作类
		$this->cpsMod = new CpsMod();
	}
	
	/**
	 * 获取费率
	 */
    public function doRestGetExpenseratio($url, $data) {
    	
    	// 校验参数
		if (!empty($data['uid']) && !empty($data['nickname'])) {
			// 配置费率
			$result = $this->cpsMod->getExpenseRatio($data);
			$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}
    }
	
	/**
	 * 配置费率
	 */
    public function doRestPostConfigexpenseratio($data) {
    	
    	// 校验参数
		if (!empty($data['expenseRatio']) && !empty($data['uid']) && !empty($data['nickname'])) {
			// 配置费率
			$result = $this->cpsMod->configExpenseRatio($data);
			$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}
    }

    /**
     * 获取推广管理报表
     */
    public function doRestGetCpsShowReport($url, $data) {
        // 校验参数
        if (!empty($data['uid']) && !empty($data['nickname'])) {
            $result = $this->cpsMod->getShowReport($data);
            if ($result['success']) {
                $this->returnRest($result['data']);
            } else {
                $this->returnRest($result['msg'], true, 230015, $result['msg']);
            }
        } else {
            // 返回参数不正确
            $this->returnRest(array(), false, 210000, '参数不正确！');
        }
    }
    /**
     * 获取推广管理报表导出的EXCEL
     */
    public function doRestGetCpsShowReportExcel($url, $param) {
        // 初始化导出标题
        $fileName = '招客宝推广管理信息表-' . date('Ymdhis');
        // 输出Excel文件头
        header('Content-Type: application/vnd.ms-excel;charset=gbk');
        header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
        header('Cache-Control: max-age=0');
        // PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        // 输出Excel列头信息
        $head = array('供应商ID', '供应商名称', '结算方式', '结算时间', '订单编号', '下单时间', '签约时间', '出游归来时间', '产品推广开始时间', '产品推广结束时间', '线路编号', '采购单号', '采购成本', '推广费用比例', '推广费用', '结算状态', '发票是否开具', '疑问');
        // CSV的Excel支持GBK编码，一定要转换，否则乱码
        $head = $this->mapUtf8ToGbk($head);
        // 写入列头
        fputcsv($fp, $head);
        unset($head);

        $step = 500;
        for ($offset = 0; ; $offset += $step) {
            $param['start'] = $offset;
            $param['limit'] = $step;

            $result = $this->cpsMod->getShowReport($param);
            $result = $result['data']['rows'];
            if (empty($result)) {
                break;
            }
            $result = $this->mapShowReportValues($result);
            $this->mapUtf8ToGbk($result);
            foreach ($result as &$row) {
                fputcsv($fp, $row);
            }

            if (count($result) < $step) {
                break;
            }

            unset($result);
        }
    }
    private function mapShowReportValues(&$valuesRows){
        foreach ($valuesRows as &$row) {
            $row['purchaseState'] = ($row['purchaseState'] ? "已结算" : "未结算");
            $row['invoiceState'] = ($row['invoiceState'] ? "已开" : "未开");
        }
        return $valuesRows;
    }

    private function mapUtf8ToGbk(&$values) {
        if (is_array($values)){
            foreach ($values as &$value) {
                $value = $this->mapUtf8ToGbk($value);
            }
        } else if (is_string($values)){
            $values = iconv('utf-8', 'gbk', $values);
        } else {
            $values = strval($values);
            $values = iconv('utf-8', 'gbk', $values);
        }
        return $values;
    }
}