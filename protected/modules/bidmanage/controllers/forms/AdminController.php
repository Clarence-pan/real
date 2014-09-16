<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 13-12-10
 * @time 下午4:05
 * @description AdminController.php
 */
Yii::import('application.modules.bidmanage.models.bid.BidAdminMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.models.user.ConfigManageMod');

class AdminController extends CController
{
    private $_bidAdminMod;
    private $_manageMod;
    private $_configManageMod;

    public function __construct()
    {
        if($_GET['password'] != '06b8f6d2f44740f0893044642dae3963'){
            echo '对不起，您不具备执行权限。请联系开发人员，非常感谢！';
            die;
        }
        $this->_bidAdminMod = new BidAdminMod();
        $this->_manageMod = new UserManageMod();
        $this->_configManageMod = new ConfigManageMod();
    }

    /**
     * 管理员专用：解决招客宝账户冻结款项异常的问题
     *
     * @author chenjinlong 20131210
     */
    public function actionFixFmisFreezeError()
    {
        $accountId = intval($_GET['accountId']);
        $isExec = intval($_GET['isExec']);
        if($accountId == 0){
            echo '输入参数不符合入参约束，请检查';
            die;
        }else{
            try{
                $result = $this->_bidAdminMod->fixFmisFreezeErrorProcess($accountId, $isExec);
                print_r($result);
            }catch (Exception $e){
                print_r($e->getTrace());
            }
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /brandname
     * @method GET
     * @param string $url
     * @param  array $data {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b"}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】同步供应商品牌名数据
     */
    public function actionBrandName() {
        try{
            $result = $this->_manageMod->runBrandNameTask();
            print_r('同步供应商品牌名数据成功');
        }catch (Exception $e){
            print_r($e->getTrace());
        }
    }

    /**
     * 管理员专用：操作Memcache缓存内容
     *
     * @author chenjinlong 20131210
     */
    public function actionDelMemcache()
    {
        try{
            $flag = strval($_GET['flag']);
            $key = strval($_GET['key']);
            $result = array();
            switch($flag)
            {
                case 'get':
                    $result = Yii::app()->memcache->get($key);
                    break;
                case 'set':
                    $value = json_decode(strval($_GET['value']));
                    $expire = intval($_GET['expire']);
                    $result = Yii::app()->memcache->set($key, $value, $expire);
                    break;
                case 'delete':
                    $result = Yii::app()->memcache->delete($key);
                    break;
                case 'flush':
                    $result = Yii::app()->memcache->flush();
                    break;
                default:
                    break;
            }
            var_dump($_GET, $result);
        }catch (Exception $e){
            print_r($e->getTrace());
        }
    }

    /**
     * 管理员专用：同步网站首页广告位类型数据
     *
     * @author chenjinlong 20140512
     */
    public function actionSynAdPosType()
    {
        try{
            $startCityCode = intval($_GET['startCityCode']);
            $isExec = intval($_GET['isExec']);
            if($startCityCode > 0 || $startCityCode == -100){
                $result = $this->_bidAdminMod->synTuniuAdPosType($startCityCode, $isExec);
                print_r($result);
            }else{
                print_r('输入参数不正确');
            }
        }catch (Exception $e){
            print_r($e->getTrace());
        }
    }

    /**
     * 管理员专用：同步网站首页和分类页某个广告位类型数据
     *
     * @author huangxun 20140710
     */
    public function actionSynOneAdPosType()
    {
        try{
            $startCityCode = intval($_GET['startCityCode']);
            $adKey = strval($_GET['adKey']);
            $isExec = intval($_GET['isExec']);
            if($startCityCode > 0 && $adKey){
                $result = $this->_bidAdminMod->synTuniuOneAdPosType($startCityCode, $adKey, $isExec);
                print_r($result);
            }else{
                print_r('输入参数不正确');
            }
        }catch (Exception $e){
            print_r($e->getTrace());
        }
    }

    /**
     * 管理员专用：同步“竞价成功-推广开始”期间的变动首页板块TAB广告位
     *
     * @author chenjinlong 20140514
     */
    public function actionSynBbpAdPosType()
    {
        try{
            $startCityCode = intval($_GET['startCityCode']);
            $showDateId = intval($_GET['showDateId']);
            $isShowProduct = intval($_GET['isShowProduct']);
            $isExec = intval($_GET['isExec']);
            if(($startCityCode > 0 && $showDateId > 0) or ($startCityCode == -100 && $showDateId > 0)){
                $synParamsArr = array(
                    'start_city_code' => $startCityCode == -100 ? 0 : $startCityCode,
                    'show_date_id' => $showDateId,
                    'isShowProduct' => $isShowProduct,
                    'isExec' => $isExec,
                );
                $result = $this->_bidAdminMod->synBidBidProductAdPosType($synParamsArr);
                print_r($result);
            }else{
                print_r('输入参数不正确');
            }
        }catch (Exception $e){
            print_r($e->getTrace());
        }
    }

    /**
     * 替换竞拍成功，但是未开始推广的产品
     *
     * @author chenjinlong 20140515
     */
//    public function actionHandleReplaceProduct()
//    {
//        try{
//            $replaceProductRows = $_GET['productRows'];
//            if(!empty($replaceProductRows) && is_array($replaceProductRows)){
//                foreach($replaceProductRows as $replaceRow)
//                {
//                    $productItem = array(
//                        'product_type' => $replaceRow['productType'],
//                        'product_id' => $replaceRow['productId'],
//                        'product_name' => $replaceRow['productName'],
//
//                        'account_id' => $replaceRow['accountId'],
//                        'bid_id' => $replaceRow['bidId'],
//                        /*'ad_key' => $replaceRow['adKey'],
//                        'start_city_code' => $replaceRow['startCityCode'],
//                        'web_class' => $replaceRow['webClass'],
//                        'search_keyword' => $replaceRow['searchKeyword'],
//                        'ranking' => $replaceRow['ranking'],*/
//                    );
//                    $result = $this->_bidAdminMod->handleProductReplaceAct($productItem);
//                }
//                print_r($result);
//            }else{
//                print_r('输入参数不正确');
//            }
//        }catch (Exception $e){
//            print_r($e->getTrace());
//        }
//    }

    /**
     * 同步供应商赠币配置
     */
    public function actionSynCouponConfig() {
        try{
            $isExec = intval($_GET['isExec']);
            if ($isExec == 1) {
                $this->_configManageMod->synCouponConfig();
                print_r('同步供应商品牌名数据成功');
            }
        }catch (Exception $e){
            print_r($e->getTrace());
        }
    }
}
 