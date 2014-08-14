<?php
Yii::import('application.modules.manage.dal.iao.AgencyAccountIao');
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');

class AgencyAccountMod {
      
    /**
     *  列表初始化，搜索
     *  @param array $params
     *  @return Ambigous <unknown, boolean, unknown>
     */
    public function getAgencyLists($readParams){
        $productList = AgencyAccountIao::getAgencyAccountList($readParams);
        if($productList['rows']){
             $addIds = array();
            foreach ($productList['rows'] as $row_list){
                $addIds[] = $row_list['id'];
            }
            
            $havedIds = AgencyAccountIao::getIdByAgency($addIds);
            
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

        $addFab = AgencyAccountIao::addAgencyAccount($fabParams);
        $addBB = AgencyAccountIao ::addVendorAccount($bbParams);
        if($addBB&&$addFab){
            return true;
        }else{
            return false;
        }
        
    }
     /**
     *  列表初始化，搜索
     *  @param array $params
     *  @return Ambigous <unknown, boolean, unknown>
     */
    public function getVerdorLists($readParams){       
        $verdorIdsList = AgencyAccountIao::getVerdorList($readParams);//@return array(array(id,agency_id),...)
        $userList = array();        
        if(!empty($verdorIdsList['rows'])){
            $agencyIds = array();
            foreach($verdorIdsList['rows'] as $verdorIds){
                $agencyIds[] = $verdorIds['agency_id'];
            }
             $verdorList = AgencyAccountIao::getVerdorListFab($agencyIds);
             $userList = array();
             foreach($verdorIdsList['rows'] as &$verdor_row){                
                 $verdor_row['rechargeAmount'] = $verdorList[$verdor_row['agency_id']]['total_charge_amount'];
                 $verdor_row['availableBalance'] = $verdorList[$verdor_row['agency_id']]['available_balance'];
                 $verdor_row['balance'] = $verdorList[$verdor_row['agency_id']]['balance'];
                 $verdor_row['consume'] = $verdor_row['rechargeAmount']-$verdor_row['balance'];
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

}