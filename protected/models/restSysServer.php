<?php


class restSysServer extends restfulServer {

    function beforeRest($data) {
        //控制访问来源,仅允许通过 指定域名访问 非UI接口
//        if($_SERVER['HTTP_HOST'] == 'public-api.bj.vnd.tuniu.org')
            return true;
    }

}