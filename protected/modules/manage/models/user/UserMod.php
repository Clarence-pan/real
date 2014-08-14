<?php
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');
Yii::import('application.modules.manage.dal.iao.FinanceIao');
Yii::import('application.modules.manage.dal.iao.BossIao');
Yii::import('application.modules.manage.dal.dao.user.UserDao');
class UserMod{
	private $userDao;
	
	function __construct(){
		$this->userDao = new UserDao();
	}
	
    /**
     *  列表初始化，搜索
     *  @param array $params
     *  @return Ambigous <unknown, boolean, unknown>
     */
    public function getVerdorLists($readParams){       
        $verdorIdsList = BuckbeekIao::getVerdorList($readParams);//@return array(array(id,agency_id),...)
        $userList = array();        
        if(!empty($verdorIdsList['rows'])){
            $agencyIds = array();
            foreach($verdorIdsList['rows'] as $verdorIds){
                $agencyIds[] = $verdorIds['agency_id'];
            }
             $verdorList = FinanceIao::getVerdorList($agencyIds);
             $userList = array();
             foreach($verdorIdsList['rows'] as &$verdor_row){                
                 $verdor_row['rechargeAmount'] = $verdorList['niu'][$verdor_row['agency_id']]['total_charge_amount'];
                 $verdor_row['availableBalance'] = $verdorList['niu'][$verdor_row['agency_id']]['available_balance'];
                 $verdor_row['balance'] = $verdorList['niu'][$verdor_row['agency_id']]['balance'];
                 $verdor_row['consume'] = $verdor_row['rechargeAmount']-$verdor_row['balance'];
                 
                 $verdor_row['couponRechargeAmount'] = $verdorList['coupon'][$verdor_row['agency_id']]['total_charge_coupon_amount'];
                 $verdor_row['couponAvailableBalance'] = $verdorList['coupon'][$verdor_row['agency_id']]['coupon_available_balance'];
                 $verdor_row['couponBalance'] = $verdorList['coupon'][$verdor_row['agency_id']]['coupon_balance'];
                 $verdor_row['couponConsume'] = $verdor_row['couponRechargeAmount']-$verdor_row['couponBalance'];
                 if($verdor_row['rechargeAmount']==0){
                    $verdor_row['delFlag'] = 1;
                 } else {
                    $verdor_row['delFlag'] = 0;    
                 }
                 $userList[] = $verdor_row;
             }
        }
        return array('count'=>$verdorIdsList['count'],'rows'=>$userList);        
    }
    
    
    /**
     *  列表初始化，搜索
     *  @param array $params
     *  @return Ambigous <unknown, boolean, unknown>
     */
    public function getAgencyLists($readParams){
        $productList = BossIao::getAgencyAccountList($readParams);
        if($productList['rows']){
             $addIds = array();
            foreach ($productList['rows'] as $row_list){
                $addIds[] = $row_list['id'];
            }
            
            $havedIds = BuckbeekIao::getIdByAgency($addIds);
            
            if (!empty($havedIds) && is_array($havedIds)) {
                $subArr = array();
                foreach ($havedIds as $id_row) {
                    $subArr[] = $id_row['vendor_id'];
                }
            }else{
                $subArr = array();
            }
            
            foreach ($productList['rows'] as &$data){
                if(in_array($data['id'], $subArr)){
                    $data['addFlag']=1;
                }else{
                    $data['addFlag']=0;
                }
            }
        }
        return $productList;        
    }
    
    /**
     *  添加供应商账户
     *  @param array $params
     *  @return Ambigous <unknown, boolean, unknown>
     */
    public function addAccount($fabParams,$bbParams){
        $checkParams = array(
            'vendorId' => $bbParams['vendorId'],
        );
        $bbAccountRows = BuckbeekIao::getIdByAgency($checkParams);
        if(empty($bbAccountRows)){
            $addBB = BuckbeekIao::addVendorAccount($bbParams);
            $addFab = FinanceIao::addAgencyAccount($fabParams);
        }else{
            $addBB = false;
        }
        if($addBB&&$addFab){
            return true;
        }else{
            return false;
        }
        
    }
    
/**
     * 删除供应商收客宝帐号
     *
     * @author chenjinlong 20121218
     * @param $inParamsArr
     * @return bool
     */
    public function deleteAgencyBuckbeekAccount($inParamsArr)
    {
        $removedResult = BuckbeekIao::deleteBuckbeekAccount($inParamsArr);
        return $removedResult;
    }
    
    public function getVendorInfoByAccountId($accountId) {
        $vendor = $this->userDao->getVendorInfoByAccountId($accountId);
        return $vendor;
    }
    
    public function insertVendorInfo($params) {
        $result = $this->userDao->insertVendorInfo($params);
        return $result;
    }
    
    public function updateVendorInfo($vendorInfo,$params) {
        if($params['cmpName'] != null && $params['cmpName'] != $vendorInfo['cmpName']) {
            $vendorInfo['cmpName'] = $params['cmpName'];
        }
        if($params['cmpPhone'] != null && $params['cmpPhone'] != $vendorInfo['cmpPhone']) {
            $vendorInfo['cmpPhone'] = $params['cmpPhone'];
        }
        if($params['contractor'] != null && $params['contractor'] != $vendorInfo['contractor']) {
            $vendorInfo['contractor'] = $params['contractor'];
        }
        if($params['contractorTel'] != null && $params['contractorTel'] != $vendorInfo['contractorTel']) {
            $vendorInfo['contractorTel'] = $params['contractorTel'];
        }
        if($params['contractorTel2'] != null && $params['contractorTel2'] != $vendorInfo['contractorTel2']) {
            $vendorInfo['contractorTel2'] = $params['contractorTel2'];
        }
        if($params['invoiceType'] != null && $params['invoiceType'] != $vendorInfo['invoiceType']) {
            $vendorInfo['invoiceType'] = $params['invoiceType'];
        }
        if($params['cmpBank'] != null && $params['cmpBank'] != $vendorInfo['cmpBank']) {
            $vendorInfo['cmpBank'] = $params['cmpBank'];
        }
        if($params['cmpAccount'] != null && $params['cmpAccount'] != $vendorInfo['cmpAccount']) {
            $vendorInfo['cmpAccount'] = $params['cmpAccount'];
        }
        if($params['taxNo'] != null && $params['taxNo'] != $vendorInfo['taxNo']) {
            $vendorInfo['taxNo'] = $params['taxNo'];
        }
        if($params['cmpAddress'] != null && $params['cmpAddress'] != $vendorInfo['cmpAddress']) {
            $vendorInfo['cmpAddress'] = $params['cmpAddress'];
        }
        if($params['attachUrl'] != null && $params['attachUrl'] != $vendorInfo['attachUrl']) {
            $vendorInfo['attachUrl'] = $params['attachUrl'];
        }
        $result = $this->userDao->updateVendorInfo($vendorInfo);
        return $result;
    }
    
    public function updateVendorFmisInfo($data) {
        if($data['invoiceType'] == 1) {
            $data['invoiceType'] = 2;
        } else if($data['invoiceType'] == 2){
            $data['invoiceType'] = 1;
        }
        // 向财务推送，重新审核
        $financeParams = array();
        $financeParams['agency_id'] = $data['vendorId'];
        $financeParams['cmp_name'] = $data['cmpName'];
        $financeParams['tax_no'] = $data['taxNo'];
        $financeParams['cmp_bank'] = $data['cmpBank'];
        $financeParams['cmp_account'] = $data['cmpAccount'];
        $financeParams['cmp_phone'] = $data['cmpPhone'];
        $financeParams['cmp_address'] = $data['cmpAddress'];
        $financeParams['contractor'] = $data['contractor'];
        $financeParams['contractor_tel'] = $data['contractorTel'];
        $financeParams['contractor_tel2'] = $data['contractorTel2'];
        $financeParams['address'] = $data['address'];
        $financeParams['invoice_title'] = $data['invoiceTitle'];
        $financeParams['invoice_type'] = $data['invoiceType'];
        $financeParams['attach_url'] = $data['attachUrl'];
        //删除数组中的空值  否则操作财务数据会报错
        $financeParams = array_filter($financeParams);
        $res = FinanceIao::updateVendorInfo($financeParams);
        return $res;
    }
    
    public function getVendorInfoByVendorId($vendorId) {
        $vendor = $this->userDao->getVendorInfoByVendorId($vendorId);
        return $vendor;
    }
    
    
    
    public function uploadFile($_FILES) {
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        if (!empty($_FILES)) {
            foreach ($_FILES as $file){
                if (isset($file['size']) && $file['size'] > 0) {
                    //处理文件名
                    $imageInfo = getimagesize($file['tmp_name']);
                    $extension = image_type_to_extension($imageInfo[2]);
                    $replacements = array(
                            'jpeg' => 'jpg',
                            'tiff' => 'tif',
                    );
                    $extension = strtr($extension, $replacements);
                    if (!$filename) {
                        $filename = $file['name'].$extension;
                    } else {
                        $filename = $filename.$extension;
                    }
                    $result = json_decode(CurlUploadModel::save($file),true);
                    if ($result['success']) {
                        //save to database
                        $url = $result['data'][0]['url'];
                        return $url;
                    } else {
                        return null;
                    }
                }
            }
        }
    }
        
        
    /**
     * 计算资料完整度
     * @param unknown_type $accountId
     */
    public function getAccountDataLevel($accountId) {
        
        $vendorInfo = $this->getVendorInfoByAccountId($accountId);
                
                
//      $res = HagridIao::getVendorInfo($params);
        $level = 0;
        if($vendorInfo['id']==null) {
            $level = 0;
        } else if($vendorInfo['invoiceType'] == 1 && $vendorInfo['cmpName'] != null &&
                    $vendorInfo['cmpPhone'] != null && $vendorInfo['contractor'] != null &&
                    $vendorInfo['contractorTel'] != null && $vendorInfo['contractorTel2'] != null &&
                    $vendorInfo['attachUrl'] != null) {
            $level = 100;   
        } else if($vendorInfo['invoiceType'] == 2 && $vendorInfo['cmpName'] != null &&
                    $vendorInfo['cmpPhone'] != null && $vendorInfo['contractor'] != null &&
                    $vendorInfo['contractorTel'] != null && $vendorInfo['contractorTel2'] != null &&
                    $vendorInfo['attachUrl'] != null && $vendorInfo['cmpBank'] != null &&
                    $vendorInfo['cmpAccount'] != null && $vendorInfo['cmpAddress'] != null &&
                    $vendorInfo['taxNo'] != null) {
            $level = 100;
        }else {
            $level = 50;
        }
        return $level;
    }

    /**
     * 查询供应商广告信息
     *
     * @param Params
     * @return bool
     */
    public function getAdMessage($params)
    {
        $result = BuckbeekIao::getAdMessage($params);
        return $result;
    }

    /**
     * 更新供应商广告信息
     *
     * @param Params
     * @return bool
     */
    public function updateAdMessage($params)
    {
        $result = BuckbeekIao::updateAdMessage($params);
        return $result;
    }
}
?>