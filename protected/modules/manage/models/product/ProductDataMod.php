<?php
/**
 * Created by JetBrains PhpStorm.
 * User: huangxun
 * Date: 13-12-26
 * Time: 上午10:01
 * To change this template use File | Settings | File Templates.
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
Yii::import('application.modules.manage.dal.iao.BossIao');
Yii::import('application.modules.manage.dal.dao.user.UserDao');
Yii::import('application.modules.manage.dal.dao.product.StaticProductLine');

class ProductDataMod {

    private $_userDao;
    private $_productLineDao;

    function __construct() {
        $this->_userDao = new UserDao;
        $this->_productLineDao = new StaticProductLine();
    }

    /**
     * 查询产品列表信息
     *
     * @author huangxun 20131226
     * @param $params
     * @return array
     */
    public function readProductList($params) {
        $result = BuckbeekIao::getProductList($params);
        return $result;
    }

    /**
     * 导出推广产品列表文件
     * @author huangxun 20131226
     * @param $params
     * @return array
     */

    public function readProductFile($params) {
        $fileUrl = BuckbeekIao::getProductFile($params);
        return $fileUrl;
    }

    /**
     * 查询产品经理
     * @author huangxun 20131226
     * @param $params
     * @return array
     */

    public function getManagerName($params) {
        $fileUrl = BuckbeekIao::getManagerName($params);
        return $fileUrl;
    }

    /**
     * 查询产品线ID串
     * @author huangxun 20140106
     * @param $params
     * @return array
     */
    public function readProductLineIdStr($params) {
        $productLineIdStr = $this->_productLineDao->readProductLineIdStr($params);
        return $productLineIdStr;
    }
    
    /**
     * 查询广告位操作记录
     * 
     * @author p-sunhao 20140404
     * @param $params
     * @return array
     */
    public function getProductHis($params) {
    	// 调用接口
        $result = BuckbeekIao::getProductHis($params);
        // 返回结果
        return $result;
    }

    /**
     * 保存/编辑包场记录
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function saveBuyout($params) {
        // 调用接口
        $result = BuckbeekIao::saveBuyout($params);
        // 返回结果
        return $result;
    }

    /**
     * 获得包场信息
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function getBuyout($params) {
        // 调用接口
        $result = BuckbeekIao::getBuyout($params);
        // 返回结果
        return $result;
    }

    /**
     * 删除包场记录
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function delBuyout($params) {
        // 调用接口
        $result = BuckbeekIao::delBuyout($params);
        // 返回结果
        return $result;
    }

    /**
     * 获得包场广告位类型
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function getBuyoutType($params) {
        // 调用接口
        $result = BuckbeekIao::getBuyoutType($params);
        // 返回结果
        return $result;
    }

    /**
     * 获得包场分类页信息
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function getWebClassInfo($params) {
        // 调用接口
        $result = BuckbeekIao::getWebClassInfo($params);
        // 返回结果
        return $result;
    }

    /**
     * 查询包场搜索关键词
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function getKeyword($params) {
        // 调用接口
        $result = BuckbeekIao::getKeyword($params);
        // 返回结果
        return $result;
    }

    /**
     * 获得产品类型
     * @author huangxun 20140527
     * @param $params
     * @return array
     */
    public function getProductType($params) {
        // 调用接口
        $result = BuckbeekIao::getProductType($params);
        // 返回结果
        return $result;
    }
    
}