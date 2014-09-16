<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 14-4-16
 * @time 上午11:27
 * @description RorProductIao.php
 */ 
class RorProductIao 
{
    private $_client;

    function __construct()
    {
        $this->_client = new RESTClient();
    }

    public function querySimilarProductList($inputParams)
    {
    	$bbLog = new BBLog();
        $queryParams = array(
            'isQuery' => 'true',
        );
        if($inputParams['productId'] > 0){
            $queryParams['productId'] = $inputParams['productId'];
        }
        if($inputParams['productType'] > 0){
            $queryParams['classBrandType'] = ConstDictionary::$bbRorProductMapping[$inputParams['productType']];
        }
        if($inputParams['startCityCode'] > 0){
            $queryParams['cityCode'] = $inputParams['startCityCode'];
        }
        if($inputParams['vendorId'] > 0 && !in_array($inputParams['productType'], array(3,33,))){
            $queryParams['vendorId'] = $inputParams['vendorId'];
        }
        if($inputParams['webClassId'] > 0){
            $queryParams['categoryId'] = $inputParams['webClassId'];
        }
        if($inputParams['limit'] > 0 && $inputParams['start'] >= 0){
            $queryParams['currentPage'] = $inputParams['start'] / $inputParams['limit'] + 1;
            // $queryParams['limit'] = $inputParams['limit'];
            $queryParams['limit'] = 60;
        }
        if(!empty($inputParams['productNameKeyword']) && $inputParams['productNameType'] > 0){
            $queryParams['key'] = $inputParams['productNameKeyword'];
        }
        // 新增首页广告位产品种类批量查询
        if (is_array($inputParams['categoryId']) && !empty($inputParams['categoryId'])) {
            $queryParams['categoryIds'] = $inputParams['categoryId'];
        }
        if (is_array($inputParams['classBrandTypes']) && !empty($inputParams['classBrandTypes'])) {
            $queryParams['classBrandTypes'] = $inputParams['classBrandTypes'];
        }
        if (is_array($inputParams['catType']) && !empty($inputParams['catType'])) {
            $queryParams['productCatType'] = $inputParams['catType'];
        }
        /*if(!empty($inputParams['checkFlag'])){

        }
        if($inputParams['adKey']){

        }*/
        $outputResult = array(
            'success' => true,
            'data' => array(),
        );
        $uri = Yii::app()->params['PLA_HOST'] . 'ror/category/query';
		// $uri = "http://public-api.bj.pla.tuniu.org/ror/category/query";
        try{
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
        	$result = $this->_client->get($uri, $queryParams);
            
            if($result['success']){
                $finalFormatResult = array(
                    'count' => $result['data']['count'],
                    'rows' => array(),
                );
                foreach($result['data']['rows'] as $eachProduct)
                {
                    if($eachProduct['classBrandId'] != 6){
                        $finalFormatResult['rows'][] = array(
                            'productId' => $eachProduct['productId'],
                            'productName' => $eachProduct['productName'],
                            'agencyProductName' => '',
                            'checkFlag' => 2,
                            'productType' => ConstDictionary::$rorBbProductMapping[$eachProduct['classBrandId']],
                            //'startCityCode' => $eachProduct['bookCity'][0]['bookCityCode'],
                            'bookCityArr' => $eachProduct['bookCity'],
                            'tuniuPrice' => floor($eachProduct['price']),
                            'ticketProductId' => 0,
                        );
                    }else{
                        foreach($eachProduct['ticketList'] as $eachTicketProduct)
                        {
                            $finalFormatResult['rows'][] = array(
                                'productId' => str_replace('poi', '', $eachProduct['id']),
                                'productName' => $eachProduct['scenicName'],
                                'agencyProductName' => '',
                                'checkFlag' => 2,
                                'productType' => ConstDictionary::$rorBbProductMapping[$eachProduct['classBrandId']],
                                //'startCityCode' => $eachTicketProduct['bookCity'][0]['bookCityCode'],
                                'bookCityArr' => $eachProduct['bookCity'],
                                'tuniuPrice' => floor($eachTicketProduct['tuniuPrice']),
                                'ticketProductId' => $eachTicketProduct['ticketId'],
                            );
                            break;
                        }
                    }
                }
                $outputResult['data'] = $finalFormatResult;
            }else{
                $outputResult['data'] = array(
                    'count' => 0,
                    'rows' => array(),
                );
            }
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface($queryParams, $uri, $result, chr(48), $posM, 700, __METHOD__.'::'.__LINE__);
			}
        }catch (Exception $e){
        	// 打印日志
        	if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231300, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231300)], $result, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface($queryParams, $uri, $result, chr(48), $posM, 700, __METHOD__.'::'.__LINE__);
        	}
            $outputResult['data'] = array(
                'count' => 0,
                'rows' => array(),
            );
        }
        return $outputResult;
    }

    public function queryWebCategoryList($inputParams)
    {
    	$bbLog = new BBLog();
        $queryParams = array(
            'isQuery' => 'true',
            'isFilter' => 'true',
        );
        if($inputParams['agencyId']){
            $queryParams['vendorId'] = $inputParams['agencyId'];
        }
        if($inputParams['startCityCode']){
            $queryParams['cityCode'] = $inputParams['startCityCode'];
        }
        if(!empty($inputParams['classBrandTypes'])){
        	$queryParams['classBrandTypes'] = $inputParams['classBrandTypes'];
        }
        if(!empty($inputParams['categoryId'])){
        	$queryParams['categoryId'] = $inputParams['categoryId'];
        }
        /*if($inputParams['classificationName']){

        }
        if($inputParams['classificationDepth']){

        }
        if($inputParams['start']){

        }
        if($inputParams['limit']){

        }*/
        $uri = Yii::app()->params['PLA_HOST'] . 'ror/category/statis/query';
        //$uri = "http://public-api.bj.pla.tuniu.org/ror/category/statis/query";
        try{
        	// 开启监控
			$posM = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $result = $this->_client->get($uri, $queryParams);
            // 填充日志
			if ($bbLog->isInfo()) {
				$bbLog->logInterface($queryParams, $uri, $result, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
			}
            if($result['success']){
                return $result['data'];
            }else{
                return array();
            }
        }catch (Exception $e){
        	// 打印日志
        	if ($bbLog->isInfo()) {
            	$bbLog->logException(ErrorCode::ERR_231301, ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231301)], $result, CommonTools::getErrPos($e));
            	$bbLog->setAuth();
            	$bbLog->logInterface($queryParams, $uri, $result, chr(48), $posM, 500, __METHOD__.'::'.__LINE__);
        	}
            return array();
        }
    }

}
 
