<?php
/**
 * Created by JetBrains PhpStorm.
 * User: huangxun
 * Date: 14-3-24
 * Time: 下午4:19
 * Description: ReleaseBrandZoneMod.php
 */
Yii::import('application.modules.bidmanage.models.release.ReleaseCustom');
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');

class ReleaseBrandZoneMod extends ReleaseCustom
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 执行发布操作
     *
     * @author huangxun 20130324
     * @return bool
     */
    public function runRelease()
    {
        //do release job
        //查询配置的容纳产品最大值
        $maxShowRec = $this->getAdPositionCountByType('brand_zone');
        //查询推广开始日期为当天的出价列表
        $bidProductArray = $this->_releaseProductMod->getProductArr('brand_zone');
        //将需要推广的产品按推广城市位置分组
        $bidProductGroups = $this->groupingReleaseProduct($bidProductArray);

        //用以保存组名
        $groupsArr = array();
        foreach ( $bidProductGroups as $key => $bidProductGroup ) {

            CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '当前组为：'.$key, 11, 'huangxun', 11, 0, json_encode($key));
            //避免同一组重复推广
            if ( in_array( $key , $groupsArr ) ) {
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '出现错误，该组多次进入循环：'.$key, 11, 'huangxun', -11, 0, json_encode($key));
                break;
            } else {
                $groupsArr[] = $key;
            }

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
            $beforeFormattingProducts = $preReleaseBidProductArr['valid'];
            // 初始化空广告位的数组
            $imperfect = array();
            foreach($beforeFormattingProducts as $product){
                array_push($imperfect, $product);
            }

            //推送后，新增推广表记录(逐条)
            if(!empty($imperfect)){
                $exeSuccessReleaseArr = array();
                $feedBackResultArrNonRepeated = array();
                foreach($imperfect as $im){
                    $feedBackResultArrNonRepeated[] = $im['id'];
                }

                foreach ($feedBackResultArrNonRepeated as $bidId) {
                    foreach ($bidProductGroup as $relItems) {
                        if ($relItems['id'] == $bidId) {
                            //新增推广表记录
                            $showProductRecArr['account_id'] = $relItems['account_id'];
                            // 品牌专区时产品编号插入供应商编号，产品类型插入500
                            $manageMod = new UserManageMod();
                            $accountInfo = $manageMod->read(array('id'=>$relItems['account_id']));
                            $showProductRecArr['product_id'] = $accountInfo['vendorId'];
                            $showProductRecArr['product_type'] = '500';
                            $showProductRecArr['show_date_id'] = $relItems['show_date_id'];
                            $showProductRecArr['ad_key'] = $relItems['ad_key'];
                            $showProductRecArr['cat_type'] = $relItems['cat_type'];
                            $showProductRecArr['web_class'] = $relItems['web_class'];
                            $showProductRecArr['start_city_code'] = $relItems['start_city_code'];
                            $showProductRecArr['bid_price'] = $relItems['bid_price'];
                            $showProductRecArr['ranking'] = $relItems['ranking'];
                            $showProductRecArr['bid_id'] = $relItems['id'];
                            $showProductRecArr['search_keyword'] = $relItems['search_keyword'];
                            $showProductRecArr['bid_price_coupon'] = $relItems['bid_price_coupon'];
                            $showProductRecArr['bid_price_niu'] = $relItems['bid_price_niu'];
                            $showProductRecArr['is_buyout'] = $relItems['is_buyout'];
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
                                // 计算竞价产品的总出价（包括：竞价产品价格+附加属性价格）
                                $vasPrice = $this->countBidPrice($relItems['id']);
                                // 品牌专区时产品编号插入供应商编号，产品类型插入500
                                $manageMod = new UserManageMod();
                                $accountInfo = $manageMod->read(array('id'=>$relItems['account_id']));
                                $tempSuccessProduct = array(
                                    'account_id' => $relItems['account_id'],
                                    'amt' => $relItems['bid_price']+$vasPrice,
                                    'max_limit_price' => $relItems['max_limit_price']+$vasPrice,
                                    'amt_niu' => $relItems['bid_price_niu'],
                                    'max_limit_price_niu' => $relItems['max_limit_price_niu'],
                                    'amt_coupon' => $relItems['bid_price_coupon'],
                                    'max_limit_price_coupon' => $relItems['max_limit_price_coupon'],
                                    'vasPrice' => $vasPrice,
                                    'serial_id' => $showProductId,
                                    'bid_id' => $relItems['id'],
                                    'bid_mark' => $relItems['bid_mark'],
                                    'login_name' => $relItems['login_name'],
                                    'search_keyword' => $relItems['search_keyword'],
                                    'web_class' => $relItems['web_class'],
                                    'product_id' => $accountInfo['vendorId'],
                                    'product_type' => '500',
                                    'start_city_code' => $relItems['start_city_code'],
                                    'ad_key' => $relItems['ad_key'],
                                    'ranking' => $relItems['ranking'],
                                    'is_buyout' => $relItems['is_buyout'],
                                );
                                $this->_agencySucReleaseProductArr[] = $tempSuccessProduct;
                                $exeSuccessReleaseArr[] = $tempSuccessProduct;
                            }
                            break;
                        }
                    }
                }
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '推广-结果', 11, 'huangxun', 0, 0, json_encode($exeSuccessReleaseArr));
                //更新出价表推广状态-推广成功
                $this->runUpdateReleaseState($exeSuccessReleaseArr, '1');
                CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '本次品牌专区推广结束', 11, 'huangxun',0,0,'');
            }
        }
    }
}