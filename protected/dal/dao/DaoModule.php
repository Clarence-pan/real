<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: zhangzheng
 * Date: 11/27/12
 * Time: 05:04 PM
 * Description: DaoModule.inc.php
 */
class DaoModule {
    public $dbRO;
    public $dbRW;

	const ROW = 'row';
	
	const SROW = 'srow';
	
	const ALL = 'all';
	
	const SALL = 'sall';

    function __construct() {
        $this->dbForReadOnly();
        $this->dbForWrite();
    }

    protected function dbForReadOnly() {
        $this->dbRO = Yii::app()->buckbeek_slave;
    }

    protected function dbForWrite() {
        $this->dbRW = Yii::app()->buckbeek_master;
    }
    
    public function executeSql($sql, $flag) {
    	$transaction = $this->dbRW->beginTransaction();
    	$result = array();
    	try {
    		switch($flag) {
	    		case self::ALL:
	    			$result = $this->dbRW->createCommand($sql)->queryAll();
	    			break;
	    		case self::ROW:
	    			$result = $this->dbRW->createCommand($sql)->queryRow();
	    			break;
	    		case self::SALL:
	    			foreach ($sql as $sqlObj) {
	    				$this->dbRW->createCommand($sqlObj)->execute();
	    			}
	    			break;
	    		case self::SROW:
	    			$this->dbRW->createCommand($sql)->execute();
	    			$result = $this->dbRW->lastInsertID;
	    			break;
	    		default:
	    			throw new Exception('调用数据库底层函数错误！', 23500);
	    			break;
    		}
    		$transaction->commit();
    		return $result;
    	} catch (Exception $e) {
    		$transaction->rollback();
    		Yii::log($e);
            throw $e;
		}
    }
    
}