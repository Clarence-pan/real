<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
Yii::import('application.modules.bid.models.ProductMod');
class ProductClientController extends CController{
    //static $tUrl='http://bb.test.tuniu.org';
    static $tUrl='http://buckbeek-branch.tuniu.com';
    private $_client;
    static $control_flag = 0;
    var $successCount = 0;
    var $failCount = 0;
    var $result = array();
    //
    function __construct()
    {
        $this->_client = new RESTClient();
    }
    
    function actionIndex(){
        $result['webclass'] = $this->actionWebClass();
        $result['allproduct'] = $this->actionAllproduct();
        $result['getproduct'] = $this->actionGetproduct();
        $result['postproduct'] = $this->actionPostproduct();
        $result['postproductDel'] = $this->actionPostproductDel();
        
        $this->printResult($result);
        
    }
    
           
    /*test for : http://www.tuniu.cn/bb/bid/product */
    public function actionWebClass(){
        if(1 == self::$control_flag)
            $url = self::$tUrl.'/bb/product/webclass';
        else 
            $url = self::$tUrl.'/bb/product/webclass';
        
        $param = Array(
            'accountId'=>'1',
            'token'=>'XXXXXXXXXXXXXXXXXXXXXX',
            'productId'=>'9235'           
        );
        $output = $this->_client->get($url,$param);
        
       // var_dump($output);
        return $output;
    }
    /*test for: http://www.tuniu.cn/bb/bid/update-product*/
    public function actionPostproduct()
    {
        if(1 == self::$control_flag)
            $url = '';//self::$tUrl.'/bb/bid/update-product';
        else
            $url = self::$tUrl.'/bb/product/update-product';    
        
        $param = array(
            "accountId"=> '1',
            "delFlag"=> '0',
            "r"=> '0.8665123255816861',
            "productIds" =>"9144",
            "token" => "5351888f8b0bf8d7de2bf7"
            
        );
        $output = $this->_client->post($url,$param);
        if($output['success'])
        {
           // echo 'actionPostproduct add succuss';
        }
        //var_dump($output);
        //print_r($output);
        return $output;
    }
    public function actionPostproductDel()
    {
        if(1 == self::$control_flag)
            $url = '';//self::$tUrl.'/bb/bid/update-product';
        else
            $url = self::$tUrl.'/bb/product/update-product';    
        
        $param = array(
            "accountId"=> '1',
            "delFlag"=> '1',
            "r"=> '0.8665123255816861',
            "productIds" =>"9144",
            "token" => "5351888f8b0bf8d7de2bf7"
            
        );
        $output = $this->_client->post($url,$param);
        if($output['success'])
        {
       //     echo 'actionPostproductDel add succuss</br>--------------------------------</br>';
        }
        return $output;
        //print_r($output);
    }
    
    
     public function actionGetproduct()
    {
        if(1 == self::$control_flag)
            $url = self::$tUrl.'/bb/bid/product';
        else
            $url = self::$tUrl.'/bb/product/product';    
        
        $param = array(
            "accountId"=> 1,
  "limit" => 10,
  "start" => 0,
  "token" => "5351888f8b0bf8d7de2bf7",
"searchKey"=>"",     //检索关键字
"searchType"=>"",    //检索字段, 1:供应商产品名称,2:途牛产品名称 默认为1
"startCityCode"=>"0", //出发城市编号 默认为0
"rankIsChange"=>"1",   //排名发生变化 0=所有 1=仅排名变化的 默认为0
"sortType"=>"4",      //排序方式 1:产品更新时间,2：订单数，3：浏览量 4=推广添加时间,5：均价,6：投放时间,7：过期时间,8：天数      默认为1
"orderType"=>"1",   //1：降序    2：升序  默认为1
        );
        $output = $this->_client->get($url,$param);
       // print_r($output);
        return $output;
    }
    
    public function actionAllproduct()
    {
       
        if(1 == self::$control_flag)
            $url = self::$tUrl.'/bb/bid/allproduct';
        else 
            $url = self::$tUrl.'/bb/product/allproduct';
        
        
        $param = array(
            "accountId"=> 1,
            "limit" => 10,
            "start" => 0,
            "token" => "5351888f8b0bf8d7de2bf7",
            "searchKey"=>"",  //检索关键字
            "searchType"=>"2", //检索字段, 1:供应商产品名称,2:途牛产品名称.默认为1
            "startCityCode"=>0, //出发城市编号 默认为0
            "checkerFlag"=>0,   //审核状态 默认为0
            "sortType"=>"1",      //排序方式 1:产品更新时间,2：订单数，3：浏览量 默认为1
            "productType"=>"1",	  //1:跟团游,2:公司旅游,3:自助游,4:签证,105:当地参团,106:自驾游  默认为1
            "isAdded"=>0,   //添加状态  0：未添加   1：已添加  
            "rankIsChange"=> 0,
            "r"=> 0.5014492632032351,
//    "accountId": "1",
//    "token": "5351888f8b0bf8d7de2bf7",
//    "r": 0.5014492632032351,
//    "startCityCode": "0",
//    "rankIsChange": 0,
//    "searchKey": "",
//    "searchType": "2",
//    "sortType": 1,
//    "start": 0,
//    "limit": 10

            
            
        );
        $output = $this->_client->get($url,$param);
        //print_r($output);
        return $output;
    }
    

    private function printResult($result)
    {
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
                $info = "<span style='background-color:green;font-weight:bold;'>".$key.'失败'.'</span>';
            printf($info.'<br/>');
            print_r($value);
        }        
    }
    
}
?>
