<?php
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
class AdminTool {
	public static function isAdmin($params) {
		$adminId = Yii::app()->params['ADMINID'];
		$vendorId = $params['vendorId'];
		$isAdmin = false;
		if(empty($vendorId)) {
			$accountId = $params['accountId'];
			if(!empty($accountId)) {
				$manageMod = new UserManageMod();
				$vendorId = $manageMod->getVendorIdByAccountId($accountId);
				$params['vendorId'] = $vendorId;
			} else {
				return false;
			}
		}
		if(!empty($params['vendorId']) && in_array($params['vendorId'],$adminId)) {
			$isAdmin = true;
		} else {
			$isAdmin = false;
		}
		return $isAdmin;
	}
}

?>