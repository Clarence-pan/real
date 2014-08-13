<?php

/*
 * Created on 2013-12-31
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class SearchIao {
	public static function fetchData($input, $key, $url) {
		$json_data = json_encode($input);
		$md5_data = md5($json_data . $key);
		$post_data = array (
			'data' => $json_data,
			'md5' => $md5_data
		);
		$json_post_data = json_encode($post_data);
		$encr_data = base64_encode($json_post_data);
		$d_p = array (
			'd' => $encr_data
		);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $d_p);
		$result = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if (is_array($result)) {
			$result = $result['d'];
		}
		$result = base64_decode($result);
		$result = json_decode($result);
		$data = $result->data;
		//if ($code != 200) { $result = false; }
		curl_close($curl);
			// print_r(json_decode($result->data));
		return json_decode($result->data, true);
		//}
	}
}
?>
