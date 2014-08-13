<?php
/*
* Created on 2014-7-30
*
* To change the template for this generated file go to
* Window - Preferences - PHPeclipse - PHP - Code Templates
*/
class BusinessType{

    /*** 广告位类型 start ***/
    //首页_全部
    const INDEX_CHOSEN_ALL ="index_chosen_all";

    //首页
    const INDEX_CHOSEN ="index_chosen";

    //搜索页
    const SEARCH_COMPLEX ="search_complex";

    //专题页
    const SPECIAL_SUBJECT ="special_subject";

    //分类页
    const CLASS_RECOMMEND = "class_recommend";

    //品牌专区
    const BRAND_ZONE = "brand_zone";

    //频道页
    const CHANNEL_CHOSEN = "channel_chosen";

    //首页_全部
    const INDEX_CHOSEN_ALL_TYPE = 1;

    //搜索页
    const SEARCH_COMPLEX_TYPE = 3;

    //专题页
    const SPECIAL_SUBJECT_TYPE = 4;

    //分类页
    const CLASS_RECOMMEND_FIRST_TYPE = 2;

    //分类页
    const CLASS_RECOMMEND_SECOND_TYPE = 21;

    //品牌专区
    const BRAND_ZONE_TYPE = 6;

    //频道页
    const CHANNEL_CHOSEN_TYPE = 5;

    //所有广告位类型数组
    public static $ADKEY_TYPE_ARRAY = array(1,2,3,4,5,6,21);

    //首页_全部
    const INDEX_CHOSEN_ALL_NAME = "首页-全部";

    //首页
    const INDEX_CHOSEN_NAME = "首页";

    //搜索页
    const SEARCH_COMPLEX_NAME = "搜索页";

    //专题页
    const SPECIAL_SUBJECT_NAME = "专题页";

    //分类页
    const CLASS_RECOMMEND_FIRST_NAME = "分类页";

    //分类页
    const CLASS_RECOMMEND_SECOND_NAME = "分类页";

    //品牌专区
    const BRAND_ZONE_NAME = "品牌专区";

    //频道页
    const CHANNEL_CHOSEN_NAME = "频道页";
    /*** 广告位类型 end ***/

    /*** 产品类型 start ***/

    // 跟团游产品类型-招客宝
    const ROUTE_AGENCY_LOC = 1;

    // 自助游产品类型-招客宝
    const DIY_TOUR_LOC = 3;

    // 门票产品类型-招客宝
    const TICKET_LOC = 33;

    // 签证产品类型-招客宝
    const VISA_LOC = 4;

    // 游轮产品类型-招客宝
    const TANK_LOC = 5;

    // 跟团游产品类型-搜索
    const ROUTE_AGENCY_ROR = 1;

    // 自助游产品类型-搜索
    const DIY_TOUR_ROR = 2;

    // 酒店产品类型-搜索
    const HOTEL_ROR = 3;

    // 机票产品类型-搜索
    const FLIGHT_TICKET_ROR = 4;

    // 团队游产品类型-搜索
    const TEAM_TOUR_ROR = 5;

    // 门票产品类型-搜索
    const TICKET_ROR = 6;

    // 保险产品类型-搜索
    const INSURANCE_ROR = 7;

    // 自驾游产品类型-搜索
    const DIY_VIEC_ROR = 8;

    // 签证产品类型-搜索
    const VISA_ROR = 9;

    // 游轮产品类型-搜索
    const TANK_ROR = 10;

    // 火车票产品类型-搜索
    const HANYY_TICKET_ROR = 11;

    /*** 产品类型 end ***/

    /*** 分类页分类类型 start ***/
    // 周边-招客宝
    public static $AROUND_LOC = array(1,7,9,13);

    // 国内-招客宝
    public static $DOMESTIC_LOC = array(2,5,10,14);

    // 出境-招客宝
    public static $OUTBOUND_LOC = array(3,11,16,4,12,15,6);

    // 周边-搜索
    const AROUND_ROR = 26;

    // 国内-搜索
    const DOMESTIC_ROR = 27;

    // 出境-搜索
    const OUTBOUND_ROR = 28;

    // 周边-频道号
    const AROUND_CHN = 1;

    // 国内-频道号
    const DOMESTIC_CHN = 2;

    // 出境-频道号
    const OUTBOUND_CHN = 3;

    /*** 分类页分类类型 end ***/

    /*** 审核状态类型 start ***/
    // 未审核
    const NOT_CHECK = 1;

    // 已审核
    const YET_CHECK = 2;

    // 已过期
    const OUT_CHECK = 3;

    // 已关闭
    const OFF_CHECK = 9;

    // 未审核
    const NOT_CHECK_NAME = '未审核';

    // 已审核
    const YET_CHECK_NAME = '已审核';

    // 已过期
    const OUT_CHECK_NAME = '已过期';

    // 已关闭
    const OFF_CHECK_NAME = '已关闭';

    // 未知状态
    const NO_CHECK_NAME = '未知状态';

    /*** 审核状态类型 end ***/

    /*** 竞拍状态类型 start ***/
    // 推广成功
    const SPREAD_SUCCESS = 1;

    // 竞价成功
    const BID_SUCCESS = 2;

    // 产品未审核
    const PRODUCT_NOT_CHECK = -1;

    // 推广失败
    const SPREAD_FAIL = -2;

    // 竞价失败
    const BID_FAIL = -3;

    // 系统故障
    const SYSTEM_FAIL = -100;

    // 推广成功
    const SPREAD_SUCCESS_NAME = '推广成功';

    // 竞价成功
    const BID_SUCCESS_NAME = '竞价成功';

    // 产品未审核
    const PRODUCT_NOT_CHECK_NAME = '产品未审核';

    // 推广失败
    const SPREAD_FAIL_NAME = '推广失败';

    // 竞价失败
    const BID_FAIL_NAME = '竞价失败';

    // 系统故障
    const SYSTEM_FAIL_NAME = '系统故障';

    // 未知状态
    const NO_BID_NAME = '未知状态';

    /*** 竞拍状态类型 end ***/

    /*** 包场状态类型 start ***/
    // 未开始
    const BUYOUT_NOT_START = 1;

    // 竞价成功
    const BUYOUT_BID_SUCCESS = 2;

    // 推广中
    const BUYOUT_SPREADING = 3;

    // 推广失败
    const BUYOUT_SPREAD_FAIL = -1;

    // 推广结束
    const BUYOUT_SPREAD_END = 4;

    // 未开始
    const BUYOUT_NOT_START_NAME = '未开始';

    // 竞价成功
    const BUYOUT_BID_SUCCESS_NAME = '竞价成功';

    // 推广中
    const BUYOUT_SPREADING_NAME = '推广中';

    // 推广失败
    const BUYOUT_SPREAD_FAIL_NAME = '推广失败';

    // 推广结束
    const BUYOUT_SPREAD_END_NAME = '推广结束';

    // 未知状态
    const BUYOUT_NO_NAME = '未知状态';

    /*** 包场状态类型 end ***/
    
    /*** 消耗明细类型 start ***/

    // 竞拍
    const EXPENSE_BID_NAME = '竞拍';

    // 打包计划
    const EXPENSE_CLS_PACK_NAME = '分类页打包';

    // 线下
    const EXPENSE_OFFLINE_NAME = '线下扣款';

    // 过期
    const EXPENSE_OVERDUE_NAME = '过期';

    /*** 消耗明细类型 end ***/
    
    // 未知状态
    const NO_NAME = '未知状态';
}
?>
