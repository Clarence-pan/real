<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-8-1
 * Time: 上午10:44
 */
class DictionaryTools {

    /*
     * 转换产品类型
     */
    public static function getTypeTool($params) {
        switch ($params) {
            case BusinessType::ROUTE_AGENCY_ROR:
                $result = BusinessType::ROUTE_AGENCY_LOC;
                break;
            case BusinessType::DIY_TOUR_ROR:
                $result = BusinessType::DIY_TOUR_LOC;
                break;
            case BusinessType::TICKET_ROR:
                $result = BusinessType::TICKET_LOC;
                break;
            case BusinessType::VISA_ROR:
                $result = BusinessType::VISA_LOC;
                break;
            case BusinessType::TANK_ROR:
                $result = BusinessType::TANK_LOC;
                break;
            case BusinessType::AROUND_ROR:
                $result = BusinessType::$AROUND_LOC;
                break;
            case BusinessType::DOMESTIC_ROR:
                $result = BusinessType::$DOMESTIC_LOC;
                break;
            case BusinessType::OUTBOUND_ROR:
                $result = BusinessType::$OUTBOUND_LOC;
                break;
            default:
                $result = BusinessType::ROUTE_AGENCY_LOC;
                break;
        }
        return $result;
    }

    /*
     * 转换产品线顶级分类为频道号
     */
    public static function getCatToChnTool($productCatType) {
        switch ($productCatType) {
            case in_array($productCatType,BusinessType::$AROUND_LOC):
                $result = BusinessType::AROUND_CHN;
                break;
            case in_array($productCatType,BusinessType::$DOMESTIC_LOC):
                $result = BusinessType::DOMESTIC_CHN;
                break;
            case in_array($productCatType,BusinessType::$OUTBOUND_LOC):
                $result = BusinessType::OUTBOUND_CHN;
                break;
            default:
                $result = BusinessType::AROUND_CHN;
                break;
        }
        return $result;
    }

    /*
     * 获取审核状态
     */
    public static function getCheckStateTool($params) {
        switch ($params) {
            case BusinessType::NOT_CHECK:
                $result = BusinessType::NOT_CHECK_NAME;
                break;
            case BusinessType::YET_CHECK:
                $result = BusinessType::YET_CHECK_NAME;
                break;
            case BusinessType::OUT_CHECK:
                $result = BusinessType::OUT_CHECK_NAME;
                break;
            case BusinessType::OFF_CHECK:
                $result = BusinessType::OFF_CHECK_NAME;
                break;
            default:
                $result = BusinessType::NO_CHECK_NAME;
                break;
        }
        return $result;
    }

    /*
     * 获取竞拍状态
     */
    public static function getBidStateTool($params) {
        switch ($params) {
            case BusinessType::SPREAD_SUCCESS:
                $result = BusinessType::SPREAD_SUCCESS_NAME;
                break;
            case BusinessType::BID_SUCCESS:
                $result = BusinessType::BID_SUCCESS_NAME;
                break;
            case BusinessType::PRODUCT_NOT_CHECK:
                $result = BusinessType::PRODUCT_NOT_CHECK_NAME;
                break;
            case BusinessType::SPREAD_FAIL:
                $result = BusinessType::SPREAD_FAIL_NAME;
                break;
            case BusinessType::BID_FAIL:
                $result = BusinessType::BID_FAIL_NAME;
                break;
            case BusinessType::SYSTEM_FAIL:
                $result = BusinessType::SYSTEM_FAIL_NAME;
                break;
            default:
                $result = BusinessType::NO_BID_NAME;
                break;
        }
        return $result;
    }

    /*
     * 获取包场状态
     */
    public static function getBuyoutStateTool($params) {
        switch ($params) {
            case BusinessType::BUYOUT_NOT_START:
                $result = BusinessType::BUYOUT_NOT_START_NAME;
                break;
            case BusinessType::BUYOUT_BID_SUCCESS:
                $result = BusinessType::BUYOUT_BID_SUCCESS_NAME;
                break;
            case BusinessType::BUYOUT_SPREADING:
                $result = BusinessType::BUYOUT_SPREADING_NAME;
                break;
            case BusinessType::BUYOUT_SPREAD_FAIL:
                $result = BusinessType::BUYOUT_SPREAD_FAIL_NAME;
                break;
            case BusinessType::BUYOUT_SPREAD_END:
                $result = BusinessType::BUYOUT_SPREAD_END_NAME;
                break;
            default:
                $result = BusinessType::BUYOUT_NO_NAME;
                break;
        }
        return $result;
    }
    
    /*
     * 获取消耗明细类型
     */
    public static function getExpenseType($params) {
        switch (strval($params)) {
            case chr(49):
            	// 1   竞拍
                $result = BusinessType::EXPENSE_BID_NAME;
                break;
            case chr(50):
                // 2 分类页打包
                $result = BusinessType::EXPENSE_CLS_PACK_NAME;
                break;
            case chr(51):
            	// 3   线下          
                $result = BusinessType::EXPENSE_OFFLINE_NAME;
                break;
            case chr(52):
            	// 4   过期            
                $result = BusinessType::EXPENSE_OVERDUE_NAME;
                break;
            default:
                $result = BusinessType::NO_NAME;
                break;
        }
        return $result;
    }
}