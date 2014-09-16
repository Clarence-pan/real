<?php
/**
 *  * Promotion Fmis interface
 * @author xiongyun@2013-01-04
 * @version 1.1
 * @func doRestPostFmisInvoice
 * @func doRestGetReconciliationList
 * @func doRestGetReconciliationDetail
 */

Yii::import('application.modules.bidmanage.models.fmis.FmisManageMod');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.models.CommonSysLogMod');

class FmisController extends restSysServer {
    
    private $fmis;
    private $userManage;

    function __construct() {
    	$this->userManage = new UserManageDao();
        $this->fmis = new FmisManageMod();
    }

 
    /**
     * $client = new restful_client();
     * $request_data = array('fmisId'=>35 ,'invoiceFlag'=>'1');
     * $response = $client->request(RESTFUL_POST, $url, $request_data);
     * 
     * @mapping /fimsinvoice
     * @method POST
     * @param  array $data {"fmisId":35,"invoiceFlag":"1"}
     * @return array {"success":false|true,"msg":"","errorcode":|230001,"data":}
     * @desc   财务已开发票回调接口
     */
    public function doRestPostFmisInvoice($data) {
        if (!$data['fmisId']) {
            $this->returnRest(array(), false, 230029, '更新发票状态入参错误：fmisId为空');
            return;
        }

        $params = array(
            'fmisId' => $data['fmisId'],
            'invoiceFlag' => intval($data['invoiceFlag'])
        );

        $result = $this->fmis->updateFmisInvoice($params);

        if ($result) {
            $this->returnRest($result);
        } else {
            $this->returnRest(array(), false, 230028, '财务更新发票状态失败');
        }
    }
   
    /**
     * 获取竞价成功次数
     */
    public function doRestGetBidCount($url, $data) {
    	// 初始化返回结果
    	$result = array();
    	// 获取竞价成功数量
    	$result['rows'] = $this->fmis->getBidCount($data['agencyIDArr']);
    	// 返回结果
    	$this->returnRest($result);
	}
	
	/**
     * 接口：存储传入的供应商消息信息
     *
     * @author wenrui 20131212
     * @param $data
     * @return
     */
	public function doRestPostInsertMsg($data) {
		
		// 根据vendorId查询accountId
		$account = $this->userManage->getAccountInfoByAgentId($data['vendorId']);
		
		// 判断是否能查询到有效地accountId
		if(empty($account)){
			// 如果查询结果为空返回错误信息
			$this->returnRest(array(), false, 230008, 'error:没有有效的accountId');
			// 日志表插入消息记录
			$params = array(
	            'func' => 'FmisController-doRestPostInsertMsg',
	            'params' => $data,
	        );
			CommonSysLogMod::log(date('Y-m-d H:i:s').__CLASS__,'消息中心-存储消息-报错',1,'wenrui',0,0,'没有与vendorId相对应的供应商信息',json_encode($params),'','');
		}else{
			$accountId = $account['id'];
			// 处理待插入消息信息
			$param = array (
				'accountId' => $accountId,
				'type' => $data['type'],
				'content' => $data['content'],
				'addUid' => $data['add_uid'],
			);
			// 插入消息信息,返回成功失败标志
			$result = $this->fmis->insertMsg($param);
			// 返回成功失败提示
			if($result){
				$this->returnRest(array(), true, 230000, 'success');
			}else{
				$this->returnRest(array(), false, 230008, 'error:插入消息信息出错');
			};
		}
	}
	
	/**
     * 过期供应商子账户余额
     */
    public function doRestGetAgencyoverdate($url, $data) {
    	// 初始化返回结果
    	$result = array();
    	// 过期供应商子账户余额
    	$result = $this->fmis->overdateAgency($data['data']);
    	// 返回结果
    	$this->returnRest(array(), $result['success']);
    }
   
}


    