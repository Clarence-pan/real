<?php
/**
 * UI呈现接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-06-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.product.ClsrecommendMod');

class ClsrecommendController extends restUIServer {

	private $clsrecommendMod;
    
    function __construct() {
        $this->clsrecommendMod = new ClsrecommendMod();
    }
    
}