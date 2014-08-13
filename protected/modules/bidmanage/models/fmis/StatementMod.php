<?php
Yii::import('application.modules.bidmanage.dal.dao.fmis.StatementDao');
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao');
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.iao.BossIao');
Yii::import("application.models.CurlUploadModel");
Yii::import('application.modules.bidmanage.dal.iao.ProductIao');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');

//bid-fmis
class StatementMod {

    private $statementDao;
    private $commonDao;
    private $productDao;
    private $userDao;
    private $_productIao;

    function __construct() {
        $this->statementDao = new StatementDao();
        $this->commonDao = new CommonDao();
        $this->productDao = new ProductDao();
        $this->userDao = new UserManageDao();
        $this->_productIao = new ProductIao;
    }

    /**
     * [product]报表-查询所有的BI数据
     * @param  $condParams
     */
    public function getAllBIInfo($condParams){
        // 变量初始化赋值
        $biTempParam = array();
        $allIp = 0;
        $allValidOrderCount = 0;
        $allValidSignOrderCount = 0;
        $bidIdArr = array();
        $accountIdArr = array();
        // 总的消费金额
        $bidAllPrice = $this->statementDao->getAllConsumption($condParams);
        $allConsumption = intval($bidAllPrice['bidAllPrice']);
        // 按位置和产品和打包日期维度来获取所有需要拼接的参数
        $queryHgAllRows = $this->statementDao->getHgReportFormsAllList($condParams);
        foreach($queryHgAllRows as $val){
            array_push($bidIdArr,$val['bidId']);
            array_push($accountIdArr,$val['accountId']);
        }
        // 查询所有附加费费用
        $additionParams = array('bidId' => trim(implode(',',$bidIdArr)), 'accountId' => trim(implode(',',$accountIdArr)));
        $additionPrice = $this->statementDao->getAdditionPrice($additionParams);
        $allAdditionPrice = intval($additionPrice['additionPrice']);
        // 查询所有的BI数据
        $queryAllRows = $this->statementDao->getReportFormsAllList($condParams);
        foreach($queryAllRows as $val){
            $showStartDate = strtotime($val['showStartDate']);
            $showEndDate = strtotime($val['showEndDate']);
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userDao->getVendorInfoAll($val['accountId']);
            // 拆分展示日期范围为每天
            while($showStartDate <= $showEndDate){
                $temp = array();
                // 把展示日期范围拆分为每一天来展示
                $temp['statisticDate'] = date("Y-m-d",intval($showStartDate));
                $temp['agencyId'] = intval($verdorInfo[0]['vendorId']);
                $temp['routeId'] = intval($val['productId']);
                $biTempParam[] = $temp;
                // 设置日期加一天操作
                $showStartDate = strtotime('+1 day',$showStartDate);
            }
        }
        // 调用NB的接口获取BI的数据,每次500条进行分批查询
        $biDataPart = array();
        for ($i = 0; $i < count($biTempParam); $i = $i + 500) {
            $biParamPart = array_slice($biTempParam,$i,500);
            $biParam = array('biParam' => $biParamPart);
            $biData = $this->_productIao->getAllBiInfo($biParam);
            $biDataPart[] = $biData['data'];
        }
        // 叠加每次查询的结果
        foreach ($biDataPart as $dataPart) {
            $allIp += $dataPart['allIp'];
            $allValidOrderCount += $dataPart['allValidOrderCount'];
            $allValidSignOrderCount += $dataPart['allValidSignOrderCount'];
        }
        $biInfo = array(
            'allIp' => $allIp,
            'allValidOrderCount' => $allValidOrderCount,
            'allValidSignOrderCount' => $allValidSignOrderCount,
            'allOrderConversionRate' => $allIp ? round(($allValidOrderCount/$allIp)*100,2).'%' : '',// ip访问数/订单数 再四舍五入后取百分数
            'allConsumption' => $allConsumption,
            'allAdditionPrice' => $allAdditionPrice,
        );
        return $biInfo;
    }

    /*
     * 获取分类页信息
     */
    public function getClassInfo($queryRows) {
        // 设置参数获取分类页信息
        $webClassId = array();
        $webClassData = array();
        foreach($queryRows as $val){
            if ($val['webClass']) {
                array_push($webClassId,$val['webClass']);
            }
        }
        if ($webClassId) {
            // 使用循环每次10条来获取数据
            for ($i = 0; $i < count($queryRows); $i = $i + 10) {
                // 调用网站接口获取分类信息
                $paramWebClassId = array_slice($webClassId, $i, 10);
                $webClassArr = array('webClassId' => $paramWebClassId);
                $webClassInfo = $this->_productIao->getWebClassInfo($webClassArr);
                $webClassData[] = $webClassInfo['data'];
            }
        }
        return $webClassData;
    }

    /*
     * 从缓存或者NB接口获取BI数据
     */
    public function getDiffBIInfo($queryRows) {
        $biTempParam = array();
        $biParam = array();
        // 获取所有的缓存数据
        $memAllData = array();
        foreach($queryRows as $val){
            $showStartDate = strtotime($val['showStartDate']);
            $showEndDate = strtotime($val['showEndDate']);
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userDao->getVendorInfoAll($val['accountId']);
            while($showStartDate <= $showEndDate){
                $temp = array();
                // 把展示日期范围拆分为每一天来展示
                $temp['statisticDate'] = date("Y-m-d",intval($showStartDate));
                $temp['agencyId'] = intval($verdorInfo[0]['vendorId']);
                $temp['routeId'] = intval($val['productId']);
                // 设置缓存
                $key = md5(json_encode($temp));
                $data = Yii::app()->memcache->get($key);
                if (!empty($data)) {
                    $memAllData[] = $data;
                } else {
                    // 缓存中获取不到数据的参数放进去以后调用的时候就会再次调用接口获取
                    $biTempParam[] = $temp;
                }
                // 设置日期加一天操作
                $showStartDate = strtotime('+1 day',$showStartDate);
            }
        }
        // 调用NB的接口获取BI的数据
        $allData = array();
        if (!empty($biTempParam)) {
            $biParam['biParam'] = $biTempParam;
            $biData = $this->_productIao->getBiInfo($biParam);
            $allData = $biData['data']['rows'];
        }
        // 循环设置缓存
        foreach($queryRows as $val){
            $showStartDate = strtotime($val['showStartDate']);
            $showEndDate = strtotime($val['showEndDate']);
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userDao->getVendorInfoAll($val['accountId']);
            while($showStartDate <= $showEndDate){
                $temp = array();
                $temp['statisticDate'] = date("Y-m-d",intval($showStartDate));
                $temp['agencyId'] = intval($verdorInfo[0]['vendorId']);
                $temp['routeId'] = intval($val['productId']);
                if (!empty($allData)) {
                    foreach ($allData as $tempData) {
                        if ($tempData['agencyId'] === intval($temp['agencyId']) && $tempData['routeId'] === intval($temp['routeId'])
                            && $tempData['statisticDate'] === $temp['statisticDate']) {
                            Yii::app()->memcache->set(md5(json_encode($temp)), $tempData, 43200);
                        }
                    }
                }
                // 设置日期加一天操作
                $showStartDate = strtotime('+1 day',$showStartDate);
            }
        }
        return array('memAllData' => $memAllData,'allData' => $allData);
    }

    /**
     * [product]bb-招客宝报表
     * @param unknown_type $params
     */
    public function getReportFormsList($condParams) {
        $queryCount = $this->statementDao->getReportFormsCount($condParams);
        $queryRows = $this->statementDao->getReportFormsList($condParams);
        $rows = array();
        $webClassData = $this->getClassInfo($queryRows);
        // 从缓存或者接口获取BI数据
        $diffBIInfo = $this->getDiffBIInfo($queryRows);
        $memAllData = $diffBIInfo['memAllData'];
        $allData = $diffBIInfo['allData'];
        // 循环拼接数据
        foreach($queryRows as $val){
            $bidPrice = 0;
            $addPrice = 0;
            // 按位置和产品和打包日期维度来获取某产品的消费金额
            $condParams['productId'] = intval($val['productId']);
            $queryHgRows = $this->statementDao->getHgReportFormsAllList($condParams);
            // 产品按位置取得所有的推广费用并进行相加得到产品维度的正确值
            foreach($queryHgRows as $hgRowsVal){
                // 查询附加费费用
                $additionParam = array('bidId' => $hgRowsVal['bidId'], 'accountId' => $hgRowsVal['accountId']);
                $additionPrice = $this->statementDao->getAdditionPrice($additionParam);
                if ($val['productId'] == $hgRowsVal['productId'] && $val['showDateId'] == $hgRowsVal['showDateId']) {
                    $bidPrice += intval($hgRowsVal['bidPrice']);
                    $addPrice += intval($additionPrice['additionPrice']);
                }
            }
            $showStartDate = strtotime($val['showStartDate']);
            $showEndDate = strtotime($val['showEndDate']);
            // 查询出发城市名称
            $cityName = $this->productDao->getCityName(intval($val['startCityCode']));
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userDao->getVendorInfoAll($val['accountId']);
            // 拆分展示日期范围为每天
            while($showStartDate <= $showEndDate){
                $temp = array();
                $temp['bidId'] = intval($val['bidId']);
                $temp['bidDate'] = $val['bidStartDate'].' '.$val['bidStartTime'].'点～'.$val['bidEndDate'].' '.$val['bidEndTime'].'点';
                $temp['showDateId'] = intval($val['showDateId']);
                $temp['webClassId'] = intval($val['webClass']);
                $temp['startCityCode'] = intval($val['startCityCode']);
                $temp['startCityName'] = $cityName['name'];
                $temp['productId'] = intval($val['productId']);
                $temp['productType'] = intval($val['productType']);
                $temp['adKey'] = $val['adKey'];
                // 广告位数据拼接
                if ($condParams['adKey'] == "index_chosen") {
                    $temp['adKeyName'] = "首页";
                    $temp['adKeyDetail'] = '';
                } elseif ($condParams['adKey'] == "class_recommend") {
                    $temp['adKeyName'] = "分类页";
                    if ($webClassData) {
                        foreach($webClassData as $tempStr){
                            if (intval($tempStr[$temp['webClassId']]['id']) == intval($val['webClass'])) {
                                $temp['adKeyDetail'] = $tempStr[$temp['webClassId']]['classificationName'];
                            }
                        }
                    }
                } elseif ($condParams['adKey'] == "search_complex") {
                    // 修改搜索页字段
                    $temp['adKeyName'] = "搜索页";
                    $temp['adKeyDetail'] = '';
                } elseif ($condParams['adKey'] == "special_subject") {
                    // 修改专题页字段
                    $temp['adKeyName'] = "专题页";
                    $temp['adKeyDetail'] = '';
                } elseif ($condParams['adKey'] == "channel_chosen") {
                    // 修改频道页字段
                    $temp['adKeyName'] = "频道页";
                    $temp['adKeyDetail'] = '';
                } else {
                    $temp['adKeyName'] ='';
                }
                $temp['searchKeyword'] = $val['searchName'];
                $temp['bidPrice'] = $bidPrice;
                $temp['additionPrice'] = $addPrice;
                $temp['ranking'] = intval($val['ranking']);
                $temp['productName'] = $val['productName'];
                $temp['showStartDate'] = $val['showStartDate'];
                $temp['showEndDate'] = $val['showEndDate'];
                // 拼接展示日期范围
                $temp['showDateRange'] = $val['showStartDate'].'～'.$val['showEndDate'];
                // 把展示日期范围拆分为每一天来展示
                $temp['showDate'] = date("Y-m-d",intval($showStartDate));
                $temp['vendorId'] = $verdorInfo[0]['vendorId'];
                $temp['vendorName'] = $verdorInfo[0]['accountName'];
                // 从缓存取数据
                if (!empty($memAllData)) {
                    foreach ($memAllData as $tempData) {
                        if ($tempData['agencyId'] == $temp['vendorId'] && $tempData['routeId'] == $temp['productId']
                            && $tempData['statisticDate'] == $temp['showDate']) {
                            $temp['ip'] = $tempData['ip'];// ip访问数
                            $temp['validOrderCount'] = $tempData['validOrderCount'];// 有效订单数
                            $temp['validSignOrderCount'] = $tempData['validSignOrderCount'];// 签约订单数
                            $temp['orderConversionRate'] = round(($tempData['orderConversionRate'])*100,2).'%';// 订单转化率
                            break;
                        } else {
                            $temp['ip'] = 0;
                            $temp['validOrderCount'] = 0;
                            $temp['validSignOrderCount'] = 0;
                            $temp['orderConversionRate'] = '0%';
                        }
                    }
                }
                // 从接口取数据
                if (!empty($allData)) {
                    foreach ($allData as $tempData) {
                        if ($tempData['agencyId'] == $temp['vendorId'] && $tempData['routeId'] == $temp['productId']
                                && $tempData['statisticDate'] == $temp['showDate']) {
                            $temp['ip'] = $tempData['ip'];// ip访问数
                            $temp['validOrderCount'] = $tempData['validOrderCount'];// 有效订单数
                            $temp['validSignOrderCount'] = $tempData['validSignOrderCount'];// 签约订单数
                            $temp['orderConversionRate'] = round(($tempData['orderConversionRate'])*100,2).'%';// 订单转化率
                            break;
                        } else {
                            $temp['ip'] = 0;
                            $temp['validOrderCount'] = 0;
                            $temp['validSignOrderCount'] = 0;
                            $temp['orderConversionRate'] = '0%';
                        }
                    }
                }
                $rows[] = $temp;
                // 设置日期加一天操作
                $showStartDate = strtotime('+1 day',$showStartDate);
            }
        }
        $result['count'] = $queryCount;
        $result['rows'] = $rows;
        return !empty($result) ? $result : array();
    }

    /**
     * [product]bb-招客宝报表产品趋势
     * @param array $condParams
     */
    public function getProductTrend($condParams) {
        $biTempParam = array();
        $biParam = array();
        $result = array();
        // 获取前7天和后7天的时间范围
        $startDate = strtotime('-7 day',strtotime($condParams['showStartDate']));
        $endDate = strtotime('+7 day',strtotime($condParams['showEndDate']));
        while ($startDate <= $endDate) {
            $temp = array();
            // 把展示日期范围拆分为每一天来展示
            $temp['statisticDate'] = date("Y-m-d",intval($startDate));
            $temp['agencyId'] = intval($condParams['vendorId']);
            $temp['routeId'] = intval($condParams['routeId']);
            $biTempParam[] = $temp;
            // 设置日期加一天操作
            $startDate = strtotime('+1 day',$startDate);
        }
        // 调用NB的接口获取BI的数据
        $biParam['biParam'] = $biTempParam;
        $biData = $this->_productIao->getBiInfo($biParam);
        $allData = $biData['data']['rows'];
        // 获取前7天和后7天的时间范围
        $startDate = strtotime('-7 day',strtotime($condParams['showStartDate']));
        $endDate = strtotime('+7 day',strtotime($condParams['showEndDate']));
        // 循环设置每天的数据坐标（x轴为日期，y轴为数据）
        while ($startDate <= $endDate) {
            $temp = array();
            $index = array();
            if (!empty($allData)) {
                foreach ($allData as $tempData) {
                    if ($tempData['agencyId'] == intval($condParams['vendorId']) && $tempData['routeId'] == intval($condParams['routeId'])
                            && $tempData['statisticDate'] == date("Y-m-d",intval($startDate))) {
                        $temp['ip'] = $tempData['ip'];// ip访问数
                        $temp['validOrderCount'] = $tempData['validOrderCount'];// 有效订单数
                        $temp['validSignOrderCount'] = $tempData['validSignOrderCount'];// 签约订单数
                        $temp['orderConversionRate'] = $tempData['orderConversionRate'];// 订单转化率
                        break;
                    } else {
                        $temp['ip'] = 0;
                        $temp['validOrderCount'] = 0;
                        $temp['validSignOrderCount'] = 0;
                        $temp['orderConversionRate'] = '';
                    }
                }
            }
            $index['x'] = date("Y-m-d",intval($startDate));
            // 根据参数来确定是需要查询什么类型的数据
            switch ($condParams['trendType']) {
                case 1:
                    $index['y'] = $temp['ip'];// ip访问数
                    break;
                case 2:
                    $index['y'] = $temp['validOrderCount'];// 有效订单数
                    break;
                case 3:
                    $index['y'] = $temp['validSignOrderCount'];// 签约订单数
                    break;
                case 4:
                    $index['y'] = $temp['orderConversionRate'];// 订单转化率
                    break;
                default:
                    break;
            }
            if (!$index['y']) {
                $index['y'] = 0;
            }
            $result[] = $index;
            // 设置日期加一天操作
            $startDate = strtotime('+1 day',$startDate);
        }
        return !empty($result) ? $result : array();
    }

    /**
     * [product]hg-招客宝报表
     * @param unknown_type $params
     */
    public function getHgReportFormsList($condParams) {
        $queryCount = $this->statementDao->getHgReportFormsCount($condParams);
        $queryRows = $this->statementDao->getHgReportFormsList($condParams);
        $rows = array();
        $webClassData = $this->getClassInfo($queryRows);
        // 从缓存或者接口获取BI数据
        $diffBIInfo = $this->getDiffBIInfo($queryRows);
        $memAllData = $diffBIInfo['memAllData'];
        $allData = $diffBIInfo['allData'];
        // 循环拼接数据
        foreach($queryRows as $val){
            $showStartDate = strtotime($val['showStartDate']);
            $showEndDate = strtotime($val['showEndDate']);
            // 查询出发城市名称
            $cityName = $this->productDao->getCityName(intval($val['startCityCode']));
            // 根据accountId查询供应商信息
            $verdorInfo = $this->userDao->getVendorInfoAll($val['accountId']);
            // 查询附加费费用
            $additionParam = array('bidId' => $val['bidId'], 'accountId' => $val['accountId']);
            $additionPrice = $this->statementDao->getAdditionPrice($additionParam);
            // 拆分展示日期范围为每天
            while($showStartDate <= $showEndDate){
                $temp = array();
                $temp['bidId'] = intval($val['bidId']);
                // 拼接展示日期范围
                $temp['showDateRange'] = $val['showStartDate'].'～'.$val['showEndDate'];
                $temp['bidDate'] = $val['bidStartDate'].' '.$val['bidStartTime'].'点～'.$val['bidEndDate'].' '.$val['bidEndTime'].'点';
                $temp['showDateId'] = intval($val['showDateId']);
                $temp['vendorId'] = $verdorInfo[0]['vendorId'];
                $temp['vendorName'] = $verdorInfo[0]['accountName'];
                $temp['productId'] = intval($val['productId']);
                $temp['webClassId'] = intval($val['webClass']);
                $temp['startCityCode'] = intval($val['startCityCode']);
                $temp['startCityName'] = $cityName['name'];
                $temp['productType'] = intval($val['productType']);
                $temp['adKey'] = $val['adKey'];
                // 广告位数据拼接
                if ($condParams['adKey'] == "index_chosen") {
                    $temp['adKeyName'] = "首页";
                    $temp['adKeyDetail'] = '';
                } elseif ($condParams['adKey'] == "class_recommend") {
                    $temp['adKeyName'] = "分类页";
                    if ($webClassData) {
                        foreach($webClassData as $tempStr){
                            if (intval($tempStr[$temp['webClassId']]['id']) == intval($val['webClass'])) {
                                $temp['adKeyDetail'] = $tempStr[$temp['webClassId']]['classificationName'];
                            }
                        }
                    }
                } elseif ($condParams['adKey'] == "search_complex") {
                    // 修改搜索页字段
                    $temp['adKeyName'] = "搜索页";
                    $temp['adKeyDetail'] = '';
                } elseif ($condParams['adKey'] == "special_subject") {
                    // 修改专题页字段
                    $temp['adKeyName'] = "专题页";
                    $temp['adKeyDetail'] = '';
                } elseif ($condParams['adKey'] == "channel_chosen") {
                    // 修改频道页字段
                    $temp['adKeyName'] = "频道页";
                    $temp['adKeyDetail'] = '';
                } else {
                    $temp['adKeyName'] ='';
                }
                $temp['searchKeyword'] = $val['searchName'];
                $temp['additionPrice'] = intval($additionPrice['additionPrice']);
                $temp['bidPrice'] = intval($val['bidPrice']);
                $temp['ranking'] = intval($val['ranking']);
                $temp['productName'] = $val['productName'];
                // 把展示日期范围拆分为每一天来展示
                $temp['showDate'] = date("Y-m-d",intval($showStartDate));
                // 从缓存取数据
                if (!empty($memAllData)) {
                    foreach ($memAllData as $tempData) {
                        if ($tempData['agencyId'] == $temp['vendorId'] && $tempData['routeId'] == $temp['productId']
                            && $tempData['statisticDate'] == $temp['showDate']) {
                            $temp['ip'] = $tempData['ip'];// ip访问数
                            $temp['validOrderCount'] = $tempData['validOrderCount'];// 有效订单数
                            $temp['validSignOrderCount'] = $tempData['validSignOrderCount'];// 签约订单数
                            $temp['orderConversionRate'] = round(($tempData['orderConversionRate'])*100,2).'%';// 订单转化率
                            break;
                        } else {
                            $temp['ip'] = 0;
                            $temp['validOrderCount'] = 0;
                            $temp['validSignOrderCount'] = 0;
                            $temp['orderConversionRate'] = '0%';
                        }
                    }
                }
                // 从接口取数据
                if (!empty($allData)) {
                    foreach ($allData as $tempData) {
                        if ($tempData['agencyId'] == $temp['vendorId'] && $tempData['routeId'] == $temp['productId']
                                && $tempData['statisticDate'] == $temp['showDate']) {
                            $temp['ip'] = $tempData['ip'];// ip访问数
                            $temp['validOrderCount'] = $tempData['validOrderCount'];// 有效订单数
                            $temp['validSignOrderCount'] = $tempData['validSignOrderCount'];// 签约订单数
                            $temp['orderConversionRate'] = round(($tempData['orderConversionRate'])*100,2).'%';// 订单转化率
                            break;
                        } else {
                            $temp['ip'] = 0;
                            $temp['validOrderCount'] = 0;
                            $temp['validSignOrderCount'] = 0;
                            $temp['orderConversionRate'] = '0%';
                        }
                    }
                }
                $rows[] = $temp;
                // 设置日期加一天操作
                $showStartDate = strtotime('+1 day',$showStartDate);
            }
        }
        $result['count'] = $queryCount;
        $result['rows'] = $rows;
        return !empty($result) ? $result : array();
    }


	/**
	 * 获取财务报表
	 */
	public function getFmisCharts($param) {
		// 填充日志
		if ($this->bbLog->isInfo()) {
			$this->bbLog->logMethod($param, "获取财务报表：".$param['isExcel'], __METHOD__.Symbol::CONS_DOU_COLON.__LINE__, chr(50));
		}
		
		// 初始化返回结果，如果为其他，则这样初始化
		$result = array();
		// 如果为列表则这样初始化
		$result['rows'] = array();
		$result['count'] = 0;

		// 逻辑全部在异常块里执行，代码量不要超过200，超过200需要另抽方法
		try {
			// 添加监控示例
			$posTry = BPMoniter::createMoniter(__METHOD__.Symbol::CONS_DOU_COLON.__LINE__);
			$flag = Symbol::BPM_EIGHT_HUNDRED;
			
			// 整合参数
			$fmisParam['isExcel'] = $param['isExcel'];
			$fmisParam['start'] = $param['start'];
			$fmisParam['limit'] = $param['limit'];
			$fmisParam['agencyId'] = intval(chr(48));
			if (!empty($param['agencyId'])) {
				$fmisParam['agencyId'] = $param['agencyId'];
			}
			$fmisParam['agencyName'] = Symbol::EMPTY_STRING;
			if (!empty($param['agencyName'])) {
				$fmisParam['agencyName'] = $param['agencyName'];
			}
			$fmisParam['startDate'] = Symbol::EMPTY_STRING;
			$fmisParam['endDate'] = Symbol::EMPTY_STRING;
			if (empty($param['startDate']) && empty($param['endDate'])) {
				$fmisParam['startDate'] = date(Sundry::TIME_Y_M_D, strtotime('-6 d',date(Sundry::TIME_Y_M_D)));
				$fmisParam['endDate'] = date(Sundry::TIME_Y_M_D);
			}
			if (!empty($param['startDate'])) {
				$fmisParam['startDate'] = $param['startDate'];
			}
			if (!empty($param['endDate'])) {
				$fmisParam['endDate'] = $param['endDate'];
			}
			
			// 获取财务系统报表信息
			$iaoData = FinanceIao::getFmisCharts($fmisParam);
			unset($fmisParam);
			
			// 设置数量
			if (intval(chr(49)) == $param['isExcel']) {
				$result['count'] = count($iaoData['rows']);
				$flag = Symbol::BPM_TWENTY_THOUSAND;
			} else {
				$result['count'] = $iaoData['count'];
			}
			
			// 如果结果不为空，则整合数据
			if (!empty($iaoData['rows']) && is_array($iaoData['rows'])) {
				// 初始化接口数据维度
				$iaoWd = array();
				
				$chargeTemp = $iaoData['charge'];
				foreach($chargeTemp as $chargeTempObj) {
					$iaoWd[$chargeTempObj['agency_id'].chr(95).$chargeTempObj['currency_type'].chr(95).chr(48)] = $chargeTempObj['amt'];
				}
				unset($chargeTemp);
				
				$expenseChargeTemp = $iaoData['expenseCharge'];
				foreach($expenseChargeTemp as $expenseChargeTempObj) {
					$iaoWd[$expenseChargeTempObj['agency_id'].chr(95).$expenseChargeTempObj['currency_type'].chr(95).chr(49)] = $expenseChargeTempObj['amt'];
				}
				unset($expenseChargeTemp);
				
				$expenseTemp = $iaoData['expense'];
				foreach($expenseTemp as $expenseTempObj) {
					$iaoWd[$expenseTempObj['agency_id'].chr(95).$expenseTempObj['currency_type'].chr(95).chr(50)] = $expenseTempObj['amt'];
				}
				unset($expenseTemp);
				
				// 整合数据
				$rowsTemp =  $iaoData['rows'];
				unset($iaoData);
				
				$rows = array();
				foreach ($rowsTemp as $rowsTempObj) {
					$temp = array();
					$temp['agencyId'] = $rowsTempObj['agency_id'];
					$temp['agencyName'] = $rowsTempObj['agency_name'];
					$temp['chargeNiuAmt'] = CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(48).chr(95).chr(48)])
									+ CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(49).chr(95).chr(48)]);
					$temp['chargeCouponAmt'] = CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(50).chr(95).chr(48)]);
					$temp['expenseNiuAmt'] = abs(CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(48).chr(95).chr(49)])) 
									+ abs(CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(49).chr(95).chr(49)]))
									+ CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(48).chr(95).chr(50)])
									+ CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(49).chr(95).chr(50)]);
					$temp['expenseCouponAmt'] = abs(CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(50).chr(95).chr(49)]))
									+ CommonTools::getEmptyNum($iaoWd[$rowsTempObj['agency_id'].chr(95).chr(50).chr(95).chr(50)]);
					$temp['availableNiuAmt'] = $rowsTempObj['balance'];
					$temp['availableCouponAmt'] = $rowsTempObj['coupon_balance'];
					$temp['niuAmt'] = $temp['availableNiuAmt'] - $temp['chargeNiuAmt'] + $temp['expenseNiuAmt'];
					$temp['couponAmt'] = $temp['availableCouponAmt'] - $temp['chargeCouponAmt'] + $temp['expenseCouponAmt'];
					array_push($rows, $temp);
				}
				unset($rowsTemp);
				$result['rows'] = $rows;
			}
			
			// 结束监控示例
			BPMoniter::endMoniter($posTry, $flag, __LINE__);
		} catch (BBException $e) {
			BPMoniter::endMoniter($posTry, Symbol::BPM_ONE_MILLION, __LINE__);
            // 抛异常
            throw $e;
        } catch (Exception $e) {
        	// 抛异常
			throw new BBException($e->getCode(), $e->getMessage(), BPMoniter::getMoniter($posTry).Symbol::CONS_DOU_COLON."获取财务报表异常", $e);
        }
        
        // 返回结果
        return $result; 
	}

}

?>