<?php
class BuckbeekIao {

    /**
     * 删除供应商收客宝帐号
     * @author chenjinlong 20121218
     * @param $inParams
     * @return bool
     */
    public static function deleteBuckbeekAccount($inParams) {
        $client = new RESTClient;
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/create-deleteaccount';
        $paramArr = array(
            'agencyId' => $inParams['agencyId'],
            'uid' => $inParams['uid'],
            'nickname' => $inParams['nickname'],
        );
        try{
            $response = $client->post($url, $paramArr);
            if($response['success']){
                return array(
                    'flag' => true,
                    'msg' => '删除成功',
                );
            }else{
                return array(
                    'flag' => false,
                    'msg' => $response['msg'],
                );
            }
        }catch (Exception $e){
            if(YII_DEBUG)
                print_r($e->getTraceAsString());
            return array(
                'flag' => false,
                'msg' => '删除接口调用失败',
            );
        }
    }
    
     public static function updateBuckbeekVendorDataLevel($inParams) {
        $client = new RESTClient;
        //这是bb代码整改后的路径，bb合入test放开
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/create-auditvendorinfo';
        $paramArr = array(
            'accountId' => $inParams['vendorId'],
            'certFlag' => $inParams['certFlag'],
        );
        try{
            $response = $client->post($url, $paramArr);
            if($response['success']){
                return array(
                    'flag' => true,
                    'msg' => '删除成功',
                );
            }else{
                return array(
                    'flag' => false,
                    'msg' => $response['msg'],
                );
            }
        }catch (Exception $e){
            if(YII_DEBUG)
                print_r($e->getTraceAsString());
            return array(
                'flag' => false,
                'msg' => '删除接口调用失败',
            );
        }
    }
    
/**
     * 获取收客宝信息接口
     * @param array $params
     * @return array
     */
    public static function getVerdorList($params){

        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/vendorlist';

        try {
            $response = $client->get($url, $params);
                
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        
        if($response['success']) {
            return $response['data'];
        }
        
        return array();
    }
    
    /**
     * 通过供应商获得收客宝ID接口
     * @param array $params
     * @return array
     */
    public static function getIdByAgency($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/accountids';

        try {
            $response = $client->get($url, $params);
            if ($response['success']) {
                return $response['data'];
            }else{
                return array();
            }
        } catch (Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
    }
    
    /**
     * 新增收客宝帐号接口
     * @param array $params
     * @return array
     */
    public static function addVendorAccount($params){

        $agencyId = $params['vendorId'];
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/addaccount';
      
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        
        if($response['success']) {
            return $agencyId;
        }
        
        return array();
    }

    /**
     * 获取指定日期的推广产品列表(新增：：查询产品showList)
     */
    public static function getReleaseProductArray($param)
    {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/BidShowList';
        $params = array(
            'accountId' => $param['account_id'],
            'showDate' => $param['show_date'],
        );
        try {
            $response = $client->get($url, $params);
            if($response['success']) {
                $renderRows = array();
                if(!empty($response['data'])){
                    foreach($response['data'] as $row)
                    {
                        $renderRows[] = array(
                            'id' => $row['id'],
                            'account_id' => $row['accountId'],
                            'product_id' => $row['productId'],
                            'bid_date' => $row['bidDate'],
                            'ad_key' => $row['adKey'],
                            'cat_type' => $row['catType'],
                            'web_class' => $row['webClass'],
                            'start_city_code' => $row['startCityCode'],
                            'bid_price' => $row['bidPrice'],
                            'ranking' => $row['ranking'],
                            'bid_id' => $row['bidId'],
                            'product_type' => $row['productType'],
                        );
                    }
                }

                return $renderRows;
            }else{
                return array();
            }
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }

    }

    /**
     * 招客宝改版-查询出发城市信息
     *
     * @param string $queryParams
     * @return array
     */
    public static function getDepartureCities($queryParams='') {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/departurelist';

        $params = array(
            'cityCode' => -1, //获取所有出发城市信息
        );

        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }else{
            return array();
        }
    }

    /**
     * 获取产品分类接口（tuniu.com）
     * @param array $productArr
     * @return array
     */
    public static function getProductClassification($productArr) {
        $client = new RESTClient();
        $url = Yii::app()->params['TUNIU_HOST'].'interface/restful_interface.php';

        $params = array(
            'func' => 'product.queryRouteBasicInfoArray',
            'params' => $productArr
        );

        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }

        if($response['success']) {
            return $response['data'];
        }

        return array();
    }

    /**
     * 新增或更新bb_effect记录条目(新增：：更新概况条目内容)
     */
    public static function updateShowProductEffectData($params)
    {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/UpdateSta';
        try {
            $response = $client->post($url, $params);
            if($response['success']) {
                return $response;
            }else{
                return array();
            }
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }

    }


    /**
     * 获得打包时间信息
     * @param array $params
     * @return array $response
     */
    public static function getPackageDate($params)
    {
    	// 初始化客户端对象
        $client = new RESTClient();
        // 初始化URL
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/quePakDat';
		try {
        	// 调用接口
            return $client->get($url, $params);
        }catch(Exception $e) {
        	// 打印错误日志
            Yii::log($e, 'warning');
            // 返回错误结果
            return array(array(), false, 230115, '接口调用失败！');
        }

    }
    
    /**
     * 新增或修改打包时间信息
     * @param array $params
     * @return array $response
     */
    public static function postPackageDate($params)
    {
	    // 初始化客户端对象
        $client = new RESTClient();
        // 初始化URL
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-savPakDat';
        try {
        	// 调用接口
            return $client->post($url, $params);
        }catch(Exception $e) {
        	// 打印错误日志
            Yii::log($e, 'warning');
            // 返回错误结果
            return array(array(), false, 230115, '接口调用失败！');
        }

    }
    
    /**
     * 获取招客宝广告信息接口
     * @param array $params
     * @return array
     */
    public static function getAdMessage($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/admessage';
        try {
            $response = $client->get($url, $params);

        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 更新招客宝广告信息接口
     * @param array $params
     * @return array
     */
    public static function updateAdMessage($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/create-admessage';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        return $response;
    }
    /**
     * 查询已发布的打包时间列表
     *
     * @author chenjinlong 20131203
     * @param $params
     * @return array
     */
    public static function getShowDateList($params)
    {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/showdatelist';
        try {
            return $client->get($url, $params);
        }catch(Exception $e) {
            return array(array(), false, 230115, '接口调用失败！');
        }

    }

    /**
     * 后台产品列表查询接口
     * @param array $params
     * @return array
     */
    public static function getProductList($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/hglist';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 后台产品列表Excel查询接口
     * @param array $params
     * @return array
     */
    public static function getProductFile($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/hgfile';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 查询产品经理
     * @param array $params
     * @return array
     */
    public static function getManagerName($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/manager';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 招客宝报表
     * @param array $params
     * @return array
     */
    public static function getReportForms($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/reportforms';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 招客宝报表-查询所有的BI数据
     * @param array $params
     * @return array
     */
    public static function getBIInfo($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/biinfo';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }
    
    /**
     * 获取网站提供的有效预定城市（出发城市）列表
     * @return array
     */
    public static function getMultiCityInfo(){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/common/startcity';
        try {
            $response = $client->get($url);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 广告位管理列表
     * @return array
     */
    public static function getAdManageList($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/admanagelist';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 广告位是否存在
     * @return array
     */
    public static function getAdIsExist($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/adisexist';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 删除广告位
     * @return array
     */
    public static function postAdDel($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-addel';
        try {
            $response = $client->post($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 添加广告位
     * @return array
     */
    public static function postAdAdd($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-adadd';
        try {
            $response = $client->post($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    
    /**
     * 获取广告位操作记录
     * 
     * @param array $params
     * @return array
     */
    public static function getProductHis($params) {
        $client = new RESTClient;
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/Producthis';
        try {
            $response = $client->get($url, $params);
            return $response;
        } catch (Exception $e) {
            return array(
                    'data' => array(),
                    'success' => false,
                    'errorCode' => 230199,
                    'msg' => '系统错误！'
            );
        }
    }

    /**
     * 查询可添加和编辑包场的运营计划
     * @return array
     */
    public static function getBuyoutDate($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/buyoutdate';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 保存/编辑包场记录
     * @return array
     */
    public static function saveBuyout($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/create-buyout';
        try {
            $response = $client->post($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 获得包场信息
     * @return array
     */
    public static function getBuyout($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/buyout';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 删除包场记录
     * @return array
     */
    public static function delBuyout($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/create-delbuyout';
        try {
            $response = $client->post($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 获得包场广告位类型
     * @return array
     */
    public static function getBuyoutType($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/buyouttype';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }

    /**
     * 获得包场分类页信息
     * @return array
     */
    public static function getWebClassInfo($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/webclassinfo';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }

    /**
     * 查询包场搜索关键词
     * @return array
     */
    public static function getKeyword($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/searchad';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }

    /**
     * 获得产品类型
     * @return array
     */
    public static function getProductType($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/product/producttype';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }

    /**
     * 查询打包日期详情
     * @return array
     */
    public static function getShowDateInfo($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/showdateinfo';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }

    /**
     * 保存打包计划日期
     * @return array
     */
    public static function saveShowDateInfo($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-showdateinfo';
        try {
            $response = $client->post($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }

    /**
     * 查询广告位置信息
     * @return array
     */
    public static function getAdPositionInfo($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/adpositioninfo';
        try {
            $response = $client->get($url,$params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response;
        }
        return array();
    }
    
    /**
     * 获取首页所有位置的配置信息
     * 
     * @author wenrui 2014-06-05
     */
    public static function getIndexAdList($param){
    	$client = new RESTClient;
    	$url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/indexAdList';
    	try {
            $response = $client->get($url, $param);
        } catch (Exception $e) {
            return array(
                'data' => array(),
                'success' => false,
                'errorCode' => 230199,
                'msg' => '系统错误！'
            );
        }
        return $response;
    }
    
    /**
	 * 添加多个广告位的运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
    public static function addPakDtList($param){
    	$client = new RESTClient;
    	$url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-addPakDtList';
    	try {
            $response = $client->post($url, $param);
        } catch (Exception $e) {
            return array(
                'data' => array(),
                'success' => false,
                'errorCode' => 230199,
                'msg' => '系统错误！'
            );
        }
        return $response;
    }
    
	/**
	 * 添加运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
    public static function addPakDt($param){
    	$client = new RESTClient;
    	$url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-addPakDt';
    	try {
            $response = $client->post($url, $param);
        } catch (Exception $e) {
            return array(
                'data' => array(),
                'success' => false,
                'errorCode' => 230199,
                'msg' => '系统错误！'
            );
        }
        return $response;
    }
    
    /**
     * 获取供应商信息
     */
    public static function getAgencyInfo($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/agencyinfo';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
    }
    
    /**
	 * 获取搜索的产品列表
	 */
	public static function getPlaProduct($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/plaproduct';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 获取打包计划列表
	 */
	public static function getPackagePlans($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/packageplans';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 获取打包计划产品详情
	 */
	public static function getPlanProductDetail($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/planproductdetail';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 保存打包计划
	 */
	public static function savePackPlan($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/create-packplan';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 发布打包计划
	 */
	public static function submitPackPlan($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/create-submitpackplan';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 保存打包计划线路
	 */
	public static function savePackPlanProduct($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/create-packplanproduct';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 删除打包计划线路
	 */
	public static function deletePackPlan($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/create-deletepackplan';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 获取打包计划状态
	 */
	public static function getPlanStatus($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packageplan/planstatus';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 查询招客宝的频道页区块信息
	 */
	public static function getChannelinfobycity($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/channel/channelinfobycity';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}
	
	/**
	 * 打开或关闭广告位
	 */
	public static function updatePackOpenStatus($params) {
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/packagedate/create-Packopenstatus';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
		
	}
	
	/**
	 * 保存全局配置
	 */
	public static function saveOverallConfig($params) {
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/channel/create-overallconfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
		
	}
	
	/**
	 * 保存特殊非统一配置
	 */
	public static function saveSpecialNoConfig($params) {
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/channel/create-specialnoconfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
		
	}
	
	/**
	 * 保存特殊统一配置
	 */
	public static function saveSpecialYesConfig($params) {
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/channel/create-specialyesconfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
		
	}
	
    
 	/**
     * 查询招客宝的频道页特殊配置列表信息
     */
	public static function getSpecialconfig($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/channel/specialconfig';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}	


 	/**
     * 根据出发城市获取分类页
     */
	public static function getClassInfoByCity($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/clsrecommend/Classinfobycity';
        // $url = 'http://bbtest.test.tuniu.org/bb/public/clsrecommend/Classinfobycity';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}	
	
	/**
	 * 保存分类页全局配置
	 */
	public static function saveClassOverallConfig($params) {
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/clsrecommend/create-overallconfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
		
	}
	
	/**
	 * 保存分类页特殊配置
	 */
	public static function saveClassSpecialConfig($params) {
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/clsrecommend/create-specialconfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
		
	}

 	/**
     * 查询分类页特殊配置
     */
	public static function getClassSpecialConfig($params){
		$client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/clsrecommend/specialconfig';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
	}

    /**
     * 获取赠币配置列表
     */
    public static function getCouponConfigList($params){
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/config/couponConfigList';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
    }

    /**
     * 插入供应商赠币配置
     */
    public static function saveCouponConfig($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/config/create-addCouponConfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;

    }

    /**
     * 删除供应商赠币配置
     */
    public static function delCouponConfig($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/config/create-delCouponConfig';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;

    }
    
    /**
     * 查询财务账户报表
     */
    public static function getFmisCharts($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/user/fmischarts';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;

    }
    
    /**
     * 获取费率
     */
    public static function getExpenseRatio($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/cps/Expenseratio';
        try {
            $response = $client->get($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
    }
    
    /**
     * 配置费率
     */
    public static function configExpenseRatio($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'].'bb/public/cps/create-Configexpenseratio';
        try {
            $response = $client->post($url, $params);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;

    }    

    /**
     * 查询CPS推广报表
     */
    public static function getCpsShowReport($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['BB_HOST'] . 'bb/public/cps/cpsshowreport';
        try {
            $response = $client->get($url, $params);
        } catch(Exception $e) {
            Yii::log($e, "warnning");
            return array(array(), false, 230115, '接口调用失败！');
        }
        return $response;
    }
    
    
    
}