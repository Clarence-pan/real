<?php
Yii::import('application.dal.dao.DaoModule');
Yii::import('application.modules.manage.dal.iao.OaIao');
class ManageDao extends DaoModule {
	
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
	
    public function insertRefundRequest($params) {
		$sql = "INSERT INTO hagrid.ba_refund_request
                       ( account_id, amt, account_name, bank_name, account_num, vendor_id, state, remark, col_unit,mobile,add_time)
                VALUES ( :account_id, :amt, :account_name, :bank_name, :account_num, :vendor_id, :state, :remark, :col_unit, :mobile,:add_time);";
		$connection = $this->dbRW;
		try {
			$command = $connection->createCommand($sql);
			$command->bindValue(':account_id', $params['accountId']?$params['accountId']:0);
			$command->bindValue(':vendor_id', $params['vendorId']?$params['vendorId']:0);
			$command->bindValue(':amt', $params['amt']);
			$command->bindValue(':account_name', $params['accountName']?$params['accountName']:'');
			$command->bindValue(':bank_name', $params['bankName']?$params['bankName']:'');
			$command->bindValue(':account_num', $params['accountNum']?$params['accountNum']:'');
			$command->bindValue(':state',0);
			$command->bindValue(':remark', $params['remark']?$params['remark']:'');
			$command->bindValue(':col_unit', $params['colUnit']?$params['colUnit']:'');
            $command->bindValue(':mobile', $params['mobile']?$params['mobile']:'');
            $command->bindValue(':add_time', date('Y-m-d H:i:s'));
			$command->execute();
			return true;
		} catch (Exception $e) {
            var_dump(__CLASS__,__FUNCTION__,$e);
			return false;
		}
	}
	
    public function updateRefundRequest($params) {
		$sql = "UPDATE ba_refund_request
                    SET state = :state,
                        is_traffic = :is_traffic,
                        mark = :mark,
                        add_time = :add_time,
                        trade_time = :trade_time,
                        trade_saler_id = :trade_saler_id,
                        add_saler_id = :add_saler_id,
                        op_saler_id = :op_saler_id,
                        back_reason = :back_reason,
                        is_back = :is_back,
                        ref_cmp_id = :ref_cmp_id,
                        ref_acc_id = :ref_acc_id,
                        back_time = :back_time
                    WHERE id = :id ";
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
	
	/**
	 * 获取退款申请数
	 * @param array $params
	 * @return int
	 */
	public function readRefundCount($params) {
	    $paramsMapSegment = array();
	    //删除标记
	    $condSqlSegment = " del_flag=:delFlag";
	    $paramsMapSegment[':delFlag'] = 0;
	    //供应商名称
	    if (!empty($params['vendorName'])) {
	        $condSqlSegment .= ' AND account_name=:vendorName';
	        $paramsMapSegment[':vendorName'] = $params['vendorName'];
	    }
	    //退款金额
	    if (0 != $params['drawbackMoney']) {
	        $condSqlSegment .= ' AND amt=:drawbackMoney';
	        $paramsMapSegment[':drawbackMoney'] = $params['drawbackMoney'];
	    }
	    //申请时间
	    if (!empty($params['applicationDateArray'][0]) && !empty($params['applicationDateArray'][1])) {
	        $condSqlSegment .= ' AND add_time>=:applicationFrom AND add_time<=:applicationTo';
	        $paramsMapSegment[':applicationFrom'] = $params['applicationDateArray'][0];
	        $paramsMapSegment[':applicationTo'] = $params['applicationDateArray'][1];
	    }
	    
	    if (2 == intval($params['refundTab'])) {    //已确认tab下的搜索条件
	        //确认时间
	        if (!empty($params['confirmDateArray'][0]) && !empty($params['confirmDateArray'][1])) {
	            $condSqlSegment .= ' AND trade_time>=:confirmFrom AND trade_time<=:confirmTo';
	            $paramsMapSegment[':confirmFrom'] = $params['confirmDateArray'][0];
	            $paramsMapSegment[':confirmTo'] = $params['confirmDateArray'][1];
	        }
	        //状态
	        if (0 != intval($params['confirmState'])) {
	            $condSqlSegment .= ' AND state=:confirmState';
	            $paramsMapSegment[':confirmState'] = intval($params['confirmState']);
	        }
	    } elseif(3 == intval($params['refundTab'])) {    //已退回tab下的搜索条件
	        //确认时间
	        if (!empty($params['backDateArray'][0]) && !empty($params['backDateArray'][1])) {
	            $condSqlSegment .= ' AND back_time>=:backFrom AND back_time<=:backTo';
	            $paramsMapSegment[':backFrom'] = $params['backDateArray'][0];
	            $paramsMapSegment[':backTo'] = $params['backDateArray'][1];
	        }
	    }
	    
	    if (1 == intval($params['refundTab'])) {    //待处理tab
	        $condSqlSegment .= ' AND is_back=:isBack AND state=:state';
	        $paramsMapSegment[':isBack'] = 0;
	        $paramsMapSegment[':state'] = 0;
	    } elseif (2 == intval($params['refundTab'])) {    //已确认tab
	        $condSqlSegment .= ' AND is_back=:isBack AND (state=1 OR state=2 OR state=3)';
	        $paramsMapSegment[':isBack'] = 0;
	    } elseif(3 == intval($params['refundTab'])) {    //已退回tab
	        $condSqlSegment .= ' AND (is_back=:isBack OR state=:state)';
	        $paramsMapSegment[':isBack'] = 1;
	        $paramsMapSegment[':state'] = -1;
	    }
	    
	    $refundCount = $this->dbRO->createCommand()
	            ->select('COUNT(*) count')
	            ->from('ba_refund_request')
	            ->where($condSqlSegment, $paramsMapSegment)
	            ->queryScalar();
	    
	    return $refundCount;
	}
	
	/**
	 * 获取退款列表
	 * @param array $params
	 * @return array
	 */
	public function readRefundList($params) {
        $paramsMapSegment = array();
        //删除标记
        $condSqlSegment = " del_flag=:delFlag";
        $paramsMapSegment[':delFlag'] = 0;
        //供应商名称
        if (!empty($params['vendorName'])) {
            $condSqlSegment .= ' AND account_name=:vendorName';
            $paramsMapSegment[':vendorName'] = $params['vendorName'];
        }
        //退款金额
        if (0 != $params['drawbackMoney']) {
            $condSqlSegment .= ' AND amt=:drawbackMoney';
            $paramsMapSegment[':drawbackMoney'] = $params['drawbackMoney'];
        }
        //申请时间
        if (!empty($params['applicationDateArray'][0]) && !empty($params['applicationDateArray'][1])) {
            $condSqlSegment .= ' AND add_time>=:applicationFrom AND add_time<=:applicationTo';
            $paramsMapSegment[':applicationFrom'] = $params['applicationDateArray'][0];
            $paramsMapSegment[':applicationTo'] = $params['applicationDateArray'][1];
        }
        
        if (2 == intval($params['refundTab'])) {    //已确认tab下的搜索条件
            //确认时间
            if (!empty($params['confirmDateArray'][0]) && !empty($params['confirmDateArray'][1])) {
                $condSqlSegment .= ' AND trade_time>=:confirmFrom AND trade_time<=:confirmTo';
                $paramsMapSegment[':confirmFrom'] = $params['confirmDateArray'][0];
                $paramsMapSegment[':confirmTo'] = $params['confirmDateArray'][1];
            }
            //状态
            if (0 != intval($params['confirmState'])) {
                $condSqlSegment .= ' AND state=:confirmState';
                $paramsMapSegment[':confirmState'] = intval($params['confirmState']);
            }
        } elseif(3 == intval($params['refundTab'])) {    //已退回tab下的搜索条件
	        //确认时间
	        if (!empty($params['backDateArray'][0]) && !empty($params['backDateArray'][1])) {
	            $condSqlSegment .= ' AND back_time>=:backFrom AND back_time<=:backTo';
	            $paramsMapSegment[':backFrom'] = $params['backDateArray'][0];
	            $paramsMapSegment[':backTo'] = $params['backDateArray'][1];
	        }
	    }
        
        $selectSegment = 'id,account_name accountName,amt,remark,mobile,add_time addTime,state';
        
        if (1 == intval($params['refundTab'])) {    //待处理tab
            $condSqlSegment .= ' AND is_back=:isBack AND state=:state';
            $paramsMapSegment[':isBack'] = 0;
            $paramsMapSegment[':state'] = 0;
        } elseif (2 == intval($params['refundTab'])) {    //已确认tab
            $selectSegment .= ',op_saler_id opSalerId,is_traffic isTraffic,trade_time tradeTime, ref_cmp_id refCmpId,ref_acc_id refAccId';
            $condSqlSegment .= ' AND is_back=:isBack AND (state=1 OR state=2 OR state=3)';
            $paramsMapSegment[':isBack'] = 0;
        } elseif(3 == intval($params['refundTab'])) {    //已退回tab
            $selectSegment .= ',op_saler_id opSalerId,back_reason backReason,is_back isBack,back_time backTime';
            $condSqlSegment .= ' AND (is_back=:isBack OR state=:state)';
            $paramsMapSegment[':isBack'] = 1;
            $paramsMapSegment[':state'] = -1;
        }
        
        $refund = $this->dbRO->createCommand()
                ->select($selectSegment)
                ->from('ba_refund_request')
                ->where($condSqlSegment, $paramsMapSegment)
                ->limit($params['limit'], $params['start'])
                ->queryAll();
        // 财务操作人
        if(count($refund) > 0) {
        	foreach($refund as &$value) {
        		$res = OaIao::getSaleInfo($value);
        		if($res['success']) {
        			$value['opSalerName'] = $res['data']['sales'][$value['opSaleId']]['name'];
        			$index = $value['opSalerId'];
        			$value['opSalerName'] = $res['data']['sales'][$index]['name'];
        		} else {
        			$value['opSalerName'] = null;
        		}
        	}
        }
        return $refund;
	}
	
	/**
	 * 退款审核
	 * @param array $param
	 * @return boolean
	 */
	public function updateRefundAudit($param) {
		$cond = 'id=:id AND del_flag=0';
	    $params = array(':id' => $param['applicationId']);
	    
	    $result = $this->dbRW->createCommand()->update('ba_refund_request', array(
	            'state' => intval($param['operateFlag']),
                'update_uid' => $param['uid'],
                'update_time' => date('Y-m-d H:i:s'),
	    		'back_time' => date('Y-m-d H:i:s'),
	        ), $cond, $params);
	    
	    if (!empty($result)) {
	        return true;
	    } else {
	        return false;
	    }
	}
	
	/**
	 * 获取退款明细
	 * @param int $id 退款申请ID
	 * @return array
	 */
	public function readRefundDetail($id) {
	    $paramsMapSegment = array();
	    //删除标记
	    $condSqlSegment = " del_flag=:delFlag";
	    $paramsMapSegment[':delFlag'] = 0;
	    
	    //退款申请ID
	    if (!empty($id)) {
	        $condSqlSegment .= ' AND id=:applicationId';
	        $paramsMapSegment[':applicationId'] = intval($id);
	    } else {
	        return array();
	    }
	    
	    $refund = $this->dbRO->createCommand()
                ->select('id bb_id,vendor_id agency_id,amt,account_name,bank_name,account_num,add_saler_id,col_unit,mobile,remark')
                ->from('ba_refund_request')
                ->where($condSqlSegment, $paramsMapSegment)
                ->queryRow();
	    
	    return $refund;
	}
	
	/**
	 * 退款信息回调
	 * @param array $param
	 * @return boolean
	 */
	public function updateRefundFmis($param) {
	    $cond = 'id=:id AND del_flag=0';
	    $params = array(':id' => $param['id']);
	    
	    $result = $this->dbRW->createCommand()->update('ba_refund_request', array(
	            'trade_time' => $param['tradeTime'],
	            'trade_saler_id' => $param['tradeSalerId'],
	            'add_saler_id' => $param['addSalerId'],
	            'op_saler_id' => $param['opSalerId'],
	            'back_reason' => $param['backReason'],
	            'is_back' => $param['isBack'],
                'is_traffic' => $param['isTraffic'],
	            'ref_cmp_id' => $param['refCmpId'],
	            'ref_acc_id' => $param['refAccId'],
	            'back_time' => $param['backTime'],
	            'state' => $param['state'],
	            'update_time' => date('Y-m-d H:i:s')
	    ), $cond, $params);
	    
	    if (!empty($result)) {
	        return true;
	    } else {
	        return false;
	    }
	}
}
?>