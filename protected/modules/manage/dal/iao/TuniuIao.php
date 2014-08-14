<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-4-25
 * Time: 下午5:11
 */

class TuniuIao {

    /**
     * 获取网站广告位类型
     * @return array
     */
    public static function getAdAddList($params){
        $client = new RESTClient();
//        $url = Yii::app()->params['TUNIU_HOST'].'home/bb/'.intval($params['startCityCode']);
        $url = 'http://www.tuniu.com/home/bb/'.intval($params['startCityCode']);
        try {
            $response = $client->get($url);
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
        if($response['success']) {
            return $response['data'];
        }
        return array();
    }

    /**
     * 查询网站频道页板块广告位列表
     *
     * @param $startCityCode
     * @return array
     */
    public static function getTuniuChannelAdList($startCityCode) {
        $client = new RESTClient();
        // $url = Yii::app()->params['TUNIU_HOST'] . '/interface/siteConfig/Channel';
        $url = "http://172.30.20.200/interface/siteConfig/Channel";

        try {
            $param['cityCode'] = $startCityCode;
            $response = $client->post($url,$param);
            if($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }else{
                return array();
            }
        }catch(Exception $e) {
            Yii::log($e, 'warning');
            return array();
        }
    }
} 