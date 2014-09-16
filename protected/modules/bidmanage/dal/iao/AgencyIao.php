<?php
class AgencyIao {
	const PRODUCT_API_PATH = "restfulServer/vendor";
	public static function getAgencyBrandName($params) {
		$client = new RESTClient();
		$url = Yii::app()->params['CRM_HOST'].self::PRODUCT_API_PATH.'/condlists';
		try {
			$response = $client->get($url, $params);
		}catch(Exception $e) {
			Yii::log($e, 'warning');
			return array();
		}
		 
		if($response['success']) {
			return $response['data'];
		}
		 
		return array();
	}
}

?>