<?php
Yii::import('application.modules.bidmanage.models.bid.BidProduct');
Yii::import('application.modules.bidmanage.models.bid.BidProductEmployeeImpl');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
class BidModFactory {
	private static $_bidProduct;
	private static $_bidProductEmployeeImpl;
	const AGENCY = 1;
	const EMPLOYEE = 2;
	
	public static function getInstance() {
		$userManage = new UserManageMod();
		$accountType = $userManage->getAccountType();
		if($accountType == self::AGENCY) {
			if(self::$_bidProduct == null) {
				self::$_bidProduct = new BidProduct();
			}
			return self::$_bidProduct;
		} else if($accountType == self::EMPLOYEE ) {
			if(self::$_bidProductEmployeeImpl == null) {
				self::$_bidProductEmployeeImpl = new BidProductEmployeeImpl();
			}
			return self::$_bidProductEmployeeImpl;
		}
	}
	
	
}

?>