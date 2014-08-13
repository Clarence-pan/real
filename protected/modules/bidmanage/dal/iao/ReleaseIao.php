<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 11:53 AM
 * Description: ReleaseIao.php
 */
class ReleaseIao
{
    private $_restfulClient;

    function __construct()
    {
        $this->_restfulClient = new RESTClient;
    }

    /**
     * 频道页-产品推荐位-推广接口
     *
     * @author chenjinlong 20121209
     * @param $productArr
     * @return array()
     */
    public function releaseToChannelAndClsRoutes($productArr)
    {
        $url = Yii::app()->params['TUNIU_HOST'].'interface/restful_interface.php';
        $requestParamsArr = array(
            'func' => 'bb.addRecommendRoute',
            'params' => $productArr,
        );
        try{
        	$pos = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $responseArr = $this->_restfulClient->post($url, $requestParamsArr);
            BPMoniter::endMoniter($pos, 500, __LINE__);
			// 校验是否有返回数据
			if(!empty($responseArr['data'])){
				// 记录接口监控日志
            	CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'推广-网站推广接口',11,'wuke', 13,sizeof($responseArr['data']['failRoute']), '', json_encode($requestParamsArr), json_encode($responseArr));
			}
            if($responseArr['success'] && !empty($responseArr['data'])){
                return $responseArr['data'];
            } else{
                return array();
            }
        }catch (Exception $e){
			BPMoniter::endMoniter($pos, 500, __LINE__);
            //记录接口监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'推广-网站推广接口-异常',11,'chenjinlong', -13, 0, '', json_encode($requestParamsArr), json_encode($e->getTraceAsString()));

            return array();
        }
    }
    
    /**
     * 新产品推荐位-推广接口
     *
     * @author p-sunhao
     * @param $productArr
     * @return array()
     */
    public function releaseToChannelAndClsRoutesNew($productArr)
    {
        $url = Yii::app()->params['TUNIU_HOST'].'interface/restful_interface.php';
        $requestParamsArr = array(
            'func' => 'bb.addRecommendRoute',
            'params' => $productArr,
        );
        try{
        	$pos = BPMoniter::createMoniter(__METHOD__.'::'.__LINE__);
            $responseArr = $this->_restfulClient->post($url, $requestParamsArr);
            BPMoniter::endMoniter($pos, 500, __LINE__);
			// 校验是否有返回数据
			if(!empty($responseArr['data'])){
				// 记录接口监控日志
            	CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'推广-网站推广接口',11,'wuke', 13,sizeof($responseArr['data']['failRoute']), '', json_encode($requestParamsArr), json_encode($responseArr));
			}
            return $responseArr;
        }catch (Exception $e){
			BPMoniter::endMoniter($pos, 500, __LINE__);
            //记录接口监控日志
            CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'推广-网站推广接口-异常',11,'chenjinlong', -13, 0, '', json_encode($requestParamsArr), json_encode($e->getTraceAsString()));

            return array();
        }
    }

}
