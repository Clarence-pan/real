<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-4-16
 * Time: 上午11:03
 */
Yii::import('application.modules.bidmanage.models.iao.IaoReleaseMod');
Yii::import('application.modules.bidmanage.models.release.ReleaseCustom');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');

class ReleaseMod extends ReleaseCustom
{
    private $_iaoReleaseMod;

    function __construct()
    {
        parent::__construct();
        $this->_iaoReleaseMod = new IaoReleaseMod;
    }

    /**
     * 执行发布操作
     *
     * @author huangxun 20140416
     * @return bool
     */
    public function runRelease()
    {
        // 获取推广位置数组
        // $adKeyArr = $this->releaseAdKey();
        $adKeyArrRe = $this->_releaseProductMod->getPositionWd();
        $adKeyArr = $adKeyArrRe['data'];
        // 根据adKeyArr数组循环推送不同广告位数据
        foreach ($adKeyArr as $adKeyObj) {
        	$adKey = $adKeyObj['ad_key'];
            //查询配置的容纳产品最大值
            $maxShowRec = $this->getAdPositionCountByType($adKeyObj);
            //查询推广开始日期为当天的出价列表
            $bidProductArray = $this->_releaseProductMod->getProductArr($adKeyObj);
            //将需要推广的产品按推广城市位置分组
            $bidProductGroups = $this->groupingReleaseProduct($bidProductArray);
            //用以保存组名
            $groupsArr = array();
            foreach ( $bidProductGroups as $key => $bidProductGroup ) {
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, $adKey.'=>'.'当前组为：'.$key, 11, 'wuke', 11, 0, json_encode($key));
                //避免同一组重复推广
                if ( in_array( $key , $groupsArr ) ) {
                    CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '出现错误，该组多次进入循环：'.$key, 11, 'wuke', -11, 0, json_encode($key));
                    break;
                } else {
                    $groupsArr[] = $key;
                }

                //根据出发城市修改productId为从线路id
//                $bidProductArr = $this->routeStartCityCode($bidProductGroup);

                //过滤出有效的产品
                $preReleaseBidProductArr = $this->_releaseProductMod->filterValidProductArr($bidProductGroup, $maxShowRec);
                if(!empty($preReleaseBidProductArr['invalid'])){
                    foreach($preReleaseBidProductArr['invalid'] as $invalidBidProduct){
                        $this->_agencyFailReleaseProductArr[] = $invalidBidProduct;
                    }
                    //更新出价表推广状态
                    $this->runUpdateReleaseState($preReleaseBidProductArr['invalid'], '-2');
                }
                //转化产品列表数据结构，把打包的推广日期拆散
                $afterSplit = $this->splitDate($preReleaseBidProductArr['valid']);
                // 已拆散要推广至网站的数组
                $formattedProducts = $afterSplit['push'];
                // 空广告位不用推广至网站的数组
                $imperfect = $afterSplit['not_push'];
                
                //转换为接口约定的数据格式
                $releaseProductArr = $this->reconstructApiParamsArray($formattedProducts);
                //推送模块发布
                $feedBackResultArr = $this->_iaoReleaseMod->pushRoutesIntoChannelAndClsSet($releaseProductArr);

                //推送后，新增推广表记录(逐条)
                if(!empty($imperfect)||!empty($feedBackResultArr)){
                    $exeFailReleaseArr = array();
                    $exeSuccessReleaseArr = array();
                    //  推广成功数据处理
                    if(!empty($imperfect)||(!empty($feedBackResultArr['valid']) && is_array($feedBackResultArr['valid']))){
                        //推送成功的bidId数组--去重
                        $feedBackResultArrNonRepeated = array_unique($feedBackResultArr['valid']);
                        // 3.0的竞价广告位没有添加产品放入推广成功列表
                        if(!empty($imperfect)){
                            foreach($imperfect as $im){
                                $feedBackResultArrNonRepeated[] = $im['id'];
                            }
                        }

                        foreach ($feedBackResultArrNonRepeated as $bidId) {
                            foreach ($bidProductGroup as $relItems) {
                                if ($relItems['id'] == $bidId) {
                                    // 数据分装
                                    $showProductRecArr = $this->reconstructShowProductArray($relItems,$adKey);
                                    // 新增推广数据
                                    $showProductId = $this->_releaseProductMod->insertReleaseShowPrdArr($showProductRecArr);
                                    //记录成功涉及供应商信息及数额
                                    //先判断该产品是否已记录，防止重复
                                    $tempArr = $this->_agencySucReleaseProductArr;
                                    $successRecorded = 0;
                                    foreach($tempArr as $tempItem){
                                        if($tempItem['bid_id'] == $relItems['id']){
                                            $successRecorded = 1;
                                        }
                                    }
                                    if($successRecorded == 0){
                                        $tempSuccessProduct = $this->reconstructBidSuccessArray($relItems,$adKey,$showProductId);
                                        $this->_agencySucReleaseProductArr[] = $tempSuccessProduct;
                                        $exeSuccessReleaseArr[] = $tempSuccessProduct;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    // 推广失败数据处理
                    if (!empty($feedBackResultArr['invalid']) && is_array($feedBackResultArr['invalid'])) {
                        //推送失败bidId去重
                        $feedBackResultArrNonRepeated = array_unique($feedBackResultArr['invalid']);

                        foreach ($feedBackResultArrNonRepeated as $bidId) {
                            foreach ($releaseProductArr as $relItems) {
                                if ($relItems['bidId'] == $bidId) {
                                    //记录失败涉及供应商信息及数额
                                    //先判断是否已经记录，防止重复
                                    $tempFailArr = $this->_agencyFailReleaseProductArr;
                                    $failRecorded = 0;
                                    foreach ($tempFailArr as $tempFailItem) {
                                        if ($tempFailItem['bid_id'] == $relItems['bidId']) {
                                            $failRecorded = 1;
                                        }
                                    }
                                    //判断在成功记录中是否存在，防止在成功、失败记录中同时存在
                                    $tempArr = $this->_agencySucReleaseProductArr;
                                    foreach ($tempArr as $tempItem) {
                                        if ($tempItem['bid_id'] == $relItems['bidId']) {
                                            $failRecorded = 1;
                                        }
                                    }
                                    if ($failRecorded == 0) {
                                        $tmpFailRelArr = $this->reconstructionBidFailArray($relItems);
                                        $this->_agencyFailReleaseProductArr[] = $tmpFailRelArr;
                                        $exeFailReleaseArr[] = $tmpFailRelArr;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '推广-结果', 11, 'wenrui', 0, 0, json_encode($exeFailReleaseArr), json_encode($exeSuccessReleaseArr));
                    //更新出价表推广状态-推广失败
                    $this->runUpdateReleaseState($exeFailReleaseArr, '-2');
                    //更新出价表推广状态-推广成功
                    $this->runUpdateReleaseState($exeSuccessReleaseArr, '1');
                    CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, $adKey.'=>'.'本次‘'.$key.'’组推广结束', 11, 'wenrui',0,0,'');
                }
            }
        }
    }
}