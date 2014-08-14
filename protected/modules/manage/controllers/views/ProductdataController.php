<?php
/** UI呈现接口 | Hagrid 基础数据
* Hagrid user interfaces for inner UI system.
* @author xiongyun@2013-01-04
* @version 1.0
* @func doRestGetStaticProduct
*/
Yii::import('application.modules.manage.models.product.StaticProductCatMod');
Yii::import('application.modules.manage.models.product.ProductDataMod');
class ProductdataController extends restUIServer{
    private $_productCatMod;
    private $_productDataMod;

    function __construct() {
        $this->_productCatMod = new StaticProductCatMod;
        $this->_productDataMod = new ProductDataMod;
    }
    
    /**
     * $client = new RESTClient();
     * $requestData = array('listType'=>,'productType'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /staticproduct
     * @method GET
     * @param string $urlVar 
     * @param  array $inParams {"listType":"destinationClass","productType":1,"uid": "4220","nickname": ""}
     * @return array {"success":false|true,"msg":"","errorcode":|230115,"data":}
     * @desc UI基础数据
     */
    public function doRestGetStaticProduct($urlVar, $inParams) {

        if (empty($inParams['listType'])) {
            $this->returnRest(array(), false, 230115, '输入参数错误');
        } else {
            $listType = $inParams['listType'];
            switch ($listType) {
                case 'departureCity':
                    $result = $this->_productCatMod->getDepartureCity();
                    break;
                case 'productType':
                    $result = $this->_productCatMod->getProductTypeList();
                    break;
                case 'destinationClass':
                    if(empty($inParams['productType'])){
                       $this->returnRest(array(), false, 230115, '输入参数错误'); 
                    }
                    $result = $this->_productCatMod->getProductCatTypeList($inParams['productType']);
                    break;
                default:
                break;
            }
            
            if ($result) {
                $this->returnRest($result);
            } else {
                $this->returnRest(array());
            }
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('listType'=>,'productType'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /list
     * @method GET
     * @param string $urlVar
     * @param  array $inParams {"listType":"destinationClass","productType":1,"uid": "4220","nickname": ""}
     * @return array {"success":false|true,"msg":"","errorcode":|230115,"data":}
     * @desc hg-查看产品列表
     */
    public function doRestGetList($urlVar, $inParams) {
        if (!$inParams['uid']) {
            $this->returnRest(array(), false, 230113, '获取产品列表入参错误：uid为空');
            return;
        }
        if (!$inParams['nickname']) {
            $this->returnRest(array(), false, 230113, '获取产品列表入参错误：nickname为空');
            return;
        }

        $params = array(
            'bidState' => $inParams['bidState'],
            'vendorId' => $inParams['vendorId'],
            'vendorName' => $inParams['vendorName'],
            'startDate' => $inParams['startDate'],
            'endDate' => $inParams['endDate'],
            'adKey' => $inParams['adKey'],
            'startCityCode' => $inParams['startCityCode'],
            'productId' => $inParams['productId'],
            'productName' => $inParams['productName'],
            'checkFlag' => $inParams['checkFlag'],
            'start' => intval($inParams['start']) ? intval($inParams['start']) : 0,
            'limit' => intval($inParams['limit']) ? intval($inParams['limit']) : 10,
            'sortName' => $inParams['sortname'],
            'sortOrder' => $inParams['sortorder'],
            'adName' => $inParams['adName'],
        );
        $response = $this->_productDataMod->readProductList($params);
        $this->returnRest($response);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('listType'=>,'productType'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /file
     * @method GET
     * @param string $urlVar
     * @param  array $inParams {"listType":"destinationClass","productType":1,"uid": "4220","nickname": ""}
     * @return array {"success":false|true,"msg":"","errorcode":|230115,"data":}
     * @desc hg-导出推广产品列表文件
     */
    public function doRestGetFile($urlVar, $inParams) {
        if (!$inParams['uid']) {
            $this->returnRest(array(), false, 230113, '获取产品列表入参错误：uid为空');
            return;
        }
        if (!$inParams['nickname']) {
            $this->returnRest(array(), false, 230113, '获取产品列表入参错误：nickname为空');
            return;
        }

        $params = array(
            'bidState' => $inParams['bidState'],
            'vendorId' => $inParams['vendorId'],
            'vendorName' => $inParams['vendorName'],
            'startDate' => $inParams['startDate'],
            'endDate' => $inParams['endDate'],
            'adKey' => $inParams['adKey'],
            'startCityCode' => $inParams['startCityCode'],
            'productId' => $inParams['productId'],
            'productName' => $inParams['productName'],
            'checkFlag' => $inParams['checkFlag'],
            'sortName' => $inParams['sortname'],
            'sortOrder' => $inParams['sortorder'],
            'adName' => $inParams['adName'],
            'isDownload' => 1
        );
        $timeNow = date('Ymdhis');
        $fileName = '招客宝信息统计表-'.$timeNow;
		// 输出Excel文件头 
		header('Content-Type: application/vnd.ms-excel;charset=gbk');
		header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
		header('Cache-Control: max-age=0');
		// PHP文件句柄，php://output 表示直接输出到浏览器 
		$fp = fopen('php://output', 'a');
		// 输出Excel列头信息
        if (3 == $inParams['bidState'] || -1 == $inParams['bidState']) {
            $head = array('竞价日期', '展示日期', '产品编号', '产品名称', '供应商编号', '供应商品牌名', '产品经理', '出发城市', '推广页面', '分类明细', '搜索关键字', '最终出价', '当前出价牛币',
                '当前出价赠币', '当前排名');
        } else {
            $head = array('竞价日期', '展示日期', '产品编号', '产品名称', '供应商编号', '供应商品牌名', '产品经理', '出发城市', '推广页面', '分类明细', '搜索关键字', '当前出价', '最高出价', '当前出价牛币',
                '最高出价牛币', '当前出价赠币', '最高出价赠币', '当前排名', '状态');
            if (2 == $inParams['bidState']) {
                array_pop($head);
            }
        }
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
        	$params['start'] = $start;
        	$params['limit'] = $limit;
        	$result = $this->_productDataMod->readProductFile($params);
	        if ($result) {
				foreach ($result as $row) {
                    if (3 == $inParams['bidState'] || -1 == $inParams['bidState']) {
                        $list = array($row['bidDate'],$row['showDate'],$row['productId'],$row['productName'],$row['vendorId'],$row['vendorName'],$row['managerName'],$row['startCityName'],$row['adKeyName'],$row['adKeyDetail'],
                            $row['searchKeyword'],$row['bidPrice'],$row['bidPriceNiu'],$row['bidPriceCoupon'],$row['ranking']);
                    } else {
                        $list = array($row['bidDate'],$row['showDate'],$row['productId'],$row['productName'],$row['vendorId'],$row['vendorName'],$row['managerName'],$row['startCityName'],$row['adKeyName'],$row['adKeyDetail'],
                            $row['searchKeyword'],$row['bidPrice'],$row['maxLimitPrice'],$row['bidPriceNiu'],$row['maxLimitPriceNiu'],$row['bidPriceCoupon'],$row['maxLimitPriceCoupon'],
                            $row['ranking'],$row['bidState']);
                        if (2 == $inParams['bidState']) {
                            array_pop($list);
                        }
                    }
				    foreach ($list as $i => $v) {
				        $list[$i] = iconv('utf-8', 'gbk', $v);
				    }
				    fputcsv($fp, $list);
				}
	        } else {
	            $this->returnRest(array(), false, 230116, '下载产品列表表格失败');
	        }
        	
        	if(count($result)<500){
        		break;
        	}
        	$start += 500;
        } while ( 1==1 );
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
        $managerInfo = $this->_productDataMod->getManagerName($params);
        $this->returnRest($managerInfo);
    }
    
    /**
     * 获得广告位操作记录
     */
    public function doRestGetProducthis($url, $data) {
    	
    	// 调用远程接口
    	$result = $this->_productDataMod->getProductHis($data);
    	
    	// 判断是否调用成功
    	if ($result) {
            $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
        } else {
            $this->returnRest(array(), false, 210003, '远程接口调用失败！');
        }
    	
    }

    /**
     * 保存/编辑包场记录
     */
    public function doRestPostBuyout($data) {

        // 调用远程接口
        $result = $this->_productDataMod->saveBuyout($data);

        // 判断是否调用成功
        if ($result) {
            $this->returnRest($result);
        } else {
            $this->returnRest(array(), false, 210003, '远程接口调用失败！');
        }
    }

    /**
     * 获得包场信息
     */
    public function doRestGetBuyout($url, $data) {

        // 调用远程接口
        $result = $this->_productDataMod->getBuyout($data);

        // 判断是否调用成功
        if ($result) {
            $this->returnRest($result);
        } else {
            $this->returnRest(array(), false, 210003, '远程接口调用失败！');
        }
    }

    /**
     * 删除包场记录
     */
    public function doRestPostDelbuyout($data) {

        // 调用远程接口
        $result = $this->_productDataMod->delBuyout($data);

        // 判断是否调用成功
        if ($result) {
            $this->returnRest($result);
        } else {
            $this->returnRest(array(), false, 210003, '远程接口调用失败！');
        }
    }

    /**
     * 获得包场广告位类型
     */
    public function doRestGetBuyoutType($url, $data) {
        if (empty($data['showDateId'])) {
            // 返回参数不正确
            $this->returnRest(array(), false, 210003, '请选择推广时间！');
        } else {
            // 调用远程接口
            $result = $this->_productDataMod->getBuyoutType($data);

            // 判断是否调用成功
            if ($result) {
                $this->returnRest($result['data']);
            } else {
                $this->returnRest(array(), false, 210003, '远程接口调用失败！');
            }
        }
    }

    /**
     * 获得包场分类页信息
     */
    public function doRestGetWebClassInfo($url, $data) {
        if (empty($data['webClassName'])) {
            // 返回参数不正确
            $this->returnRest(array(), false, 210003, '请填写分类名称！');
        } else {
            // 调用远程接口
            $result = $this->_productDataMod->getWebClassInfo($data);

            // 判断是否调用成功
            if ($result) {
                $this->returnRest($result['data']);
            } else {
                $this->returnRest(array(), false, 210003, '远程接口调用失败！');
            }
        }
    }

    /**
     * 查询包场搜索关键词
     */
    public function doRestGetKeyword($url, $data) {
        if (empty($data['keyword'])) {
            // 返回参数不正确
            $this->returnRest(array(), false, 210003, '请填写搜索关键词！');
        } else {
            // 调用远程接口
            $result = $this->_productDataMod->getKeyword($data);

            // 判断是否调用成功
            if ($result) {
                $this->returnRest($result['data']);
            } else {
                $this->returnRest(array(), false, 210003, '远程接口调用失败！');
            }
        }
    }

    /**
     * 获得产品类型
     */
    public function doRestGetProductType($url, $data) {

        // 调用远程接口
        $result = $this->_productDataMod->getProductType($data);

        // 判断是否调用成功
        if ($result) {
            $this->returnRest($result['data']);
        } else {
            $this->returnRest(array(), false, 210003, '远程接口调用失败！');
        }
    }
    
}
