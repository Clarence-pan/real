<?php

class branchfmisController extends CController {

    public function actionIndex() {
//        $this->actionFmisinvoice(); // k.o
//        echo '<Br><Br> /*     * **************************** fmis interface doRestPostFmisInvoice ***************************** */ <Br><Br>';
//        $this->actionReconciliationlist(); // k.o | accountname
//        echo '<Br><Br> /*     * **************************** fmis interface doRestGetReconciliationList ***************************** */ <Br><Br>';
//        $this->actionReconciliationDetail(); // k.o
//        echo '<Br><Br> /*     * **************************** fmis interface doRestGetReconciliationdetail ***************************** */ <Br><Br>';
//        $this->actionFinanceInfo(); // k.o
//        echo '<Br><Br> /*     * **************************** fmis views doRestGetFinanceInfo ***************************** */ <Br><Br>';
       $this->actionStatement(); // k.o
//        echo '<Br><Br> /*     * **************************** fmis views doRestGetStatement ***************************** */ <Br><Br>';
//         $this->actionStatementFile(); // k.o
//        echo '<Br><Br> /*     * **************************** fmis views doRestGetStatementFile ***************************** */ <Br><Br>';
//         $this->actionRefund(); // k.o
//        echo '<Br><Br> /*     * **************************** fmis views doRestGetRefund ***************************** */ <Br><Br>';
//        
    }
    /*     * **************************** fmis interface doRestPostFmisInvoice ***************************** */ //0106通过

    public function actionFmisinvoice() {
        $client = new RESTClient();

        $uri = 'http://bb_branch.tuniu.com/bb/public/fmis/create-fmisinvoice';
        $params = array(
            'fmisId' => 35,
            'invoiceFlag' =>3,
        );


        $format = 'encrypt';
        $res = $client->get($uri, $params, $format);

//        $client->debug();
        var_dump($res);
    }

    /*     * **************************** fmis interface doRestGetReconciliationList ***************************** */ //0106通过

    public function actionReconciliationlist() {
        $client = new RESTClient();



        $uri = 'http://bb_branch.tuniu.com/bb/public/fmis/reconciliationlist';
        $params = array(
            'agencyId' => "",
            'accountPeriod' => "2012-12",
            'agencyName' => "",
            'limit' => 10,
            'start' => 0,
        );
        var_dump(json_encode($params));
        $format = 'encrypt';

        $res = $client->get($uri, $params, $format);


//        $client->debug();
        var_dump($res);
    }

    /*     * **************************** fmis interface doRestGetReconciliationDetail ***************************** */ //0106通过

    public function actionReconciliationDetail() {
        $client = new RESTClient();



        $uri = 'http://bb_branch.tuniu.com/bb/public/fmis/reconciliationdetail';
        $params = array(
            'agencyId' => "44",
            'accountPeriod' => "",
            'isGivenInvoice' => "",
            'limit' => 10,
            'start' => 0,
        );
        var_dump(json_encode($params));
        $format = 'encrypt';

        $res = $client->get($uri, $params, $format);

        $client->debug();
        var_dump($res);
    }

    /*     * **************************** fmis views doRestGetFinanceInfo ***************************** */

    public function actionFinanceInfo() {
        $client = new RESTClient();

        $uri = 'http://bb_branch.tuniu.com/bb/fmis/FinanceInfo';
        $params = array(
            'accountId' => 44,
            'token' => ''
        );
        var_dump(json_encode($params));
        $format = 'encrypt';

        $res = $client->get($uri, $params, $format);

        $client->debug();
        var_dump($res);
    }

    /*     * **************************** fmis views doRestGetStatement ***************************** */

    public function actionStatement() {
        $client = new RESTClient();

        $uri = 'http://www.buckbeek.me/bb/fmis/statement';
        $params = array(
            'accountId' =>1,
            'token' => '',
            'endDate' =>'2013-01-13',
            'startDate' =>'2013-01-14',
        	'startCityCode' => 200,
        	'adKey' => 'index_chosen',
        	'productName' =>'111',
        	'productId' =>'1',
            'isPaied' =>'2',
            'start' => 0,
            'limit' => 10,
        	'startCityCode' => 200,
        );

        $format = 'encrypt';

        $res = $client->get($uri, $params, $format);

        $client->debug();
        var_dump($res);
    }

    /*     * **************************** fmis views doRestGetStatementFile ***************************** */

    public function actionStatementFile() {
        $client = new RESTClient();


        $uri = 'http://bb_branch.tuniu.com/bb/fmis/statementfile';
        $params = array(
           'accountId' =>44,
            'token' => '',
            'endDate' =>'',
            'startDate' =>'',
            'isPaied' =>'1',
            'start' => 0,
            'limit' => 10,
        );

        $format = 'encrypt';

        $res = $client->get($uri, $params, $format);

        $client->debug();
        var_dump($res);
    }

    /*     * **************************** fmis views doRestPostRefund ***************************** */

    public function actionRefund() {
        $client = new RESTClient();


        $uri = 'http://bb_branch.tuniu.com/bb/fmis/update-refund';
        $params = array(
            'accountId'=>"44" ,
            'token'=>"",
            'accountName'=>"账户名",
            'accountNum'=>"20121222",
            "colUnit"=>"收款单位",
            'bankName'=>"开户行",
            'remark'=>"退款理由",
            'mobile'=>"13225685212",
            'amt'=>"11.08",
      
        );


        $format = 'encrypt';

        $res = $client->post($uri, $params, $format);

        $client->debug();
        var_dump($res);
    }

}
