<?php

class restfulServer extends ERestController {

    protected $errorNo;
    protected $errorMsg;

    private $_REQ = array();
    
    public function actionRestView($id, $var = null, $var2 = null) {
        if (isset($var) && isset($var2)) {
            $var = array($var, $var2);
        }
        $id = 'doRestGet' . ucfirst($id);
        if (method_exists($this, $id)) {
            $data = $this->data();

            $this->_REQ = array(
                'function' => get_class($this) . '.' . $id,
                'parameter' => $data,
            );

            $re = $this->beforeRest(&$data);
            if (!$re) {
                return $this->returnRest(array(), false, $this->errorNo, $this->errorMsg);
            }
            $this->$id($var, $data);
        } else {
            throw new CHttpException(500, 'Method does not exist: '.  get_class($this).'.'.__FUNCTION__.' -> '.$id);
        }
    }

    public function actionRestUpdate($id, $var = false) {
        $this->HTTPStatus = $this->getHttpStatus('201');

        $var = 'doRestPut' . ucfirst($var);
        if (method_exists($this, $var)) {
            $data = $this->data();

            $this->_REQ = array(
                'function' => get_class($this) . '.' . $var,
                'parameter' => $data,
            );

            $re = $this->beforeRest(&$data);
            if (!$re) {
                return $this->returnRest(array(), false, $this->errorNo, $this->errorMsg);
            }
            $this->$var($id, $data);
        }
        else
            throw new CHttpException(500, 'Method does not exist: '.  get_class($this).'.'.__FUNCTION__.' -> '.$var);
    }

    public function actionRestCreate($func = null) {
        $this->HTTPStatus = $this->getHttpStatus('201');

        //we can assume if $id is set the user is trying to call a custom method
        $func = 'doRestPost' . ucfirst($func);
        if (method_exists($this, $func)) {
            $data = $this->data();

            $this->_REQ = array(
                'function' => get_class($this) . '.' . $func,
                'parameter' => $data,
            );

            $re = $this->beforeRest(&$data);
            if (!$re) {
                return $this->returnRest(array(), false, $this->errorNo, $this->errorMsg);
            }
            $this->$func($data);
        }
        else
            throw new CHttpException(500, 'Method does not exist: '.  get_class($this).'.'.__FUNCTION__.' -> '.$func);
    }

    public function actionRestDelete($id, $var = false) {
        $var = 'doRestDel' . ucfirst($var);
        if (method_exists($this, $var)) {
            $data = $this->data();

            $this->_REQ = array(
                'function' => get_class($this) . '.' . $var,
                'parameter' => $data,
            );

            $re = $this->beforeRest($data);
            if (!$re) {
                return $this->returnRest(array(), false, $this->errorNo, $this->errorMsg);
            }
            $this->$var($id, $data);
        }
        else
            throw new CHttpException(500, 'Method does not exist: '.  get_class($this).'.'.__FUNCTION__.' -> '.$var);
    }

    function returnRest($data, $success = true, $errorCode = 230000, $msg = '成功') {
        $this->_returnData['success'] = $success;
	  	$this->_returnData['msg'] = $msg;
	  	$this->_returnData['errorCode'] = $errorCode;
        if(empty($data)){
            $data = new stdClass();
        }
	  	$this->_returnData['data'] = $data;

        /**
         * 服务端I/O调用日志记录 chenjinlong 20140306
         */
        LBSLogger::logging("IncomeCalling",'Inner-API','服务端I/O接口调用','',array(
            'http_method' => $_SERVER['REQUEST_METHOD'],
            'parameter' => json_encode($this->_REQ),
            'response' => json_encode($this->_returnData),
        ));

        $this->renderJson();
    }

     function beforeRest($data) {
         return true;
     }
    
    function returnRestStand($result) {
    	$this->returnRest(isset($result['data']) ? $result['data'] : array(), 
    						isset($result['success']) ? $result['success'] : false, 
    						isset($result['errorCode']) ? $result['errorCode'] : ErrorCode::ERR_231000, 
    						isset($result['msg']) ? $result['msg'] : ErrorCode::$errorCodeMap[strval(ErrorCode::ERR_231000)]);
    }
    
    function genrateReturnRest() {
    	return array('data'=>array(), 'success'=>true, 'errorCode'=>0, 'msg'=>'成功！');
    }

}