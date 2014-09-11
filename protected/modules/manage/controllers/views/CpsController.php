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
    public function doRestGetExpenseratio($data) {
    	
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

    public function doRestGetCpsShowReport($url, $data) {
        $result = $this->cpsMod->getCpsShowReport($data);
        if ($result['success']) {
            $this->returnRest($result['data']);
        } else {
            $this->returnRest($result['msg'], true, 230015, $result['msg']);
        }
    }
}