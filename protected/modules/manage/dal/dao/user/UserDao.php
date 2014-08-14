<?php
Yii::import('application.dal.dao.DaoModule');
class UserDao extends DaoModule{
	
    public function getVendorInfoByAccountId($accountId) {
        $condSeg = '';
        $condParam = array();
        $condSeg .= ' del_flag=:del_flag';
        $condParam[':del_flag'] = 0;
        $condSeg .= ' AND account_id=:account_id';
        $condParam[':account_id'] = $accountId;
        $info = $this->dbRO->createCommand()
        ->select('id,account_id accountId,cmp_name cmpName,tax_no taxNo,cmp_bank cmpBank,cmp_account cmpAccount,cmp_phone cmpPhone,
                    cmp_address cmpAddress,address,invoice_title invoiceTitle,invoice_type invoiceType,
                    attach_url attachUrl,contractor,contractor_tel contractorTel,
                    contractor_tel2 contractorTel2')
        ->from('ba_vendor_info')
        ->where($condSeg, $condParam)
        ->queryRow();
        return $info;
    }
    
    public function updateVendorInfo($params) {
        $sql = "UPDATE ba_vendor_info SET cmp_name=:cmpName,
                        cmp_bank=:cmpBank,cmp_account=:cmpAccount,
                        cmp_phone=:cmpPhone,invoice_type=:invoiceType,
                        tax_no=:taxNo,cmp_address=:cmpAddress,
                        contractor=:contractor,contractor_tel=:contractorTel,
                        contractor_tel2=:contractorTel2,attach_url=:attachUrl,
                        address=:address,invoice_title=:invoiceTitle,
                        update_time=:updateTime
                WHERE account_id=:accountId";
        $connection = $this->dbRW;
        try {
            $command = $connection->createCommand($sql);
            $command->bindValue(':accountId', $params['accountId']);
            $command->bindValue(':cmpName', $params['cmpName']!=null?$params['cmpName']:'');
            $command->bindValue(':cmpBank', $params['cmpBank']!=null?$params['cmpBank']:'');
            $command->bindValue(':cmpAccount', $params['cmpAccount']!=null?$params['cmpAccount']:'');
            $command->bindValue(':cmpPhone', $params['cmpPhone']!=null?$params['cmpPhone']:'');
            $command->bindValue(':invoiceType', $params['invoiceType']!=null?$params['invoiceType']:2);
            $command->bindValue(':taxNo', $params['taxNo']!=null?$params['taxNo']:'');
            $command->bindValue(':cmpAddress', $params['cmpAddress']!=null?$params['cmpAddress']:'');
            $command->bindValue(':contractor', $params['contractor']!=null?$params['contractor']:'');
            $command->bindValue(':contractorTel', $params['contractorTel']!=null?$params['contractorTel']:'');
            $command->bindValue(':contractorTel2', $params['contractorTel2']!=null?$params['contractorTel2']:'');
            $command->bindValue(':attachUrl', $params['attachUrl']!=null?$params['attachUrl']:'');
            $command->bindValue(':address', $params['address']!=null?$params['address']:'');
            $command->bindValue(':invoiceTitle', $params['invoiceTitle']!=null?$params['invoiceTitle']:'');
            $command->bindValue(':updateTime', date('Y-m-d H:i:s'));
            $command->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    public function insertVendorInfo($params) {
        $sql = "INSERT INTO ba_vendor_info(account_id,vendor_id,
                    cmp_name,tax_no,cmp_bank,cmp_account,
                    cmp_phone,cmp_address,contractor,contractor_tel,
                    contractor_tel2,address,invoice_title,invoice_type,
                    attach_url,add_time,update_uid,update_time,
                    del_flag,misc) 
                VALUES(:accountId,:vendorId,:cmpName,:taxNo,
                       :cmpBank,:cmpAccount,:cmpPhone,:cmpAddress,
                       :contractor,:contractorTel,
                       :contractorTel2,:address,
                       :invoiceTitle,:invoiceType,:attachUrl,
                       :addTime,:updateUid,
                       :updateTime,:delFlag,
                       :misc)";
        try {
            $connection = $this->dbRW;
            $command = $connection->createCommand($sql);
            $command->bindValue(':accountId', $params['accountId']);
            $command->bindValue(':vendorId', $params['vendorId']);
            $command->bindValue(':cmpName', $params['cmpName']!=null?$params['cmpName']:'');
            $command->bindValue(':cmpBank', $params['cmpBank']!=null?$params['cmpBank']:'');
            $command->bindValue(':cmpAccount', $params['cmpAccount']!=null?$params['cmpAccount']:'');
            $command->bindValue(':cmpPhone', $params['cmpPhone']!=null?$params['cmpPhone']:'');
            $command->bindValue(':invoiceType', $params['invoiceType']!=null?$params['invoiceType']:2);
            $command->bindValue(':taxNo', $params['taxNo']!=null?$params['taxNo']:'');
            $command->bindValue(':cmpAddress', $params['cmpAddress']!=null?$params['cmpAddress']:'');
            $command->bindValue(':contractor', $params['contractor']!=null?$params['contractor']:'');
            $command->bindValue(':contractorTel', $params['contractorTel']!=null?$params['contractorTel']:'');
            $command->bindValue(':contractorTel2', $params['contractorTel2']!=null?$params['contractorTel2']:'');
            $command->bindValue(':attachUrl', $params['attachUrl']!=null?$params['attachUrl']:'');
            $command->bindValue(':address', $params['address']!=null?$params['address']:'');
            $command->bindValue(':invoiceTitle', $params['invoiceTitle']!=null?$params['invoiceTitle']:'');
            $command->bindValue(':addTime', date('Y-m-d H:i:s'));
            $command->bindValue(':updateTime', date('Y-m-d H:i:s'));
            $command->bindValue(':updateUid', $params['accountId']);
            $command->bindValue(':delFlag', 0);
            $command->bindValue(':misc', '');
            $command->execute();
            return true;
        } catch (Exception $e) {
            Yii::log($e);
            return false;
        }

    }
    
    public function getVendorInfoByVendorId($vendorId) {
        $condParam = array();
        $condSeg = ' del_flag=:del_flag AND vendor_id=:vendorId';
        $condParam[':del_flag'] = 0;
        $condParam[':vendorId'] = $vendorId;
        
        $vendor = $this->dbRO->createCommand()
                ->select('account_id accountId,vendor_id vendorId')
                ->from('ba_vendor_info')
                ->where($condSeg, $condParam)
                ->queryRow();
        
        return $vendor;
    }
}
?>