<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/24/12
 * Time: 4:03 PM
 * Description: StaBiWebFlowDao.php
 */
Yii::import('application.dal.dao.DaoModule');

class StaBiWebFlowDao
{
    /**
     * 查询BI之当天网站URL效果统计表
     *
     * @author chenjinlong 20121224
     * @param $trackedUrlArr
     * @param $staDate
     * @return array
     */
    public function queryWebFlowBBUrlTrackInfo($trackedUrlArr, $staDate)
    {   
        $trackedUrlStr = implode("','", $trackedUrlArr);
        if (strval($trackedUrlStr)) {
            $statisticDate = $staDate ? $staDate : date("Y-m-d", strtotime('-1 day'));
            //根据url和static_date查询BI网站效果表信息
            $sql = "SELECT [PV], [IP], [url]
                    FROM [BI_EXCHANGE].[mid].[webflow_bb_url_pv_ip_order]
                    WHERE [url] IN ('$trackedUrlStr')
                    AND [StaticDate] = '$statisticDate'";

            $rows = self::fetchMsSqlRows($sql);//Yii::app()->bi_slave->createCommand($sql)->queryAll();
            if (!empty($rows) && is_array($rows)) {
                return $rows;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    /**
     * 查询BI之当天网站产品效果表
     *
     * @author chenjinlong 20121224
     * @param $productIdArr
     * @param $staDate
     * @return array
     */
    public function queryWebFlowBBRouteTrackInfo($productIdArr, $staDate)
    {
        if(is_array($productIdArr) && !empty($productIdArr) && !empty($staDate)){
            $productIdStr = implode(',', $productIdArr);
            $statisticDate = $staDate ? $staDate : date("Y-m-d", strtotime('-1 day'));
            //根据product_id和static_date查找BI产品效果表信息
            $sql = "SELECT [StaticDate] , [route_id] , [PV] , [order_count]
                    FROM [BI_EXCHANGE] .[mid] .[webflow_bb_route_pv_order]
                    WHERE [route_id] IN ($productIdStr)
                    AND [StaticDate] = '$statisticDate'";

            $rows = self::fetchMsSqlRows($sql);//Yii::app()->bi_slave->createCommand($sql)->queryAll();
            if(!empty($rows) && is_array($rows)){
                return $rows;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    /**
     * 临时解决PDO_MSSQL扩展问题
     */
    private static function fetchMsSqlRows($sql)
    {
        $host = Yii::app()->params['extended_db']['bi_slave']['host'];
        $dbname = Yii::app()->params['extended_db']['bi_slave']['dbname'];
        $username = Yii::app()->params['extended_db']['bi_slave']['usename'];
        $password = Yii::app()->params['extended_db']['bi_slave']['password'];
        $isPersistent = Yii::app()->params['extended_db']['bi_slave']['persistent'];

        if($isPersistent){
            $mssqlResource = mssql_pconnect($host, $username, $password);
        }else{
            $mssqlResource = mssql_connect($host, $username, $password);
        }
        mssql_select_db($dbname, $mssqlResource);
        $query = mssql_query($sql, $mssqlResource);
        $outArr = array();
        while ($resultObject = mssql_fetch_object($query)) {
            $outArr[] = (array)$resultObject;
        }
        return $outArr;
    }

}
