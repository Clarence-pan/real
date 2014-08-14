<?php
class OaIao {
	public static function getSaleInfo($params) {
		$client = new RESTClient;
		$url = Yii::app()->params->OA_HOST.'/api';
		$cond = array(array('type' => 1,
					  'value' => $params['opSalerId']),);
		$params = array(
				'subSystem'=>Yii::app()->params->UC_SUB_SYSTEM,
            	'key'=>Yii::app()->params->UC_API_KEY,
				'data' => array('service'=>'query_sales',
								'cond' => $cond),
		);
		try {
			$response = $client->post($url, $params);
		} catch (Exception $e) {
			$response = array(
					'data' => array(),
					'success' => false,
					'errorCode' => 230199,
					'msg' => '系统错误'
			);
		}
		return $response;
	}
}

?>