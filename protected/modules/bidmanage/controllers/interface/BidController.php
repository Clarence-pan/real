<?php
Yii::import('application.modules.bidmanage.models.common.CommonMod');

class BidController extends restSysServer
{
    private $_commonMod;

    function __construct()
    {
        $this->_commonMod = new CommonMod();
    }

    /**
     * 对外接口：查询招客宝广告位信息
     *
     * @author chenjinlong 20131226
     * @param $url
     * @param $data
     * Contains Keys:
     * "adKey":广告位页面标识字符串(required),
     */
    public function doRestGetAdPosition($url, $data) {
        if(empty($data['adKey'])){
            $this->returnRest(array(), false, 230015, '输入参数错误');
        }else{
            $params = array(
                'adKey' => strval($data['adKey']),
            );
            $adPositionRow = $this->_commonMod->readAdPosition($params);
            if(!empty($adPositionRow)) {
                $result = array(
                    'adKey' => $params['adKey'],
                    'adName' => $adPositionRow['ad_name'],
                    'floorPrice' => $adPositionRow['floor_price'],
                    'adProductCount' => intval($adPositionRow['ad_product_count']),
                );
                $this->returnRest($result);
            }else {
                $this->returnRest(array());
            }
        }
    }
}