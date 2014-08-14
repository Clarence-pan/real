<?php


class restUIServer extends restfulServer {
    function beforeRest($data) {
        //权限控制

        //设置当天账户

        return true;
    }

    function getAccountId() {
        return $this->accountId;
    }

    function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    function getAccountInfo(){
        $user = new UserMod;
        $params = array('id'=>$this->getAccountId());
        return $user->read($params);
    }
}