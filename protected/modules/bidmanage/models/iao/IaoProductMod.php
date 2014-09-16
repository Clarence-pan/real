<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 5:13 PM
 * Description: IaoProductMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');
Yii::import('application.modules.bidmanage.dal.iao.ProductIao');
Yii::import('application.modules.bidmanage.dal.iao.RorProductIao');

class IaoProductMod
{
    private $_bidProductIao;

    private $_rorProductIao;

    function __construct()
    {
        $this->_bidProductIao = new BidProductIao;
        $this->_rorProductIao = new RorProductIao;
    }

    /**
     * 批量查询产品基本信息和各产品的新网站分类信息
     *
     * @author chenjinlong 20121209
     * @param $productArr = array(array('product_id'=>'','product_type'=>''))
     * @return array
     */
    public function getProductInfoArr($productArr)
    {
        if(!empty($productArr) && is_array($productArr)){
            $rows = $this->_bidProductIao->getProductClassification($productArr);
            if(!empty($rows) && is_array($rows)){
                return $rows;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    /**
     * 查询网站预定城市（出发城市列表）
     *
     * @author chenjinlong 20140124
     * @return array
     */
    public static function getMultiCityInfo()
    {
        $memcacheKey = md5('getMultiCityInfoFromTuniuApi');
        $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
        if(!empty($finalBeginCityResult) && !empty($finalBeginCityResult['all']) && !empty($finalBeginCityResult['major']) && !empty($finalBeginCityResult['minor'])){
            return $finalBeginCityResult;
        }else{
            $finalBeginCityResult = array(
                'all' => array(),
                'major' => array(),
                'minor' => array(),
            );
            $beginCityList = ProductIao::getMultiCityInfoFromTuniu();
            if($beginCityList['success'] && !empty($beginCityList['data'])){
                $srcBeginCityResult = $beginCityList['data'];
                foreach($srcBeginCityResult as $row)
                {
                    $majorBeginCityCodeArr = array();
                    foreach($row['cities'] as $subRow)
                    {
                        //转换天津POI的code为天津DEPARTURE的code
                        if($subRow['code'] == 3002){
                            $subRow['code'] = 3000;
                        }
                        if($subRow['isCompany'] == 1){
                            $majorBeginCityCodeArr[] = $subRow['code'];
                        }
                    }

                    foreach($row['newcity'] as $subRow)
                    {
                        //转换天津POI的code为天津DEPARTURE的code
                        if($subRow['code'] == 3002){
                            $subRow['code'] = '3000';
                        }
                        $tempRow = array();
                        $tempRow['bigClass'] = $row['bigClass'];
                        $tempRow['code'] = $subRow['code'];
                        $tempRow['type'] = $subRow['type'];
                        $tempRow['name'] = $subRow['name'];
                        $tempRow['letter'] = $subRow['letter'];
                        $tempRow['firstLetter'] = strtoupper(substr($subRow['letter'], 0, 1));
                        $tempRow['orderDesc'] = $subRow['orderDesc'];
                        if($subRow['mark'] == 0){
                            $finalBeginCityResult['all'][] = $tempRow;
                        }
                        if($subRow['mark'] == 0 && in_array($subRow['code'], $majorBeginCityCodeArr)){
                            $finalBeginCityResult['major'][] = $tempRow;
                        }elseif($subRow['mark'] == 0){
                            $finalBeginCityResult['minor'][] = $tempRow;
                        }
                    }
                }
            }
            Yii::app()->memcache->set($memcacheKey, $finalBeginCityResult, 86400);
            return $finalBeginCityResult;
        }
    }

}
