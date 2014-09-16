<?php
class BidclientController extends CController{
	const DOMAIN = 'http://www.buckbeek.me/';
	var $successCount = 0;
	var $failCount = 0;
	var $failFunc = '';
	
	
	public function actionIndex() {
// 		$this->updateIndexChosenRecordTest();
// 		$this->delIndexChosenRecordTest();
// 		$this->updateClassCommendRecordTest();
// 		$this->delClassCommendRecordTest();
// 		$this->getIndexChosenRecordTest();
// 		$this->getClassRecommendRecordTest();
		$this->getSpreadFile();
	}
	
	private function updateIndexChosenRecordTest() {
		$client = new RESTClient();
		$url = self::DOMAIN.'bb/bid/update-record';
		$params = array(
				'accountId' => 1,
		        'adKey' => 'index_chosen',
		        'productId' => 13109,
				'dateList' => '2013-01-29',
		        'price' =>600,
		        'webClassId' => 0,
		        'delFlag' => 0,
		);
		$format = 'encrypt';
		$res = $client->post($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
	
	private function delIndexChosenRecordTest() {
		$client = new RESTClient();
		$url = self::DOMAIN.'/bb/bid/update-record';
		$params = array(
				'accountId' => 1,
				'adKey' => 'index_chosen',
				'productId' => 13109,
				'dateList' => '2013-01-29',
				'price' =>0,
				'webClassId' => 0,
				'delFlag' => 1,
		);
		$format = 'encrypt';
		$res = $client->post($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
	
	private function updateClassCommendRecordTest() {
		$client = new RESTClient();
		$url = self::DOMAIN.'bb/bid/update-record';
		$params = array(
				'accountId' => 44,
				'adKey' => 'class_recommend',
				'productId' => 9144,
				'dateList' => '2014-01-12',
				'price' =>50,
				'webClassId' => 860,
				'delFlag' => 0,
		);
		$format = 'encrypt';
		$res = $client->post($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
	
	private function delClassCommendRecordTest() {
		$client = new RESTClient();
		$url = self::DOMAIN.'bb/bid/update-record';
		$params = array(
				'accountId' => 44,
				'adKey' => 'class_recommend',
				'productId' => 9144,
				'dateList' => '2014-01-12',
				'price' =>50,
				'webClassId' => 860,
				'delFlag' => 1,
		);
		$format = 'encrypt';
		$res = $client->post($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
	
	private function getIndexChosenRecordTest() {
		$client = new RESTClient();
		$url = self::DOMAIN.'bb/bid/rank';
		$params = array(
				'accountId' => 44,
				'adKey' => 'index_chosen',
				'productId' => 11060,
				'date' => '2012-12-20',
		);
		$format = 'encrypt';
		$res = $client->get($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
	
	private function getClassRecommendRecordTest() {
		$client = new RESTClient();
		$url = self::DOMAIN.'bb/bid/rank';
		$params = array(
				'accountId' => 44,
				'adKey' => 'class_recommend',
				'productId' => 11060,
				'date' => '2012-12-29',
				'webClassId' => 2015,
		);
		$format = 'encrypt';
		$res = $client->get($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
	
	function printResult($funcName,$res) {
		$desc = '';
		if($res['success']) {
			$this->successCount ++;
			$desc = '运行成功！';
			$result = "<span style='background-color:green;font-weight:bold;'>".$funcName.$desc.'</span>';
		} else {
			$this->failCount ++;
			$desc = '运行失败！';
			$result = "<span style='background-color:red;font-weight:bold;'>".$funcName.$desc.'</span>';
			$this->failFunc .= $funcName.'，';
		}
		print_r('******************************************************************************************');
		print_r('<br/>');
		print_r($result);
		print_r('<br/>');
		print_r($res);
		print_r('<br/>');
		print_r('******************************************************************************************');
		print_r('<br/>');
		print_r('运行成功'.$this->successCount.'个；'.'失败'.$this->failCount.'个。');
		if($this->failCount>0) {
			print_r('<br/>');
			print_r('失败函数：'.$this->failFunc);
		}
	}
	
	private function getSpreadFile() {
		$client = new RESTClient();
		$url = self::DOMAIN.'bb/user/spreadfile';
		$params = array(
				'accountId' => 1,
		);
		$format = 'encrypt';
		$res = $client->get($url, $params, $format);
		$client->debug();
		var_dump($res);
		$this->printResult(__FUNCTION__,$res);
	}
}
?>