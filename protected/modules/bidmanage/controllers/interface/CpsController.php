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
	 * 获取费率
	 */
	public function doRestGetExpenseratio($url, $data) {
		
		// 初始化返回结果
		$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (!empty($data['uid']) && !empty($data['nickname'])) {
					
				// 配置费率
				$dataRe = $this->cpsMod->getExpenseRatio($data);
				
				// 整合结果，自定义编码和语句
				$result['errorCode'] = ErrorCode::ERR_231500;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
				$result['data'] = $dataRe;
				
				// 返回结果
				$this->returnRestStand($result);
				
			} else {
				$result['success'] = Symbol::CONS_FALSE;
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
				
			}
		} catch(BBException $e) {
			$result['success'] = Symbol::CONS_FALSE;
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
			$result['success'] = Symbol::CONS_FALSE;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
	}
    
    /**
	 * 配置费率
	 */
	public function doRestPostConfigexpenseratio($data) {
		
		// 初始化返回结果
		$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (!empty($data['expenseRatio']) && !empty($data['uid']) && !empty($data['nickname'])) {
					
				// 配置费率
				$this->cpsMod->configExpenseRatio($data);
				
				// 整合结果，自定义编码和语句
				$result['errorCode'] = ErrorCode::ERR_231511;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231511)];
				
				// 返回结果
				$this->returnRestStand($result);
				
			} else {
				$result['success'] = Symbol::CONS_FALSE;
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
				
			}
		} catch(BBException $e) {
			$result['success'] = Symbol::CONS_FALSE;
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
			$result['success'] = Symbol::CONS_FALSE;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
	}
	
	/**
	 * 获取网站显示的产品
	 */
	public function doRestGetShowcpsproduct($url, $data) {
		
		// 初始化返回结果
		$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (!empty($data['webClass']) && !empty($data['startCityCode']) && !empty($data['productType']) && isset($data['blockName'])) {
					
				// 获取网站显示的产品
				$dataRe = $this->cpsMod->getShowCpsProduct($data);
				
				// 整合结果，自定义编码和语句
				$result['errorCode'] = ErrorCode::ERR_231500;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
				$result['data'] = $dataRe;
				
				// 返回结果
				$this->returnRestStand($result);
				
			} else {
				$result['success'] = Symbol::CONS_FALSE;
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
				
			}
		} catch(BBException $e) {
			$result['success'] = Symbol::CONS_FALSE;
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
			$result['success'] = Symbol::CONS_FALSE;
			$result['errorCode'] = ErrorCode::ERR_231000;
			$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)];
			// 返回结果
			$this->returnRestStand($result);
		}
	}
	
	/**
	 * 网站删除区块，同步数据
	 */
	public function doRestGetDelcpsblock($url, $data) {
		
		// 初始化返回结果
		$result = $this->genrateReturnRest();
		
		try {
			// 校验参数
			if (!empty($data['webClass']) && !empty($data['startCityCode']) && !empty($data['productType']) 
				&& isset($data['delBlockName']) && isset($data['defaultBlockName'])) {
					
				// 网站删除区块，同步数据
				$this->cpsMod->delCpsBlock($data);
				
				// 整合结果，自定义编码和语句
				$result['errorCode'] = ErrorCode::ERR_231500;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231500)];
				
				// 返回结果
				$this->returnRestStand($result);
				
			} else {
				$result['success'] = Symbol::CONS_FALSE;
				$result['errorCode'] = ErrorCode::ERR_210000;
				$result['msg'] = ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_210000)];
				// 返回参数不正确
				$this->returnRestStand($result);
				
			}
		} catch(BBException $e) {
			$result['success'] = Symbol::CONS_FALSE;
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
			$result['success'] = Symbol::CONS_FALSE;
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

    /**导出推广管理报表为excel
     * @param $url   -- 无用
     * @param $data  -- 参数
     */
    public function doRestGetCpsShowReportExcel($url, $param) {
        // 初始化导出标题
        $timeNow = date('Ymdhis');
        $fileName = '招客宝推广管理信息表-'.$timeNow;
        // 输出Excel文件头
        header('Content-Type: application/vnd.ms-excel;charset=gbk');
        header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
        header('Cache-Control: max-age=0');
        // PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        // 输出Excel列头信息
        $head = array('供应商ID', '供应商名称', '结算方式', '结算时间', '订单编号', '下单时间', '签约时间', '出游归来时间', '线路编号', '采购单号', '采购成本', '推广费用比例', '推广费用', '结算状态', '发票是否开具', '疑问');
        // CSV的Excel支持GBK编码，一定要转换，否则乱码
        $head = CommonTools::mapUtf8ToGbk($head);
        // 写入列头
        fputcsv($fp, $head);
        unset($head);

        $step = 500;
        for ($offset = 0; ; $offset += $step) {
            $param['start'] = $offset;
            $param['limit'] = $step;
            $result = $this->cpsMod->getShowReport($param);
            if (empty($result)) {
                break;
            }

            $result = CommonTools::mapCpsShowReportValues($result);
            $result = $result['rows'];
            CommonTools::mapUtf8ToGbk($result);
            foreach ($result as &$row) {
                fputcsv($fp, $row);
            }

            if (count($result) < $step) {
                break;
            }
        }
    }
    
}