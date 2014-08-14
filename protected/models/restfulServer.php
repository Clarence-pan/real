<?php

class restfulServer extends ERestController {

    protected $errorNo;
    protected $errorMsg;

    public function actionRestView($id, $var = null, $var2 = null) {
        if (isset($var) && isset($var2)) {
            $var = array($var, $var2);
        }
        $id = 'doRestGet' . ucfirst($id);
        if (method_exists($this, $id)) {
            $data = $this->data();
            $re = $this->beforeRest($data);
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
            $re = $this->beforeRest($data);
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
            $re = $this->beforeRest($data);
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
        $this->renderJson();
    }

     function beforeRest($data) {
         return true;
     }

}