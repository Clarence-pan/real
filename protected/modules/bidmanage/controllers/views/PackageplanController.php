<?php
/**
 * UI呈现接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-06-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.pack.PackageplanMod');
Yii::import('application.modules.bidmanage.models.product.ProductMod');
// Yii::import('application.modules.bidmanage.models.bid.BidProduct');
// Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');
class PackageplanController extends restUIServer {

	private $packageplanMod;
	
    private $_productMod;
    // private $bidProduct;
    // private $iaoProductMod;
    
    function __construct() {
        $this->packageplanMod = new PackageplanMod();
		$this->_productMod = new ProductMod();
    }
    
    /**
     * 查询供应商账户信息
     */
    public function doRestGetAgencyconsumption($url, $data) {
    	// 获取供应商账户信息
        $result = $this->packageplanMod->getAgencyConsumption($this->getAccountId());
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 查询未推广打包列表
     */
    public function doRestGetPackageplansinit($url, $data) {
    	$data['accountId'] = $this->getAccountId();
    	// 查询供应商账户信息
    	$result = $this->packageplanMod->getPackPlanList($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 查询打包计划产品明细
     */
    public function doRestGetPlanproductdetail($url, $data) {
    	// 若参数正确，则查询打包计划产品明细
    	if ((!empty($data['start']) || 0 == $data['start']) && !empty($data['limit']) && !empty($data['packPlanId'])) {
            // 获取供应商信息
            $result = $this->packageplanMod->getPlanProductDetail($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
	}
    
    /**
     * 查询推广列表
     */
    public function doRestGetPackplanspreadlist($url, $data) {
    	// 若参数正确，则查询打包计划产品明细
    	if ((!empty($data['start']) || 0 == $data['start']) && !empty($data['limit'])) {
    		$data['accountId'] = $this->getAccountId();
            // 获取供应商信息
            $result = $this->packageplanMod->getPackPlanSpreadList($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
	}
    
    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /manager
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-查询产品经理
     */
    public function doRestGetManager ($url, $data) {
        $params = array(
            'managerName' => $data['managerName'],
        );
        $managerInfo = $this->_productMod->getManagerName($params);
        $this->returnRest($managerInfo);
    }
    
    /**
     * 获取列表头数量
     */
    public function doRestGetPackplantotalcount($url, $data) {
    	$data['accountId'] = $this->getAccountId();
    	$result = $this->packageplanMod->getPackPlanTotalCount($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }

	/**
     * 发布打包计划
     */
    public function doRestPostSubmitpackplan($data) {
    	// 若参数正确，则发布打包计划
    	if (!empty($data['packPlanId']) && !empty($data['packPlanPrice'])) {
    		$data['accountId'] = $this->getAccountId(); 
            // 发布打包计划
            $result = $this->packageplanMod->submitPackPlan($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }

}