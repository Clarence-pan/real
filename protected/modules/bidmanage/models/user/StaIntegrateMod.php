<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/24/12
 * Time: 3:58 PM
 * Description: StaIntegrateMod.php
 */
Yii::import('application.modules.bidmanage.models.product.ReleaseProductMod');
Yii::import('application.modules.bidmanage.models.user.StaUrlBuilderMod');
Yii::import('application.modules.bidmanage.models.iao.IaoHagridMod');
Yii::import('application.modules.bidmanage.models.user.StaBbEffectMod');

class StaIntegrateMod
{
    private $_staUrlBuilderMod;

    private $_releaseProductMod;

    private $_iaoHagridMod;

    private $_staBbEffectMod;

    function __construct()
    {
        $this->_staUrlBuilderMod = new StaUrlBuilderMod;
        $this->_releaseProductMod = new ReleaseProductMod;
        $this->_iaoHagridMod = new IaoHagridMod;
        $this->_staBbEffectMod = new StaBbEffectMod;
    }

    /**
     * 更新BI统计记录
     *
     * @author chenjinlong 20130314
     * @param $staRows
     */
    public function doUpdateStatisticRec($staRows)
    {
        if(!empty($staRows) && is_array($staRows)){
            //逻辑清除影响二次统计的数据
            $params = array(
                'sta_date' => $staRows[0]['date'],
                'misc' => '手工执行二次统计',
            );
            $this->_staBbEffectMod->removeOlderEffectedRows($params);

            foreach($staRows as $row)
            {
                $checkingParamArr = array(
                    'account_id' => $row['account_id'],
                    'product_id' => $row['product_id'],
                    'product_type' => $row['product_type'],
                    'date' => $row['date'],
                );
                $existRow = $this->_staBbEffectMod->getSpecificBbEffectRecordArr($checkingParamArr);
                if(empty($existRow)){
                    //新增记录
                    $this->_staBbEffectMod->insertEffectedRecord($row);
                }else{
                    //更新记录
                    $tgtArr = array(
                        'consumption' => $existRow['consumption'] + $row['consumption'],
                        'reveal' => $existRow['reveal'] + $row['reveal'],
                        'ip_view' => $existRow['ip_view'] + $row['ip_view'],
                        'click_num' => $existRow['click_num'] + $row['click_num'],
                        'order_conversion' => $existRow['order_conversion'] + $row['order_conversion'],
                    );
                    $conditionArr = array(
                        'account_id' => $existRow['account_id'],
                        'product_id' => $existRow['product_id'],
                        'product_type' => $existRow['product_type'],
                        'date' => $existRow['date'],
                    );
                    $this->_staBbEffectMod->updateSpecificBbEffectRecordArr($tgtArr, $conditionArr);
                }
            }
        }
        CommonSysLogMod::log(__FUNCTION__, 'BI跟踪数据统计', 2, 'chenjinlong', 0, 0, '', '', json_encode($staRows));
    }

    /**
     * 执行BI跟踪结果的统计
     *
     * @author chenjinlong 20121225
     * @return array
     */
    public function runStatisticTask()
    {
        $staRows = $this->getStatisticInfoArr();
        if(!empty($staRows) && is_array($staRows)){
            foreach($staRows as $row)
            {
                $checkingParamArr = array(
                    'account_id' => $row['account_id'],
                    'product_id' => $row['product_id'],
                    'product_type' => $row['product_type'],
                    'date' => $row['date'],
                );
                $existRow = $this->_staBbEffectMod->getSpecificBbEffectRecordArr($checkingParamArr);
                if(empty($existRow)){
                    //新增记录
                    $this->_staBbEffectMod->insertEffectedRecord($row);
                }else{
                    //更新记录
                    $tgtArr = array(
                        'consumption' => $existRow['consumption'] + $row['consumption'],
                        'reveal' => $existRow['reveal'] + $row['reveal'],
                        'ip_view' => $existRow['ip_view'] + $row['ip_view'],
                        'click_num' => $existRow['click_num'] + $row['click_num'],
                        'order_conversion' => $existRow['order_conversion'] + $row['order_conversion'],
                    );
                    $conditionArr = array(
                        'account_id' => $existRow['account_id'],
                        'product_id' => $existRow['product_id'],
                        'product_type' => $existRow['product_type'],
                        'date' => $existRow['date'],
                    );
                    $this->_staBbEffectMod->updateSpecificBbEffectRecordArr($tgtArr, $conditionArr);
                }
            }
        }
        CommonSysLogMod::log(__FUNCTION__, 'BI跟踪数据统计', 2, 'chenjinlong', 0, 0, '', '', json_encode($staRows));
    }

    /**
     * 获取整理后的待新增统计记录集合
     *
     * @author chenjinlong 20121225
     * @return array
     */
    public function getStatisticInfoArr()
    {
        $curDateShowProductArr = $this->_releaseProductMod->getCurDateShowProductArray();
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
            //收集涉及影响的产品ID,查询BI追踪的产品ID统计数据
            $productIdArr = array();
            foreach($srcRows as $row)
            {
                $productIdArr[] = array(
                    'productId' => $row['product_id'],
                    'productType' => $row['product_type'],
                );
            }
            $staBiPrdTrackInfo = $this->_iaoHagridMod->getIntegratedBiProductTrackSta($productIdArr);

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
            $webFlowUrlTrackRow = $this->_iaoHagridMod->getBiUrlTrackSta($trackedUrlArr);
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


    /**
     * 获取所有当天BI发布数据相关的跟踪URL
     *
     * @author chenjinlong 20130105
     * @return array
     */
    public function getStatisticUrlSet()
    {
        $curDateShowProductArr = $this->_releaseProductMod->getCurDateReleaseProductArray();
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
                if(!empty($trackedUrlArr) && is_array($trackedUrlArr)){
                	$keys = array_keys($trackedUrlArr);
                    $outArr[$keys[0]] = $trackedUrlArr[$keys[0]];
                }else{
                    continue;
                }
                
            }
            return array_unique($outArr);
        }else{
            return array();
        }
    }

}
