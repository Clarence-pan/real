<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/24/12
 * Time: 3:58 PM
 * Description: StaIntegrateMod.php
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
Yii::import('application.modules.manage.models.user.StaUrlBuilderMod');
Yii::import('application.modules.manage.models.user.StaBiWebFlowMod');

class StaIntegrateMod
{
    private $_staUrlBuilderMod;

    private $_staBiWebFlowMod;

    function __construct()
    {
        $this->_staUrlBuilderMod = new StaUrlBuilderMod;
        $this->_staBiWebFlowMod = new StaBiWebFlowMod;
    }

    /**
     * 执行BI跟踪结果的统计数据
     *
     * @author chenjinlong 20121225
     * @return array
     */
    public function runStatisticTask()
    {
        $staRows = $this->getStatisticInfoArr();
        if(!empty($staRows) && is_array($staRows)){
            //执行更新操作
            $resetParam = array();
            foreach($staRows as $val)
            {
                $resetParam[] = array(
                    'accountId' => $val['account_id'],
                    'productId' => $val['product_id'],
                    'productType' => $val['product_type'],
                    'staDate' => $val['date'],
                    'consumption' => $val['consumption'],
                    'reveal' => $val['reveal'],
                    'ipView' => $val['ip_view'],
                    'clickNum' => $val['click_num'],
                    'orderConversion' => $val['order_conversion'],
                );
            }

            BuckbeekIao::updateShowProductEffectData($resetParam);
        }
    }

    /**
     * 获取整理后的待新增统计记录集合
     *
     * @author chenjinlong 20121225
     * @return array
     */
    public function getStatisticInfoArr()
    {
        //指定统计日期
        $staDate = defined('STA_DATE') ? STA_DATE : date("Y-m-d", strtotime('-1 day'));

        $reqParam = array(
            'account_id' => 0,
            'show_date' => $staDate,
        );
        $curDateShowProductArr = BuckbeekIao::getReleaseProductArray($reqParam);
        if(!empty($curDateShowProductArr) && is_array($curDateShowProductArr)){
            $outArr = array();
            foreach($curDateShowProductArr as $productItem)
            {
                $conditionArr = array(
                    'bid_show_product_id' =>$productItem['id'],
                    'product_id' => $productItem['product_id'],
                    'product_type' => $productItem['product_type'],
                    'ad_key' => $productItem['ad_key'],
                    'start_city_code' => $productItem['start_city_code'],
                    'cat_type' => $productItem['cat_type'],
                    'web_class' => $productItem['web_class'],
                );
                $this->_staUrlBuilderMod->buildUrlParameterArr($conditionArr);
                $trackedUrlArr = $this->_staUrlBuilderMod->outputTrackedUrlString();

                if(!empty($trackedUrlArr)){
                    //BI之URL跟踪记录行
                    $webFlowUrlTrackRow = $this->getIntegrateWebFlowBBUrlTrackInfo($trackedUrlArr);
                }else{
                    $webFlowUrlTrackRow = array(
                        'reveal' => 0,
                        'ip_view' => 0,
                    );
                }
                $outArr[] = array(
                    'account_id' => $productItem['account_id'],
                    'product_id' => $productItem['product_id'],
                    'product_type' => $productItem['product_type'],
                    'date' => $productItem['bid_date'],
                    'consumption' => $productItem['bid_price'],

                    //BI之URL跟踪记录行
                    'reveal' => $webFlowUrlTrackRow['reveal']>0?$webFlowUrlTrackRow['reveal']:0,
                    'ip_view' => $webFlowUrlTrackRow['ip_view']>0?$webFlowUrlTrackRow['ip_view']:0,
                );
            }

            return $this->reArrangeBbEffectRows($outArr);
        }else{
            return array();
        }
    }

    /**
     * 整理待插入的统计数据
     *
     * @author chenjinlong 20130106
     * @param $srcRows
     * @return array
     */
    protected function reArrangeBbEffectRows($srcRows)
    {
        if(!empty($srcRows) && is_array($srcRows)){
            //暂不兼容门票产品的检索结果的解决办法
            $productIds = array();
            foreach($srcRows as $row)
            {
                $productIds[] = $row['product_id'];
            }

            //指定统计日期
            $staDate = defined('STA_DATE') ? STA_DATE : date("Y-m-d", strtotime('-1 day'));

            $staBiPrdTrackInfo = $this->_staBiWebFlowMod->getBiProductTrackStaInfo($productIds, $staDate);

            //整合最终数组
            $outArr = array();
            foreach($srcRows as $row)
            {
                $searchKey = strval($row['account_id'].'-'.$row['product_id']);

                if(!empty($outArr[$searchKey]) && isset($outArr[$searchKey])){
                    $outArr[$searchKey]['consumption'] = $outArr[$searchKey]['consumption'] + $row['consumption'];
                    //BI之URL跟踪记录行
                    $outArr[$searchKey]['reveal'] = $outArr[$searchKey]['reveal'] + $row['reveal'];
                    $outArr[$searchKey]['ip_view'] = $outArr[$searchKey]['ip_view'] + $row['ip_view'];
                }else{
                    $outArr[$searchKey] = array(
                        'account_id' => $row['account_id'],
                        'product_id' => $row['product_id'],
                        'product_type' => $row['product_type'],

                        'date' => $row['date'],
                        'consumption' => $row['consumption'],

                        //BI之URL跟踪记录行
                        'reveal' => $row['reveal'],
                        'ip_view' => $row['ip_view'],

                        //BI之产品跟踪记录行
                        'click_num' => $staBiPrdTrackInfo[$row['product_id']]['clickNum']>0?$staBiPrdTrackInfo[$row['product_id']]['clickNum']:0,
                        'order_conversion' => $staBiPrdTrackInfo[$row['product_id']]['orderCount']>0?$staBiPrdTrackInfo[$row['product_id']]['orderCount']:0,
                    );
                }
            }

            return $outArr;
        }else{
            return array();
        }
    }

    /**
     * 批量加法计算BI之URL跟踪记录行
     *
     * @author chenjinlong 20121225
     */
    protected function getIntegrateWebFlowBBUrlTrackInfo($trackedUrlArr)
    {
        $trackInfoArr = array(
            'reveal' => 0,
            'ip_view' => 0,
        );
        if (!empty($trackedUrlArr)) {
            //指定统计日期
            $staDate = defined('STA_DATE') ? STA_DATE : date("Y-m-d", strtotime('-1 day'));

            $webFlowUrlTrackRow = $this->_staBiWebFlowMod->getBiUrlTrackStaInfo($trackedUrlArr, $staDate);
            if (!empty($webFlowUrlTrackRow) && is_array($webFlowUrlTrackRow)) {
                foreach ($webFlowUrlTrackRow as $val)
                {
                    $trackInfoArr['reveal'] += $val['reveal'] > 0 ? $val['reveal'] : 0;
                    $trackInfoArr['ip_view'] += $val['ipView'] > 0 ? $val['ipView'] : 0;
                }
            }
        }
        return $trackInfoArr;
    }

}
