<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/6/13
 * Time: 1:51 PM
 * Description: StaBiWebFlowMod.php
 */
Yii::import('application.modules.manage.dal.dao.user.StaBiWebFlowDao');

class StaBiWebFlowMod
{
    private $_staBiWebFlowDao;

    function __construct()
    {
        $this->_staBiWebFlowDao = new StaBiWebFlowDao;
    }

    /**
     * 查询每一个推荐位置的产品跟踪统计数据
     *
     * @author chenjinlong 20130106
     * @param $trackedUrlArr
     * @param $staDate
     * @return array
     */
    public function getBiUrlTrackStaInfo($trackedUrlArr, $staDate)
    {
        //BI之URL跟踪记录行
        $webFlowUrlTrackRow = $this->_staBiWebFlowDao->queryWebFlowBBUrlTrackInfo($trackedUrlArr, $staDate);
        $staInfoArr = array();
        if(!empty($webFlowUrlTrackRow)){
            foreach($webFlowUrlTrackRow as $elem)
            {
                $staInfoArr[] = array(
                    'url' => trim(strval($elem['url'])),
                    'reveal' => $elem['PV']>0?intval($elem['PV']):0,
                    'ipView' => $elem['IP']>0?intval($elem['IP']):0,
                );
            }
        }
        return $staInfoArr;
    }

    /**
     * 查询每一个推荐位置的产品跟踪统计数据
     *
     * @author chenjinlong 20130106
     * @param array $productIdArr
     * @param string $staDate
     * @return array
     */
    public function getBiProductTrackStaInfo($productIdArr, $staDate)
    {
        //BI之产品跟踪记录行
        $webFlowRouteTrackRows = $this->_staBiWebFlowDao->queryWebFlowBBRouteTrackInfo($productIdArr, $staDate);

        $staInfoArr = array();
        if(!empty($webFlowRouteTrackRows)){
            foreach($webFlowRouteTrackRows as $elem)
            {
                $staInfoArr[$elem['route_id']] = array(
                    'productId' => intval($elem['route_id']),
                    'clickNum' => $elem['PV']>0?intval($elem['PV']):0,
                    'orderCount' => $elem['order_count']>0?intval($elem['order_count']):0,
                );
            }
        }
        return $staInfoArr;
    }

}