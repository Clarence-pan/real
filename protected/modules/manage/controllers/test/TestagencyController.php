<?php

class TestagencyController extends CController{
	
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
//    	$result['testGetaccountInfo'] = $this->testGetaccountInfo();
//        $result['testGetvendorInfo'] = $this->testGetvendorInfo();  //暂时拿不到数据，验证完插入之后再验	  
//    	$result['testGetagencylists'] = $this->testGetagencylists();
    	$result['testAgencyAdd'] = $this->testAgencyAdd();
//    	$result['testAgencydel'] = $this->testAgencydel();
//        $result['testRefundList'] = $this->testRefundList();
//    	//testRefundAudit  需要有数据  用例验完需要去数据库和页面上验证
//    	$result['testRefundAudit'] = $this->testRefundAudit();
//    	
//    	$result['testreconciliationList'] = $this->testreconciliationList();
//    	$result['testreconciliationdetail'] = $this->testreconciliationdetail();
    	
    	$this->printResult($result);
    }        
    
    
    
    
    /**
     * [ui-user]收客宝帐号列表
     * @param array $data
     */
    public function testGetaccountInfo()
    {
    	if(0 == self::$control_flag)
    	   $url = self::selfUrl.'hg/agency/accountinfo';
    	else 
    	   $url = self::selfUrl.'hg/user/accountinfo';   
    	$params = array(
//		    "rp"=> 10,
//		    "start"=> 0,
//		    "limit"=> 10,
//		    "sortname"=> "",
//		    "sortorder"=> "",
//		    "accountName"=> "",
//		    "agencyId"=> "",
//		    "certificateFlag"=> "1",
//		    "uid"=> "4220",
//		    "nickname"=> "王龙生"

    "rp"=> 10,
    "start"=> 0,
    "limit"=> 10,
    "sortname"=> "",
    "sortorder"=> "",
    "accountName"=> "",
    "agencyId"=> "",
    "certificateFlag"=> "-1",
    "uid"=> "2315",
    "nickname"=> "吴焕红"

    	);   
        $res = $this->_client->get($url, $params);   
//        var_dump($res);
        return $res;
    }
    
    public function testGetvendorInfo()
    {
        if(0 == self::$control_flag)
           $url = self::selfUrl.'hg/agency/vendorinfo';
        else 
           $url = self::selfUrl.'hg/public/user/VendorInfo';   
        $params = array(
            "accountId"=>"1",
            "uid"=>"4220",
            "nickname"=>"王龙生"
        );   
        $res = $this->_client->get($url, $params);   
//        var_dump($res);
        return $res;
    }
    
    public function testGetagencylists()
    {
        if(0 == self::$control_flag)
           $url = self::selfUrl.'hg/agency/agencylists';
        else 
           $url = self::selfUrl.'/hg/user/agencylists';   
        $params = array(
            "rp"=> 8,
		    "start"=> 0,
		    "limit"=> 8,
		    "sortname"=> "",
		    "sortorder"=> "",
		    "uid"=> "4220",
		    "nickname"=> "王龙生"
        );   
        $res = $this->_client->get($url, $params);   
//        var_dump($res);
        return $res;
    }
    
    
    private function testAgencyAdd(){
    	if(0 == self::$control_flag)
           $url = self::selfUrl.'/hg/agency/update-agencyadd';
        else 
           $url = self::selfUrl.'hg/user/update/agencyadd';   
        $params = array(
			    "uid"=> "4220",
			    "nickname"=> "王龙生",
			    "addedList"=> 
                    array(
			        array(
		            "id"=> "250",
		            "agencyName"=> "江苏中山国际旅行社",
		            "mainBusiness"=> ""
	                )
	                )
        );   
        
        
        $res = $this->_client->post($url, $params);   
//        file_put_contents('ax.txt', print_r($res,true));
        
//        var_dump($res);
        return $res;
    	
    	
    }
       
    private function testAgencydel(){
        if(0 == self::$control_flag)
           $url = self::selfUrl.'/hg/agency/update-deleteaccount';
        else 
           $url = self::selfUrl.'hg/user/update/deleteaccount';      
        $params = array(
		    "agencyId"=> "37",
		    "uid"=> "4220",
		    "nickname"=> "王龙生"

        );   
        
        
        $res = $this->_client->post($url, $params);   
//        file_put_contents('ax.txt', print_r($res,true));
        
//        var_dump($res);
        return $res;
        
        
    }
    
    public function testRefundAudit()
    {
    	if(0 == self::$control_flag)
           $url = self::selfUrl.'/hg/agency/update-refundaudit';
        else 
           $url = self::selfUrl.'hg/refund/update/refundaudit';   
        $params = array(
            //修改 operateFlag = 1为确认  -1为驳回
		    "operateFlag"=> 1,
		    "applicationId"=> "18",
		    "uid"=> "4220",
		    "nickname"=> "王龙生"
        );   
        
        
        $res = $this->_client->post($url, $params);   
//        file_put_contents('ax.txt', print_r($res,true));
        
//        var_dump($res);
        return $res;
    }
   
    
    private function testRefundList()
    {
        if(0 == self::$control_flag)
           $url = self::selfUrl.'/hg/agency/refundlist';
        else 
           $url = self::selfUrl.'hg/refund/RefundList';   
        $params = array(
		    "rp"=> 10,
		    "start"=> 0,
		    "limit"=> 10,
		    "sortname"=> "",
		    "sortorder"=> "",
		    "refundTab"=> 1,
		    "uid"=> "4220",
		    "nickname"=> "王龙生"
        );   
        
        
        $res = $this->_client->get($url, $params);   
//        file_put_contents('ax.txt', print_r($res,true));
        
//        var_dump($res);
        return $res;
    }
    
    
    
    /*
     * 
    "rp": 10,
    "start": 0,
    "limit": 10,
    "sortname": "",
    "sortorder": "",
    "uid": "4220",
    "nickname": "王龙生"
     * */
    private function testreconciliationList()
    {
        if(0 == self::$control_flag)
           $url = self::selfUrl.'/hg/agency/reconciliationlist';
        else 
           $url = self::selfUrl.'hg/reconciliation/reconciliationlist';   
        $params = array(
            "rp"=> 10,
            "start"=> 0,
            "limit"=> 10,
            "sortname"=> "",
            "sortorder"=> "",
            "uid"=> "4220",
            "nickname"=> "王龙生"
            
            
//            {
//    "rp": 10,
//    "start": 0,
//    "limit": 10,
//    "sortname": "",
//    "sortorder": "",
//    "uid": "4064",
//    "nickname": "杨怀江"
//}
            
            
        );   
        
        
        $res = $this->_client->get($url, $params);   
//        file_put_contents('ax.txt', print_r($res,true));
        
//        var_dump($res);
        return $res;
    }
    
    
    /*
     *
    "rp": 10,
    "start": 0,
    "limit": 10,
    "sortname": "",
    "sortorder": "",
    "agencyId": "1",
    "accountPeriod": "2013-01",
    "uid": "4220",
    "nickname": "王龙生"
*/
    private function testreconciliationdetail()
    {
        if(0 == self::$control_flag)
           $url = self::selfUrl.'/hg/agency/reconciliationdetail';
        else 
           $url = self::selfUrl.'hg/reconciliation/reconciliationdetail';
        $params = array(
            "rp"=> 10,
            "start"=> 0,
            "limit"=> 10,
            "sortname"=> "",
            "sortorder"=> "",
            "agencyId"=> "1",
            "accountPeriod"=> "2013-01",
            "uid"=> "4220",
            "nickname"=> "王龙生"
        );   
        
        
        $res = $this->_client->get($url, $params);   
//        file_put_contents('ax.txt', print_r($res,true));
        
//        var_dump($res);
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