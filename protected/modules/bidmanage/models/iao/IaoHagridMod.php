<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/6/13
 * Time: 4:57 PM
 * Description: IaoHagridMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.HagridIao');

class IaoHagridMod
{
    private $_hagridIao;

    function __construct()
    {
        $this->_hagridIao = new HagridIao;
    }

    /**
     * 查询BI产品跟踪结果数组
     *
     * @author chenjinlong 20130106
     * @param $productIdArr
     * @return array
     */
    public function getIntegratedBiProductTrackSta($productIdArr)
    {
        if(!empty($productIdArr) && is_array($productIdArr)){
            $resultArr = $this->_hagridIao->queryBiProductTrackSta($productIdArr);

            $outArr = array();
            foreach($resultArr as $row)
            {
                $outArr[$row['productId']] = $row;
            }
            return $outArr;
        }else{
            return array();
        }
    }

    /**
     * 查询BI跟踪结果之URL跟踪数组
     *
     * @author chenjinlong 20120106
     * @param $urlSet
     * @return array
     */
    public function getBiUrlTrackSta($urlSet)
    {
        if(!empty($urlSet) && is_array($urlSet)){
            $resultArr = $this->_hagridIao->queryBiUrlTrackSta($urlSet);
            return $resultArr;
        }else{
            return array();
        }
    }

}
