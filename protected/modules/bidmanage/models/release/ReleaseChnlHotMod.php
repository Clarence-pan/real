<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 10:56 PM
 * Description: ReleaseChnlHotMod.php
 */
Yii::import('application.modules.bidmanage.models.iao.IaoReleaseMod');
Yii::import('application.modules.bidmanage.models.release.ReleaseCustom');

class ReleaseChnlHotMod extends ReleaseCustom
{
    /*
     * 产品目的地大分类的字段cat_type，该字段被重新由后台BOSS产品模块系统二次定义。
     * 当前对其不作修改，沿用其原生值进行区分。参考如下：
     * 1:  around              周边
     * 2:  domestic            国内长线
     * 3:  abroad_s            出境短线
     * 4:  abroad_l            出境长线
     * 5:  domestic_local      国内当地参团
     * 6:  abroad_local        出境当地参团
     * 7:  around_drive        周边自驾游
     * 8:  domestic_drive      国内自驾游
     * 9:  special_around      牛人专线-周边
     * 10: special_l           牛人专线-国内长线
     * 11: special_abroad_s    牛人专线-出境短线
     * 12: special_abroad_l    牛人专线-出境长线
     * 13: tuniu_around        途牛自组-周边
     * 14: tuniu_around_l      途牛自组-国内长线
     * 15: tuniu_abroad_l      途牛自组-出境长线
     * 16: tuniu_abroad_s      途牛自组-出境短线
     * END @2012/12/12
     */
    private static $_aroundCatTypeArr = array(1,7,9,13);

    private static $_domesticCatTypeArr = array(2,5,8,10,14);

    private static $_abroadCatTypeArr = array(3,4,6,11,12,15,16);

    private $_iaoReleaseMod;

    function __construct()
    {
        parent::__construct();
        $this->_iaoReleaseMod = new IaoReleaseMod;
    }

    public function runRelease()
    {
        //do release job
        //查询配置的容纳产品最大值
        $maxShowRec = $this->getAdPositionCountByType('channel_hot');

        //查询当天发布的出价列表
        $srcBidProductArr = $this->_releaseProductMod->getReleaseToChannelHotProductArr();
        //将原产品数据，分类格式化
        $bidProductArr = self::formatChannelHotProductArr($srcBidProductArr);

        if(!empty($bidProductArr) && is_array($bidProductArr)){
            foreach($bidProductArr as $key => $catTypeBidArr)
            {
                //过滤出有效的产品(3类频道页面数据源)
                $preReleaseBidProductArr = $this->_releaseProductMod->filterValidProductArr($catTypeBidArr, $maxShowRec);
                if(!empty($preReleaseBidProductArr['invalid'])){
                    $this->_agencyFailReleaseProductArr = array_merge($this->_agencyFailReleaseProductArr, $preReleaseBidProductArr['invalid']);
                    //更新出价表推广状态
                    $this->runUpdateReleaseState($preReleaseBidProductArr['invalid'], '-1');
                }
                //转换为接口约定的数据格式
                $releaseProductArr = self::reconstructApiParamsArray($preReleaseBidProductArr['valid']);

                //推送到网站频道页发布
                //反馈的失败竞价ID和成功的竞价ID数组
                $feedBackResultArr = $this->_iaoReleaseMod->pushRoutesIntoChannelAndClsSet($releaseProductArr);
                if(!empty($feedBackResultArr['invalid'])){
                    $tmpFailReleaseProductArr = self::filterReleaseProductArrByBidId($feedBackResultArr['invalid'], $preReleaseBidProductArr['valid']);
                    $this->_agencyFailReleaseProductArr = array_merge($this->_agencyFailReleaseProductArr, $tmpFailReleaseProductArr);
                    //更新出价表推广状态
                    $this->runUpdateReleaseState($tmpFailReleaseProductArr, '-2');
                }
                //推送成功，新增推广表记录(逐条)
                if(!empty($feedBackResultArr['valid'])){
                    foreach($feedBackResultArr['valid'] as $bidId)
                    {
                        foreach($releaseProductArr as $relItems)
                        {
                            if($bidId == $relItems['bidId']){
                                //新增推广表记录
                                $showProductRecArr = array(
                                    'account_id' => $relItems['accountId'],
                                    'product_id' => $relItems['productId'],
                                    'product_type' => $relItems['productType'],
                                    'bid_date' => $relItems['showDate'],
                                    'ad_key' => $relItems['adKey'],
                                    'cat_type' => $relItems['catType'],
                                    'web_class' => $relItems['webClass'],
                                    'start_city_code' => $relItems['startCityCode'],
                                    'bid_price' => $relItems['bidPrice'],
                                    'ranking' => $relItems['ranking'],
                                    'bid_id' => $relItems['bidId'],
                                );
                                $showProductId = $this->_releaseProductMod->insertReleaseShowPrdArr($showProductRecArr);
                                //记录成功涉及供应商信息及数额
                                $this->_agencySucReleaseProductArr[] = array(
                                    'account_id' => $relItems['accountId'],
                                    'amt' => $relItems['bidPrice'],
                                    'serial_id' => $showProductId,

                                    'bid_id' => $relItems['bidId'],
                                    'bid_mark' => $relItems['bid_mark'],
                                );
                            }
                        }
                    }
                    //更新出价表推广状态
                    $this->runUpdateReleaseState($this->_agencySucReleaseProductArr, '1');
                }

                //推送失败，记录失败涉及供应商信息及数额
                if(!empty($feedBackResultArr['invalid'])){
                    foreach ($feedBackResultArr['invalid'] as $bidId)
                    {
                        foreach($releaseProductArr as $relItems)
                        {
                            if($bidId == $relItems['bidId']){
                                //记录失败涉及供应商信息及数额
                                $this->_agencyFailReleaseProductArr[] = array(
                                    'account_id' => $relItems['accountId'],
                                    'amt' => $relItems['bidPrice'],

                                    'bid_id' => $relItems['bidId'],
                                    'bid_mark' => $relItems['bid_mark'],
                                );
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * 重新组织拼装数组，适用于调用频道页发布接口
     *
     * @author chenjinlong 20121210
     * @param $inArr
     * @return array()
     */
    protected static function reconstructApiParamsArray($inArr)
    {
        if(!empty($inArr) && is_array($inArr)){
            $outArr = array();
            $formatRankArr = array();
            foreach($inArr as $bidProductRow)
            {
                $index = count($formatRankArr[$bidProductRow['start_city_code'].'_'.$bidProductRow['cat_type']]) + 1;
                $tmpArr = array(
                    'bidId' => $bidProductRow['id'],
                    'accountId' => $bidProductRow['account_id'],
                    'productId' => $bidProductRow['product_id'],
                    'productType' => $bidProductRow['product_type'],
                    'showDate' => $bidProductRow['bid_date'],
                    'adKey' => $bidProductRow['ad_key'],
                    'catType' => $bidProductRow['cat_type'],
                    'webClass' => $bidProductRow['web_class'],
                    'startCityCode' => $bidProductRow['start_city_code'],
                    'bidPrice' => $bidProductRow['bid_price'],
                    'ranking' => $index,

                    'bid_mark' => $bidProductRow['bid_mark'],
                );
                $formatRankArr[$bidProductRow['start_city_code'].'_'.$bidProductRow['cat_type']][] = $tmpArr;

                $outArr[] =  $tmpArr;
            }
            return $outArr;
        }else{
            return array();
        }
    }

    /**
     * 将原始数据格式转换，以产品线顶级分类划分
     *
     * @author chenjinlong 20121211
     * @param $inArr
     * @return array
     */
    public static function formatChannelHotProductArr($inArr)
    {
        if(!empty($inArr) && is_array($inArr)){
            $outArr = array();
            foreach($inArr as $bidProduct)
            {
                $catType = $bidProduct['cat_type'];
                if(in_array($catType, self::$_abroadCatTypeArr)){
                    $outArr[34][] = $bidProduct;
                }elseif(in_array($catType, self::$_aroundCatTypeArr)){
                    $outArr[1][] = $bidProduct;
                }elseif(in_array($catType, self::$_domesticCatTypeArr)){
                    $outArr[2][] = $bidProduct;
                }
            }

            return $outArr;
        }else{
            return array();
        }
    }

    /**
     * 根据出价编号，在有限数据集合中查找详情
     *
     * @author chenjinlong 20121211
     * @param $bidId
     * @param $srcReleaseProductInfoArr
     */
    public static function filterReleaseProductArrByBidId($bidIdArr, $srcReleaseProductInfoArr)
    {
        if (!empty($srcReleaseProductInfoArr) && !empty($bidIdArr)) {
            $outArr = array();
            foreach($bidIdArr as $bidId)
            {
                foreach($srcReleaseProductInfoArr as $elem)
                {
                    if($bidId == $elem['id']){
                        $outArr[] = array(
                            'account_id' => $elem['account_id'],
                            'amt' => $elem['bid_price'],

                            'bid_id' => $elem['id'],
                            'bid_mark' => $elem['bid_mark'],
                        );
                    }
                }
            }
            return $outArr;
        }else{
            return array();
        }
    }

}
