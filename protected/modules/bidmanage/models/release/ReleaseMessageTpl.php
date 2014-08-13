<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/18/12
 * Time: 11:25 AM
 * Description: ReleaseMsgTpl.php
 */
class ReleaseMessageTpl
{
    /**
     * 构建发布模块所需的消息中心模版消息
     *
     * @author chenjinlong 20121218
     * @param $messageType
     * @param $involvedProductCount
     * @param $involvedReleaseUnitCount
     * @param $involvedAmount
     * @return string
     */
    public static function buildReleaseMessage($messageType, $involvedProductCount, $involvedReleaseUnitCount, $involvedAmount=0)
    {
        switch($messageType){
            //产品未审核
            case 2:
                $messageStr = self::getNotCheckedMessage($involvedProductCount);
                break;
            //有效排名范围之外
            case 3:
                $messageStr = self::getNotRangeMessage($involvedProductCount);
                break;
            //扣款成功
            case 101:
                $messageStr = self::getSucDeductMessage($involvedProductCount, $involvedReleaseUnitCount);
                break;
            //退款成功
            /*case 103:
                $messageStr = self::getSucRefundMessage($involvedProductCount, $involvedReleaseUnitCount);
                break;*/
            default:
                $messageStr = '';
                break;
        }
        return $messageStr;
    }

    private static function getNotCheckedMessage($involvedProductCount)
    {
        $messageStr = "有".$involvedProductCount."个产品因为未审核原因, 未能展示, 竞价金额未被扣除";
        return $messageStr;
    }

    private static function getNotRangeMessage($involvedProductCount)
    {
        $messageStr = "有".$involvedProductCount."个产品因为已掉出排名, 未能展示, 竞价金额未被扣除";
        return $messageStr;
    }

    private static function getSucDeductMessage($involvedProductCount, $involvedReleaseUnitCount)
    {
        $messageStr = "推广费用: <span style='color:#FF6600;'>".$involvedProductCount."</span>个产品展示在<span style='color:#FF6600;'>".$involvedReleaseUnitCount."</span>个推广单元";
        return $messageStr;
    }

    /*private static function getSucRefundMessage($involvedProductCount, $involvedReleaseUnitCount)
    {
        $messageStr = '费用退回可支配余额: '.$involvedProductCount.'个产品在'.$involvedReleaseUnitCount.'个推广单元中未能成功展示';
        return $messageStr;
    }*/

}
