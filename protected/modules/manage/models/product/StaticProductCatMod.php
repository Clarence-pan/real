<?php
/**
 * Created by JetBrains PhpStorm.
 * User: xiongyun
 * Date: 2013-02-01
 * Time: 4:56 PM
 * To change this template use File | Settings | File Templates.
 */
Yii::import('application.modules.manage.dal.dao.product.StaticProductLine');

class StaticProductCatMod {

    private $_productCatDao;

    function __construct() {
        $this->_productCatDao = new StaticProductLine();
    }

    public function getProductTypeList() {
        $catParams = array();
        $resList = $this->_productCatDao->readProductCatList('productType', $catParams);
        return $resList;
    }

    public function getProductCatTypeList($productTypeId) {
        $catParams = array('product_type' => $productTypeId);
        $resList = $this->_productCatDao->readProductCatList('destinationClass', $catParams);
        return $resList;
    }

    public function getDepartureCity() {
        $catParams = array();
        $resList = $this->_productCatDao->readProductCatList('startCityCode', $catParams);
        return $resList;
    }

}
