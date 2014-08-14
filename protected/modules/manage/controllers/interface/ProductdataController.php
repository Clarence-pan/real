<?php
/**
 * Created by JetBrains PhpStorm.
 * User: huangxun
 * Date: 14-1-6
 * Time: 下午3:40
 * To change this template use File | Settings | File Templates.
 * 对外系统接口 | Hagrid产品信息相关
 */
Yii::import('application.modules.manage.models.product.ProductDataMod');

class ProductdataController extends restfulServer{

    private $_productDataMod;

    function __construct() {
        $this->_productDataMod = new ProductDataMod;
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /productlineid
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【产品】hg-查询产品线ID串
     */
    public function doRestGetProductLineId ($url, $data) {
        $params = array(
            'productType' => $data['productType'],
            'destinationClass' => $data['destinationClass'],
        );
        $productLineIdStr = $this->_productDataMod->readProductLineIdStr($params);
        $this->returnRest($productLineIdStr);
    }
}