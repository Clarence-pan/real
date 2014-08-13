<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 14-1-21
 * @time 下午11:10
 * @description CommonController.php
 */
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');

class CommonController extends restSysServer
{
    private $_iaoProductMod;

    public function __construct()
    {
        $this->_iaoProductMod = new IaoProductMod();
    }

    /**
     * 获取网站提供的有效预定城市（出发城市）列表
     *
     * @author chenjinlong 20140124
     * @param $url
     * @param $data
     */
    public function doRestGetStartCity($url, $data)
    {
        $memcacheKey = md5('CommonController.doRestGetStartCity');
        $finalBeginCityResult = '';//Yii::app()->memcache->get($memcacheKey);
        if(!empty($finalBeginCityResult)){
            $beginCityList = $finalBeginCityResult;
        } else {
            $beginCityList = $this->_iaoProductMod->getMultiCityInfo();

            $hotCityArr = array();
            foreach($beginCityList['all'] as $cityRow)
            {
                if($cityRow['orderDesc'] >= 4300){
                    $hotCityArr[] = $cityRow['code'];
                }
            }

            foreach ($beginCityList as $key => &$cityCatRows) {
                $orderDescCol = array();
                $firstLetterCol = array();
                foreach ($cityCatRows as $subKey => $row) {
                    $orderDescCol[$subKey] = $row['orderDesc'];
                    $firstLetterCol[$subKey] = $row['firstLetter'];
                }
                array_multisort($firstLetterCol, SORT_ASC, $orderDescCol, SORT_DESC, $cityCatRows);

                // 热门城市前置以及排序
                $hotCityInfoRows = array();
                foreach ($cityCatRows as $eachSubKey => &$eachRow) {
                    if (in_array($eachRow['code'], $hotCityArr)) {
                        $eachRow['isHotCity'] = 1;
                        $hotCityInfoRows[] = $eachRow;
                        unset($cityCatRows[$eachSubKey]);
                    }else{
                        $eachRow['isHotCity'] = 0;
                    }
                }
                $orderDescColHot = array();
                foreach ($hotCityInfoRows as $subKeyHot => $rowHot) {
                    $orderDescColHot[$subKeyHot] = $rowHot['orderDesc'];
                }
                array_multisort($orderDescColHot, SORT_DESC, $hotCityInfoRows);
                $cityCatRows = array_merge($hotCityInfoRows, $cityCatRows);
            }
            Yii::app()->memcache->set($memcacheKey, $beginCityList, 86400);
        }
        $this->returnRest($beginCityList);
    }
}
 