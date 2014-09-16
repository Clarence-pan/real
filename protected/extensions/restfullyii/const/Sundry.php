<?php
/*
 * Created on 2014-7-30
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 class Sundry{
 	
 	/*** memcacheKey start ***/
 	
 	//用来缓存和获取出发城市
 	const START_CITY = "CommonController.doRestGetStartCity";
 	
 	//初始化MD5加密key 在刷新搜索表脚本入口被用到
 	const INIT_MD5KEY = "$#%^%&1234()&asdfKY&^%&H";
 	
 	//同步分类和城市数据到memcache
 	const START_CITY_BACKGROUND ="CommonController.doRestGetStartCityBackground";
 	
 	//获得首页的位置信息
 	const GET_BB_CITY_INFO ="getBBCityInfo";
 	
 	//查询网站预定城市（出发城市列表）
 	const GET_CITY_FROM_TUNIU_API="getMultiCityInfoFromTuniuApi";
 	
 	// 查询产品经理
 	const GET_PRODUCT_MANAGER="BUCKBEEK::getProductManagerBoss";
 	
 	/*** memcacheKey end ***/
 	
 	/*** 日期表达式 start ***/
 	//初始化日期格式
 	const RELEASETIME = '0000-00-00 00:00:00';
 	
 	// 年月日 时分秒
 	const TIME_Y_M_D_H_I_S = 'Y-m-d H:i:s';
 	
 	//年(2位)月日 时分秒
 	const TIME_SY_M_D_H_I_S ='y-m-d H:i:s';
 	
 	// 年月日 时分
 	const TIME_Y_M_D_H_I = 'Y-m-d H:i';
 	
 	// 年月日 时
 	const TIME_Y_M_D_H = 'Y-m-d H';
 	
 	// 年月日
 	const TIME_Y_M_D = 'Y-m-d';
 	
 	// 年月
 	const TIME_Y_M = 'Y-m';
 	
 	// 年
 	const TIME_Y = 'Y';
 	
 	// 时分秒
 	const TIME_H_I_S = 'H:i:s';
 	
 	// 时分
 	const TIME_H_I = 'H:i';
 	
 	// 时
 	const TIME_H = 'H';
 	
 	// 月
 	const TIME_M = 'm';
 	
 	// 日
 	const TIME_D = 'd';
 	
 	// 分
 	const TIME_I = 'i';
 	
 	// 秒
 	const TIME_S = 's';
 	
 	/*** 日期表达式 end ***/
 	
 	/*** 查询保存字符串 start***/
 	const QUERY = 'query';
 	
 	const SAVE = 'save';
 	
 	const INSERT = 'insert';
 	
 	const UPDATE = 'update';
 	
 	/*** 查询保存字符串 end***/
 	
 	/*** 常用字符串 start***/
 	
 	const SUSPENMSION = '...';
 	
 	const DEFAULT_BLICK = '其他产品推荐';
 	
 	/*** 常用字符串 end***/
 }
?>
