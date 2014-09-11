<?php
/**
 * hagrid对外接口 | 收客宝 打包计划相关
 * Buckbeek product interfaces for inner UI system.
 * @author p-sunhao@2014-06-11
 * @version 1.0
 * @func doRestGetWebClass
 * @func doRestPostProduct
 * @func doRestGetAllProduct
 * @func doRestGetProduct
 */
Yii::import('application.modules.bidmanage.models.cps.CpsMod');

class CpsController extends restSysServer {

	private $cpsMod;
    
    function __construct() {
        $this->cpsMod = new CpsMod();
    }
    
    /**
	 * 查询消费明细列表
	 */
	public function doRestGetExpenseinfo($url, $data) {
		
		$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (!empty($data['expenseType']) && is_numeric($data['expenseType']) && isset($data['start']) 
				&& !empty($data['limit']) && is_numeric($data['start']) && is_numeric($data['limit']) ) {
					
				// 查询财务账户报表
				$data['accountId'] = $this->getAccountId();
				$resultMod = $this->fmis->getExpenseInfo($data);
				
				// 整合结果，自定义编码和语句
				$result['data'] = $resultMod;
				$result['errorCode'] = ErrorCode::ERR_231500;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
				
				// 返回结果
				$this->returnRestStand($result);
				
			} else {
				
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
				
			}
		} catch(BBException $e) {
			$result['success'] = false;
			if (intval(chr(48)) != $e->getErrCode()) {
				$result['errorCode'] = $e->getErrCode();
				$result['msg'] = $e->getErrMessage();
			} else {
				$result['errorCode'] = ErrorCode::ERR_231000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			}
			
			// 返回结果
			$this->returnRestStand($result);
		} catch(Exception $e) {
			// 注入异常和日志
			new BBException($e->getCode(), $e->getMessage());
			$result['success'] = false;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
	}

    /**
     * 查询推广报表
     * /public/cps/cpsshowreport
     * 输入: $params['vendorId'] -- 供应商ID，可选
     *                vendorName -- 供应商名称，可选
     *                purchaseType    -- 结算方式 ，可选
     *                purchaseState   -- 结算状态 ，可选
     *                placeOrderTime  -- 下单时间 ，可选
     *                show_start_time~show_end_time -- 推广时间，可选
     * 输出：vendorId -- 供应商ID
     *       vendorName -- 供应商名称
     *       purchaseType -- 结算方式
     *       purchaseTime -- 结算时间
     *       orderId      -- 订单编号
     *       placeOrderTime -- 下单时间
     *       signContractTime -- 签约时间
     *       returnTime  -- 出游归来时间
     *       productId  --  线路编号 / 产品编号
     *       purchaseOrderId -- 采购单号
     *       purchaseCost -- 采购成本
     *       expenseRatio -- 推广费用比例
     *                       格式: 百分比，如"3%"
     *       expense -- 推广费用
     *       purchaseState -- 结算状态 0 未结算 1 已结算  (默认未结算)
     *       invoiceState -- 发票/是否开具发票 0 未开具 1 已开具 (默认未开)
     *       problem --  疑问 = "未提出"
     * */
    public function doRestGetCpsShowReport($url, $data) {
        $result = $this->genrateReturnRest();
        try {
            // 判断参数合法性
            if (isset($data['start']) and is_numeric($data['start'])
                and isset($data['limit']) and is_numeric($data['limit'])){
                $resultMod = $this->cpsMod->getShowReport($data);
                // 整合结果，自定义编码和语句
                $result['data'] = $resultMod;
                $result['errorCode'] = ErrorCode::ERR_231500;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];

                // 返回结果
                $this->returnRestStand($result);
            } else {
                $result['errorCode'] = ErrorCode::ERR_210000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
                // 返回参数不正确
                $this->returnRestStand($result);
            }
        } catch(BBException $e) {
            $result['success'] = false;
            if (intval(chr(48)) != $e->getErrCode()) {
                $result['errorCode'] = $e->getErrCode();
                $result['msg'] = $e->getErrMessage();
            } else {
                $result['errorCode'] = ErrorCode::ERR_231000;
                $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            }

            // 返回结果
            $this->returnRestStand($result);
        } catch(Exception $e) {
            // 注入异常和日志
            new BBException($e->getCode(), $e->getMessage());
            $result['success'] = false;
            $result['errorCode'] = ErrorCode::ERR_231000;
            $result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
            // 返回结果
            $this->returnRestStand($result);
        }

    }
}