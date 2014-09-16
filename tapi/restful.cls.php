<?php

define('RESTFUL_GET', 'GET');
define('RESTFUL_ADD', 'POST');
define('RESTFUL_MDF', 'PUT');
define('RESTFUL_DEL', 'DELETE');

class restful {
    
	protected function gen_original_data($data) {
        if (!$data) {
            return;
        }
        try {
	        $dencr_data = $this->decrypt_data($data);
	        if (!$dencr_data) {
	            throw new restful_exception('数据解密错误!', restful_exception::ERROR_BASE64_DECODE_FAIL);
	        }
	        $json_data = $this->json_data_decode($dencr_data);
	    	if (!$json_data) {
	    		throw new restful_exception('JSON解密错误!', restful_exception::ERROR_JSON_DECODE_FAIL);
	        }
        } catch (restful_exception $e) {
    		throw $e;
		}
        return $json_data;
    }

    private function json_data_decode($data) {
        $json_data = json_decode($data, true);
        return $json_data;
    }

    private function decrypt_data($base64_data) {
        $data = base64_decode($base64_data);
        return $data;
    }

    protected function gen_restful_data($data) {
        if (!$data) {
            return;
        }
        $json_data = $this->json_data($data);
        $encr_data = $this->encrypt_data($json_data);
        return $encr_data;
    }

    private function json_data($data) {
        $json_data = json_encode($data);
        return $json_data;
    }

    private function encrypt_data($data) {
        $base64_data = base64_encode($data);
        return $base64_data;
    }

}

/**
 * restful客户端
 * @author wuhuanhong
 */
class restful_client extends restful {
    
     private static $restfulCache = array();
    
    /**
     * 请求接口数据
     * @param 常量 $method restful 方法：RESTFUL_GET | RESTFUL_ADD | RESTFUL_MDF | RESTFUL_DEL
     * @param url $url 此不带?和之后的参数。有额外数据，请使用$data进行传输
     * @param string | array $data 额外数据
     * @return string | array 
     */
    function request($method, $url, $request_data=null) {
        $rest_data = $this->gen_restful_data($request_data);
        try{
        $key = md5(json_encode($method. $url. $rest_data));
         if(self::$restfulCache[$key]){
             return self::$restfulCache[$key];
         }
        }catch(Exception $exc) {
            //不处理
        }
        
        $ret_data = $this->request_http($method, $url, $rest_data);
        if (!$ret_data) {
            return $ret_data;
        }
        $response_data = $this->gen_original_data($ret_data);
        self::$restfulCache[$key] = $response_data;
        return $response_data;
    }

    private function request_http($method, $url, $rest_data) {
    	try {
    		$curl = curl_init();
           
	        if ($method == RESTFUL_GET) {
	            if ($rest_data) {
	                $url .='?' . $rest_data;
	            }
	        } else {
	            curl_setopt($curl, CURLOPT_POSTFIELDS, $rest_data);
	        }

	        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	        curl_setopt($curl, CURLOPT_URL, $url);
	        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
	        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($curl, CURLOPT_HEADER, 0);
	
	        $result = curl_exec($curl);
	        $curl_err_code = curl_errno($curl);
	        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			
	        if ($curl_err_code > 0) {
	        	throw new restful_exception('Curl error: ' . curl_error($curl) . ', Curl Error No: ' . $curl_err_code, restful_exception::ERROR_CURL_ERROR);
	        }
	        if ($code != 200) {
	        	throw new restful_exception('http status code: ' .$code. '.', restful_exception::ERROR_HTTP_STTUS_CODE);
	        }
	        
    	} catch (restful_exception $e) {
    		throw $e;
		}
        return $result;
    }

}

/**
 * restful服务端
 */
class restful_server extends restful {
    //const RESTFUL_KEY='restful';

    /**
     * 获取restful请求数据
     * @return string | array 
     */
    function get_request_data() {
        $rest_data = $this->get_restful_data();
        $data = $this->gen_original_data($rest_data);
        return $data;
    }

    private function get_restful_data() {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            //$_DATA = $_REQUEST[self::RESTFUL_KEY];
            $_DATA = $_SERVER['QUERY_STRING'];
        } else {
            $_DATA = file_get_contents('php://input');
        }
        return $_DATA;
    }

    /**
     * 响应restful数据
     * @param string | array 
     */
    function response_data($data) {
        $rest_data = $this->gen_restful_data($data);
        echo $rest_data;
    }

}

class restful_exception extends Exception {
	const ERROR_BASE64_DECODE_FAIL = -1;
	const ERROR_JSON_DECODE_FAIL = -2;
	const ERROR_HTTP_STTUS_CODE = -3;
	const ERROR_CURL_ERROR = -4;
}

?>