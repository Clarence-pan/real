<?php
/**
 * UI呈现接口 | 收客宝 产品相关
 * Buckbeek product interfaces for inner UI system.
 * @author wanglongsheng@2013-01-04
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.pack.PackageplanMod');
// Yii::import('application.modules.bidmanage.models.bid.BidProduct');
// Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');
class PackageplanController extends restSysServer {

     private $packageplanMod;
    // private $bidProduct;
    // private $iaoProductMod;
    
    function __construct() {
        $this->packageplanMod = new PackageplanMod();
        // $this->bidProduct = new BidProduct();
        // $this->iaoProductMod = new IaoProductMod();
    }
    
    /**
     * 查询供应商信息
     */
    public function doRestGetAgencyinfo($url, $data) {
    	// 若参数正确，则查询供应商信息
    	if (!empty($data['agencyId'])) {
            // 获取供应商信息
            $result = $this->packageplanMod->getAgencyInfo($data['agencyId']);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 获取搜索的产品
     */
    public function doRestGetPlaproduct($url, $data) {
    	// 若参数正确，则查询搜索的产品
    	if (!empty($data['agencyId']) && (!empty($data['start']) || 0 == $data['start']) 
    		&& !empty($data['limit']) && !empty($data['productType'])) {
            // 获取供应商信息
            $result = $this->packageplanMod->getPlaProduct($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 查询打包列表
     */
    public function doRestGetPackageplans($url, $data) {
    	// 若参数正确，则查询打包列表
    	if ((!empty($data['start']) || 0 == $data['start']) && !empty($data['limit'])) {
    		$data['isHagrid'] = 1;
            // 获取供应商信息
            $result = $this->packageplanMod->getPackPlanList($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 查询打包计划产品详情
     */
    public function doRestGetPlanproductdetail($url, $data) {
    	// 若参数正确，则查询打包计划产品详情
    	if ((!empty($data['start']) || 0 == $data['start']) && !empty($data['limit']) && !empty($data['packPlanId'])) {
            // 获取打包计划产品详情
            $result = $this->packageplanMod->getPlanProductDetail($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 保存打包计划
     */
    public function doRestPostPackplan($data) {
    	// 若参数正确，则保存打包计划
    	if ((!empty($data['packPlanId']) || 0 == $data['packPlanId']) && !empty($data['packPlanName']) && !empty($data['endDate'])
    		&& !empty($data['planPrice']) && !empty($data['agencyId']) && !empty($data['managerId']) && !empty($data['saveFlag'])
    		&& (1 == $data['saveFlag'] || 2 == $data['saveFlag']) && !empty($data['isAgencySubmit'])) {
            // 保存打包计划
            $result = $this->packageplanMod->savePackPlan($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }

	/**
     * 发布打包计划
     */
    public function doRestPostSubmitpackplan($data) {
    	// 若参数正确，则发布打包计划
    	if (!empty($data['packPlanId']) && !empty($data['packPlanPrice']) && !empty($data['agencyId'])) { 
            // 发布打包计划
            $result = $this->packageplanMod->submitPackPlan($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 保存打包计划线路
     */
    public function doRestPostPackplanproduct($data) {
    	// 若参数正确，则保存打包计划线路
    	if ((!empty($data['packState']) || 0 == $data['packState']) && !empty($data['packPlanId'])) {
            // 发布打包计划
            $result = $this->packageplanMod->savePackPlanProduct($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 删除打包计划
     */
    public function doRestPostDeletepackplan($data) {
    	// 若参数正确，则删除打包计划
    	if (!empty($data['packPlanId'])) {
            // 删除打包计划
            $result = $this->packageplanMod->deletePackPlan($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 网站上线产品
     */
    public function doRestPostOnlinepackplan($data) {
    	// 若参数正确，则上线产品
    	if (!empty($data['data']) && is_array($data['data'])) {
            // 上线产品
            $result = $this->packageplanMod->tuniuOnLineProducts($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }
    
    /**
     * 网站下线产品
     */
    public function doRestPostOfflinepackplan($data) {
    	// 若参数正确，则下线产品
    	if (!empty($data['data']) && is_array($data['data'])) {
            // 下线产品
            $result = $this->packageplanMod->tuniuOffLineProducts($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }    
    
    /**
     * 查询产品状态
     */
    public function doRestGetPlanstatus($url, $data) {
    	// 若参数正确，则下线产品
    	if (!empty($data['packPlanId'])) {
            // 下线产品
            $result = $this->packageplanMod->getPlanStatus($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    }    
    
}