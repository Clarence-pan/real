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
Yii::import('application.modules.bidmanage.models.product.ProductMod');
Yii::import('application.modules.bidmanage.models.bid.BidProduct');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');
class ProductController extends restUIServer {

    private $product;
    private $bidProduct;
    private $iaoProductMod;
    
    function __construct() {
        $this->product = new ProductMod();
        $this->bidProduct = new BidProduct();
        $this->iaoProductMod = new IaoProductMod();
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=> ,'token'=>
     * ,'searchKey'=> ,'searchType'=> ,startCityCode'=> ,'checkerFlag'=> ,'sortType'=> ,
     * 'productType'=> ,'isAdded'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /allproduct
     * @method GET
     * @param string $url 
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","searchType":2,"productType":1,"start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】获取供应商产品列表接口
     */
    public function doRestGetAllProduct($url, $data) {
        $user = $this->getAccountInfo();
        
        $searchType = 0;
        if (!empty($data['searchType'])) {
        	$searchType = intval($data['searchType']) ? intval($data['searchType']) : 2;
        }
        
        // 预初始化参数
        $params = array();
        // 分类初始化新旧招客宝参数
        if (!empty($data['newVersion']) && 1 == intval($data['newVersion'])) {
        	// 转换自助游类型
        	if ('3' == $data['productType']) {
        		$data['productType'] = '3_3';
        	}
        	// 初始化新招客宝参数
        	// 预初始化默认startcitycode
    	    $startcitycode = 0;
    	    // 如果类型为跟团游或自助游，则初始化startcitycode
    	    if (in_array($data['productType'], array('3_3','1','4','5',)) &&
                ("search_complex" == $params['adKey'] || "class_recommend" == $params['adKey'])) {
    	    	$startcitycode = intval($data['startCityCode']);
    	    }
			// 分类初始化跟团游和自助游，门票参数
			if ('1' == $data['productType'] || '4' == $data['productType']) {
				// 跟团游
				$params = array(
		            'vendorId' => $user['vendorId'],
    		        'accountId' => $this->getAccountId(),
	    	        'start' => intval($data['start']),
	        	    'limit' => intval($data['limit']),
	    	        'productType' => $data['productType'],
	    	        'productId' => $data['productId'],
    	    	    'searchType' => $searchType,
	    	        'searchKey' => $data['searchKey'],
    	         	'startCityCode' => $startcitycode,
    	        	'checkerFlag' => intval($data['checkerFlag']),
	    	        'sortType' => intval($data['sortType'])
        		);
			} else {
				// 自助游和门票
				$params = array(
                    'vendorId' => $user['vendorId'],
    		        'accountId' => $this->getAccountId(),
	    	        'start' => intval($data['start']),
	        	    'limit' => intval($data['limit']),
	    	        'productType' => $data['productType'],
	    	        'productId' => $data['productId'],
    	    	    'searchType' => $searchType,
	    	        'searchKey' => $data['searchKey'],
    	         	'startCityCode' => $startcitycode,
    	        	'checkerFlag' => intval($data['checkerFlag']),
	    	        'sortType' => intval($data['sortType'])
        		);
			}
        } else {
            // 初始化旧招客宝参数
            $params = array(
                'vendorId' => $user['vendorId'],
                //'vendorId' => $data['vendorId'],
                'accountId' => $this->getAccountId(),
                'start' => intval($data['start']),
                'limit' => intval($data['limit']),
                'productType' => $data['productType'],
                'searchType' => $searchType,
                'searchKey' => $data['searchKey'],
                // 'startCityCode' => intval($data['startCityCode']),
                'startCityCode' => 0,
                'checkerFlag' => intval($data['checkerFlag']),
                'sortType' => intval($data['sortType'])
            );
        }
        
        $productList = $this->product->readAllProduct($params);
        if (empty($productList) || empty($productList['rows'])) {
            $this->returnRest(array());
        } else {
            //减少执行CDbConnect次数 mdf by chenjinlong 20121226
            $productIdArr = array();
            foreach ($productList['rows'] as $product) {
            	if($product['productId']){
                    $productIdArr[] = $product['productId'];
            	}
            }
            
            if($data['productType'] ==33){
            	$addedInfo = $this->product->getIsAddedProduct($productIdArr,$user,$data['productType']);
            }else{
            	$addedInfo = $this->product->getIsAddedProduct($productIdArr,$user);
            }
            		
            $productArray = array();
            foreach ($productList['rows'] as &$product) {
            	$isAdded = 0;
            	foreach($addedInfo as $info){
            		if($info['product_id']== $product['productId'] && $info['product_type']== $product['productType'] && $info['del_flag'] == 0){
            			$isAdded = 1;
            			break;
            		}
            	}
                $product['isAdded'] = $isAdded;
                $productArray[] = $product;
            }
            
            $products = array(
                'count' => $productList['count'],
                'rows' => $productArray,
            );
            $this->returnRest($products);
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /dateparam
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】查询推广位置列表打包时间参数-3.0
     */
    public function doRestGetDateParam($url, $data) {
        $params = array(
            'bidState' => $data['bidState'],
        );
        $dateParam = $this->product->getDateParam($params);
        if ($dateParam) {
            $this->returnRest($dateParam);
        } else {
            $this->returnRest(array());
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /newlist
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】查询推广位置列表-3.0
     */
    public function doRestGetNewList($url, $data) {
        $params = array(
            'accountId' => $this->getAccountId(),
            'bidState' => $data['bidState'],
            'showDateId' => $data['showDateId'],
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'],
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10,
        );
        $newProductList = $this->product->getNewProductList($params);
        $newProductCount = $this->product->getNewProductListCount($params);
        if (count($newProductList) > 0) {
            $this->returnRest(array('count' => $newProductCount, 'rows' => $newProductList));
        } else {
            $this->returnRest(array('count' => 0, 'rows' => array()), true, 230000, array());
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /newcount
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】查询推广位置列表tab页条数-3.0
     */
    public function doRestGetNewCount($url, $data) {
        $params = array(
            'accountId' => $this->getAccountId(),
            'bidState' => $data['bidState'],
            'showDateId' => $data['showDateId'],
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'],
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10,
        );
        $params['bidState'] = 1;
        $biding = $this->product->getNewProductListCount($params);
        $params['bidState'] = 2;
        $bidSuccess = $this->product->getNewProductListCount($params);
        $params['bidState'] = 3;
        $spreading = $this->product->getNewProductListCount($params);
        $params['bidState'] = 4;
        $spreadSuccess = $this->product->getNewProductListCount($params);
        $params['bidState'] = -1;
        $spreadFail = $this->product->getNewProductListCount($params);
        if ($biding > 0 || $bidSuccess > 0 || $spreading> 0 || $spreadSuccess > 0 || $spreadFail > 0) {
            $this->returnRest(array('biding' => intval($biding),'bidSuccess' => intval($bidSuccess),
                'spreading' => intval($spreading),'spreadSuccess' => intval($spreadSuccess),'spreadFail' => intval($spreadFail)));
        } else {
            $this->returnRest(array('biding' => 0,'bidSuccess' => 0,'$spreading' => 0,'spreadFail' => 0,'spreadSuccess' => 0));
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /bidadinfo
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】查询推广产品查看列表-3.0
     */
    public function doRestGetBidAdInfo($url, $data) {
        $params = array(
            'accountId' => $this->getAccountId(),
            'adKey' => $data['adKey'],
            'showDateId' => $data['showDateId'],
            'startCityCode' => $data['startCityCode'],
            'webClass' => $data['webClassId'],
            'searchKeyword' => $data['searchKeyword'],
            'bidState' => $data['bidState'],
            'bidMark' => $data['bidMark'],
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10
        );
        $bidAdInfo = $this->product->getBidAdInfo($params);
        if (count($bidAdInfo) > 0) {
            $this->returnRest(array('count' => $bidAdInfo['count'], 'rows' => $bidAdInfo['rows']));
        } else {
            $this->returnRest(array('count' => 0, 'rows' => array()), true, 230000, array());
        }
    }
    
	/**
     * 查询搜索页广告位
     */
    public function doRestGetSearchad($url, $data) {
    	// 查询数据库
    	$result = $this->product->getKeywordData($data);
    	// 返回结果
    	$this->returnRest($result);
    }
    
    /**
     * 获取竞拍时间
     */
    public function doRestGetBiddate($url, $data) {
    	// 查询数据库
    	$result = $this->product->getBidDate();
    	// 返回结果
    	$this->returnRest($result);
    }
    
    /**
     * 获取广告位类型
     */
    public function doRestGetAdtype($url, $data) {
    	// 查询数据库
    	$result = $this->product->getPositionType();
    	// 返回结果
    	$this->returnRest($result);
    }
    
    /**
     * 获取出价查询列表
     */
    public function doRestPostBidlist($data) {
    	// 设置account_id参数
		$data['account_id'] = $this->getAccountId();
        // 生成竞价列表
        $result = $this->product->generateBidListNew($data);
        // 返回结果
        $this->returnRest($result);
    }
    
    /**
     * 获取查看和出价页面头
     */
    public function doRestGetHeadcommon($url, $data) {
    	// 设置account_id参数
		$data['account_id'] = $this->getAccountId();
    	// 获取查看和出价页面头
    	$result = $this->product->getHeadcommon($data);
    	// 返回结果
    	$this->returnRest($result);
    }
    
    /**
	 * 获取编辑列表
	 */
	public function doRestGetEditlist($url, $data) {
		// 设置account_id参数
		$data['account_id'] = $this->getAccountId();
    	// 获取查看和出价页面头
    	$result = $this->product->getEditList($data);
    	// 返回结果
    	$this->returnRest($result);
    }
    
    /**
	 * 获取编辑个性化
	 */
	public function doRestGetEditvas($url, $data) {
		// 设置account_id参数
		$data['accountId'] = $this->getAccountId();
		// 设置父账号标记
		$data['isFather'] = $this->getAdminFlag();
		// 设置登录名
		$data['subAgency'] = $this->getLoginName();
    	// 获取查看和出价页面头
    	$result = $this->product->getEditVas($data);
    	// 返回结果
    	$this->returnRest($result);
    }
    
    /**
	 * 保存编辑列表
	 */
	public function doRestPostEditlist($data) {
		// 设置account_id参数
		$data['accountId'] = $this->getAccountId();
		// 设置login_name参数
		$data['subAgency'] = $this->getLoginName();
		// 设置供应商ID参数
		$data['agencyId'] = $this->getAgencyId();
		// 设置父子供应商标记
		$data['isFather'] = $this->getAdminFlag();
		// 初始化广告位名称查询参数
        $adParam = array(
        	'showDateId' => 0,
			'adKey' => $data['rows'][0]['adKey'],
            'startCityCode' => $data['rows'][0]['startCityCode'],
			'searchKeyword' => $data['rows'][0]['searchKeyword'],
			'positionId' => 0,
			'webId' => 0,
			'webClassId' => $data['rows'][0]['webId'],
			'viewName' => '',
			'showDate' => '',
			'account_id' => $data['accountId']
        );
        // 查询广告位名称
        $adName = $this->product->getHeadcommon($adParam);
        // 设置广告位名称
        $data['viewName'] = $adName['viewName'];
    	// 获取查看和出价页面头
    	$result = $this->product->saveEditList($data);
    	// 返回结果
    	$this->returnRest(array(), $result['success'], 230000, $result['msg']);
    }
    
    /**
     * 招客宝3.0获取相似产品替换
     */
    public function doRestGetSimilarProduct($url, $data) {
 	    // 设置account_id参数
		$data['accountId'] = $this->getAccountId();
 	    // 获取分类页广告位
    	$result = $this->product->getSimilarProduct($data);
    	// 返回结果
    	$this->returnRest($result['data'], $result['success'], 230000, $result['msg']);
    }
    
    /**
     * 过滤出当前支持的广告位类型
     */
    public function doRestGetAvailableType($url, $data){
    	$result = $this->product->getAvailableType();
    	$data = array();
        if (!empty($result)) {
            foreach ($result as $a) {
                $temp = array();
                $temp['adKey'] = $a['adKey'];
                $temp['adName'] = $a['adName'];
                $data[] = $temp;
            }
        }
    	// 返回结果
    	$this->returnRest($data, true, 230000, 'success');
    }

    /**
     * 查询供应商日志
     */
    public function doRestGetAgencylog($url, $data){
    	// 设置默认登录名
    	if (empty($data['subAgency']) || '' == $data['subAgency']) {
    		$data['subAgencyDefault'] = $this->getLoginName();
    	}
    	$data['agencyId'] = $this->getAgencyId();
    	// 设置account_id参数
		$data['accountId'] = $this->getAccountId();
		$data['isFather'] = $this->getAdminFlag();
    	// 查询供应商日志
        $result = $this->product->getAgencyLog($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }

    /**
     * 查询当前可参与竞拍的首页的广告位
     */
    public function doRestGetIndexAdKey($url, $data) {
        // 设置account_id参数
        $data['accountId'] = $this->getAccountId();
        $result = $this->product->getIndexAdKey($data);
        $this->returnRest($result);
    }
    
    /**
     * 获得广告位信息完整性
     */
    public function doRestGetAdwholeness($url, $data) {
    	// 设置account_id参数
        $data['accountId'] = $this->getAccountId();
        // 查询广告位信息完整性
        $result = $this->product->getAdWholeness($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }

}