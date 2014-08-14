<?php

class TestManageController extends CController{
	CONST selfUrl = 'http://hagrid-branch.tuniu.com/';
    var $_client;
    var $successCount = 0;
    var $failCount = 0;
    var $result = array();
    static $control_flag = 1;
    
    
    function __construct(){
        $this->_client = new RESTClient;
    }
	
    function actionIndex(){ 
        $result['testPostBaVendorInfo'] = $this->testPostBaVendorInfo();
        
        $result['testPostRefund']       = $this->testPostRefund();
        $result['testPostRefundFmis']   = $this->testPostRefundFmis();
        $result['testPostVendor']       = $this->testPostVendor();
        $result['testGetBaVendorInfo']  = $this->testGetBaVendorInfo();
        $this->printResult($result);
    }  
    
    
    private function testGetBaVendorInfo(){
    	if(0 == self::$control_flag)
    	   $uri = self::selfUrl.'hg/manage/baVendorinfo';
    	else 
    	   $uri = self::selfUrl.'hg/public/user/baVendorInfo';
    	   
    	$params = array(
    	   "accountId" => 1 
    	); 
    	   
    	$res = $this->_client->get($uri, $params);
    	
    	return $res;
    }
    
    
    private function testPostBaVendorInfo(){
        if(0 == self::$control_flag)
           $uri = self::selfUrl.'hg/manage/update-Vendorinfo';
        else 
           $uri = self::selfUrl.'hg/public/user/create/vendorinfo';
           
        $params = array(
	        "vendorId"=>35,
			"accountId"=>1,
			"cmpName"=>"江苏金陵商务国际旅行社有限责任公司",
			"cmpPhone"=>"11123",
			"contractor"=>111111,
			"contractorTel"=>159253289022,
			"contractorTel2"=>1222222,
			"invoiceType"=> 0,
        ); 
           
        $res = $this->_client->post($uri, $params);
        file_put_contents('ae.txt', print_r($res,true));
        return $res;
    }
    
    private function testPostRefund(){
        if(0 == self::$control_flag)
           $uri = self::selfUrl.'hg/manage/update-Refund';
        else 
           $uri = self::selfUrl.'hg/public/refund/create/Refund';
           
        $params = array(
            "accountId" =>  3,
			"vendorId"=>3,
			"amt"=> 999,
			"accountName"=> '桂林市飞扬国际旅行社有限责任公司',
			"bankName"=>'aa',
			"accountNum"=>'1',
			"remark"=>'1',
			"colUnit"=>'1',
			"mobile"=>'1111111111'
        ); 
           
        $res = $this->_client->post($uri, $params);
        file_put_contents('ae.txt', print_r($res,true));
        return $res;
    }
       
    
 private function testPostRefundFmis(){
        if(0 == self::$control_flag)
           $uri = self::selfUrl.'hg/manage/update-RefundFmis';
        else 
           $uri = self::selfUrl.'hg/public/refund/create/refundfmis';
           
        $params = array(
		    "id"=>16,
			"trade_time"=>'2013-01-10 11:10:88',
			"trade_saler_id"=>'40',
			"add_saler_id"=>'',
			"op_saler_id"=>'2431',
			"back_reason"=>'退款用例1',
			"is_back"=>'0',
			"ref_cmp_id"=>'0',
			"ref_acc_id"=>'0',
			"back_time"=>'2013-01-14 03:54:50',
            "state"=>'3'
        
        ); 
           
        $res = $this->_client->post($uri, $params);
        return $res;
    }
    
    
    private function testPostVendor(){
        if(0 == self::$control_flag)
           $uri = self::selfUrl.'hg/manage/update-Vendor';
        else 
           $uri = self::selfUrl.'hg/public/user/create/vendor';
           
        $params = array(
            "vendorId"=>35,
            "accountId"=>1,
            "cmpName"=>"江苏金陵商务国际旅行社有限责任公司",
            "cmpPhone"=>"11123",
            "contractor"=>111111,
            "contractorTel"=>159253289031,
            "contractorTel2"=>1222223,
            "invoiceType"=> 0,
        
        ); 
           
        $res = $this->_client->post($uri, $params);
        return $res;
    }
    
    private function printResult($result)
    {
    	printf("control_flag value:".self::$control_flag.'</br>');
        foreach($result as $key=>$value)
        {
            if($value)
                $this->successCount++;
            else
                $this->failCount++;
        }
        printf('用例成功：'.$this->successCount.'<br/>用例失败：'.$this->failCount.'<br/>');
        foreach($result as $key=>$value)
        {
            printf("=========================================================================================================================<br/>");
            if($value)
                $info = "<span style='background-color:green;font-weight:bold;'>".$key.'成功'.'</span>';
            else
                $info = "<span style='background-color:red;font-weight:bold;'>".$key.'失败'.'</span>';
            printf($info.'<br/>');
            print_r($value);
        }        
    }
}

?>