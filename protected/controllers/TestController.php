<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 7:28 PM
 * Description: TestController.php
 */
class TestController extends CController
{
    /**
     * 查看当前PHP版本信息
     */
    public function actionPhpinfo()
    {
        phpinfo();
    }

    public function actionMssqltest()
    {
        error_reporting(E_ALL);

        $priority = $_GET['level']?$_GET['level']:0;
        mssql_min_error_severity($priority);

        $host = $_GET['host']?$_GET['host']:'172.22.0.193:1433';

        $mssql_resource = mssql_pconnect($host, 'skb', 'skb12#df35r5yy');
        var_dump($mssql_resource);
        echo '<br />';

        if(!$mssql_resource){
            die('MSSQL error: ' . mssql_get_last_message());
        }

        mssql_select_db('BI_EXCHANGE', $mssql_resource);
        $msSql = "SELECT TOP 10 [StaticDate] , [route_id] , [PV] , [order_count]
                     FROM [BI_EXCHANGE] .[mid] .[webflow_bb_route_pv_order]";
        echo $msSql;
        echo '<br />';

        $query = mssql_query($msSql, $mssql_resource);

        if(!$query){
            die('MSSQL error: ' . mssql_get_last_message());
        }

        echo mssql_num_rows($query) . '<br />';
        var_dump($query);
        echo '<br />';
        while ($resultObject = mssql_fetch_object($query)) {
            //if($row = mssql_fetch_array($query)){
            //    var_dump($row);;
            //}
            $arr[] = (array)$resultObject;
        }
        $errorMsg = mssql_get_last_message();
        var_dump($arr, $errorMsg);
    }

}
