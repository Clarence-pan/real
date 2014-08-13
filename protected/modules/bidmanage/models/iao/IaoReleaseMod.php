<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 2:23 PM
 * Description: IaoReleaseMod.php
 */
Yii::import('application.modules.bidmanage.dal.iao.ReleaseIao');
class IaoReleaseMod
{
    private $_releaseIao;

    function __construct()
    {
        $this->_releaseIao = new ReleaseIao;
    }

    /**
     * 发布收客宝推广线路到网站频道页/分类页
     *
     * @author chenjinlong 20121210
     * @param $inParams
     * @return array
     */
    public function pushRoutesIntoChannelAndClsSet($inParams)
    {
        $invokeResult = array(
            'valid' => array(),
            'invalid' => array(),
        );
        if(!empty($inParams) && is_array($inParams)){
            $totalBidIdArr = array();
            foreach($inParams as $productArr)
            {
                $totalBidIdArr[] = $productArr['bidId'];
            }
            // 分组进行处理
            for($i=0;$i<count($inParams);$i=$i+10){
            	$invalidBidIdArr = $this->_releaseIao->releaseToChannelAndClsRoutes(array_slice($inParams,$i,10));
            	// 校验返回数据是否为空
				if(!empty($invalidBidIdArr)){
					$invokeResult['invalid'][] = empty($invalidBidIdArr['failRoute'])?array():$invalidBidIdArr['failRoute'];
				}
            }
            $invokeResult['valid'] = array_diff($totalBidIdArr, $invokeResult['invalid']);
        }
        CommonSysLogMod::log(date('Y-m-d H:i:s') . __CLASS__, '发布-结束', 11, 'wenrui', 0, 0, '发布成功线路：'.sizeof(array_unique($invokeResult['valid'])).',发布失败线路：'.sizeof(array_unique($invokeResult['invalid'])), json_encode($inParams), json_encode($invokeResult));
        return $invokeResult;
    }

}
