<?php
/** UI呈现接口 | Hagrid 报表统计查询
* Hagrid statement interfaces for inner UI system.
* @author xiongyun@2013-01-29
* @version 1.0
* @func doRestGetSpreadOverview 
* @func doRestGetSpreadFile
*/
Yii::import('application.modules.manage.models.statistic.StatementMod');
class StatementController extends restfulServer{
	private $statementMod;

	function __construct() {
		$this->statementMod = new StatementMod();
	}

    /**
     * $client = new RESTClient();
      $url = self::DOMAIN.'bb/public/user/spreadOverview';
      $params = array(
      'accountId' => 1,
      'startDate' => '2012-01-01',
      'endDate' => '2014-01-01',
      'startCityCode' => 2500,
      'productType' => 1,
      'destinationClass' => 1,
      'productLineName' => 'aaaaa',
      'productName' => 'bbbbb',
      'productId' => 1,
      'isPaied' => 2,
      'adKey' => 'index_chosen',
      );
      $format = 'encrypt';
      $res = $client->get($url, $params, $format);
     *
     * @mapping /spreadfile
     * @method GET
     * @param string $url 
     * @param  array $data {"accountId":2820,"startDate":,"endDate":,
     * 							"startCityCode":,"productType":,"destinationClass":,"productLineName":,
     * 							"productName":,"productId":,"isPaied":,"adKey":}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 获取推广数据报表
     */
    public function doRestGetSpreadFile($url, $data) {
        if (!$data['uid']) {
            $this->returnRest(array(), false, 230113, '获取推广概况表格入参错误：uid为空');
            return;
        }
        if (!$data['nickname']) {
            $this->returnRest(array(), false, 230113, '获取推广概况列表表格错误：nickname为空');
            return;
        }

        $params = array(
            'vendorId' => $data['vendorId'],
            'vendorName' => $data['vendorName'],
            'productLine' => $data['productLine'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'isPaied' => intval($data['isPaied']),
            'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
            'productLineName' => $data['productLineName'],
            'productId' => $data['productId'],
            'productName' => $data['productName'],
            'productType' => $data['productLine']['productType'],
            'destinationClass' => $data['productLine']['destinationClass'],
            'productLineName' => $data['productLineName'],
            'isDownload' => 2,
        );


        $resultUrl = $this->statementMod->readStatmentListFile($params);

        if ($resultUrl) {
            $filename = date('Ymdhis') ."HGstatement";
            //提示用户保存一个生成的 excel 文件
            Header("Content-type: application/vnd.ms-excel");
            Header("Accept-Ranges: bytes");
            Header("Content-Disposition: attachment; filename=".$filename.".xls");
            Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            Header("Pragma: no-cache");
            Header("Expires: 0");
            readfile($resultUrl);
            die();
        } else {
            $this->returnRest(array(), false, 230116, '下载推广概况表格失败');
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('listType'=>,'productType'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /reportforms
     * @method GET
     * @param string $url
     * @param  array $inParams {"listType":"destinationClass","productType":1,"uid": "4220","nickname": ""}
     * @return array {"success":false|true,"msg":"","errorcode":|230115,"data":}
     * @desc hg-招客宝报表
     */
    public function doRestGetReportForms($url, $data) {
        if (!$data['uid']) {
            $this->returnRest(array(), false, 230113, '获取招客宝报表入参错误：uid为空');
            return;
        }
        if (!$data['nickname']) {
            $this->returnRest(array(), false, 230113, '获取招客宝报表入参错误：nickname为空');
            return;
        }

        $params = array(
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
            'productId' => $data['productId'],
            'productName' => $data['productName'],
            'vendorId' => $data['vendorId'],
            'vendorName' => $data['vendorName'],
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10,
        );
        $response = $this->statementMod->getReportForms($params);
        $this->returnRest($response);
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('listType'=>,'productType'=>,'uid'=>,'nickname'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /biInfo
     * @method GET
     * @param string $url
     * @param  array $inParams {"listType":"destinationClass","productType":1,"uid": "4220","nickname": ""}
     * @return array {"success":false|true,"msg":"","errorcode":|230115,"data":}
     * @desc hg-招客宝报表-查询所有的BI数据
     */
    public function doRestGetBIInfo($url, $data) {
        if (!$data['uid']) {
            $this->returnRest(array(), false, 230113, '获取招客宝报表入参错误：uid为空');
            return;
        }
        if (!$data['nickname']) {
            $this->returnRest(array(), false, 230113, '获取招客宝报表入参错误：nickname为空');
            return;
        }

        $params = array(
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'adKey' => $data['adKey'],
            'startCityCode' => $data['startCityCode'],
            'productId' => $data['productId'],
            'productName' => $data['productName'],
            'vendorId' => $data['vendorId'],
            'vendorName' => $data['vendorName'],
        );
        $response = $this->statementMod->getBIInfo($params);
        $this->returnRest($response);
    }
    
    /**
	 * 查询财务账户报表
	 */
	public function doRestGetFmischarts($url, $data) {
		
		// 校验参数
		if (isset($data['start']) && !empty($data['limit']) && isset($data['isExcel']) 
			&& is_numeric($data['start']) && is_numeric($data['limit']) && is_numeric($data['isExcel'])) {
			$result = $this->statementMod->getFmisCharts($data);
			if (0 == $data['isExcel']) {
				$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
			} else if (1 == $data['isExcel']) {
				// 初始化导出标题
		        $timeNow = date('Ymdhis');
		        $fileName = '招客宝财务统计表-'.$timeNow;
				// 输出Excel文件头 
				header('Content-Type: application/vnd.ms-excel;charset=gbk');
				header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
				header('Cache-Control: max-age=0');
				// PHP文件句柄，php://output 表示直接输出到浏览器 
				$fp = fopen('php://output', 'a');
				// 输出Excel列头信息
				$head = array('供应商ID', '供应商名称', '原有本币', '原有赠币', '充值本币', '赠送赠币', '消耗本币', '消耗赠币', '剩余本币', '剩余赠币');

				foreach ($head as $i => $v) {
				    // CSV的Excel支持GBK编码，一定要转换，否则乱码 
				    $head[$i] = iconv('utf-8', 'gbk', $v);
				}
				// 写入列头 
				fputcsv($fp, $head);
				// 计数器
//		        $start = 0;
//		        $limit = 500;
		        
//		        do{
//		        	$param['start'] = $start;
//		        	$param['limit'] = $limit;
//		        	$result = $this->_packagePlanMod->getPackagePlans($data);
		        	$rows = $result['data']['rows'];
		        	$count = $result['data']['count'];
			        if ($rows) {
						foreach ($rows as $row) {
						    $list = array($row['agencyId'],$row['agencyName'],$row['niuAmt'],$row['couponAmt'],$row['chargeNiuAmt'],strval($row['chargeCouponAmt']),
						    			$row['expenseNiuAmt'],$row['expenseCouponAmt'],$row['availableNiuAmt'],$row['availableCouponAmt']);
						    foreach ($list as $i => $v) {
						        $list[$i] = iconv('utf-8', 'gbk', $v);
						    }
						    fputcsv($fp, $list);
						}
			        } else {
			        	break;
			        }
		        	// $start += 500;
		        	
//		        	if($start > $count){
//		        		break;
//		        	}
//		        } while ( 1==1 );
			}
		} else {
			// 返回参数不正确
			$this->returnRest(array(), false, 210000, '参数不正确！');
		}

	}
	

}
