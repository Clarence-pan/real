<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 12/10/12
 * Time: 09:42 AM
 * Description: DaoModule.inc.php
 */
class DaoModule {
    public $dbRO;
    public $dbRW;

    function __construct() {
        $this->dbForReadOnly();
        $this->dbForWrite();
    }

    protected function dbForReadOnly() {
        $this->dbRO = Yii::app()->hagrid_slave;
    }

    protected function dbForWrite() {
        $this->dbRW = Yii::app()->hagrid_master;
    }
}